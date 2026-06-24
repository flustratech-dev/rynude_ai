<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\SupportsToolUse;

/**
 * Drives the agentic loop: call the model, run any tools it requests, feed the
 * results back, and repeat until the model answers without asking for tools.
 *
 * Provider-agnostic — it relies on SupportsToolUse. Providers that don't support
 * tools (or endpoints that reject them) degrade gracefully to plain chat.
 */
class AgentRunner
{
    public function __construct(private AiService $ai) {}

    /**
     * Run the loop, yielding UI events:
     *   ['type' => 'text', 'text' => string]
     *   ['type' => 'tool', 'name' => string, 'input' => array, 'status' => 'running'|'done', 'summary' => string]
     *
     * @param array      $messages Unified message history (system first).
     * @param string     $model    Selected model.
     * @param AgentTools $tools    Tool registry.
     * @param int        $maxIterations Hard cap on model↔tool round-trips.
     */
    public function run(array $messages, string $model, AgentTools $tools, int $maxIterations = 8): \Generator
    {
        $provider = $this->ai->resolveProvider($model);
        $schemas = $tools->schemas();

        // No tool support → plain chat passthrough.
        if (!($provider instanceof SupportsToolUse) || empty($schemas)) {
            foreach ($this->ai->streamResponse($messages, $model) as $chunk) {
                yield ['type' => 'text', 'text' => $chunk];
            }
            return;
        }

        $baseMessages = $messages;  // system + original user turns, for plain-chat fallback
        $toolTranscript = '';       // human-readable log of tool results gathered so far
        $toolsEnabled = true;
        $retriedPlain = false;      // guard so we only degrade to plain chat once

        for ($iter = 0; $iter < $maxIterations; $iter++) {
            $assistantText = '';
            $toolCalls = [];

            $turn = $provider->streamAgentTurn($messages, $model, $toolsEnabled ? $schemas : []);

            foreach ($turn as $event) {
                if (($event['type'] ?? '') === 'text') {
                    $assistantText .= $event['text'];
                    yield ['type' => 'text', 'text' => $event['text']];
                } elseif (($event['type'] ?? '') === 'tool_use') {
                    $toolCalls[] = $event;
                }
            }

            $result = $turn->getReturn() ?? ['stop_reason' => 'end'];

            if (($result['stop_reason'] ?? '') === 'error') {
                if (!$retriedPlain) {
                    // The endpoint choked on the tool protocol (no tool support, or a
                    // translating proxy that can't round-trip tool messages). Clear the
                    // error text we streamed and degrade to a plain-chat answer — using
                    // any tool output already gathered to keep it grounded.
                    $retriedPlain = true;
                    $toolsEnabled = false;
                    yield ['type' => 'reset'];

                    if ($toolTranscript !== '') {
                        yield from $this->finalizeWithPlainChat($baseMessages, $toolTranscript, $model);
                        return;
                    }
                    // No native tool support (e.g. a translating proxy). Run a
                    // text-protocol ReAct loop over plain chat instead — works anywhere.
                    yield from $this->runReAct($baseMessages, $model, $tools, $maxIterations);
                    return;
                }
                return; // already degraded once — give up cleanly
            }

            if (empty($toolCalls)) {
                return; // model gave its final answer
            }

            // Record the assistant turn (text + the tool calls it requested).
            $messages[] = [
                'role' => 'assistant',
                'content' => $assistantText,
                'tool_calls' => array_map(fn ($c) => [
                    'id' => $c['id'],
                    'name' => $c['name'],
                    'input' => $c['input'],
                ], $toolCalls),
            ];

            // Execute each tool and append its result.
            foreach ($toolCalls as $call) {
                yield ['type' => 'tool', 'name' => $call['name'], 'input' => $call['input'], 'status' => 'running'];

                $output = $tools->execute($call['name'], $call['input']);

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $call['id'],
                    'name' => $call['name'],
                    'content' => $output,
                ];

                $argSummary = json_encode($call['input'] ?: new \stdClass());
                $toolTranscript .= "### {$call['name']}({$argSummary})\n{$output}\n\n";

                yield [
                    'type' => 'tool',
                    'name' => $call['name'],
                    'input' => $call['input'],
                    'status' => 'done',
                    'summary' => $tools->summarize($call['name'], $output),
                ];
            }
        }

        yield ['type' => 'text', 'text' => "\n\n_[Reached the {$maxIterations}-step tool limit. Answering with what I have.]_\n"];
    }

    /**
     * Produce a final answer via plain chat (no tools), folding the gathered tool
     * output into the prompt. Used when the endpoint can't continue a tool
     * conversation but we already have useful context to answer from.
     */
    private function finalizeWithPlainChat(array $baseMessages, string $toolTranscript, string $model): \Generator
    {
        $baseMessages[] = [
            'role' => 'user',
            'content' => "I gathered the following context with tools. Use ONLY this to answer my question above — "
                . "do not ask to run more tools.\n\n" . $toolTranscript,
        ];

        foreach ($this->ai->streamResponse($baseMessages, $model) as $chunk) {
            yield ['type' => 'text', 'text' => $chunk];
        }
    }

    /**
     * Text-protocol (ReAct) agent loop for endpoints without native tool support.
     *
     * The model requests a tool by emitting `<tool>name {json}</tool>`; we execute
     * it and feed the result back as a user message. When it answers without a tool
     * tag, that text is the final answer. Works over plain chat completions, so it
     * runs on any provider/proxy — including ones that reject the `tools` param.
     */
    private function runReAct(array $messages, string $model, AgentTools $tools, int $maxIterations): \Generator
    {
        // Append the tool protocol + catalogue to the system message.
        $catalogue = '';
        foreach ($tools->schemas() as $t) {
            $props = array_keys($t['input_schema']['properties'] ?? []);
            $catalogue .= "- {$t['name']}(" . implode(', ', $props) . "): {$t['description']}\n";
        }
        $protocol = "\n\n=== TOOL USE PROTOCOL ===\n"
            . "You can call tools to inspect the codebase and the web. Available tools:\n"
            . $catalogue
            . "To call a tool, output ONE tag and then STOP. Use either form:\n"
            . "<tool>read_file src/Version.php</tool>\n"
            . "<tool>search_code Version</tool>\n"
            . "(JSON args also work: <tool>read_file {\"path\": \"src/Version.php\"}</tool>)\n"
            . "I will run it and reply with the result, then you continue. Call tools one at a "
            . "time, as many times as needed. When you have enough information, write your final "
            . "answer in normal prose WITHOUT any <tool> tag. Never invent file contents — read them first.";

        if (isset($messages[0]) && ($messages[0]['role'] ?? '') === 'system') {
            $messages[0]['content'] .= $protocol;
        } else {
            array_unshift($messages, ['role' => 'system', 'content' => trim($protocol)]);
        }

        for ($iter = 0; $iter < $maxIterations; $iter++) {
            $text = '';
            foreach ($this->ai->streamResponse($messages, $model) as $chunk) {
                $text .= $chunk;
            }

            $call = $this->parseReActCall($text);

            if ($call === null) {
                // No tool requested — this is the final answer.
                yield ['type' => 'text', 'text' => trim($text)];
                return;
            }

            yield ['type' => 'tool', 'name' => $call['name'], 'input' => $call['input'], 'status' => 'running'];
            $output = $tools->execute($call['name'], $call['input']);
            yield [
                'type' => 'tool',
                'name' => $call['name'],
                'input' => $call['input'],
                'status' => 'done',
                'summary' => $tools->summarize($call['name'], $output),
            ];

            $messages[] = ['role' => 'assistant', 'content' => $text];
            $messages[] = [
                'role' => 'user',
                'content' => "Result of {$call['name']}:\n\n{$output}\n\n"
                    . "Continue: call another tool with <tool>…</tool>, or give your final answer.",
            ];
        }

        // Hit the step cap — ask for a final answer from what we have.
        $messages[] = ['role' => 'user', 'content' => 'Stop using tools now and give your best final answer from what you have gathered.'];
        foreach ($this->ai->streamResponse($messages, $model) as $chunk) {
            yield ['type' => 'text', 'text' => $chunk];
        }
    }

    /**
     * Extract a <tool>name {json}</tool> request from model output, if present.
     *
     * @return array{name: string, input: array}|null
     */
    private function parseReActCall(string $text): ?array
    {
        if (!preg_match('/<tool>\s*([a-zA-Z_]\w*)\b(.*?)<\/tool>/s', $text, $m)) {
            return null;
        }
        $name = $m[1];
        $rest = trim($m[2]);
        $input = [];

        if ($rest !== '') {
            if (str_starts_with($rest, '{')) {
                $decoded = json_decode($rest, true);
                if (is_array($decoded)) {
                    $input = $decoded;
                }
            } else {
                // Positional argument — map to the tool's primary parameter.
                $val = trim($rest, " \t\n\r\"'`");
                if ($val !== '') {
                    $key = in_array($name, ['search_code', 'web_search'], true) ? 'query' : 'path';
                    $input = [$key => $val];
                }
            }
        }

        return ['name' => $name, 'input' => $input];
    }
}

