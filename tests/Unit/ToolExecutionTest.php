<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\ToolExecution;
use App\Enums\ToolStatus;
use App\Enums\ToolCategory;
use DateTimeImmutable;
use InvalidArgumentException;
use Illuminate\Support\Str;

class ToolExecutionTest extends TestCase
{
    public function test_can_instantiate_tool_execution()
    {
        $id = Str::uuid()->toString();
        $sessionId = Str::uuid()->toString();
        $workflowId = Str::uuid()->toString();
        $agentId = Str::uuid()->toString();
        $startTime = new DateTimeImmutable();

        $execution = new ToolExecution(
            $id,
            $sessionId,
            $workflowId,
            $agentId,
            'MyTool',
            ToolCategory::RESEARCH,
            ToolStatus::STARTED,
            $startTime
        );

        $this->assertEquals($id, $execution->id);
        $this->assertEquals(ToolStatus::STARTED, $execution->status);
        $this->assertEquals(ToolCategory::RESEARCH, $execution->toolCategory);
        $this->assertEquals(null, $execution->progressPercent);
    }

    public function test_validates_uuids()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The session id field must be a valid UUID.');

        new ToolExecution(
            Str::uuid()->toString(),
            'not-a-uuid',
            Str::uuid()->toString(),
            Str::uuid()->toString(),
            'MyTool',
            ToolCategory::RESEARCH,
            ToolStatus::STARTED,
            new DateTimeImmutable()
        );
    }

    public function test_progress_percent_limits()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The progress percent field must not be greater than 100.');

        new ToolExecution(
            Str::uuid()->toString(),
            Str::uuid()->toString(),
            Str::uuid()->toString(),
            Str::uuid()->toString(),
            'MyTool',
            ToolCategory::RESEARCH,
            ToolStatus::RUNNING,
            new DateTimeImmutable(),
            null,
            null,
            [],
            105
        );
    }

    public function test_serializes_to_array_correctly()
    {
        $id = Str::uuid()->toString();
        $sessionId = Str::uuid()->toString();
        $workflowId = Str::uuid()->toString();
        $agentId = Str::uuid()->toString();
        $startTime = new DateTimeImmutable('2023-01-01T12:00:00Z');

        $execution = new ToolExecution(
            $id,
            $sessionId,
            $workflowId,
            $agentId,
            'MyTool',
            ToolCategory::RESEARCH,
            ToolStatus::STARTED,
            $startTime,
            null,
            null,
            ['key' => 'value'],
            50
        );

        $array = $execution->toArray();
        $this->assertEquals($id, $array['id']);
        $this->assertEquals('research', $array['toolCategory']);
        $this->assertEquals('started', $array['status']);
        $this->assertEquals('2023-01-01T12:00:00.000000Z', $array['startTime']);
        $this->assertEquals(50, $array['progressPercent']);
        $this->assertEquals(['key' => 'value'], $array['metadata']);
    }
}
