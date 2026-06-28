<?php

namespace App\Domain\Enums;

enum ToolCategory: string
{
    case RESEARCH = 'research';
    case WRITING = 'writing';
    case REVIEW = 'review';
    case MEMORY = 'memory';
    case KNOWLEDGE_GRAPH = 'knowledge_graph';
    case EXTERNAL_MODEL = 'external_model';
    case SYSTEM = 'system';
}
