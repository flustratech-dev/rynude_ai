# RYNUDE AI V5 - IMPLEMENTATION STATUS

Last Updated: 2026-06-28

---

# PROJECT OVERVIEW

Project Name:

RYNUDE AI V5

Mission:

Build a model-agnostic AI Research Operating System capable of producing Claude-level or better outputs regardless of underlying LLM provider.

Supported Models:

* OpenAI GPT
* Claude
* Gemini
* DeepSeek
* Qwen
* Future Models

Core Philosophy:

Quality should come from system architecture, not from dependence on a specific model.

---

# CURRENT PROJECT STATUS

Current Version:

V5 Development

Current Sprint:

Sprint 1 — Implementation in progress (Steps 1–4 complete, paused for review per user rules)

Overall Progress:

40% — Steps 1 (migrations) + 2 (DTOs) + 3 (ModelAdapter base + AnthropicAdapter) + 4 (OpenAI / Google / Mistral adapters + extended registry) of Section 12 implementation order complete

Project State:

Sprint 1 Implementation — Steps 1, 2, 3 & 4 complete and tested; awaiting review before Step 5 (OutputNormalizer + extractors)

Reference:

docs/SPRINT_1_ARCHITECTURE.md  (final package — Section 12 implementation order)

---

# ARCHITECTURE PRINCIPLES

These principles must not be removed without explicit approval.

1. Planning Before Generation

2. Research Before Writing

3. Reflection Before Publishing

4. Quality Scoring Before Delivery

5. Memory Before Context Loss

6. Academic Consistency Across Entire Project

7. Model Agnostic Design

8. Modular Architecture

9. Production First Engineering

10. Test Driven Development

---

# LOCKED TECHNICAL DECISIONS

Captured from current codebase analysis. Lock on approval.

Backend:

Laravel 12 (PHP 8.2+)

Frontend:

Livewire 4 + Tailwind CSS + Vite

Database:

SQLite (database/database.sqlite) — sufficient through Phase 4; revisit at Phase 5 (Research Workflow Engine)

Vector Database:

Deferred to Phase 3/4 (Memory + Knowledge Graph)

Realtime:

Livewire streams (`$this->stream(to: ...)`) — sufficient for Phase 1/2; Phase 8 may add a broadcaster

Authentication:

Laravel Breeze (session-based)

State Management:

Livewire component state + DB persistence (per-conversation memory, generation runs)

Queue System:

Laravel queues, default `sync` driver (Phase 1/2 will not require Redis)

Storage:

Local disk (`storage/app/public/attachments`)

LLM HTTP Layer:

GuzzleHttp (already in use across all providers)

Pipeline Composition:

Laravel Pipeline pattern over plain PHP service classes; staged via `PipelineStage` contract

Execution Modes (LOCKED):

* Fast Mode — quality threshold 80, light pipeline (analyzer + draft + score), default for short conversational turns
* Chat Mode — quality threshold 85, standard pipeline (analyzer + planning + draft + reflection + score), default for normal generation
* Research Mode — quality threshold 90, full pipeline (analyzer + planning + research + multi-pass writing + reflection + score), default for skripsi / thesis / jurnal / laporan

Maximum Regeneration Attempts (LOCKED):

2 (applies to all modes; on exhaustion the run completes with `metadata.warning = quality_threshold_not_met`)

---

# PHASE TRACKER

## PHASE 1

Model Normalization Layer

Status:

DESIGN APPROVED (2026-06-28) — Awaiting Approval to Generate Code

Features:

* Model Adapter (wraps existing AnthropicProvider / OpenAIProvider / GoogleProvider / MistralProvider)
* Universal System Prompt (section-based builder with provider variants)
* Output Normalizer (Artifact + Reasoning + Citation + Refusal extractors)
* Quality Scoring Engine (5 rubrics, weighted overall, mode-specific thresholds: Fast=80, Chat=85, Research=90)

Dependencies:

None — adapters delegate to the existing `LLMProviderInterface` so providers do not need rewriting

Notes:

Build adds 4 namespaces under app/Services/AI: Normalization, Prompts, Quality, DTO. No changes to existing providers. ChatInterface refactored to delegate streaming to GenerationCoordinator (drop-in backwards-compatible for Fast Mode).

---

## PHASE 2

Universal Thinking Pipeline

Status:

DESIGN APPROVED (2026-06-28) — Awaiting Approval to Generate Code

Features:

* Task Analyzer (TaskBrief: goal, audience, deliverable, constraints, mode)
* Planning Engine (ExecutionPlan: steps, required research, style rules)
* Research Engine (stubbed — wraps WebSearchService + ConversationMemoryService until Phase 5)
* Writing Engine (Multi-Pass: Draft → Improve → Review → Final — engaged only in Research Mode)
* Reflection Engine (checklist + RevisionDirectives)
* Multi Pass Writing (orchestrated by MultiPassWriter; Research Mode only)

Dependencies:

Phase 1

Notes:

Three named execution modes: Fast (threshold 80) / Chat (threshold 85) / Research (threshold 90). Mode chosen by TaskAnalyzer auto-detect or forced via UI selector. Regeneration loop bounded to MAX 2 attempts across all modes. Every stage persists a `generation_stages` row for full auditability. Reuses existing ConversationMemoryService and AgentRunner for compatibility with skripsi-mode behaviour.

---

## PHASE 3

Memory System

Status:

NOT STARTED

Features:

* Project Memory
* Long Context Compression
* Persistent Knowledge Layer

Dependencies:

Phase 2

Notes:

Pending Architecture Design

---

## PHASE 4

Thesis Knowledge Graph

Status:

NOT STARTED

Features:

* Theory Tracking
* Variable Tracking
* Method Tracking
* Consistency Detection

Dependencies:

Phase 3

Notes:

Pending Architecture Design

---

## PHASE 5

Research Workflow Engine

Status:

NOT STARTED

Features:

* Research Planner
* Reference Collector
* Gap Detector
* Outline Builder

Dependencies:

Phase 4

Notes:

Pending Architecture Design

---

## PHASE 6

Thesis Agent System

Status:

NOT STARTED

Features:

* Research Analyst
* Methodology Expert
* Academic Writer
* Academic Reviewer
* Examiner Simulator

Dependencies:

Phase 5

Notes:

Pending Architecture Design

---

## PHASE 7

Academic Quality Engine

Status:

NOT STARTED

Features:

* Literature Matrix Generator
* Gap Detector
* Citation Auditor
* Consistency Checker
* Academic Compliance Checker

Dependencies:

Phase 6

Notes:

Pending Architecture Design

---

## PHASE 8

Artifact System Rewrite

Status:

NOT STARTED

Features:

* Artifact Rendering
* Realtime Updates
* Authentication Stability
* Event Tracing

Dependencies:

Independent

Notes:

High Priority

---

# IMPLEMENTATION LOG

Add new entries after every sprint.

Example:

## YYYY-MM-DD

Sprint:

Sprint 1

Status:

Completed

Completed Work:

* Universal Thinking Pipeline
* Reflection Engine

Files Added:

* src/core/pipeline/*
* src/core/reflection/*

Files Modified:

* src/api/chat.ts

Important Decisions:

* Use Zustand
* Use PostgreSQL

Risks:

None

Next Sprint:

Sprint 2

---

## 2026-06-28 — Sprint 1 Steps 1 & 2

Sprint:

Sprint 1

Status:

In Progress — Steps 1 & 2 complete, awaiting review

Completed Work:

* Step 1: 7 migrations creating the pipeline persistence layer
  (generation_runs, generation_stages, quality_scores, plans,
  reflection_logs, prompt_templates) + 2 column additions on existing
  messages and message_artifacts tables
* Step 2: 14 DTOs / value objects for the pipeline (GenerationContext,
  GenerationOptions, NormalizedRequest, NormalizedOutput, ArtifactDto,
  ReasoningTrace, ToolCallDto, CitationDto, TaskBrief, ExecutionPlan,
  ReflectionResult, RevisionDirective, QualityScore, NormalizedEvent)

Files Added:

* database/migrations/2026_06_29_000001_create_generation_runs_table.php
* database/migrations/2026_06_29_000002_create_generation_stages_table.php
* database/migrations/2026_06_29_000003_create_quality_scores_table.php
* database/migrations/2026_06_29_000004_create_plans_table.php
* database/migrations/2026_06_29_000005_create_reflection_logs_table.php
* database/migrations/2026_06_29_000006_create_prompt_templates_table.php
* database/migrations/2026_06_29_000007_add_pipeline_fields_to_messages_and_artifacts.php
* app/Services/AI/Coordinator/GenerationContext.php
* app/Services/AI/Coordinator/GenerationOptions.php
* app/Services/AI/DTO/NormalizedRequest.php
* app/Services/AI/DTO/NormalizedOutput.php
* app/Services/AI/DTO/ArtifactDto.php
* app/Services/AI/DTO/ReasoningTrace.php
* app/Services/AI/DTO/ToolCallDto.php
* app/Services/AI/DTO/CitationDto.php
* app/Services/AI/Planning/DTO/TaskBrief.php
* app/Services/AI/Planning/DTO/ExecutionPlan.php
* app/Services/AI/Reflection/DTO/ReflectionResult.php
* app/Services/AI/Reflection/DTO/RevisionDirective.php
* app/Services/AI/Quality/DTO/QualityScore.php
* app/Services/AI/Normalization/Events/NormalizedEvent.php
* tests/Feature/Pipeline/MigrationsTest.php  (12 tests)
* tests/Unit/DTO/DtosTest.php                (24 tests)

Files Modified:

* database/migrations/2026_06_28_000000_add_outline_summary_userid_to_message_artifacts.php
  — minimal cross-DB compatibility patch: the existing MySQL-only
  `UPDATE...INNER JOIN` backfill was preventing the SQLite `:memory:`
  test harness from running ANY feature tests (`ChatFlowTest`,
  `ConversationMemoryTest`, etc. all errored at boot). Backfill is now
  driver-gated: MySQL path unchanged; SQLite uses a correlated subquery.
  Required to honour Rule 6 (run tests after each component); NOT a
  Phase 8 artifact-bug-fix change.

Test Results:

* 36 new tests / 169 assertions — ALL PASS
  (12 MigrationsTest + 24 DtosTest)
* Full suite: 89 / 90 pass. The single failure
  (`AgentToolsTest::test_list_files_respects_depth_and_gitignore`)
  was a pre-existing failure on `main`, verified via `git stash` →
  same failure without my changes. Unrelated to Sprint 1.

Important Decisions:

* DTOs use PHP 8.2 `final` + `readonly` + constructor promotion — no
  setters, no mutation; immutability enforced at language level.
* TaskBrief and ExecutionPlan get static `fromArray()` constructors so
  they can round-trip cleanly to/from the JSON columns persisted in
  the `plans` table.
* `NormalizedEvent` ships factory methods (`text()`, `thinking()`,
  `toolUse()`, `usage()`, `stage()`, `score()`, `done()`, `error()`,
  `reset()`) so call sites are self-documenting and event shapes stay
  consistent across adapters.
* All FKs on child tables (`generation_stages`, `quality_scores`,
  `plans`, `reflection_logs`) use `cascadeOnDelete`. Cross-FK from
  `messages.generation_run_id` → `generation_runs.id` uses
  `nullOnDelete` so regenerating an assistant message preserves the
  audit run.
* SQLite cascade chain verified by a test
  (`test_deleting_conversation_cascades_pipeline_rows`).

Risks:

* Modifying a pre-existing migration (the MySQL→SQLite compat patch)
  triggers a fresh re-run on any environment where it was already
  applied. Mitigation: the patch is an additive `if` branch — MySQL
  semantics are unchanged, SQLite path was previously unreachable. Safe
  to re-run.
* Pre-existing failure in AgentToolsTest is unrelated to Sprint 1 but
  may eventually need its own task. Not in scope.

Next Sprint Step:

Step 3 — ModelAdapter base class + AnthropicAdapter (smoke test against
existing AnthropicProvider)

---

## 2026-06-28 — Sprint 1 Step 3

Sprint:

Sprint 1

Status:

In Progress — Step 3 complete, awaiting review

Completed Work:

* Step 3: ModelCapability value object, ModelAdapter abstract base class,
  ModelAdapterRegistry (Anthropic-only routing for this step), and
  AnthropicAdapter that wraps the existing AnthropicProvider and translates
  its native stream into NormalizedEvent objects (text / thinking / tool_use).

Files Added:

* app/Services/AI/Normalization/ModelCapability.php
* app/Services/AI/Normalization/ModelAdapter.php
* app/Services/AI/Normalization/ModelAdapterRegistry.php
* app/Services/AI/Normalization/Adapters/AnthropicAdapter.php
* tests/Support/FakeLLMProvider.php
* tests/Unit/Normalization/ModelCapabilityTest.php          (2 tests)
* tests/Feature/Normalization/ModelAdapterRegistryTest.php  (6 tests)
* tests/Feature/Normalization/AnthropicAdapterTest.php      (13 tests)

Files Modified:

* docs/IMPLEMENTATION_STATUS.md — this entry + status header bump

(No changes to AnthropicProvider.php, ChatInterface.php, AiService.php, or
any other existing service. Design invariant §1.3.1 preserved: providers
are not rewritten in Sprint 1.)

Test Results:

* 21 new tests / 60 assertions — ALL PASS
  (2 ModelCapabilityTest + 6 ModelAdapterRegistryTest + 13 AnthropicAdapterTest)
* Combined Sprint 1 total (Steps 1+2+3): 57 new tests / 229 assertions — ALL PASS
* Full suite: 110 / 111 pass. The single failure
  (`AgentToolsTest::test_list_files_respects_depth_and_gitignore`) is the
  same pre-existing failure documented in the Steps 1+2 entry. No
  regression introduced by Step 3.
* One PHPUnit doc-comment deprecation surfaced in `PanelsRenderTest` —
  pre-existing, unrelated to Step 3.

Important Decisions:

* **Adapters are model-scoped, not provider-scoped.** `ModelAdapterRegistry::for($model)`
  returns a fresh instance constructed with the model code, because
  capabilities differ across models from the same provider (Haiku has no
  extended thinking; Sonnet / Opus do). `capabilities()` therefore reads
  `$this->model`.
* **Adapter wraps the provider via constructor injection.** `AnthropicAdapter`
  accepts an optional `LLMProviderInterface` argument that defaults to
  `new AnthropicProvider()`. This lets tests inject a `FakeLLMProvider`
  without touching the real network — and preserves the
  "do not modify AnthropicProvider" requirement.
* **Two translation surfaces, one normalized stream.** When the request
  carries tools, `AnthropicAdapter` calls `streamAgentTurn()` (structured
  event arrays) and maps each event 1:1 to `NormalizedEvent`. When tools
  are empty, it falls back to `streamResponse()` (string deltas), where a
  `[Thinking] ` prefix is the existing provider's convention for
  extended-thinking output and is rewritten into `NormalizedEvent::thinking()`.
* **Registry throws on unknown models in Step 3.** OpenAI / Google /
  Mistral adapters arrive in Step 4. Until then, an unknown model code is
  a configuration error, not a silent fallback. `kr/claude*` (9Router
  proxy) explicitly routes through OpenAI in `AiService`, so it is
  rejected here and will be picked up by `OpenAIAdapter` in Step 4.
* **Usage events deferred.** `AnthropicProvider` records tokens internally
  (`TokenUsage::record` + `CostTracker::track`) but does not yield usage
  events. Surfacing usage as `NormalizedEvent::usage()` would require a
  provider change, which violates the "preserve behavior" requirement
  for Step 3. Will be addressed when the coordinator/pipeline lands.
* **`FakeLLMProvider` lands now under `tests/Support`** (planned in
  Sprint 1 Architecture §2). Generic enough for the Step 4 adapters to
  reuse with their own scripted output.

Risks:

* None new. The Step 3 surface is read-only relative to the legacy
  ChatInterface path — `AiService::resolveProvider()` is still the only
  resolver used in production. Adapters are dormant until the
  GenerationCoordinator (Step 13) wires them up.

Next Sprint Step:

Step 4 — Remaining adapters (OpenAI, Google, Mistral) + extend
ModelAdapterRegistry to route to them. `kr/claude*` and `use_proxy` paths
become OpenAIAdapter consumers.

---

## 2026-06-28 — Sprint 1 Step 4

Sprint:

Sprint 1

Status:

In Progress — Step 4 complete, awaiting review

Completed Work:

* Step 4: OpenAIAdapter, GoogleAdapter, MistralAdapter — each wraps its
  existing provider (OpenAIProvider, GoogleProvider, MistralProvider)
  untouched and translates the provider's two native event surfaces
  (`streamResponse` strings / `streamAgentTurn` arrays) into uniform
  `NormalizedEvent` sequences.
* ModelAdapterRegistry extended to route all four adapter families by
  model-code prefix, mirroring `AiService::resolveProvider`. `kr/claude*`
  and `mmf/mimo*` (9Router proxy) now correctly resolve to OpenAIAdapter
  rather than throwing. Unknown codes fall back to OpenAIAdapter — same
  fallback AiService uses — because OpenAIProvider internally dispatches
  HuggingFace / Ollama / proxy endpoints based on user settings.

Files Added:

* app/Services/AI/Normalization/Adapters/OpenAIAdapter.php
* app/Services/AI/Normalization/Adapters/GoogleAdapter.php
* app/Services/AI/Normalization/Adapters/MistralAdapter.php
* tests/Feature/Normalization/OpenAIAdapterTest.php       (16 tests)
* tests/Feature/Normalization/GoogleAdapterTest.php       (15 tests)
* tests/Feature/Normalization/MistralAdapterTest.php      (15 tests)

Files Modified:

* app/Services/AI/Normalization/ModelAdapterRegistry.php — extended
  routing for gpt / o-series / gemini / mistral-family / kr-claude /
  mmf-mimo, with OpenAIAdapter as the fallback. `supports()` now reports
  the honest answer (true for known prefixes, false for unknowns) while
  `for()` falls back so the pipeline never throws on a configured model.
* tests/Feature/Normalization/ModelAdapterRegistryTest.php — replaced
  Step 3's "throws on non-anthropic" tests with the new routing
  assertions (12 tests).
* docs/IMPLEMENTATION_STATUS.md — this entry + status header bump.

(No changes to OpenAIProvider.php, GoogleProvider.php, MistralProvider.php,
ChatInterface.php, AiService.php, or any other existing service. Design
invariant §1.3.1 preserved: providers are not rewritten in Sprint 1.
ChatInterface remains untouched per the explicit user requirement.)

Test Results:

* 46 new adapter tests + 6 net-new registry tests / 130 assertions —
  ALL PASS
  (16 OpenAIAdapter + 15 GoogleAdapter + 15 MistralAdapter +
   6 net-new registry assertions on top of the rewritten registry suite)
* Combined Sprint 1 total (Steps 1+2+3+4): 109 new tests / 359
  assertions — ALL PASS in the Pipeline/Normalization/DTO suites
* Full suite: 160 / 161 pass. The single failure
  (`AgentToolsTest::test_list_files_respects_depth_and_gitignore`)
  is the same pre-existing failure documented in Steps 1+2 and Step 3.
  No regression introduced by Step 4.

Important Decisions:

* **OpenAIAdapter handles three families.** Genuine OpenAI (gpt-*, o-series),
  9Router proxies (kr/claude-*, mmf/mimo-*), and HuggingFace / Ollama / generic
  proxies all route through `OpenAIProvider`. The adapter reports
  `nativeTools=false` and `jsonMode=false` for the router routes
  (`kr/*`, `mmf/*`) because the underlying endpoints either reject the
  `tools` param or can't round-trip tool messages — matches the existing
  `OpenAIProvider::resolveConfig` behaviour. The pipeline can read these
  capability flags before deciding to engage tools; AgentRunner's
  ReAct-text fallback is still the runtime safety net.
* **No thinking convention for OpenAI / Google / Mistral.** None of these
  providers surface reasoning deltas through their current streaming
  APIs. `capabilities()->thinking` returns `false` across the board, and
  the `streamResponse` translator does NOT special-case any prefix —
  unlike `AnthropicAdapter`, a leading `[Thinking] ` is passed through as
  literal text. This is asserted by a dedicated test in `OpenAIAdapterTest`.
* **Context windows by model family.** Capability values are hardcoded by
  model-code prefix because the providers themselves don't expose this
  metadata. Values are conservative for unknown variants
  (e.g. unknown `gemini-*` → 128k, unknown `mistral-*` → 32k) so the
  pipeline never over-promises context to upstream stages.
* **Vision flags by model family.** `OpenAIAdapter` enables vision only
  for `gpt-4*`, `gpt-5*`, `o3*`, `o4*`. `GoogleAdapter` enables vision
  for all Gemini codes (Gemini is multimodal-first). `MistralAdapter`
  enables vision only for `pixtral-*`. Tested per model.
* **Registry fallback to OpenAIAdapter.** Step 3's "throw on unknown"
  was a Step-3-only safeguard. Step 4 mirrors `AiService::resolveProvider`,
  which falls back to OpenAIProvider for unknown codes. This is the
  correct behaviour for HuggingFace / Ollama / proxy-routed models whose
  codes are user-configured rather than prefix-based. `supports()` still
  returns `false` for unknown codes so callers can detect the fallback if
  needed.
* **DB-driven routing intentionally NOT consulted by the registry.**
  `AiService::resolveProvider` peeks at the `ai_models` table (provider
  column for `huggingface` / `ollama` / etc.) and at `users.use_proxy`.
  The registry deliberately stays free of those concerns — they are
  resolved inside `OpenAIProvider::resolveConfig` at request time, where
  the Auth context is available. Keeping the registry stateless means the
  pipeline does not require an active HTTP request to look up an adapter.
* **Translation surface kept identical across adapters.** The three new
  adapters share the same skeleton as `AnthropicAdapter`: `streamText` for
  no-tools paths, `streamAgentTurn` for tool paths, `buildMessages` for
  system-prompt prepending. No shared base / trait is extracted yet —
  premature abstraction would lock in the current shape before the
  OutputNormalizer (Step 5) reveals what each adapter really needs.

Risks:

* None new. The Step 4 surface is read-only relative to the legacy
  ChatInterface path. `AiService::resolveProvider()` is still the only
  resolver used in production; the registry is dormant until the
  GenerationCoordinator (Step 13) wires it up. `ChatInterface` has not
  been modified per the explicit user requirement.

Next Sprint Step:

Step 5 — OutputNormalizer + extractors (ArtifactExtractor,
ReasoningExtractor, CitationExtractor, RefusalDetector). This is the
first piece that consumes the NormalizedEvent stream produced by the
adapters and turns it into the persisted `NormalizedOutput` DTO.

---

# KNOWN ISSUES

## Issue 001

Title:

Artifact does not appear immediately

Status:

Open

Severity:

High

Description:

Artifact generated successfully but UI requires refresh.

Investigation:

Pending

---

## Issue 002

Title:

Logout after refresh

Status:

Open

Severity:

Critical

Description:

User session lost after refresh.

Investigation:

Pending

---

# FUTURE ENHANCEMENTS

Potential V5.1 Features

* Multi Agent Debate
* Autonomous Research Planner
* Thesis Defense Simulation
* Journal Recommendation Engine
* Academic Citation Network
* Research Timeline Builder

---

# NEXT ACTIONS

Current Recommended Task:

Approve Phase 1 + 2 architecture, then begin Sprint 1 implementation (Model Adapter Layer + Universal System Prompt + Output Normalizer).

Required Inputs:

* Approval of architecture document (this file + chat response from 2026-06-28)
* Confirmation of locked technical decisions section above
* Confirmation of quality threshold (default 85) and max regeneration cap (default 2)

Expected Deliverable:

Sprint 1 implementation: app/Services/AI/{Coordinator, Normalization, Prompts, Quality, DTO} + migrations for generation_runs, generation_stages, quality_scores, plans, reflection_logs, prompt_templates + accompanying tests.

Status:

AWAITING APPROVAL

---

# CODEBASE FINDINGS (2026-06-28)

## Existing AI Layer (app/Services/AI/)

Contracts already present:

* LLMProviderInterface::streamResponse(messages, model) — basic streaming
* SupportsToolUse::streamAgentTurn(messages, model, tools) — agentic turn with native tool support
* Concrete providers: AnthropicProvider, OpenAIProvider, GoogleProvider, MistralProvider
* AiService::resolveProvider() routes by model prefix + ai_models.provider + users.use_proxy
* AgentRunner drives multi-turn tool loops with a ReAct text-protocol fallback for endpoints that reject native tools (kr/*, HuggingFace, proxies)
* ConversationMemoryService distills a structured-JSON conversation memory into the system prompt every turn
* CostTracker (static, per-request) — pricing only configured for Anthropic; OpenAI/Google/Mistral fall through to defaults
* AgentTools, PermissionGuard, WorkspaceContext — used by the /code (ClaudeCodeApp) Livewire surface

Verdict: existing provider abstraction is sound. Phase 1 builds on top via adapters; providers do NOT need rewriting.

## Existing Data Model

Tables present (relevant):

* users (api keys per provider, use_proxy, preferences, quota)
* conversations (memory, memory_synced_count, memory_updated_at, metadata, draft_prompt, share_token)
* messages (role, content, rating)
* message_artifacts (identifier, type, language, title, content, outline_json, summary, user_id, public_token, is_public)
* message_attachments (file_path, file_type, file_name)
* projects, project_files, skills, ai_models, token_usages, cowork_tasks, designs

Verdict: schema needs 6 new tables for Phase 1+2 (generation_runs, generation_stages, quality_scores, plans, reflection_logs, prompt_templates) plus two columns added to messages and message_artifacts.

## Gap Analysis vs. V5 Phase 1 + 2

1. **System prompt assembly** is inline string concatenation in ChatInterface::generateResponse() (~50 lines, lines 683–740). No section model, no provider variants, no template versioning. Phase 1 replaces with UniversalSystemPromptBuilder.

2. **Output parsing** is a single regex for `<antArtifact>` in ChatInterface::generateResponse(). No structured extraction of thinking, citations, multiple artifacts, or refusals. Phase 1 introduces OutputNormalizer.

3. **No quality scoring or regeneration loop.** Every response is single-shot. Phase 1 introduces QualityScoringEngine + regen loop.

4. **No planning/reflection/multi-pass writing.** Phase 2 introduces these as composable Pipeline stages.

5. **Inconsistent thinking-event format across providers.** AnthropicProvider::streamResponse yields "[Thinking] …" strings, while streamAgentTurn yields structured `['type'=>'thinking', …]`. OpenAI/Mistral emit no thinking. Phase 1 NormalizedEvent unifies this.

6. **CostTracker only knows Anthropic model codes.** Other providers silently use default rates. Will be addressed as a side-fix during Phase 1 adapter work.

7. **ChatInterface is 1053 lines** and owns the streaming logic, system prompt building, artifact parsing, memory injection, web-search injection, and DB persistence. Phase 1 extracts orchestration into GenerationCoordinator, leaving ChatInterface as a thin Livewire shell that listens for NormalizedEvents and updates UI.

## Stack Constraints

* SQLite (`PRAGMA foreign_keys=ON`) — migrations must use Schema::table for column adds (already the convention).
* Sync queue driver by default — long-running stages should remain inline-friendly; the `RunGenerationPipeline` job is *optional* for full-mode runs and only used when a Redis queue is configured.
* No Pest — tests stay PHPUnit (composer has phpunit/phpunit ^11.5).
* Indonesian-language primary user base — RefusalDetector and AcademicRubric must handle ID + EN.

## Risks

* **Regeneration cost**: full-mode runs may cost 4× a normal turn (analyzer + planner + writer + reflection + scorer + possible regen). Mitigated by `mode='chat'` auto-selection for short turns and per-stage token caps.
* **Latency**: full mode adds ~4 extra completions before final text starts streaming. Mitigated by streaming intermediate `event{stage:...}` so the UI shows progress.
* **Provider drift**: AnthropicProvider's `extended-thinking-2025-04-11` beta header may change. Adapter capability detection isolates this from the pipeline.
* **Backwards compatibility**: existing share / shared-artifact routes, ArtifactPanel, ChatLayout, ChatsPanel must keep working. Coordinator stays drop-in for chat-mode; existing tests remain the regression gate.
