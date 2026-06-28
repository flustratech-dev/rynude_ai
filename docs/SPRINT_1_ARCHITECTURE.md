# SPRINT 1 ARCHITECTURE PACKAGE

RYNUDE AI V5 — Phase 1 (Model Normalization Layer) + Phase 2 (Universal Thinking Pipeline)

Status: FINAL — Awaiting Approval to Generate Code
Approved With Revisions: 2026-06-28
Sprint Window: Sprint 1
Author Role: Lead Architect (per CLAUDE_CODE_ARCHITECT.md)

---

# 0. CHANGES FROM INITIAL DESIGN

This package supersedes the initial design from earlier in 2026-06-28. Revisions:

1. Execution modes renamed and re-scoped: `chat`/`fast`/`full` → `Fast Mode` / `Chat Mode` / `Research Mode`.
2. Quality thresholds are now mode-specific:
   * Fast Mode = 80
   * Chat Mode = 85
   * Research Mode = 90
3. Maximum regeneration attempts = 2 (LOCKED across all modes).
4. Execution path documented per mode (Section 5).
5. Database impact and performance implications confirmed (Section 8).

Everything else from the initial design (folder structure, adapter strategy, normalizer extractors, persistence model) is preserved.

---

# 1. ARCHITECTURE DESIGN

## 1.1 Top-level flow

```
USER REQUEST
    │
    ▼
ChatInterface (Livewire — thin shell)
    │
    ▼
GenerationCoordinator   ◄── single entry point; replaces inline streaming in ChatInterface
    │
    ├──► UniversalSystemPromptBuilder
    │       composes canonical system prompt from sections:
    │       (Identity, Task Analysis, Execution Plan, Reasoning Instructions,
    │        Quality Standards, Output Contract)
    │
    ├──► ModeSelector
    │       resolves Fast | Chat | Research using:
    │         (1) explicit user override (UI dropdown),
    │         (2) TaskAnalyzer hints (deliverable, formality, length),
    │         (3) project / artifact context (skripsi front-matter → Research)
    │
    ├──► ModelAdapterRegistry ──► ModelAdapter (Anthropic / OpenAI / Google / Mistral)
    │       │
    │       ├── ModelCapability  (thinking, native_tools, json_mode, vision, max_ctx)
    │       ├── SystemPromptTranslator
    │       ├── MessageTranslator
    │       └── streamCompletion()
    │             wraps existing LLMProviderInterface; emits NormalizedEvent
    │             {text | thinking | tool_use | usage | done | error}
    │
    ├──► THINKING PIPELINE  (composed per mode)
    │
    │      Stages available:
    │        - TaskAnalyzerStage
    │        - PlanningStage
    │        - ResearchStage        (Phase 2: web_search + memory; Phase 5: full research engine)
    │        - MultiPassWritingStage (Draft → Improve → Review → Final)
    │        - ReflectionStage
    │        - QualityScoringStage
    │
    │      Per-mode composition: see Section 5.
    │      Regeneration: if score < threshold AND regen_count < 2 →
    │                     re-enter ImprovePass with feedback injected as system delta.
    │
    ▼
OutputNormalizer
    parses raw model output into NormalizedOutput {
        visibleText, artifact?, reasoning?, toolCalls[], citations[],
        flags { refused, missing_artifact, multiple_artifacts, language_mismatch, ... }
    }
    │
    ▼
Persistence
    Message + MessageArtifact + GenerationRun + GenerationStage[] + QualityScore + ReflectionLog
    │
    ▼
ChatInterface dispatches NormalizedEvents to Livewire streams:
    message-stream, activity-status, plan-status, score-badge, artifact panel
```

## 1.2 Component responsibilities

| Component | Responsibility | Owns |
|---|---|---|
| GenerationCoordinator | Top-level orchestrator: selects mode, composes pipeline, persists run | GenerationRun lifecycle |
| ModeSelector | Picks Fast / Chat / Research from inputs and TaskAnalyzer hints | Mode resolution policy |
| UniversalSystemPromptBuilder | Single source of truth for the system prompt | Section composition |
| ModelAdapterRegistry | Resolves model code → ModelAdapter | Adapter dispatch |
| ModelAdapter | Provider-specific translation + normalized event stream | Translation, capabilities |
| OutputNormalizer | Parses raw output into structured DTOs | Extractors, refusal detection |
| QualityScoringEngine | Multi-rubric scoring, weighted overall | Rubrics, threshold enforcement |
| ThinkingPipeline | Composes stages per mode | Stage ordering, regen loop |
| TaskAnalyzer / PlanningEngine | Pre-writing reasoning | TaskBrief, ExecutionPlan |
| MultiPassWriter | Draft → Improve → Review → Final | Pass orchestration |
| ReflectionEngine | Post-draft review checklist | RevisionDirectives |

## 1.3 Design invariants

1. **Providers are not rewritten.** Adapters wrap `LLMProviderInterface` and `SupportsToolUse`. AnthropicProvider, OpenAIProvider, GoogleProvider, MistralProvider stay byte-identical in Sprint 1.
2. **Single system prompt builder.** No string concatenation inside ChatInterface. UniversalSystemPromptBuilder is the only writer.
3. **Stage outputs are persisted.** Every stage writes a `generation_stages` row before yielding control. Failures persist with `status='failed'` and the error message.
4. **Regen cap is absolute.** `regen_count` is checked against `config('rynude.max_regenerations')` capped at 2 — UI overrides cannot exceed this.
5. **ChatInterface stays a thin shell.** It owns input handling and Livewire stream targets only. All AI logic moves into `app/Services/AI/Coordinator`.

---

# 2. FOLDER STRUCTURE

```
app/
├── Services/
│   └── AI/
│       ├── (existing) AiService, AgentRunner, AgentTools, ConversationMemoryService,
│       │   CostTracker, AnthropicProvider, OpenAIProvider, GoogleProvider,
│       │   MistralProvider, PermissionGuard, WorkspaceContext, DiffRenderer,
│       │   Contracts/{LLMProviderInterface, SupportsToolUse}
│       │
│       ├── Coordinator/
│       │   ├── GenerationCoordinator.php
│       │   ├── GenerationContext.php
│       │   ├── GenerationOptions.php
│       │   └── ModeSelector.php
│       │
│       ├── Normalization/
│       │   ├── ModelAdapterRegistry.php
│       │   ├── ModelAdapter.php                  (abstract)
│       │   ├── ModelCapability.php
│       │   ├── Adapters/
│       │   │   ├── AnthropicAdapter.php
│       │   │   ├── OpenAIAdapter.php
│       │   │   ├── GoogleAdapter.php
│       │   │   └── MistralAdapter.php
│       │   ├── Translators/
│       │   │   ├── SystemPromptTranslator.php
│       │   │   ├── MessageTranslator.php
│       │   │   └── ParamsTranslator.php
│       │   ├── OutputNormalizer.php
│       │   ├── Extractors/
│       │   │   ├── ArtifactExtractor.php
│       │   │   ├── ReasoningExtractor.php
│       │   │   ├── CitationExtractor.php
│       │   │   └── RefusalDetector.php
│       │   └── Events/
│       │       └── NormalizedEvent.php
│       │
│       ├── Prompts/
│       │   ├── UniversalSystemPromptBuilder.php
│       │   ├── PromptContext.php
│       │   ├── Sections/
│       │   │   ├── IdentitySection.php
│       │   │   ├── TaskAnalysisSection.php
│       │   │   ├── ExecutionPlanSection.php
│       │   │   ├── ReasoningInstructionsSection.php
│       │   │   ├── QualityStandardsSection.php
│       │   │   └── OutputContractSection.php
│       │   └── Templates/
│       │       ├── system.universal.md
│       │       ├── task_analyzer.md
│       │       ├── planner.md
│       │       ├── reflection.md
│       │       ├── scoring.md
│       │       └── writing.{draft,improve,review,final}.md
│       │
│       ├── Pipeline/
│       │   ├── ThinkingPipeline.php
│       │   ├── PipelineContext.php
│       │   ├── Stages/
│       │   │   ├── TaskAnalyzerStage.php
│       │   │   ├── PlanningStage.php
│       │   │   ├── ResearchStage.php           (Phase 5 will expand)
│       │   │   ├── MultiPassWritingStage.php
│       │   │   ├── ReflectionStage.php
│       │   │   └── QualityScoringStage.php
│       │   └── Contracts/
│       │       └── PipelineStage.php
│       │
│       ├── Planning/
│       │   ├── TaskAnalyzer.php
│       │   ├── PlanningEngine.php
│       │   └── DTO/{TaskBrief.php, ExecutionPlan.php}
│       │
│       ├── Writing/
│       │   ├── MultiPassWriter.php
│       │   └── Passes/{DraftPass.php, ImprovePass.php, ReviewPass.php, FinalPass.php}
│       │
│       ├── Reflection/
│       │   ├── ReflectionEngine.php
│       │   ├── ReflectionChecklist.php
│       │   └── DTO/{ReflectionResult.php, RevisionDirective.php}
│       │
│       ├── Quality/
│       │   ├── QualityScoringEngine.php
│       │   ├── Rubrics/
│       │   │   ├── AccuracyRubric.php
│       │   │   ├── CompletenessRubric.php
│       │   │   ├── ConsistencyRubric.php
│       │   │   ├── AcademicRubric.php
│       │   │   └── FormattingRubric.php
│       │   └── DTO/QualityScore.php
│       │
│       └── DTO/
│           ├── NormalizedRequest.php
│           ├── NormalizedOutput.php
│           ├── ArtifactDto.php
│           ├── ReasoningTrace.php
│           ├── ToolCallDto.php
│           └── CitationDto.php
│
├── Models/
│   ├── GenerationRun.php
│   ├── GenerationStage.php
│   ├── QualityScore.php
│   ├── Plan.php
│   ├── ReflectionLog.php
│   └── PromptTemplate.php
│
└── Jobs/
    └── RunGenerationPipeline.php   (optional async dispatch for Research Mode)

config/
└── rynude.php

database/migrations/
├── 2026_06_29_000001_create_generation_runs_table.php
├── 2026_06_29_000002_create_generation_stages_table.php
├── 2026_06_29_000003_create_quality_scores_table.php
├── 2026_06_29_000004_create_plans_table.php
├── 2026_06_29_000005_create_reflection_logs_table.php
├── 2026_06_29_000006_create_prompt_templates_table.php
└── 2026_06_29_000007_add_pipeline_fields_to_messages_and_artifacts.php

tests/
├── Feature/
│   ├── Pipeline/
│   │   ├── ModeSelectorTest.php
│   │   ├── FastModeEndToEndTest.php
│   │   ├── ChatModeEndToEndTest.php
│   │   ├── ResearchModeEndToEndTest.php
│   │   ├── RegenerationLoopTest.php
│   │   ├── MaxRegenerationCapTest.php
│   │   └── PipelineStreamingEventsTest.php
│   ├── Normalization/
│   │   ├── ModelAdapterRegistryTest.php
│   │   ├── AnthropicAdapterTest.php
│   │   ├── OpenAIAdapterTest.php
│   │   ├── GoogleAdapterTest.php
│   │   ├── MistralAdapterTest.php
│   │   ├── OutputNormalizerTest.php
│   │   └── RefusalDetectorTest.php
│   └── Prompts/
│       └── UniversalSystemPromptBuilderTest.php
└── Support/
    ├── FakeLLMProvider.php
    └── FixturePrompts.php
```

---

# 3. DATABASE DESIGN

## 3.1 New tables

```
generation_runs
  id                   bigint pk
  user_id              bigint fk users
  conversation_id      bigint fk conversations
  message_id           bigint fk messages nullable      (set after success)
  model                string                            (model code used for the writing pass)
  pipeline_version     string                            ('v5.1')
  mode                 enum('fast','chat','research')    -- LOCKED
  quality_threshold    smallint NOT NULL                 -- 80 | 85 | 90 (mirrors mode default; stored for audit if defaults shift)
  status               enum('pending','analyzing','planning','researching','writing','reviewing','scoring','complete','failed','aborted')
  regen_count          unsignedTinyInt default 0         -- max 2 enforced in code
  final_score          smallint nullable
  cost_micro_usd       integer default 0
  tokens_in            integer default 0
  tokens_out           integer default 0
  elapsed_ms           integer default 0
  error                text nullable
  metadata             json nullable                     (raw GenerationOptions, flags, warnings)
  created_at, updated_at

  idx (conversation_id, created_at)
  idx (user_id, created_at)
  idx (status)
  idx (mode)

generation_stages
  id                   bigint pk
  generation_run_id    bigint fk generation_runs onDelete cascade
  stage                enum('task_analyzer','planning','research','draft','improve','review','final','reflection','scoring')
  pass_index           tinyInt default 0                 (0..N regen iterations)
  status               enum('running','done','failed','skipped')
  prompt_template_key  string nullable
  input_json           json nullable
  output_json          json nullable
  thinking_text        text nullable
  tokens_in            integer default 0
  tokens_out           integer default 0
  elapsed_ms           integer default 0
  error                text nullable
  created_at
  idx (generation_run_id, pass_index, stage)

quality_scores
  id                   bigint pk
  generation_run_id    bigint fk generation_runs onDelete cascade
  pass_index           tinyInt default 0
  accuracy             smallint
  completeness         smallint
  consistency          smallint
  academic_quality     smallint
  formatting           smallint
  overall              smallint
  threshold            smallint NOT NULL                 (the threshold this score was evaluated against)
  rubric_version       string
  scorer_model         string
  notes_json           json nullable
  passed_threshold     boolean
  created_at
  idx (generation_run_id, pass_index)

plans
  id                   bigint pk
  generation_run_id    bigint fk generation_runs onDelete cascade
  brief_json           json                              (TaskBrief)
  execution_json       json                              (ExecutionPlan)
  created_at

reflection_logs
  id                   bigint pk
  generation_run_id    bigint fk generation_runs onDelete cascade
  pass_index           tinyInt default 0
  checklist_json       json
  directives_json      json nullable                     (empty when passed)
  passed               boolean
  created_at

prompt_templates
  id                   bigint pk
  key                  string
  version              string
  body                 longText
  variables_json       json
  provider_variant     string nullable                   ('anthropic'|'openai'|null)
  is_active            boolean default true
  created_at, updated_at
  unique (key, version, provider_variant)
```

## 3.2 Migrations on existing tables

```
messages
  + generation_run_id    bigint nullable fk
  + pipeline_mode        enum('fast','chat','research') nullable

message_artifacts
  + generation_run_id    bigint nullable fk
  + quality_score        smallint nullable
```

## 3.3 Storage sizing per run

| Mode | generation_stages rows | plans | reflection_logs | quality_scores |
|---|---|---|---|---|
| Fast | 2–3 (analyzer, draft, scoring; +improve on regen) | 0 | 0 | 1–2 |
| Chat | 4–5 (analyzer, planning, draft, reflection, scoring; +improve on regen) | 1 | 1–2 | 1–2 |
| Research | 7–9 (analyzer, planning, research, draft, improve, review, final, reflection, scoring) | 1 | 1–2 | 1–2 |

Per-run worst-case row count (with 2 regens): Research Mode ≈ 12 rows of `generation_stages` + 1 plan + 2 reflection logs + 2 quality scores ≈ **~17 rows per run**.

At an estimated 5 generations per active user per day × 1,000 active users × 17 rows = ~85,000 rows/day across pipeline tables. SQLite handles this for the first 90 days. Migration to PostgreSQL is scheduled in Phase 5 (Research Workflow Engine) when row volume warrants it. See Section 8 for full database-impact analysis.

---

# 4. API DESIGN (internal PHP contracts)

```php
final class GenerationCoordinator
{
    public function generate(GenerationContext $ctx): \Generator;   // yields NormalizedEvent
}

final class GenerationContext
{
    public User $user;
    public Conversation $conversation;
    public array $messages;              // unified message format
    public string $model;
    public array $attachments = [];
    public bool $webSearch = false;
    public ?int $projectId = null;
    public GenerationOptions $options;
}

final class GenerationOptions
{
    public string $mode = 'auto';            // 'auto' | 'fast' | 'chat' | 'research'
    public ?int $qualityThresholdOverride = null;   // optional; clamped to mode default if forbidden
    public int $maxRegenerations = 2;        // hard-capped at config('rynude.max_regenerations')
    public bool $enableThinking = true;
    public bool $enableQualityScoring = true;
    public bool $persistStages = true;
}

final class ModeSelector
{
    /**
     * Resolves the effective mode and threshold.
     * Returns: ['mode' => string, 'threshold' => int, 'reason' => string]
     */
    public function resolve(GenerationContext $ctx, ?TaskBrief $brief = null): array;
}

final class UniversalSystemPromptBuilder
{
    public function build(PromptContext $ctx): string;
}

final class ModelAdapterRegistry
{
    public function for(string $model): ModelAdapter;
}

abstract class ModelAdapter
{
    abstract public function capabilities(): ModelCapability;
    abstract public function streamCompletion(NormalizedRequest $req): \Generator;
}

final class OutputNormalizer
{
    public function parse(string $raw, NormalizedOutputContext $ctx): NormalizedOutput;
}

final class QualityScoringEngine
{
    /**
     * Scores against the threshold attached to the PipelineContext (mode-specific).
     */
    public function score(NormalizedOutput $out, PipelineContext $pctx): QualityScore;
}

final class ThinkingPipeline
{
    public function run(PipelineContext $ctx): \Generator;
}
```

The previous Section 4 of the initial design (full DTO listings) carries over unchanged.

---

# 5. EXECUTION PATHS PER MODE

## 5.1 Mode resolution

```
GenerationOptions.mode
  = 'auto'      → ModeSelector picks based on TaskAnalyzer brief
  = 'fast'      → forced Fast
  = 'chat'      → forced Chat
  = 'research'  → forced Research

ModeSelector heuristics (when mode='auto'):

  1. If the latest artifact in this conversation has YAML front-matter with
     `mode: skripsi|laporan|jurnal|document`, OR the project metadata sets
     a research deliverable, OR the user message contains keywords
     {skripsi, thesis, jurnal, laporan, makalah, riset, penelitian} →
        Research Mode (threshold 90)

  2. If the brief.deliverable is 'document' OR the user is asking to
     continue / revise an existing artifact OR the request length suggests
     ≥ 500 words of output →
        Chat Mode (threshold 85)

  3. Otherwise (short Q&A, greeting, single explanation, small revision) →
        Fast Mode (threshold 80)

  Override path: an authenticated user may force any mode via the UI
  selector. ModeSelector logs the override reason in
  `generation_runs.metadata.mode_override`.
```

## 5.2 Fast Mode (threshold = 80)

Purpose: quick conversational replies, short explanations, casual chat. Optimised for latency.

```
User Request
    │
    ▼
TaskAnalyzerStage          → TaskBrief (mode confirmed = fast)
    │
    ▼                       (planning SKIPPED)
    ▼                       (research SKIPPED)
    ▼                       (reflection SKIPPED)
    ▼
MultiPassWritingStage       → DraftPass only
    │                         (improve / review / final SKIPPED)
    ▼
OutputNormalizer            → NormalizedOutput
    │
    ▼
QualityScoringStage         → QualityScore (threshold = 80)
    │
    ├─ score >= 80 ─► persist + DONE
    │
    └─ score < 80 AND regen_count < 2:
          inject feedback notes as system delta →
          re-run DraftPass (this counts as 1 regeneration) →
          re-score
          (if regen_count == 2 and still < 80:
              persist with metadata.warning = 'quality_threshold_not_met'
              status = complete)
```

LLM call budget (no regen): TaskAnalyzer (1 light call) + Draft (1 main call) + Scoring (1 light call) = **3 calls**.

Latency budget: target ≤ 1.3× legacy single-shot for the same prompt and model.

## 5.3 Chat Mode (threshold = 85) — DEFAULT

Purpose: standard generation — content, code help, mid-length explanations, normal conversation with quality guarantees.

```
User Request
    │
    ▼
TaskAnalyzerStage           → TaskBrief
    │
    ▼
PlanningStage               → ExecutionPlan (steps, style rules)
    │
    ▼                        (research SKIPPED)
    ▼
MultiPassWritingStage       → DraftPass only
    │                          (improve / review / final SKIPPED)
    ▼
OutputNormalizer            → NormalizedOutput
    │
    ▼
ReflectionStage             → ReflectionResult (pass | RevisionDirectives)
    │
    ▼
QualityScoringStage         → QualityScore (threshold = 85)
    │
    ├─ score >= 85 AND reflection.passed ─► persist + DONE
    │
    └─ (score < 85 OR reflection produced directives) AND regen_count < 2:
          inject reflection directives + scoring feedback as system delta →
          re-run DraftPass (counts as 1 regeneration) →
          re-run Reflection → re-Score
          (if regen_count == 2 and still failing:
              persist with metadata.warning = 'quality_threshold_not_met'
              status = complete)
```

LLM call budget (no regen): Analyzer + Planning + Draft + Reflection + Scoring = **5 calls**.

Latency budget: target ≤ 2.5× legacy single-shot.

## 5.4 Research Mode (threshold = 90)

Purpose: academic generation — skripsi, laporan, jurnal, methodology chapters, citation-bearing text. Quality > latency.

```
User Request
    │
    ▼
TaskAnalyzerStage           → TaskBrief (mode confirmed = research)
    │
    ▼
PlanningStage               → ExecutionPlan with required research steps,
    │                          citation strategy, chapter structure
    ▼
ResearchStage               → ResearchPack
    │                          (Sprint 1: WebSearchService results +
    │                          ConversationMemoryService brief + project_files;
    │                          Phase 5 will expand with full research engine)
    ▼
MultiPassWritingStage:
    │   DraftPass            → initial chapter / document
    │   ImprovePass          → applies plan style rules + memory + research citations
    │   ReviewPass           → ReflectionEngine inline (intra-pass check), apply directives
    │   FinalPass            → polish, citation audit, formatting normalization
    ▼
OutputNormalizer            → NormalizedOutput
    │
    ▼
ReflectionStage             → ReflectionResult (final external check)
    │
    ▼
QualityScoringStage         → QualityScore (threshold = 90)
    │
    ├─ score >= 90 AND reflection.passed ─► persist + DONE
    │
    └─ (score < 90 OR reflection produced directives) AND regen_count < 2:
          inject combined feedback as system delta →
          re-run ImprovePass → ReviewPass → FinalPass (this counts as 1 regen) →
          re-Reflection → re-Score
          (if regen_count == 2 and still < 90:
              persist with metadata.warning = 'quality_threshold_not_met'
              status = complete)
```

LLM call budget (no regen): Analyzer + Planning + Research summarization + Draft + Improve + Review + Final + Reflection + Scoring = **9 calls**.

Latency budget: target ≤ 5× legacy single-shot. User-facing latency mitigated by streaming `event{type:'stage', name:...}` so the activity-status bar shows progress through each stage.

## 5.5 Side-by-side mode comparison

| Aspect | Fast | Chat | Research |
|---|---|---|---|
| Quality threshold | 80 | 85 | 90 |
| Max regenerations | 2 | 2 | 2 |
| TaskAnalyzer | yes | yes | yes |
| Planning | — | yes | yes |
| Research | — | — | yes |
| Draft pass | yes | yes | yes |
| Improve pass | — | — | yes |
| Review pass | — | — | yes |
| Final pass | — | — | yes |
| Reflection | — | yes | yes |
| Scoring | yes | yes | yes |
| LLM calls (no regen) | 3 | 5 | 9 |
| Worst case (2 regens) | 5 | 7 | 13 |
| Default for | short replies | standard chat | academic docs |
| Stage rows persisted | 2–5 | 4–7 | 7–13 |

---

# 6. STATE FLOW

```
                      ┌─────────────────────────────────────────────┐
                      │                                             │
   pending ─► analyzing ─► planning ─► researching ─► writing ─► reviewing ─► scoring
                                                          ▲                       │
                                                          │      score < threshold│
                                                          └───── regen_count++ ◄──┤
                                                                                  │
                                                                  ┌──── passed ◄──┘
                                                                  ▼
                                                              complete
   any stage error ─► failed
   user stop signal ─► aborted   (existing Cache::get('chat_stop_' . $conversationId) flag preserved)
```

Stage-skip per mode:

| Status | Fast | Chat | Research |
|---|---|---|---|
| analyzing | enter | enter | enter |
| planning | skip | enter | enter |
| researching | skip | skip | enter |
| writing | enter (Draft only) | enter (Draft only) | enter (4 passes) |
| reviewing | skip | enter | enter |
| scoring | enter | enter | enter |

Regen cap: `min(GenerationOptions.maxRegenerations, config('rynude.max_regenerations'), 2)`.

On regen exhaustion: status → `complete`, `final_score` = last score, `metadata.warning = 'quality_threshold_not_met'`. UI surfaces a warning badge but still ships the artifact.

---

# 7. SEQUENCE DIAGRAM (Research Mode end-to-end)

```
ChatInterface           GenerationCoordinator         ThinkingPipeline           ModelAdapter        DB
     │                          │                            │                         │              │
 send │ generate(ctx)           │                            │                         │              │
     ├─────────────────────────►│                            │                         │              │
     │                          │ ModeSelector.resolve()     │                         │              │
     │                          │   → mode=research,         │                         │              │
     │                          │     threshold=90           │                         │              │
     │                          │ create GenerationRun       ├────────────────────────────────────────►│
     │                          │ build NormalizedRequest    │                         │              │
     │                          │ via UniversalSystemPrompt  │                         │              │
     │                          │                            │                         │              │
     │                          │ ThinkingPipeline.run(pctx) │                         │              │
     │                          ├───────────────────────────►│                         │              │
     │                          │                            │ TaskAnalyzerStage       │              │
     │                          │                            ├────────────────────────►│              │
     │  event{stage:analyzed}  ◄┤◄───────────────────────────┤ persist GenerationStage ├─────────────►│
     │                          │                            │                         │              │
     │                          │                            │ PlanningStage           │              │
     │  event{stage:plan_ready}◄┤◄───────────────────────────┤ persist Plan            ├─────────────►│
     │                          │                            │                         │              │
     │                          │                            │ ResearchStage           │              │
     │  event{stage:research}  ◄┤◄───────────────────────────┤ persist Stage           ├─────────────►│
     │                          │                            │                         │              │
     │                          │                            │ MultiPassWritingStage   │              │
     │                          │                            │  ┌─ DraftPass ────────►│ stream tokens │
     │ event{type:text,...}    ◄┤◄───────────────────────────┤◄─┘                      │              │
     │                          │                            │  ┌─ ImprovePass ──────►│              │
     │                          │                            │  ┌─ ReviewPass ───────►│              │
     │                          │                            │  └─ FinalPass ────────►│              │
     │                          │                            │  OutputNormalizer.parse│              │
     │                          │                            │                         │              │
     │                          │                            │ ReflectionStage         │              │
     │ event{stage:reflection} ◄┤◄───────────────────────────┤ persist ReflectionLog   ├─────────────►│
     │                          │                            │                         │              │
     │                          │                            │ QualityScoringStage     │              │
     │ event{type:score,90}    ◄┤◄───────────────────────────┤ persist QualityScore    ├─────────────►│
     │                          │                            │                         │              │
     │                          │                            │ score 84 < threshold 90 │              │
     │                          │                            │ regen_count 0→1         │              │
     │ event{type:reset}       ◄┤◄───────────────────────────┤ inject feedback         │              │
     │                          │                            │ → ImprovePass/Review/Final            │
     │                          │                            │ → re-Reflection → re-Score            │
     │                          │                            │ score 92 ≥ threshold 90 │              │
     │                          │                            │                         │              │
     │                          │ persist Message + Artifact │                         ├─────────────►│
     │                          │ update GenerationRun:complete                        ├─────────────►│
     │ event{type:done,score:92}◄                            │                         │              │
     ▼                          ▼                            ▼                         ▼              ▼
  Livewire stream
  (message-stream, activity-status, score-badge, artifactReady)
```

Fast Mode and Chat Mode sequences are subsets of this diagram — see Section 5 for skipped stages.

---

# 8. DATABASE IMPACT & PERFORMANCE IMPLICATIONS

## 8.1 Database impact — CONFIRMED

**New tables (6):**

| Table | Rows per run | Indexes | Notes |
|---|---|---|---|
| generation_runs | 1 | conversation_id+created, user_id+created, status, mode | One row per turn |
| generation_stages | 2–13 (mode-dependent, see §3.3) | (run_id, pass_index, stage) | High-volume but bounded |
| quality_scores | 1–3 (one per scoring pass) | (run_id, pass_index) | Includes regen passes |
| plans | 0–1 | run_id | Chat + Research only |
| reflection_logs | 0–3 | run_id | Chat + Research only |
| prompt_templates | static (seeded ~12 rows) | (key, version, provider_variant) | Read-mostly |

**Existing-table modifications:**

- `messages`: +2 nullable columns (`generation_run_id`, `pipeline_mode`). Backfill not required — legacy rows stay NULL.
- `message_artifacts`: +2 nullable columns (`generation_run_id`, `quality_score`). Backfill not required.

**Foreign keys:** all `generation_run_id` FKs on child tables use `onDelete cascade` so deleting a conversation cleans up the entire pipeline trail in one cascade. Verified safe with the existing `conversations → messages → message_artifacts` cascade chain.

**SQLite-specific concerns:**

- All new schema is created via `Schema::create()` and `Schema::table()` — both work under SQLite.
- JSON columns: SQLite stores JSON as TEXT; queries on JSON paths are not used in Sprint 1 (we read whole `*_json` blobs into PHP). No performance concern.
- Write contention: pipeline stages persist sequentially within a single PHP request; no concurrent writers per run. SQLite's default write lock is acceptable.
- Database file size growth: estimate 1 KB per `generation_stages` row + 0.5 KB per other row → ~5–10 KB per Research-Mode run. At 5,000 runs/day this is ~50 MB/day for pipeline data — manageable for ~90 days before Postgres migration becomes prudent.

**Migration order:** all 7 new migrations are additive and dependency-ordered (parents before children). Idempotent under `php artisan migrate`.

**Backfill required:** none. New columns are nullable; legacy assistant messages and artifacts remain readable.

## 8.2 Performance implications — CONFIRMED

### 8.2.1 LLM call cost per mode (no regeneration)

| Mode | LLM calls | Typical model mix |
|---|---|---|
| Fast | 3 | analyzer = Haiku, draft = user-chosen, scorer = Haiku |
| Chat | 5 | analyzer/planner/reflection/scorer = Haiku, draft = user-chosen |
| Research | 9 | analyzer/planner/research-summary/reflection/scorer = Haiku, 4 writing passes = user-chosen |

Lightweight stages (analyzer, planner, reflection, scorer) default to Haiku (`claude-haiku-4-5`) regardless of the writing model. Configurable via `config/rynude.php → stage_model_overrides`.

### 8.2.2 Estimated dollar cost per turn

Using Anthropic price card (`CostTracker::$pricing`): Haiku $0.80/M input + $4/M output; Sonnet $3/M input + $15/M output.

| Mode | Light calls (Haiku, ~1.5k in / 0.5k out each) | Writing call(s) (Sonnet, ~4k in / 2k out) | Total per run | Vs. legacy single-shot |
|---|---|---|---|---|
| Legacy | — | $0.042 | $0.042 | 1.0× |
| Fast | 2 × $0.003 = $0.006 | $0.042 | $0.048 | 1.14× |
| Chat | 4 × $0.003 = $0.012 | $0.042 | $0.054 | 1.29× |
| Research | 5 × $0.003 = $0.015 | 4 × $0.042 = $0.168 | $0.183 | 4.36× |

Research Mode worst case with 2 regens: ~$0.30/turn. Acceptable for academic deliverables; users are warned via UI badge before forcing Research Mode on a free-form chat.

### 8.2.3 Latency

Stage-level streaming surfaces progress to the user well before the writing pass starts producing tokens. End-to-end latency targets (assuming Sonnet writing pass at ~3s, Haiku at ~0.8s):

| Mode | Target end-to-end | Visible to user as |
|---|---|---|
| Fast | ≤ 5s | "Thinking… Generating… Scoring…" |
| Chat | ≤ 8s | "Analyzing → Planning → Generating → Reflecting → Scoring" |
| Research | ≤ 25s | Full stage progress + intermediate artifact preview during Improve/Review passes |

Streaming preserves perceived responsiveness: the user sees `event{stage:'planning'}` within ~500 ms of submit, and writing-pass tokens start streaming once Planning completes.

### 8.2.4 Pipeline overhead (non-LLM)

- Coordinator + stage dispatch: ≤ 20 ms per stage on the existing PHP setup.
- OutputNormalizer regex + DTO construction: ≤ 5 ms for a 50 KB response.
- DB writes per stage: ≤ 10 ms (SQLite write lock).
- Cumulative non-LLM overhead per Research-Mode run: ≤ 200 ms — negligible compared to LLM latency.

### 8.2.5 Cost control mechanisms

1. **Mode auto-detection** prevents accidental Research-Mode runs on small chat turns.
2. **Regeneration cap = 2** bounds worst-case cost per turn.
3. **Per-stage token caps** configured in `config/rynude.php`:
   ```
   stage_max_tokens => [
       'task_analyzer' => 512,
       'planning'      => 1024,
       'research'      => 1024,
       'draft'         => 4096,   (Fast/Chat)
       'draft'         => 8192,   (Research)
       'improve'       => 8192,
       'review'        => 4096,
       'final'         => 8192,
       'reflection'    => 768,
       'scoring'       => 512,
   ]
   ```
4. **Light-stage model override**: Haiku for analyzer/planner/reflection/scorer cuts ~80 % of light-call cost regardless of the user's chosen writing model.

### 8.2.6 Failure modes and graceful degradation

| Failure | Behaviour |
|---|---|
| Light-stage LLM call fails | retry once with backoff (matches existing Anthropic retry); on second failure, skip the stage and log a warning. Pipeline continues. |
| Writing pass fails | run marked `status='failed'`, error persisted, user sees `event{type:'error'}`. |
| Scorer returns malformed JSON | OutputNormalizer flags it, score defaults to 0 with `notes_json.parse_error=true`, regen still triggered up to cap. |
| User stops mid-pipeline | existing `chat_stop_*` cache flag honoured between stages and inside `streamCompletion`. Run marked `aborted`; completed stage rows preserved. |
| Pipeline exceeds 5 minutes | Coordinator enforces a wall-clock guard: status → `failed`, error = `pipeline_timeout`. |

## 8.3 Confirmation

Database impact: **CONFIRMED ACCEPTABLE** for the Sprint 1 scope. No schema changes required beyond the 7 listed migrations. No backfill required. SQLite remains viable through Phase 4.

Performance implications: **CONFIRMED ACCEPTABLE**. Worst-case cost 4.36× legacy single-shot for Research Mode, mitigated by (a) mode auto-detection, (b) regen cap of 2, (c) Haiku for light stages, (d) per-stage token caps. Latency mitigated by streamed stage events keeping the user informed.

---

# 9. TESTING PLAN

## 9.1 Phase 1 — Normalization Layer

**Unit (tests/Unit)**
- `UniversalSystemPromptBuilder`: snapshot test for identical PromptContext; per-section toggles produce expected diffs.
- `OutputNormalizer`: well-formed artifact, malformed close tag, multiple artifacts, no artifact + refusal, mixed thinking blocks (Anthropic + `[Thinking]` prefix).
- `RefusalDetector`: bilingual fixtures (EN + ID — "maaf", "tidak bisa", etc.).
- `ModelCapability`: per-model code → expected capability flags.

**Feature (tests/Feature/Normalization)**
- `ModelAdapterRegistryTest`: every shipped model code resolves to the right adapter (mirrors `AiService::resolveProvider`).
- `AnthropicAdapterTest` + `OpenAIAdapterTest` + `GoogleAdapterTest` + `MistralAdapterTest`: FakeLLMProvider returns canned event streams → adapter yields the right NormalizedEvent sequence.
- Cross-provider equivalence: identical NormalizedRequest produces equivalent NormalizedOutput across all four adapters.

## 9.2 Phase 2 — Universal Thinking Pipeline

**Mode-specific end-to-end (tests/Feature/Pipeline)**
- `FastModeEndToEndTest`: 3 LLM calls, no planning, no reflection, score against threshold 80.
- `ChatModeEndToEndTest`: 5 LLM calls, plan + reflection persisted, score against threshold 85.
- `ResearchModeEndToEndTest`: 9 LLM calls including 4 writing passes, ResearchPack persisted, score against threshold 90.

**Mode selection**
- `ModeSelectorTest`:
  * Skripsi YAML front-matter → Research.
  * Project description tagged "research" → Research.
  * "buatkan skripsi" / "thesis chapter" → Research.
  * Default request → Chat.
  * "ringkas singkat", greeting, short Q&A → Fast.
  * Explicit user override always wins (forced Fast on a skripsi request → Fast).

**Regeneration**
- `RegenerationLoopTest`: stubbed scorer returns 70 then 90 → 1 regen → pass.
- `MaxRegenerationCapTest`: stubbed scorer always returns 70 → 2 regens → complete with `metadata.warning = 'quality_threshold_not_met'`.
- Per-mode threshold test: scorer returns 82 → passes in Fast Mode (≥ 80), fails in Chat Mode (< 85), fails in Research Mode (< 90).

**Streaming events**
- `PipelineStreamingEventsTest`: assert order of `event{type:'stage', name:...}` emissions matches the documented per-mode pipeline path. Assert `score` and `done` events arrive in order.

**Regression**
- All existing tests in tests/Feature/ pass unchanged. `ChatInterface::generateResponse` is refactored to delegate to `GenerationCoordinator`; observable behaviour (DB rows, artifact extraction, memory refresh) stays identical.

**Performance**
- Fast Mode pipeline overhead (non-LLM): ≤ 50 ms.
- Chat Mode overhead: ≤ 100 ms.
- Research Mode overhead: ≤ 200 ms.
- Asserted via microbenchmark with FakeLLMProvider returning immediately.

**Test infrastructure**
- `tests/Support/FakeLLMProvider.php` — implements `LLMProviderInterface` + `SupportsToolUse`, records every call, scripted responses.
- `tests/Support/FixturePrompts.php` — 12–15 user-input fixtures covering ID/EN, chat/document/revision scenarios.

---

# 10. CONFIG REFERENCE

```php
// config/rynude.php (Sprint 1 scope)
return [
    'pipeline_version' => 'v5.1',

    'max_regenerations' => 2,            // LOCKED — hard cap

    'modes' => [
        'fast' => [
            'quality_threshold' => 80,
            'stages' => ['task_analyzer', 'draft', 'scoring'],
        ],
        'chat' => [
            'quality_threshold' => 85,
            'stages' => ['task_analyzer', 'planning', 'draft', 'reflection', 'scoring'],
        ],
        'research' => [
            'quality_threshold' => 90,
            'stages' => ['task_analyzer', 'planning', 'research', 'draft', 'improve', 'review', 'final', 'reflection', 'scoring'],
        ],
    ],

    'default_mode' => 'auto',             // auto | fast | chat | research

    'stage_max_tokens' => [
        'task_analyzer' => 512,
        'planning'      => 1024,
        'research'      => 1024,
        'draft.fast'    => 4096,
        'draft.chat'    => 4096,
        'draft.research'=> 8192,
        'improve'       => 8192,
        'review'        => 4096,
        'final'         => 8192,
        'reflection'    => 768,
        'scoring'       => 512,
    ],

    'stage_model_overrides' => [
        'task_analyzer' => 'claude-haiku-4-5',
        'planning'      => 'claude-haiku-4-5',
        'research'      => 'claude-haiku-4-5',
        'reflection'    => 'claude-haiku-4-5',
        'scoring'       => 'claude-haiku-4-5',
    ],

    'pipeline_wall_clock_seconds' => 300,

    'quality_score_weights' => [
        'accuracy'         => 0.30,
        'completeness'     => 0.25,
        'consistency'      => 0.15,
        'academic_quality' => 0.15,
        'formatting'       => 0.15,
    ],
];
```

---

# 11. APPROVAL CHECKLIST

Before code generation begins:

- [x] Architecture diagram reviewed
- [x] Folder structure reviewed
- [x] Database design reviewed (6 new tables, 2 column additions)
- [x] API contracts reviewed
- [x] Three execution modes documented (Fast / Chat / Research)
- [x] Quality thresholds locked (80 / 85 / 90)
- [x] Max regenerations locked (2)
- [x] Database impact confirmed
- [x] Performance implications confirmed
- [x] Testing plan reviewed
- [ ] **AWAITING: User approval to begin Sprint 1 code generation**

---

# 12. SPRINT 1 IMPLEMENTATION ORDER (when approved)

Proposed order — each step is independently testable:

1. Migrations (7 files) — schema is the foundation everything builds on.
2. DTOs (`Coordinator/GenerationContext`, `DTO/NormalizedRequest`, `DTO/NormalizedOutput`, `DTO/QualityScore`, planning/reflection DTOs).
3. ModelAdapter base class + AnthropicAdapter (smoke test against existing AnthropicProvider).
4. Remaining adapters (OpenAI, Google, Mistral) + ModelAdapterRegistry.
5. OutputNormalizer + extractors.
6. UniversalSystemPromptBuilder + section classes + templates.
7. QualityScoringEngine + 5 rubrics.
8. TaskAnalyzer + PlanningEngine.
9. ReflectionEngine.
10. MultiPassWriter + 4 passes.
11. PipelineStage contract + 6 stage classes.
12. ThinkingPipeline + PipelineContext.
13. ModeSelector + GenerationCoordinator.
14. ChatInterface refactor (delegate to Coordinator; keep stream targets).
15. Tests for each layer (in parallel with each step).
16. Seed prompt_templates table with v5.1.0 baseline.

End of Sprint 1 package.
