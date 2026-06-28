<?php

namespace Tests\Unit\DTO;

use App\Services\AI\Coordinator\GenerationOptions;
use App\Services\AI\DTO\ArtifactDto;
use App\Services\AI\DTO\CitationDto;
use App\Services\AI\DTO\NormalizedOutput;
use App\Services\AI\DTO\NormalizedRequest;
use App\Services\AI\DTO\ReasoningTrace;
use App\Services\AI\DTO\ToolCallDto;
use App\Services\AI\Normalization\Events\NormalizedEvent;
use App\Services\AI\Planning\DTO\ExecutionPlan;
use App\Services\AI\Planning\DTO\TaskBrief;
use App\Services\AI\Quality\DTO\QualityScore;
use App\Services\AI\Reflection\DTO\ReflectionResult;
use App\Services\AI\Reflection\DTO\RevisionDirective;
use PHPUnit\Framework\TestCase;

class DtosTest extends TestCase
{
    public function test_generation_options_defaults_match_locked_values(): void
    {
        $opts = new GenerationOptions();
        $this->assertSame('auto', $opts->mode);
        $this->assertNull($opts->qualityThresholdOverride);
        $this->assertSame(2, $opts->maxRegenerations);
        $this->assertTrue($opts->enableThinking);
        $this->assertTrue($opts->enableQualityScoring);
        $this->assertTrue($opts->persistStages);
    }

    public function test_generation_options_accepts_explicit_mode(): void
    {
        $opts = new GenerationOptions(mode: 'research', maxRegenerations: 2);
        $this->assertSame('research', $opts->mode);
        $this->assertSame(2, $opts->maxRegenerations);
    }

    public function test_normalized_request_holds_values(): void
    {
        $req = new NormalizedRequest(
            systemPrompt: 'sys',
            messages: [['role' => 'user', 'content' => 'hi']],
            tools: [['name' => 't']],
            temperature: 0.5,
            maxTokens: 1024,
            thinkingBudget: 4000,
            jsonMode: true,
            jsonSchema: '{"type":"object"}',
        );

        $this->assertSame('sys', $req->systemPrompt);
        $this->assertSame(0.5, $req->temperature);
        $this->assertSame(1024, $req->maxTokens);
        $this->assertSame(4000, $req->thinkingBudget);
        $this->assertTrue($req->jsonMode);
        $this->assertSame('{"type":"object"}', $req->jsonSchema);
    }

    public function test_artifact_dto_to_array(): void
    {
        $a = new ArtifactDto(
            identifier: 'id1',
            type: 'application/vnd.ant.code',
            language: 'markdown',
            title: 'Doc',
            content: 'body',
        );

        $this->assertSame([
            'identifier' => 'id1',
            'type' => 'application/vnd.ant.code',
            'language' => 'markdown',
            'title' => 'Doc',
            'content' => 'body',
        ], $a->toArray());
    }

    public function test_normalized_output_defaults(): void
    {
        $out = new NormalizedOutput('hello');
        $this->assertSame('hello', $out->visibleText);
        $this->assertNull($out->artifact);
        $this->assertNull($out->reasoning);
        $this->assertSame([], $out->toolCalls);
        $this->assertSame([], $out->citations);
        $this->assertSame([], $out->flags);
    }

    public function test_normalized_output_with_artifact_and_reasoning(): void
    {
        $artifact = new ArtifactDto('id', 'code', 'md', 'T', 'C');
        $reasoning = new ReasoningTrace('thinking', 'sig');
        $out = new NormalizedOutput(
            visibleText: 'visible',
            artifact: $artifact,
            reasoning: $reasoning,
            flags: ['refused' => false, 'missing_artifact' => false],
        );

        $this->assertSame($artifact, $out->artifact);
        $this->assertSame('sig', $out->reasoning->signature);
        $this->assertFalse($out->flags['refused']);
    }

    public function test_reasoning_trace_signature_is_optional(): void
    {
        $r = new ReasoningTrace('thoughts');
        $this->assertSame('thoughts', $r->text);
        $this->assertNull($r->signature);
    }

    public function test_tool_call_dto_round_trips(): void
    {
        $t = new ToolCallDto('id_1', 'read_file', ['path' => 'foo.txt']);
        $this->assertSame('id_1', $t->id);
        $this->assertSame('read_file', $t->name);
        $this->assertSame(['path' => 'foo.txt'], $t->input);
    }

    public function test_citation_dto_optional_fields(): void
    {
        $c = new CitationDto('Some claim');
        $this->assertNull($c->url);
        $this->assertNull($c->title);

        $c2 = new CitationDto('Some claim', 'https://example.com', 'Example');
        $this->assertSame('https://example.com', $c2->url);
        $this->assertSame('Example', $c2->title);
    }

    public function test_task_brief_round_trips_via_array(): void
    {
        $original = new TaskBrief(
            goal: 'write chapter 2',
            audience: 'student',
            deliverable: 'document',
            constraints: ['no plagiarism'],
            successCriteria: ['includes citations'],
            suggestedMode: 'research',
            rawSignals: ['detected_yaml' => true],
        );

        $arr = $original->toArray();
        $this->assertSame('research', $arr['suggested_mode']);
        $this->assertSame(['no plagiarism'], $arr['constraints']);
        $this->assertTrue($arr['raw_signals']['detected_yaml']);

        $reconstructed = TaskBrief::fromArray($arr);
        $this->assertEquals($original->toArray(), $reconstructed->toArray());
    }

    public function test_execution_plan_defaults_and_round_trip(): void
    {
        $p = new ExecutionPlan(['step 1', 'step 2']);
        $this->assertCount(2, $p->steps);
        $this->assertSame([], $p->requiredResearch);
        $this->assertSame([], $p->styleRules);
        $this->assertNull($p->outline);

        $arr = $p->toArray();
        $restored = ExecutionPlan::fromArray($arr);
        $this->assertEquals($p->toArray(), $restored->toArray());
    }

    public function test_reflection_result_pass(): void
    {
        $r = new ReflectionResult(true, ['goal' => true, 'citations' => true]);
        $this->assertTrue($r->passed);
        $this->assertEmpty($r->directives);
    }

    public function test_reflection_result_with_directives_serializes(): void
    {
        $r = new ReflectionResult(
            passed: false,
            checklist: ['goal' => false],
            directives: [
                new RevisionDirective('completeness', 'Add the methodology section', 'high'),
                new RevisionDirective('formatting', 'Justify body text'),
            ],
        );

        $arr = $r->toArray();
        $this->assertFalse($arr['passed']);
        $this->assertCount(2, $arr['directives']);
        $this->assertSame('high', $arr['directives'][0]['severity']);
        $this->assertSame('medium', $arr['directives'][1]['severity']);
    }

    public function test_revision_directive_default_severity_is_medium(): void
    {
        $d = new RevisionDirective('completeness', 'add citations');
        $this->assertSame('medium', $d->severity);
    }

    public function test_quality_score_to_array_includes_threshold_and_passed(): void
    {
        $s = new QualityScore(
            accuracy: 90,
            completeness: 80,
            consistency: 85,
            academicQuality: 70,
            formatting: 95,
            overall: 84,
            threshold: 85,
            passed: false,
            scorerModel: 'claude-haiku-4-5',
            notes: ['completeness' => 'missing references'],
        );

        $arr = $s->toArray();
        $this->assertFalse($arr['passed']);
        $this->assertSame(85, $arr['threshold']);
        $this->assertSame(84, $arr['overall']);
        $this->assertSame('v5.1.0', $arr['rubric_version']);
        $this->assertSame('missing references', $arr['notes']['completeness']);
    }

    public function test_normalized_event_text_factory(): void
    {
        $e = NormalizedEvent::text('hello');
        $this->assertSame('text', $e->type);
        $this->assertSame('hello', $e->payload['text']);
    }

    public function test_normalized_event_thinking_factory(): void
    {
        $e = NormalizedEvent::thinking('thoughts');
        $this->assertSame('thinking', $e->type);
        $this->assertSame('thoughts', $e->payload['text']);
    }

    public function test_normalized_event_tool_use_factory(): void
    {
        $e = NormalizedEvent::toolUse('id_1', 'read_file', ['path' => 'x']);
        $this->assertSame('tool_use', $e->type);
        $this->assertSame('id_1', $e->payload['id']);
        $this->assertSame('read_file', $e->payload['name']);
        $this->assertSame(['path' => 'x'], $e->payload['input']);
    }

    public function test_normalized_event_usage_factory(): void
    {
        $e = NormalizedEvent::usage(100, 50, 10, 5);
        $this->assertSame('usage', $e->type);
        $this->assertSame(100, $e->payload['tokens_in']);
        $this->assertSame(50, $e->payload['tokens_out']);
        $this->assertSame(10, $e->payload['cache_read_tokens']);
        $this->assertSame(5, $e->payload['cache_write_tokens']);
    }

    public function test_normalized_event_stage_factory(): void
    {
        $e = NormalizedEvent::stage('planning', 'done', ['plan_id' => 7]);
        $this->assertSame('stage', $e->type);
        $this->assertSame('planning', $e->payload['name']);
        $this->assertSame('done', $e->payload['status']);
        $this->assertSame(7, $e->payload['plan_id']);
    }

    public function test_normalized_event_score_factory(): void
    {
        $e = NormalizedEvent::score(90, 85, true);
        $this->assertSame('score', $e->type);
        $this->assertSame(90, $e->payload['overall']);
        $this->assertSame(85, $e->payload['threshold']);
        $this->assertTrue($e->payload['passed']);
    }

    public function test_normalized_event_done_factory(): void
    {
        $e = NormalizedEvent::done(['final_score' => 92]);
        $this->assertSame('done', $e->type);
        $this->assertSame(92, $e->payload['final_score']);
    }

    public function test_normalized_event_error_factory(): void
    {
        $e = NormalizedEvent::error('boom');
        $this->assertSame('error', $e->type);
        $this->assertSame('boom', $e->payload['message']);
    }

    public function test_normalized_event_reset_factory(): void
    {
        $e = NormalizedEvent::reset();
        $this->assertSame('reset', $e->type);
        $this->assertSame([], $e->payload);
    }
}
