<?php

namespace App\Enums;

enum AgentEventType: string
{
    case THINKING = 'thinking';
    case PLANNING = 'planning';
    case RESEARCHING = 'researching';
    case WRITING = 'writing';
    case REVIEWING = 'reviewing';
    case TOOL_START = 'tool_start';
    case TOOL_END = 'tool_end';
    case COMPLETED = 'completed';
}
