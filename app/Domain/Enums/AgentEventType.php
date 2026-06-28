<?php

namespace App\Domain\Enums;

enum AgentEventType: string
{
    case START = 'start';
    case COMPLETED = 'completed';
    case ERROR = 'error';
    case CANCELLED = 'cancelled';
    case TIMEOUT = 'timeout';
    
    // Kept for backward compatibility or existing tests
    case THINKING = 'thinking';
    case PLANNING = 'planning';
    case RESEARCHING = 'researching';
    case WRITING = 'writing';
    case REVIEWING = 'reviewing';
    case TOOL_START = 'tool_start';
    case TOOL_END = 'tool_end';
    case MESSAGE_CHUNK = 'message_chunk';
}
