<?php

namespace App\Exceptions;

use Exception;

class PermissionRequiredException extends Exception
{
    protected string $toolName;
    protected array $toolInput;
    protected ?string $toolCallId;

    public function __construct(string $toolName, array $toolInput, ?string $toolCallId = null)
    {
        $this->toolName = $toolName;
        $this->toolInput = $toolInput;
        $this->toolCallId = $toolCallId;

        parent::__construct("Permission required to execute tool '{$toolName}'");
    }

    public function getToolName(): string
    {
        return $this->toolName;
    }

    public function getToolInput(): array
    {
        return $this->toolInput;
    }

    public function getToolCallId(): ?string
    {
        return $this->toolCallId;
    }
}
