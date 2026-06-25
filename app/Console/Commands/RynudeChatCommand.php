<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AI\AiService;
use App\Services\AI\AgentRunner;
use App\Services\AI\AgentTools;
use App\Services\AI\WorkspaceContext;
use App\Services\AI\CostTracker;
use App\Services\AI\PermissionGuard;
use App\Services\AI\DiffRenderer;
use App\Services\LocalWorkspaceService;
use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

use function Laravel\Prompts\text;
use function Laravel\Prompts\info;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\error;

class RynudeChatCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rynude:chat {--workspace= : The local workspace path to load} {--model=claude-sonnet-4-6 : The AI model to use}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start an interactive AI chat session (Claude Code style) in the terminal';

    /**
     * Resolve home directory path cross-platform.
     */
    protected function getHomeDir(): string
    {
        return getenv('HOME') ?: getenv('USERPROFILE') ?: sys_get_temp_dir();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $workspace = rtrim($this->option('workspace') ?: getcwd(), '\\/');
        $model = $this->option('model');

        // Reset session cost
        CostTracker::reset();

        // Authenticate as the first user
        $user = User::first();
        if (!$user) {
            error('No user found in the database. Please run the web UI setup first.');
            return 1;
        }
        Auth::login($user);

        // System prompt setup
        $personaPath = base_path('plan_rynudecode.md');
        $persona = is_file($personaPath) ? trim(file_get_contents($personaPath)) : '';
        if ($persona === '') {
            $persona = "You are Rynude Code, an expert autonomous AI coding agent.\n"
                . "You are NOT a general-purpose chatbot. You are a specialized coding agent.\n"
                . "Your purpose: help developers read, write, debug, refactor, and understand code.\n"
                . "CORE BEHAVIOR:\n"
                . "- Format ALL code in fenced code blocks with the language specified: ```php, ```js, ```bash, etc.\n"
                . "- Always respond as an expert engineer, not as a helpful assistant.";
        }

        $systemPrompt = $persona . "\n\n"
            . "CURRENT MODE: CLI Terminal Mode.\n"
            . "You are running directly inside the user's terminal. "
            . "You have the ability to read and edit the user's files directly in their local workspace using tools.\n"
            . "Always execute 'write_file' or 'edit_file' tools to make code modifications directly. "
            . "Do not just output code blocks if the user asks you to implement a feature; write the files!\n\n"
            . "LOCAL WORKSPACE: " . $workspace;

        // Auto-inject workspace tech stack and custom rules (RYNUDE.md, etc.)
        $systemPrompt .= WorkspaceContext::getContext($workspace);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        // Create a new conversation record
        $conversation = Conversation::create([
            'user_id' => $user->id,
            'title' => 'CLI Session (' . basename($workspace) . ')',
            'metadata' => [
                'localWorkspace' => $workspace,
                'cli' => true
            ]
        ]);

        $shortWorkspace = strlen($workspace) > 38 ? '...' . substr($workspace, -35) : $workspace;
        $padLeft = str_pad($shortWorkspace, 38, " ", STR_PAD_BOTH);

        // Welcome Box Information
        $hasRynudeMd = file_exists($workspace . '/RYNUDE.md') ? 'Found' : 'Not Found';
        $fileCount = count((new LocalWorkspaceService())->fetchTree($workspace));
        $apiKeyCheck = !empty($user->anthropic_api_key) ? 'Configured' : 'Not Configured (fallback to env)';

        $box = <<<EOT
<fg=red>┌─</> Rynude Code v1.0 <fg=red>─────────────────────────────────────────────────────────────────────┐</>
<fg=red>│</>              Welcome back!             <fg=red>│</> <fg=red>Active Session Information</>                 <fg=red>│</>
<fg=red>│</>                                        <fg=red>│</> Active Model:  <comment>{$model}</comment>               <fg=red>│</>
<fg=red>│</>               <fg=red>▀▄   ▄▀</>                  <fg=red>│</> Workspace Path:<comment>{$padLeft}</comment> <fg=red>│</>
<fg=red>│</>              <fg=red>▄█▀███▀█▄</>                 <fg=red>│</> Total Files:   <comment>{$fileCount}</comment>                      <fg=red>│</>
<fg=red>│</>             <fg=red>█▀███████▀█</>                <fg=red>│</> RYNUDE.md:     <comment>{$hasRynudeMd}</comment>                  <fg=red>│</>
<fg=red>│</>             <fg=red>█ █▀▀▀▀▀█ █</>                <fg=red>│</> API Key:       <comment>{$apiKeyCheck}</comment>            <fg=red>│</>
<fg=red>│</>                <fg=red>▀▀   ▀▀</>                  <fg=red>│</> <fg=gray>───────────────────────────────────────────</>  <fg=red>│</>
<fg=red>│</>                                        <fg=red>│</> <fg=red>Useful Slash Commands</>                       <fg=red>│</>
<fg=red>│</> rynudecode user • Local Workspace      <fg=red>│</> /doctor, /diff, /status, /undo, /compact   <fg=red>│</>
<fg=red>└───────────────────────────────────────────────────────────────────────────────────────┘</>
EOT;

        $this->output->writeln('');
        $this->output->writeln($box);
        $this->output->writeln('');

        $aiService = new AiService();
        $agentRunner = new AgentRunner($aiService);
        $historyFile = $this->getHomeDir() . '/.rynude/history.txt';

        while (true) {
            // Multi-line Input support using backslash '\'
            $userInput = '';
            while (true) {
                $line = text(
                    label: $userInput === '' ? '>' : '...',
                    placeholder: $userInput === '' ? 'Try "edit <filepath> to..."' : 'Continue writing...',
                    hint: $userInput === '' ? '? for shortcuts • <- for agents' : '',
                    required: $userInput === ''
                );
                
                if (str_ends_with($line, '\\')) {
                    $userInput .= substr($line, 0, -1) . "\n";
                } else {
                    $userInput .= $line;
                    break;
                }
            }

            $command = strtolower(trim($userInput));
            
            // Save prompt to history file
            if (!empty(trim($userInput))) {
                $hDir = dirname($historyFile);
                if (!is_dir($hDir)) {
                    @mkdir($hDir, 0755, true);
                }
                @file_put_contents($historyFile, trim($userInput) . "\n", FILE_APPEND);
            }

            if ($command === '/exit' || $command === '/quit') {
                break;
            } elseif ($command === '/clear') {
                \Laravel\Prompts\clear();
                continue;
            } elseif ($command === '/help') {
                $this->output->writeln('');
                $this->output->writeln('<info>Available Slash Commands:</info>');
                $this->output->writeln('  <comment>/help</comment>        Show this help message');
                $this->output->writeln('  <comment>/clear</comment>       Clear the terminal screen');
                $this->output->writeln('  <comment>/model</comment>       Change the AI model');
                $this->output->writeln('  <comment>/cost</comment>        Show session tokens and estimated cost in USD');
                $this->output->writeln('  <comment>/compact</comment>     Summarize and compress conversation context');
                $this->output->writeln('  <comment>/init</comment>        Create standard RYNUDE.md instructions file');
                $this->output->writeln('  <comment>/status</comment>      Show git status for workspace');
                $this->output->writeln('  <comment>/diff</comment>        Show workspace git diff changes');
                $this->output->writeln('  <comment>/doctor</comment>      Run workspace and setup diagnostic report');
                $this->output->writeln('  <comment>/permissions</comment> View and clear authorized tool permissions');
                $this->output->writeln('  <comment>/undo</comment>        Restore last modified file state from backup');
                $this->output->writeln('  <comment>/exit</comment>        Exit the CLI');
                $this->output->writeln('');
                continue;
            } elseif ($command === '/cost') {
                $summary = CostTracker::getSessionSummary();
                $this->output->writeln('');
                $this->output->writeln('<info>💰 Session Cost & Usage Summary:</info>');
                $this->output->writeln("  Estimated Cost: <comment>\${$summary['cost']} USD</comment>");
                $this->output->writeln("  Input Tokens:   <comment>{$summary['input_tokens']}</comment>");
                $this->output->writeln("  Output Tokens:  <comment>{$summary['output_tokens']}</comment>");
                $this->output->writeln("  Total Tokens:   <comment>{$summary['total_tokens']}</comment>");
                $this->output->writeln('');
                continue;
            } elseif ($command === '/compact') {
                $this->output->writeln("\n<comment>⠋ Compacting conversation using AI summarization...</comment>");
                
                try {
                    $summaryPrompt = "Summarize the key decisions, codebase facts, and progress from the conversation so far in under 300 words. Be extremely precise.";
                    $summaryMessages = $messages;
                    $summaryMessages[] = ['role' => 'user', 'content' => $summaryPrompt];
                    
                    $summaryText = "";
                    foreach ($aiService->streamResponse($summaryMessages, $model) as $chunk) {
                        $summaryText .= $chunk;
                    }
                    
                    $messages = [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'assistant', 'content' => "Summary of the conversation so far:\n" . trim($summaryText)]
                    ];
                    
                    $this->output->writeln("\r\033[K<info>✔ Conversation compacted successfully!</info>\n");
                    $this->output->writeln("<fg=gray>" . trim($summaryText) . "</>\n");
                } catch (\Exception $e) {
                    error("\nFailed to compact conversation: " . $e->getMessage());
                }
                continue;
            } elseif ($command === '/init') {
                $rynudeMdPath = $workspace . '/RYNUDE.md';
                if (file_exists($rynudeMdPath)) {
                    warning("RYNUDE.md already exists in workspace.");
                } else {
                    $defaultContent = "# Rynude Workspace Instructions\n\n" .
                                      "Use this file to guide the AI with project conventions, tech stack details, and execution behaviors.\n\n" .
                                      "## Coding Conventions\n" .
                                      "- Write clean, commented code.\n" .
                                      "- Ensure all tests pass before completing tasks.\n";
                    file_put_contents($rynudeMdPath, $defaultContent);
                    info("✔ Created RYNUDE.md at root of workspace!");
                }
                continue;
            } elseif ($command === '/status') {
                $process = \Symfony\Component\Process\Process::fromShellCommandline('git status');
                $process->setWorkingDirectory($workspace);
                $process->run();
                $this->output->writeln("\n" . trim($process->getOutput()) . "\n");
                continue;
            } elseif ($command === '/diff') {
                $process = \Symfony\Component\Process\Process::fromShellCommandline('git diff');
                $process->setWorkingDirectory($workspace);
                $process->run();
                $rawDiff = $process->getOutput();
                if (empty(trim($rawDiff))) {
                    info("No changes in git tree.");
                } else {
                    $lines = explode("\n", $rawDiff);
                    $this->output->writeln('');
                    foreach ($lines as $line) {
                        if (str_starts_with($line, '+') && !str_starts_with($line, '+++')) {
                            $this->output->writeln("<fg=green>{$line}</fg=green>");
                        } elseif (str_starts_with($line, '-') && !str_starts_with($line, '---')) {
                            $this->output->writeln("<fg=red>{$line}</fg=red>");
                        } else {
                            $this->output->writeln($line);
                        }
                    }
                    $this->output->writeln('');
                }
                continue;
            } elseif ($command === '/doctor') {
                $this->output->writeln("\n<info>🏥 RynudeCode Doctor - Diagnostic Report:</info>");
                $this->output->writeln("  ✅ PHP Version: " . PHP_VERSION);
                $apiKeyStatus = !empty($user->anthropic_api_key) ? '✅ Anthropic API Key: Configured (User Settings)' : (config('services.anthropic.key') ? '✅ Anthropic API Key: Configured (Env Fallback)' : '❌ Anthropic API Key: Not Configured');
                $this->output->writeln("  " . $apiKeyStatus);
                $gitCheck = \Symfony\Component\Process\Process::fromShellCommandline('git --version');
                $gitCheck->run();
                if ($gitCheck->isSuccessful()) {
                    $this->output->writeln("  ✅ Git: Available (" . trim($gitCheck->getOutput()) . ")");
                } else {
                    $this->output->writeln("  ❌ Git: Not Installed or not in PATH");
                }
                $rgCheck = \Symfony\Component\Process\Process::fromShellCommandline('rg --version');
                $rgCheck->run();
                if ($rgCheck->isSuccessful()) {
                    $this->output->writeln("  ✅ ripgrep: Available");
                } else {
                    $this->output->writeln("  ⚠️  ripgrep: Not Installed (falling back to standard grep)");
                }
                $testFile = $workspace . '/.rynude_doctor_test';
                @file_put_contents($testFile, 'test');
                if (file_exists($testFile)) {
                    $this->output->writeln("  ✅ Workspace Permissions: Writable");
                    @unlink($testFile);
                } else {
                    $this->output->writeln("  ❌ Workspace Permissions: Read-only (Permission Denied)");
                }
                $configPath = $this->getHomeDir() . '/.rynude/config.json';
                if (file_exists($configPath)) {
                    $configContent = @file_get_contents($configPath);
                    $configJson = json_decode($configContent, true);
                    if (is_array($configJson)) {
                        $this->output->writeln("  ✅ Config File: Valid (~/.rynude/config.json)");
                    } else {
                        $this->output->writeln("  ⚠️  Config File: Invalid JSON format (~/.rynude/config.json)");
                    }
                } else {
                    $this->output->writeln("  ✅ Config File: Not initialized yet (will use defaults)");
                }
                $backupDir = $workspace . '/.rynude/backups';
                if (!is_dir($backupDir)) {
                    @mkdir($backupDir, 0755, true);
                }
                if (is_dir($backupDir) && is_writable($backupDir)) {
                    $this->output->writeln("  ✅ Backup Directory: Writable (.rynude/backups/)");
                } else {
                    $this->output->writeln("  ❌ Backup Directory: Not writable or cannot create (.rynude/backups/)");
                }
                $this->output->writeln("  ✅ User Token Balance: " . number_format($user->token_balance) . " tokens remaining");
                $this->output->writeln('');
                continue;
            } elseif ($command === '/permissions') {
                $this->output->writeln("\n<info>🛡️  Tool Permissions Cache:</info>");
                $patterns = PermissionGuard::getApprovedPatterns();
                if (empty($patterns)) {
                    $this->output->writeln("  No permissions cached in this session.");
                } else {
                    foreach ($patterns as $tool => $targets) {
                        $this->output->writeln("  <comment>{$tool}:</comment>");
                        foreach ($targets as $t) {
                            $this->output->writeln("    - {$t}");
                        }
                    }
                    $reset = \Laravel\Prompts\confirm("Do you want to reset all cached permissions?", false);
                    if ($reset) {
                        PermissionGuard::resetApprovedPatterns();
                        info("✔ Permissions cache cleared!");
                    }
                }
                $this->output->writeln('');
                continue;
            } elseif ($command === '/undo') {
                $localSvc = new LocalWorkspaceService();
                $restored = $localSvc->restoreLastBackup($workspace);
                if ($restored) {
                    info("✔ Successfully restored '{$restored}' to its state before the last edit!");
                } else {
                    warning("No file backups found to restore.");
                }
                continue;
            } elseif ($command === '/model') {
                $options = [
                    'claude-sonnet-4-6' => 'Claude 3.5 Sonnet (Recommended)',
                    'claude-haiku-4-6' => 'Claude 3.5 Haiku',
                ];

                $dbModels = \App\Models\AiModel::where('is_active', true)->get();
                foreach ($dbModels as $m) {
                    if (!isset($options[$m->code])) {
                        $options[$m->code] = $m->name;
                    }
                }

                $model = \Laravel\Prompts\select(
                    label: 'Select AI Model:',
                    options: $options,
                    default: array_key_exists($model, $options) ? $model : null
                );
                $this->output->writeln("<info>Model changed to {$model}</info>\n");
                continue;
            }

            // Save user message
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $userInput,
            ]);

            $messages[] = ['role' => 'user', 'content' => $userInput];
            
            $tools = new AgentTools(
                repoConnected: '',
                repoTree: [],
                localFiles: [],
                uploadedContents: [],
                githubToken: $user->github_token ?? null,
                localWorkspacePath: $workspace
            );

            $this->output->writeln('');
            
            $startTime = microtime(true);
            $generator = $agentRunner->run($messages, $model, $tools);
            $fullResponse = '';

            try {
                $output = $this->output;
                
                foreach ($generator as $event) {
                    $type = $event['type'] ?? '';
                    
                    if ($type === 'text') {
                        $text = $event['text'] ?? '';
                        $fullResponse .= $text;
                        $output->write($text);
                    } elseif ($type === 'thinking') {
                        $text = $event['text'] ?? '';
                        $output->write("<fg=gray>{$text}</>");
                    } elseif ($type === 'tool') {
                        $status = $event['status'] ?? '';
                        $name = $event['name'] ?? '';
                        
                        if ($status === 'running') {
                            $inputParams = $event['input'] ?? [];
                            $paramStr = '';
                            if (!empty($inputParams['path'])) {
                                $paramStr = ' ' . $inputParams['path'];
                            } elseif (!empty($inputParams['query'])) {
                                $paramStr = ' "' . $inputParams['query'] . '"';
                            }
                            
                            $output->write("\r<comment>⠋ {$name}{$paramStr}...</comment>");
                        } elseif ($status === 'done') {
                            $summary = $event['summary'] ?? '';
                            $output->write("\r\033[K"); // Clear the line
                            $output->writeln("<info>✔ {$name} <fg=gray>({$summary})</></info>");
                        }
                    }
                }
            } catch (\Exception $e) {
                error("\nError: " . $e->getMessage());
            }

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);
            $estimatedTokensOut = round(strlen($fullResponse) / 4);

            $this->output->writeln("\n");
            $this->output->writeln("<fg=gray>✨ Selesai dalam {$duration}s • ~{$estimatedTokensOut} tokens dihasilkan</>");
            $this->output->writeln("");

            if (!empty(trim($fullResponse))) {
                // Save to database
                Message::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => $fullResponse,
                ]);

                // Avoid duplicating the assistant message in memory
                $lastMsg = end($messages);
                if ($lastMsg === false || $lastMsg['role'] !== 'assistant') {
                    $messages[] = ['role' => 'assistant', 'content' => $fullResponse];
                }
            }
        }

        return 0;
    }
}
