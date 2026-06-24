<?php

namespace App\Services\AI\Contracts;

/**
 * Implemented by LLM providers that can run a single agentic turn with tool use.
 *
 * The orchestration loop (App\Services\AI\AgentRunner) drives multiple turns:
 * it calls streamAgentTurn(), executes any requested tools, appends the results
 * to the message history, and calls again until the model stops asking for tools.
 *
 * UNIFIED MESSAGE FORMAT (superset of the plain chat format):
 *   ['role' => 'system'|'user'|'assistant', 'content' => string, 'attachments'? => array]
 *   assistant with tools: ['role' => 'assistant', 'content' => string,
 *                          'tool_calls' => [['id' => string, 'name' => string, 'input' => array], ...]]
 *   tool result:          ['role' => 'tool', 'tool_call_id' => string, 'name' => string, 'content' => string]
 *
 * UNIFIED TOOL SCHEMA (mapped to each provider's native shape):
 *   [['name' => string, 'description' => string, 'input_schema' => array (JSON schema)], ...]
 */
interface SupportsToolUse
{
    /**
     * Stream one agentic turn.
     *
     * Yields structured events:
     *   ['type' => 'text', 'text' => string]
     *   ['type' => 'tool_use', 'id' => string, 'name' => string, 'input' => array]
     *
     * The generator's return value reports how the turn ended:
     *   ['stop_reason' => 'tool_use'|'end'|'error', 'error'? => string]
     *
     * @param array  $messages Unified message history (see interface docblock).
     * @param string $model    Model identifier.
     * @param array  $tools    Unified tool schema list.
     */
    public function streamAgentTurn(array $messages, string $model, array $tools): \Generator;
}
