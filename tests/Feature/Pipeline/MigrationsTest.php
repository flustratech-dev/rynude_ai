<?php

namespace Tests\Feature\Pipeline;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_new_pipeline_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('generation_runs'));
        $this->assertTrue(Schema::hasTable('generation_stages'));
        $this->assertTrue(Schema::hasTable('quality_scores'));
        $this->assertTrue(Schema::hasTable('plans'));
        $this->assertTrue(Schema::hasTable('reflection_logs'));
        $this->assertTrue(Schema::hasTable('prompt_templates'));
    }

    public function test_generation_runs_has_required_columns(): void
    {
        $columns = [
            'id', 'user_id', 'conversation_id', 'message_id', 'model',
            'pipeline_version', 'mode', 'quality_threshold', 'status',
            'regen_count', 'final_score', 'cost_micro_usd',
            'tokens_in', 'tokens_out', 'elapsed_ms',
            'error', 'metadata', 'created_at', 'updated_at',
        ];
        foreach ($columns as $col) {
            $this->assertTrue(
                Schema::hasColumn('generation_runs', $col),
                "generation_runs is missing column: {$col}"
            );
        }
    }

    public function test_generation_stages_has_required_columns(): void
    {
        $columns = [
            'id', 'generation_run_id', 'stage', 'pass_index', 'status',
            'prompt_template_key', 'input_json', 'output_json',
            'thinking_text', 'tokens_in', 'tokens_out', 'elapsed_ms',
            'error', 'created_at',
        ];
        foreach ($columns as $col) {
            $this->assertTrue(
                Schema::hasColumn('generation_stages', $col),
                "generation_stages is missing column: {$col}"
            );
        }
    }

    public function test_quality_scores_has_required_columns(): void
    {
        $columns = [
            'id', 'generation_run_id', 'pass_index',
            'accuracy', 'completeness', 'consistency',
            'academic_quality', 'formatting', 'overall',
            'threshold', 'rubric_version', 'scorer_model',
            'notes_json', 'passed_threshold', 'created_at',
        ];
        foreach ($columns as $col) {
            $this->assertTrue(
                Schema::hasColumn('quality_scores', $col),
                "quality_scores is missing column: {$col}"
            );
        }
    }

    public function test_plans_has_required_columns(): void
    {
        foreach (['id', 'generation_run_id', 'brief_json', 'execution_json', 'created_at'] as $col) {
            $this->assertTrue(
                Schema::hasColumn('plans', $col),
                "plans is missing column: {$col}"
            );
        }
    }

    public function test_reflection_logs_has_required_columns(): void
    {
        $columns = [
            'id', 'generation_run_id', 'pass_index',
            'checklist_json', 'directives_json', 'passed', 'created_at',
        ];
        foreach ($columns as $col) {
            $this->assertTrue(
                Schema::hasColumn('reflection_logs', $col),
                "reflection_logs is missing column: {$col}"
            );
        }
    }

    public function test_prompt_templates_has_required_columns(): void
    {
        $columns = [
            'id', 'key', 'version', 'body', 'variables_json',
            'provider_variant', 'is_active', 'created_at', 'updated_at',
        ];
        foreach ($columns as $col) {
            $this->assertTrue(
                Schema::hasColumn('prompt_templates', $col),
                "prompt_templates is missing column: {$col}"
            );
        }
    }

    public function test_messages_received_pipeline_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('messages', 'generation_run_id'));
        $this->assertTrue(Schema::hasColumn('messages', 'pipeline_mode'));
    }

    public function test_message_artifacts_received_pipeline_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('message_artifacts', 'generation_run_id'));
        $this->assertTrue(Schema::hasColumn('message_artifacts', 'quality_score'));
    }

    public function test_legacy_message_row_inserts_without_new_columns(): void
    {
        $user = \App\Models\User::factory()->create();
        $conv = \App\Models\Conversation::create([
            'user_id' => $user->id,
            'title' => 'Legacy',
        ]);

        $msg = \App\Models\Message::create([
            'conversation_id' => $conv->id,
            'role' => 'assistant',
            'content' => 'hi',
        ]);

        $this->assertNotNull($msg->id);
        $this->assertNull($msg->generation_run_id);
        $this->assertNull($msg->pipeline_mode);
    }

    public function test_generation_run_lifecycle_persists(): void
    {
        $user = \App\Models\User::factory()->create();
        $conv = \App\Models\Conversation::create([
            'user_id' => $user->id,
            'title' => 'Test',
        ]);

        $runId = \Illuminate\Support\Facades\DB::table('generation_runs')->insertGetId([
            'user_id' => $user->id,
            'conversation_id' => $conv->id,
            'model' => 'claude-haiku-4-5',
            'pipeline_version' => 'v5.1',
            'mode' => 'chat',
            'quality_threshold' => 85,
            'status' => 'pending',
            'regen_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertGreaterThan(0, $runId);

        $row = \Illuminate\Support\Facades\DB::table('generation_runs')->find($runId);
        $this->assertSame('chat', $row->mode);
        $this->assertSame(85, (int) $row->quality_threshold);
        $this->assertSame(0, (int) $row->regen_count);
    }

    public function test_deleting_conversation_cascades_pipeline_rows(): void
    {
        $user = \App\Models\User::factory()->create();
        $conv = \App\Models\Conversation::create([
            'user_id' => $user->id,
            'title' => 'Cascade',
        ]);

        $runId = \Illuminate\Support\Facades\DB::table('generation_runs')->insertGetId([
            'user_id' => $user->id,
            'conversation_id' => $conv->id,
            'model' => 'claude-haiku-4-5',
            'pipeline_version' => 'v5.1',
            'mode' => 'fast',
            'quality_threshold' => 80,
            'status' => 'complete',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('generation_stages')->insert([
            'generation_run_id' => $runId,
            'stage' => 'draft',
            'pass_index' => 0,
            'status' => 'done',
            'created_at' => now(),
        ]);

        $conv->delete();

        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('generation_runs')->where('id', $runId)->count());
        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('generation_stages')->where('generation_run_id', $runId)->count());
    }
}
