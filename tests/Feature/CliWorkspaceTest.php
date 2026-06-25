<?php

namespace Tests\Feature;

use App\Services\LocalWorkspaceService;
use App\Services\AI\AgentTools;
use App\Services\AI\DiffRenderer;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CliWorkspaceTest extends TestCase
{
    private string $tempWorkspace;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tempWorkspace = sys_get_temp_dir() . '/rynude_test_ws_3_' . uniqid();
        if (!is_dir($this->tempWorkspace)) {
            mkdir($this->tempWorkspace, 0755, true);
        }

        putenv('DANGEROUSLY_SKIP_PERMISSIONS=true');
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempWorkspace)) {
            File::deleteDirectory($this->tempWorkspace);
        }
        putenv('DANGEROUSLY_SKIP_PERMISSIONS');
        parent::tearDown();
    }

    public function test_automatic_backups_created_and_restored(): void
    {
        $localSvc = new LocalWorkspaceService();
        
        // 1. Create initial file
        $localSvc->writeFile($this->tempWorkspace, 'test.txt', 'Original content');
        
        // 2. Overwrite file (triggers backup)
        $localSvc->writeFile($this->tempWorkspace, 'test.txt', 'New content');
        
        // 3. Verify backup file exists in .rynude/backups
        $backupDir = $this->tempWorkspace . '/.rynude/backups';
        $this->assertTrue(is_dir($backupDir));
        
        $manifestPath = $backupDir . '/manifest.json';
        $this->assertTrue(file_exists($manifestPath));
        
        $manifest = json_decode(file_get_contents($manifestPath), true);
        $this->assertCount(1, $manifest);
        $this->assertEquals('test.txt', $manifest[0]['relative_path']);
        
        // 4. Restore backup
        $restored = $localSvc->restoreLastBackup($this->tempWorkspace);
        $this->assertEquals('test.txt', $restored);
        
        // 5. Verify restored content
        $content = File::get($this->tempWorkspace . '/test.txt');
        $this->assertEquals('Original content', $content);
    }

    public function test_git_operation_executes_allowed_subcommands(): void
    {
        // 1. Initialize dummy git repo in temp workspace
        exec("cd " . escapeshellarg($this->tempWorkspace) . " && git init");
        
        $tools = new AgentTools(localWorkspacePath: $this->tempWorkspace);
        
        // 2. Run git status
        $result = $tools->execute('git_operation', ['subcommand' => 'status']);
        $this->assertStringContainsString('Git Exit Code:', $result);
        $this->assertStringContainsString('On branch', $result);

        // 3. Block forbidden command
        $resultBlocked = $tools->execute('git_operation', ['subcommand' => 'push origin main']);
        $this->assertStringContainsString('not allowed for security reasons', $resultBlocked);
    }

    public function test_diff_renderer_outputs_formatted_changes(): void
    {
        $old = "Hello World\nLine B";
        $new = "Hello World\nLine C\nLine D";
        
        $diff = DiffRenderer::render($old, $new);
        
        $this->assertStringContainsString('- Line B', $diff);
        $this->assertStringContainsString('+ Line C', $diff);
        $this->assertStringContainsString('+ Line D', $diff);
    }
}
