<?php

namespace App\Models;

use JsonSerializable;
use InvalidArgumentException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use App\Enums\ToolStatus;
use App\Enums\ToolCategory;
use DateTimeImmutable;
use DateTimeZone;

class ToolExecution implements JsonSerializable
{
    public readonly string $id;
    public readonly string $sessionId;
    public readonly string $workflowId;
    public readonly string $agentId;
    public readonly string $toolName;
    public readonly ToolCategory $toolCategory;
    public readonly ToolStatus $status;
    public readonly DateTimeImmutable $startTime;
    public readonly ?DateTimeImmutable $endTime;
    public readonly ?int $durationMs;
    public readonly array $metadata;
    public readonly ?int $progressPercent;

    public function __construct(
        string $id,
        string $sessionId,
        string $workflowId,
        string $agentId,
        string $toolName,
        ToolCategory|string $toolCategory,
        ToolStatus|string $status,
        DateTimeImmutable|string $startTime,
        DateTimeImmutable|string|null $endTime = null,
        ?int $durationMs = null,
        array $metadata = [],
        ?int $progressPercent = null
    ) {
        $this->id = $id;
        $this->sessionId = $sessionId;
        $this->workflowId = $workflowId;
        $this->agentId = $agentId;
        $this->toolName = $toolName;

        if (is_string($toolCategory)) {
            $this->toolCategory = ToolCategory::from($toolCategory);
        } else {
            $this->toolCategory = $toolCategory;
        }

        if (is_string($status)) {
            $this->status = ToolStatus::from($status);
        } else {
            $this->status = $status;
        }

        if (is_string($startTime)) {
            $startTime = new DateTimeImmutable($startTime);
        }
        $this->startTime = $startTime->setTimezone(new DateTimeZone('UTC'));

        if (is_string($endTime)) {
            $endTime = new DateTimeImmutable($endTime);
        }
        $this->endTime = $endTime?->setTimezone(new DateTimeZone('UTC'));

        $this->durationMs = $durationMs;
        $this->metadata = $metadata;
        $this->progressPercent = $progressPercent;

        $this->validate();
    }

    public function validate(): void
    {
        $validator = Validator::make($this->toArray(), self::schemaRules());

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }
    }

    public static function schemaRules(): array
    {
        return [
            'id' => ['required', 'string'],
            'sessionId' => ['required', 'string', 'uuid'],
            'workflowId' => ['required', 'string', 'uuid'],
            'agentId' => ['required', 'string', 'uuid'],
            'toolName' => ['required', 'string'],
            'toolCategory' => ['required', new Enum(ToolCategory::class)],
            'status' => ['required', new Enum(ToolStatus::class)],
            'startTime' => ['required', 'date'],
            'endTime' => ['nullable', 'date'],
            'durationMs' => ['nullable', 'integer', 'min:0'],
            'metadata' => ['array'],
            'progressPercent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sessionId' => $this->sessionId,
            'workflowId' => $this->workflowId,
            'agentId' => $this->agentId,
            'toolName' => $this->toolName,
            'toolCategory' => $this->toolCategory->value,
            'status' => $this->status->value,
            'startTime' => $this->startTime->format('Y-m-d\TH:i:s.u\Z'),
            'endTime' => $this->endTime?->format('Y-m-d\TH:i:s.u\Z'),
            'durationMs' => $this->durationMs,
            'metadata' => $this->metadata,
            'progressPercent' => $this->progressPercent,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
