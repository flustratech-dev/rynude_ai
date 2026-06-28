<?php

namespace App\Domain;

use JsonSerializable;
use InvalidArgumentException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use App\Domain\Enums\AgentEventType;
use DateTimeImmutable;
use DateTimeZone;

class AgentEvent implements JsonSerializable
{
    public readonly string $id;
    public readonly DateTimeImmutable $createdAt;
    public readonly int $sequenceNumber;
    public readonly string $sessionId;
    public readonly string $agentId;
    public readonly string $workflowId;
    public readonly AgentEventType $eventType;
    public readonly ?string $stage;
    public readonly string $message;
    public readonly array $metadata;

    public function __construct(
        string $id,
        DateTimeImmutable|string $createdAt,
        string $sessionId,
        string $agentId,
        string $workflowId,
        AgentEventType|string $eventType,
        ?string $stage,
        string $message,
        array $metadata = [],
        int $sequenceNumber = 0
    ) {
        $this->id = $id;
        
        // Ensure createdAt is UTC
        if (is_string($createdAt)) {
            $createdAt = new DateTimeImmutable($createdAt);
        }
        $this->createdAt = $createdAt->setTimezone(new DateTimeZone('UTC'));
        
        $this->sequenceNumber = $sequenceNumber;
        
        $this->sessionId = $sessionId;
        $this->agentId = $agentId;
        $this->workflowId = $workflowId;
        
        if (is_string($eventType)) {
            $this->eventType = AgentEventType::from($eventType);
        } else {
            $this->eventType = $eventType;
        }

        $this->stage = $stage;

        $this->message = $message;
        $this->metadata = $metadata;

        $this->validate();
    }

    public function validate(): void
    {
        // For validation, we use primitive array values.
        $validator = Validator::make([
            'id' => $this->id,
            'createdAt' => $this->createdAt->format('Y-m-d\TH:i:s.u\Z'),
            'sequenceNumber' => $this->sequenceNumber,
            'sessionId' => $this->sessionId,
            'agentId' => $this->agentId,
            'workflowId' => $this->workflowId,
            'eventType' => $this->eventType->value,
            'stage' => $this->stage,
            'message' => $this->message,
            'metadata' => $this->metadata,
        ], self::schemaRules());

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }
    }

    public static function schemaRules(): array
    {
        return [
            'id' => ['required', 'string'],
            'createdAt' => ['required', 'date'],
            'sequenceNumber' => ['required', 'integer'],
            'sessionId' => ['required', 'string', 'uuid'],
            'agentId' => ['required', 'string', 'uuid'],
            'workflowId' => ['required', 'string', 'uuid'],
            'eventType' => ['required', new Enum(AgentEventType::class)],
            'stage' => ['nullable', 'string'],
            'message' => ['required', 'string'],
            'metadata' => ['array'],
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'createdAt' => $this->createdAt->format('Y-m-d\TH:i:s.u\Z'),
            'sequenceNumber' => $this->sequenceNumber,
            'sessionId' => $this->sessionId,
            'agentId' => $this->agentId,
            'workflowId' => $this->workflowId,
            'eventType' => $this->eventType->value,
            'stage' => $this->stage,
            'message' => $this->message,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
