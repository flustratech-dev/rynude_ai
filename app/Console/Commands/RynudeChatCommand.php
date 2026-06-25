<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AI\AiService;
use App\Services\AI\AgentRunner;
use App\Services\AI\AgentTools;
use App\Services\AI\WorkspaceContext;
use App\Services\AI\CostTracker;
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
     * Execute the console command.
     */
    public function handle()
    {
        $workspace = rtrim($this->option('workspace') ?: getcwd(), '\\/');
        $model = $this->option('model');

        // Reset session cost at startup
        CostTracker::reset();

        // Authenticate as the first user (since Rynude is usually a single-user local tool)
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

        // Auto-inject tech stack & custom files context (RYNUDE.md, AGENTS.md, etc.)
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

        $shortWorkspace = substr($workspace, 0, 38);
        $padLeft = str_pad($shortWorkspace, 38, " ", STR_PAD_BOTH);

        $box = <<<EOT
<fg=red>┌─</> Rynude Code v1.0 <fg=red>─────────────────────────────────────────────────────────────────────┐</>
<fg=red>│</>              Welcome back!             <fg=red>│</> <fg=red>Tips for getting started</>                     <fg=red>│</>
<fg=red>│</>                                        <fg=red>│</> Run /init to create a RYNUDE.md file         <fg=red>│</>
<fg=red>│</>               <fg=red>▀▄   ▄▀</>                  <fg=red>│</> with instructions for Rynude                 <fg=red>│</>
<fg=red>│</>              <fg=red>▄█▀███▀█▄</>                 <fg=red>│</> <fg=gray>───────────────────────────────────────────</>  <fg=red>│</>
<fg=red>│</>             <fg=red>█▀███████▀█</>                <fg=red>│</> <fg=red>What's new</>                                   <fg=red>│</>
<fg=red>│</>             <fg=red>█ █▀▀▀▀▀█ █</>                <fg=red>│</> Added `bash` and `grep_search` tools         <fg=red>│</>
<fg=red>│</>                <fg=red>▀▀   ▀▀</>                  <fg=red>│</> Added model picker with /model               <fg=red>│</>
<fg=red>│</>                                        <fg=red>│</> Added cost tracker with /cost               <fg=red>│</>
<fg=red>│</> rynudecode user • Local Workspace      <fg=red>│</> /compact to summarize conversation          <fg=red>│</>
<fg=red>│</> {$padLeft} <fg=red>│</>                                              <fg=red>│</>
<fg=red>└───────────────────────────────────────────────────────────────────────────────────────┘</>
EOT;

        $this->output->writeln('');
        $this->output->writeln($box);
        $this->output->writeln('');

        $aiService = new AiService();
        $agentRunner = new AgentRunner($aiService);

        while (true) {
            $userInput = text(
                label: '>',
                placeholder: 'Try "edit <filepath> to..."',
                hint: '? for shortcuts • <- for agents',
                required: true
            );

            $command = strtolower(trim($userInput));
            
            if ($command === '/exit' || $command === '/quit') {
                break;
            } elseif ($command === '/clear') {
                \Laravel\Prompts\clear();
                continue;
            } elseif ($command === '/help') {
                $this->output->writeln('');
                $this->output->writeln('<info>Available Slash Commands:</info>');
                $this->output->writeln('  <comment>/help</comment>     Show this help message');
                $this->output->writeln('  <comment>/clear</comment>    Clear the terminal screen');
                $this->output->writeln('  <comment>/model</comment>    Change the AI model');
                $this->output->writeln('  <comment>/cost</comment>     Show session tokens and estimated cost in USD');
                $this->output->writeln('  <comment>/compact</comment>  Summarize and compress conversation context');
                $this->output->writeln('  <comment>/exit</comment>     Exit the CLI');
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
                    
                    // Compact messages
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

                // Append assistant turn to in-memory messages if not already done by reference in AgentRunner
                $lastMsg = end($messages);
                if ($lastMsg === false || $lastMsg['role'] !== 'assistant') {
                    $messages[] = ['role' => 'assistant', 'content' => $fullResponse];
                }
            }
        }

        return 0;
    }
}
