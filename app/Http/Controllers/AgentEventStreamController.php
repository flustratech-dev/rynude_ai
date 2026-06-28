<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class AgentEventStreamController extends Controller
{
    public function stream(Request $request, string $workflowId)
    {
        // Headers for SSE
        $headers = [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no',
        ];

        return response()->stream(function () use ($workflowId) {
            $channel = 'agent-events.' . $workflowId;

            // Optional: send initial connection message
            echo "event: connected\n";
            echo "data: {\"status\":\"connected\"}\n\n";
            ob_flush();
            flush();

            // Subscribe to Redis
            Redis::subscribe([$channel], function ($message) {
                // $message is already a JSON string of AgentEvent
                echo "event: message\n";
                echo "data: {$message}\n\n";
                ob_flush();
                flush();
            });
        }, 200, $headers);
    }
}
