#!/usr/bin/env node
/**
 * Minimal OpenAI-compatible HTTP server for a single local GGUF model, backed
 * by node-llama-cpp's programmatic API.
 *
 * node-llama-cpp (v3) does NOT ship an OpenAI `serve` command, so LlamaServerService
 * launches this script instead:
 *
 *   node scripts/llama-server.mjs --model <path.gguf> --port 8091 --ctx 16384
 *
 * It exposes:
 *   GET  /v1/models              -> lists the loaded model
 *   GET  /health                 -> { ok: true } once weights are loaded
 *   POST /v1/chat/completions    -> OpenAI chat completions (stream + non-stream)
 *
 * The context window (`--ctx`) is set explicitly here — that is the fix for the
 * 4096-token overflow. Requests are serialized (single-user desktop use).
 */

import { createServer } from "node:http";
import { getLlama, LlamaChatSession } from "node-llama-cpp";

function parseArgs(argv) {
    const args = {};
    for (let i = 0; i < argv.length; i++) {
        const a = argv[i];
        if (a.startsWith("--")) {
            const key = a.slice(2);
            const val = argv[i + 1] && !argv[i + 1].startsWith("--") ? argv[++i] : "true";
            args[key] = val;
        }
    }
    return args;
}

const args = parseArgs(process.argv.slice(2));
const modelPath = args.model;
const port = parseInt(args.port || "8091", 10);
const contextSize = parseInt(args.ctx || "16384", 10);

if (!modelPath) {
    console.error("[llama-server] --model <path> is required");
    process.exit(1);
}

const modelId = args.id || modelPath.split(/[\\/]/).pop();

// Generation guardrails. Small GGUF models (0.5B–3B) degenerate into infinite
// repetition without a repeat penalty, and will happily run to the full token
// ceiling echoing the same line hundreds of times. Defaults below are the
// small-model profile; LlamaServerService passes per-tier CLI overrides so a
// 7B–14B model gets more natural sampling. Precedence: CLI arg > env > default.
const opt = (arg, env, def) => args[arg] ?? process.env[env] ?? def;
const GEN = {
    // Lower temperature (0.4) and topP (0.85) make compact models (Vignette 0.5B, Lyric 1.5B)
    // much more logical, disciplined in closing <thinking> tags, and consistent in Markdown formatting.
    temperature: parseFloat(opt("temp", "LOCAL_GGUF_TEMPERATURE", "0.4")),
    topP: parseFloat(opt("top-p", "LOCAL_GGUF_TOP_P", "0.85")),
    topK: parseInt(opt("top-k", "LOCAL_GGUF_TOP_K", "40"), 10),
    // Increase cap to 8192 so internal monologue (<thinking>) + structured document fit comfortably.
    maxTokensCap: parseInt(opt("max-tokens", "LOCAL_GGUF_MAX_TOKENS", "8192"), 10),
    repeatPenalty: {
        // Slightly gentler frequency/presence penalties so Markdown formatting syntax
        // (# headings, - lists, ``` code blocks) is not penalized during structured output.
        penalty: parseFloat(opt("repeat-penalty", "LOCAL_GGUF_REPEAT_PENALTY", "1.12")),
        frequencyPenalty: parseFloat(opt("freq-penalty", "LOCAL_GGUF_FREQUENCY_PENALTY", "0.1")),
        presencePenalty: parseFloat(opt("pres-penalty", "LOCAL_GGUF_PRESENCE_PENALTY", "0.1")),
        lastTokens: parseInt(process.env.LOCAL_GGUF_REPEAT_LAST || "128", 10),
    },
};

function resolveMaxTokens(requested) {
    const asked = requested ? parseInt(requested, 10) : GEN.maxTokensCap;
    return Math.max(1, Math.min(asked, GEN.maxTokensCap));
}

function promptOptions(extra = {}) {
    return {
        temperature: GEN.temperature,
        topP: GEN.topP,
        topK: GEN.topK,
        repeatPenalty: GEN.repeatPenalty,
        ...extra,
    };
}

// ---- Load the model + a single context up front ----------------------------
let llama, model, context;
let ready = false;

try {
    console.log(`[llama-server] loading ${modelPath} (ctx=${contextSize})`);
    console.log(`[llama-server] gen profile: temp=${GEN.temperature} topP=${GEN.topP} topK=${GEN.topK} repeat=${GEN.repeatPenalty.penalty} maxTokens=${GEN.maxTokensCap}`);
    llama = await getLlama();
    model = await llama.loadModel({ modelPath });
    context = await model.createContext({ contextSize });
    ready = true;
    console.log(`[llama-server] model ready on port ${port}`);
} catch (err) {
    console.error(`[llama-server] failed to load model: ${err?.message || err}`);
    process.exit(1);
}

// ---- Helpers ----------------------------------------------------------------
function readBody(req) {
    return new Promise((resolve, reject) => {
        let data = "";
        req.on("data", (c) => (data += c));
        req.on("end", () => resolve(data));
        req.on("error", reject);
    });
}

// Split an OpenAI `messages` array into a system prompt, prior history, and the
// final user turn to generate a reply for.
function splitMessages(messages) {
    let systemPrompt = "";
    const history = [];
    let lastUser = "";

    const flat = (content) =>
        Array.isArray(content)
            ? content.map((p) => (typeof p === "string" ? p : p.text || "")).join("")
            : (content ?? "");

    for (const msg of messages) {
        const text = flat(msg.content);
        if (msg.role === "system") {
            systemPrompt += (systemPrompt ? "\n" : "") + text;
        } else if (msg.role === "user") {
            history.push({ type: "user", text });
        } else if (msg.role === "assistant") {
            history.push({ type: "model", response: [text] });
        }
    }

    // The trailing user message is the prompt; everything before it is history.
    if (history.length && history[history.length - 1].type === "user") {
        lastUser = history.pop().text;
    }

    return { systemPrompt, history, lastUser };
}

// Serialize requests: one context/sequence, one generation at a time.
let queue = Promise.resolve();
function enqueue(task) {
    const run = queue.then(task, task);
    queue = run.catch(() => {});
    return run;
}

async function handleChat(req, res) {
    const raw = await readBody(req);
    let payload;
    try {
        payload = JSON.parse(raw || "{}");
    } catch {
        res.writeHead(400, { "Content-Type": "application/json" });
        res.end(JSON.stringify({ error: { message: "Invalid JSON", type: "invalid_request_error", code: 400 } }));
        return;
    }

    const messages = payload.messages || [];
    const stream = payload.stream === true;
    const maxTokens = resolveMaxTokens(payload.max_tokens);
    const { systemPrompt, history, lastUser } = splitMessages(messages);
    const created = Math.floor(Date.now() / 1000);
    const id = "chatcmpl-" + Math.random().toString(36).slice(2);

    await enqueue(async () => {
        const sequence = context.getSequence();
        const session = new LlamaChatSession({
            contextSequence: sequence,
            systemPrompt: systemPrompt || undefined,
        });
        if (history.length) {
            const base = session.getChatHistory();
            session.setChatHistory([...base, ...history]);
        }

        try {
            if (stream) {
                res.writeHead(200, {
                    "Content-Type": "text/event-stream",
                    "Cache-Control": "no-cache",
                    Connection: "keep-alive",
                });
                await session.prompt(lastUser, promptOptions({
                    maxTokens,
                    onTextChunk(chunk) {
                        const data = {
                            id, object: "chat.completion.chunk", created, model: modelId,
                            choices: [{ index: 0, delta: { content: chunk }, finish_reason: null }],
                        };
                        res.write(`data: ${JSON.stringify(data)}\n\n`);
                    },
                }));
                const done = {
                    id, object: "chat.completion.chunk", created, model: modelId,
                    choices: [{ index: 0, delta: {}, finish_reason: "stop" }],
                };
                res.write(`data: ${JSON.stringify(done)}\n\n`);
                res.write("data: [DONE]\n\n");
                res.end();
            } else {
                const answer = await session.prompt(lastUser, promptOptions({ maxTokens }));
                const body = {
                    id, object: "chat.completion", created, model: modelId,
                    choices: [{ index: 0, message: { role: "assistant", content: answer }, finish_reason: "stop" }],
                };
                res.writeHead(200, { "Content-Type": "application/json" });
                res.end(JSON.stringify(body));
            }
        } catch (err) {
            const message = err?.message || String(err);
            if (!res.headersSent) {
                res.writeHead(500, { "Content-Type": "application/json" });
                res.end(JSON.stringify({ error: { message, type: "server_error", code: 500 } }));
            } else {
                res.write(`data: ${JSON.stringify({ error: { message } })}\n\n`);
                res.end();
            }
        } finally {
            sequence.dispose();
        }
    });
}

// ---- HTTP server ------------------------------------------------------------
const server = createServer(async (req, res) => {
    try {
        if (req.method === "GET" && (req.url === "/health" || req.url === "/")) {
            res.writeHead(200, { "Content-Type": "application/json" });
            res.end(JSON.stringify({ ok: ready, model: modelId }));
            return;
        }
        if (req.method === "GET" && req.url.startsWith("/v1/models")) {
            res.writeHead(200, { "Content-Type": "application/json" });
            res.end(JSON.stringify({ object: "list", data: [{ id: modelId, object: "model", owned_by: "local-gguf" }] }));
            return;
        }
        if (req.method === "POST" && req.url.startsWith("/v1/chat/completions")) {
            await handleChat(req, res);
            return;
        }
        res.writeHead(404, { "Content-Type": "application/json" });
        res.end(JSON.stringify({ error: { message: "Not found", type: "invalid_request_error", code: 404 } }));
    } catch (err) {
        if (!res.headersSent) {
            res.writeHead(500, { "Content-Type": "application/json" });
            res.end(JSON.stringify({ error: { message: err?.message || "Internal error" } }));
        }
    }
});

server.listen(port, "127.0.0.1", () => {
    console.log(`[llama-server] listening on http://127.0.0.1:${port}`);
});
