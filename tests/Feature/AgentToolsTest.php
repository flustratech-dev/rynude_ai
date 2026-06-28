<?php

namespace Tests\Feature;

use App\Services\AI\AgentTools;
use App\Services\AI\PermissionGuard;
use App\Services\LocalWorkspaceService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AgentToolsTest extends TestCase
{
    private string $tempWorkspace;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup a temporary workspace directory for tests
        $this->tempWorkspace = sys_get_temp_dir() . '/rynude_test_ws_' . uniqid();
        if (!is_dir($this->tempWorkspace)) {
            mkdir($this->tempWorkspace, 0755, true);
        }
        
        // Initialize git so git ls-files and git grep work
        exec('git init ' . escapeshellarg($this->tempWorkspace));

        // Disable interactive permissions for tests by default
        putenv('DANGEROUSLY_SKIP_PERMISSIONS=true');
    }

    protected function tearDown(): void
    {
        // Clean up temporary workspace directory
        if (is_dir($this->tempWorkspace)) {
            File::deleteDirectory($this->tempWorkspace);
        }
        
        putenv('DANGEROUSLY_SKIP_PERMISSIONS'); // Clear env
        parent::tearDown();
    }

    public function test_bash_tool_runs_commands_and_blocks_blacklist(): void
    {
        $tools = new AgentTools(localWorkspacePath: $this->tempWorkspace);
        
        // 1. Run a simple echo command
        $result = $tools->execute('bash', ['command' => 'echo "hello from test"']);
        $this->assertStringContainsString('hello from test', $result);
        $this->assertStringContainsString('Exit Code: 0', $result);

        // 2. Block dangerous commands
        $resultBlocked = $tools->execute('bash', ['command' => 'sudo apt-get update']);
        $this->assertStringContainsString('blocked by security policy', $resultBlocked);
    }

    public function test_grep_search_finds_content_in_workspace(): void
    {
        $localSvc = new LocalWorkspaceService();
        $localSvc->writeFile($this->tempWorkspace, 'src/test.txt', "First line\nTargetFunction here\nThird line");
        exec('cd ' . escapeshellarg($this->tempWorkspace) . ' && git config user.email "test@example.com" && git config user.name "Test" && git add . && git commit -m "init"');

        $tools = new AgentTools(localWorkspacePath: $this->tempWorkspace);
        
        $result = $tools->execute('grep_search', ['query' => 'TargetFunction']);
        $this->assertStringContainsString('src/test.txt:2:TargetFunction here', $result);
    }

    public function test_glob_tool_matches_patterns(): void
    {
        $localSvc = new LocalWorkspaceService();
        $localSvc->writeFile($this->tempWorkspace, 'src/test.txt', 'test');
        $localSvc->writeFile($this->tempWorkspace, 'docs/readme.md', 'docs');

        $tools = new AgentTools(localWorkspacePath: $this->tempWorkspace);
        
        $result = $tools->execute('glob', ['pattern' => 'src/*.txt']);
        $this->assertStringContainsString('src/test.txt', $result);
        $this->assertStringNotContainsString('docs/readme.md', $result);
    }

    public function test_read_file_with_line_numbers_and_slice(): void
    {
        $localSvc = new LocalWorkspaceService();
        $localSvc->writeFile($this->tempWorkspace, 'test.txt', "Line A\nLine B\nLine C\nLine D");

        $tools = new AgentTools(localWorkspacePath: $this->tempWorkspace);
        
        // Read full file
        $resultAll = $tools->execute('read_file', ['path' => 'test.txt']);
        $this->assertStringContainsString("1: Line A\n2: Line B\n3: Line C\n4: Line D", $resultAll);

        // Read sliced range
        $resultSlice = $tools->execute('read_file', [
            'path' => 'test.txt',
            'start_line' => 2,
            'end_line' => 3
        ]);
        $this->assertStringContainsString("2: Line B\n3: Line C", $resultSlice);
        $this->assertStringNotContainsString("1: Line A", $resultSlice);
    }

    public function test_edit_file_with_line_numbers(): void
    {
        $localSvc = new LocalWorkspaceService();
        $localSvc->writeFile($this->tempWorkspace, 'test.txt', "Apple\nBanana\nApple\nCherry");

        $tools = new AgentTools(localWorkspacePath: $this->tempWorkspace);
        
        // Replace only the second Apple (at line 3)
        $tools->execute('edit_file', [
            'path' => 'test.txt',
            'search' => 'Apple',
            'replace' => 'Grape',
            'start_line' => 3,
            'end_line' => 3
        ]);

        $content = File::get($this->tempWorkspace . '/test.txt');
        $this->assertEquals("Apple\nBanana\nGrape\nCherry", $content);
    }

    public function test_multi_edit_file_performs_batch_replacements(): void
    {
        $localSvc = new LocalWorkspaceService();
        $localSvc->writeFile($this->tempWorkspace, 'test.txt', "Hello World\nKeep Coding");

        $tools = new AgentTools(localWorkspacePath: $this->tempWorkspace);
        
        $tools->execute('multi_edit_file', [
            'path' => 'test.txt',
            'edits' => [
                ['search' => 'Hello', 'replace' => 'Hi'],
                ['search' => 'World', 'replace' => 'Developer'],
                ['search' => 'Coding', 'replace' => 'Building', 'start_line' => 2, 'end_line' => 2]
            ]
        ]);

        $content = File::get($this->tempWorkspace . '/test.txt');
        $this->assertEquals("Hi Developer\nKeep Building", $content);
    }

    public function test_list_files_respects_depth_and_gitignore(): void
    {
        $localSvc = new LocalWorkspaceService();
        
        // Create nested structure
        $localSvc->writeFile($this->tempWorkspace, 'a.txt', 'a');
        $localSvc->writeFile($this->tempWorkspace, 'subdir/b.txt', 'b');
        $localSvc->writeFile($this->tempWorkspace, 'subdir/nested/c.txt', 'c');
        
        // Create gitignore
        $localSvc->writeFile($this->tempWorkspace, '.gitignore', "subdir/nested/");
        exec('cd ' . escapeshellarg($this->tempWorkspace) . ' && git config user.email "test@example.com" && git config user.name "Test" && git add . && git commit -m "init"');

        $tools = new AgentTools(localWorkspacePath: $this->tempWorkspace);
        
        // List with depth = 1 (relative depth check: subdir/ is depth 0, subdir/nested/ is depth 1)
        $resultDepth1 = $tools->execute('list_files', ['max_depth' => 1]);
        $this->assertStringContainsString('a.txt', $resultDepth1);
        $this->assertStringContainsString('subdir/b.txt', $resultDepth1);
        $this->assertStringNotContainsString('subdir/nested/c.txt', $resultDepth1);
    }

    public function test_permission_guard_caching_and_approval_checks(): void
    {
        putenv('DANGEROUSLY_SKIP_PERMISSIONS'); // Clear override
        
        $guard = new PermissionGuard();
        
        // Initially not approved
        $this->assertFalse($guard->isApproved('bash', ['command' => 'npm run dev']));

        // Approve it
        $guard->approvePattern('bash', 'npm run dev');
        
        // Should be approved now
        $this->assertTrue($guard->isApproved('bash', ['command' => 'npm run dev']));
        
        // Prefix matching for bash: "npm run dev --some-arg" should also be approved
        $this->assertTrue($guard->isApproved('bash', ['command' => 'npm run dev --some-arg']));
    }
}
