<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AI\AiService;
use App\Services\AI\AgentRunner;
use App\Services\AI\AgentTools;
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

        info('');
        info('🚀 RynudeCode v1.0 CLI');
        info('📂 Workspace: ' . $workspace);
        info('🤖 Model: ' . $model);
        info('Ketik pesan Anda. Ketik "/exit" atau tekan Ctrl+C untuk keluar.');
        info('');

        $aiService = new AiService();
        $agentRunner = new AgentRunner($aiService);

        while (true) {
            $userInput = text(
                label: '❯',
                placeholder: 'Apa yang bisa saya bantu?',
                required: true
            );

            if (strtolower(trim($userInput)) === '/exit' || strtolower(trim($userInput)) === 'exit') {
                info('Sampai jumpa! 👋');
                break;
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
            
            $generator = $agentRunner->run($messages, $model, $tools);
            $fullResponse = '';

            try {
                // We will stream the text directly using symfony console output
                $output = $this->output;
                
                foreach ($generator as $event) {
                    $type = $event['type'] ?? '';
                    
                    if ($type === 'text') {
                        $text = $event['text'] ?? '';
                        $fullResponse .= $text;
                        $output->write($text);
                    } elseif ($type === 'tool') {
                        $status = $event['status'] ?? '';
                        $name = $event['name'] ?? '';
                        
                        if ($status === 'running') {
                            $output->writeln('');
                            $output->writeln("<comment>⠋ ⚙️ Executing: {$name}...</comment>");
                        } elseif ($status === 'done') {
                            $summary = $event['summary'] ?? '';
                            $output->writeln("<info>✔ ⚙️ Done: {$name} ({$summary})</info>");
                            $output->writeln('');
                        }
                    }
                }
            } catch (\Exception $e) {
                error("\nError: " . $e->getMessage());
            }

            $this->output->writeln("\n");

            if (!empty(trim($fullResponse))) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => $fullResponse,
                ]);
                $messages[] = ['role' => 'assistant', 'content' => $fullResponse];
                
                // Add tool results context back into messages sequence if needed
                // AgentRunner actually handles modifying the $messages array by reference in PHP? No, it yields.
                // Wait, AgentRunner in `run()` modifies `$messages`?
                // `run(array $messages, ...)` is passed by value in PHP. 
                // So the loop in AgentRunner doesn't update our `$messages` array for the next turn.
                // We need to keep our `$messages` updated.
                // Since AgentRunner yields text and tools, we should reconstruct the assistant message 
                // and tool calls if needed, OR we just append the final text.
                // For a continuous conversation, we just append the text.
                // But wait, the context of tool calls might be lost for the NEXT turn.
                // For simplicity, we just keep the final text for now.
            }
        }

        return 0;
    }
}
