<?php

namespace Tests\Feature;

use App\Services\AI\WorkspaceContext;
use App\Services\AI\CostTracker;
use App\Services\AI\AnthropicProvider;
use App\Services\AI\AgentRunner;
use App\Services\AI\AgentTools;
use App\Services\AI\AiService;
use App\Services\LocalWorkspaceService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AgentLoopTest extends TestCase
{
    private string $tempWorkspace;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tempWorkspace = sys_get_temp_dir() . '/rynude_test_ws_2_' . uniqid();
        if (!is_dir($this->tempWorkspace)) {
            mkdir($this->tempWorkspace, 0755, true);
        }
        
        CostTracker::reset();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempWorkspace)) {
            File::deleteDirectory($this->tempWorkspace);
        }
        parent::tearDown();
    }

    public function test_workspace_context_detection_and_file_loading(): void
    {
        // 1. Write dummy composer.json and package.json
        $localSvc = new LocalWorkspaceService();
        $localSvc->writeFile($this->tempWorkspace, 'composer.json', json_encode([
            'require' => ['laravel/framework' => '^10.0']
        ]));
        $localSvc->writeFile($this->tempWorkspace, 'package.json', json_encode([
            'dependencies' => ['react' => '^18.2.0']
        ]));
        
        // 2. Write RYNUDE.md and AGENTS.md
        $localSvc->writeFile($this->tempWorkspace, 'RYNUDE.md', 'Custom instructions here.');
        $localSvc->writeFile($this->tempWorkspace, 'AGENTS.md', 'Agent preferences here.');

        // 3. Retrieve context
        $context = WorkspaceContext::getContext($this->tempWorkspace);
        
        $this->assertStringContainsString('Laravel Framework', $context);
        $this->assertStringContainsString('React', $context);
        $this->assertStringContainsString('Custom instructions here.', $context);
        $this->assertStringContainsString('Agent preferences here.', $context);
    }

    public function test_cost_tracker_accumulation_and_reset(): void
    {
        // Model: claude-sonnet-4-6 (Input: $3/M, Output: $15/M)
        // 1. Track first turn
        $cost = CostTracker::track('claude-sonnet-4-6', 100000, 20000); // 0.1M input, 0.02M output
        
        // Input: 0.1M * $3 = $0.3
        // Output: 0.02M * $15 = $0.3
        // Total: $0.6
        $this->assertEquals(0.6, $cost);

        $summary = CostTracker::getSessionSummary();
        $this->assertEquals(0.6, $summary['cost']);
        $this->assertEquals(120000, $summary['total_tokens']);

        // 2. Track second turn
        CostTracker::track('claude-sonnet-4-6', 50000, 10000);
        $summary2 = CostTracker::getSessionSummary();
        $this->assertEquals(0.9, $summary2['cost']);

        // 3. Reset
        CostTracker::reset();
        $summaryReset = CostTracker::getSessionSummary();
        $this->assertEquals(0.0, $summaryReset['cost']);
        $this->assertEquals(0, $summaryReset['total_tokens']);
    }

    public function test_model_supports_thinking_checks(): void
    {
        $provider = new AnthropicProvider();
        
        // We make a reflection method accessible to test modelSupportsThinking
        $reflector = new \ReflectionClass(AnthropicProvider::class);
        $method = $reflector->getMethod('modelSupportsThinking');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($provider, 'claude-sonnet-4-6'));
        $this->assertTrue($method->invoke($provider, 'claude-3-5-sonnet-20241022'));
        $this->assertFalse($method->invoke($provider, 'claude-3-5-haiku-20241022')); // Haiku does not support thinking
    }
}
