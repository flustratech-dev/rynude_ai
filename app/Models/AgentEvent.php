<?php

namespace App\Models;

use JsonSerializable;
use InvalidArgumentException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use App\Enums\AgentEventType;
use DateTimeImmutable;
use DateTimeZone;

class AgentEvent implements JsonSerializable
{
    public readonly string $id;
    public readonly DateTimeImmutable $timestamp;
    public readonly string $sessionId;
    public readonly AgentEventType $eventType;
    public readonly string $message;
    public readonly array $metadata;

    public function __construct(
        string $id,
        DateTimeImmutable|string $timestamp,
        string $sessionId,
        AgentEventType|string $eventType,
        string $message,
        array $metadata = []
    ) {
        $this->id = $id;
        
        // Ensure timestamp is UTC
        if (is_string($timestamp)) {
            $timestamp = new DateTimeImmutable($timestamp);
        }
        $this->timestamp = $timestamp->setTimezone(new DateTimeZone('UTC'));
        
        $this->sessionId = $sessionId;
        
        if (is_string($eventType)) {
            $this->eventType = AgentEventType::from($eventType);
        } else {
            $this->eventType = $eventType;
        }

        $this->message = $message;
        $this->metadata = $metadata;

        $this->validate();
    }

    public function validate(): void
    {
        // For validation, we use primitive array values.
        $validator = Validator::make([
            'id' => $this->id,
            'timestamp' => $this->timestamp->format('Y-m-d\TH:i:s.u\Z'),
            'sessionId' => $this->sessionId,
            'eventType' => $this->eventType->value,
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
            'timestamp' => ['required', 'date'],
            'sessionId' => ['required', 'string'],
            'eventType' => ['required', new Enum(AgentEventType::class)],
            'message' => ['required', 'string'],
            'metadata' => ['array'],
        ];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'timestamp' => $this->timestamp->format('Y-m-d\TH:i:s.u\Z'),
            'sessionId' => $this->sessionId,
            'eventType' => $this->eventType->value,
            'message' => $this->message,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
