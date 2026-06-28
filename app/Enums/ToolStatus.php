<?php

namespace App\Enums;

enum ToolStatus: string
{
    case STARTED = 'started';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
