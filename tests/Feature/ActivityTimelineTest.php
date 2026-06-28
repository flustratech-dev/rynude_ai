<?php

namespace Tests\Feature;

use App\Livewire\ActivityTimeline;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityTimelineTest extends TestCase
{
    public function test_it_mounts_with_session_id()
    {
        $mock = \Mockery::mock(\App\Contracts\EventHistoryServiceInterface::class);
        $mock->shouldReceive('getEventsForSession')->with('test-session-123')->andReturn([]);
        $this->app->instance(\App\Contracts\EventHistoryServiceInterface::class, $mock);

        Livewire::test(ActivityTimeline::class, ['sessionId' => 'test-session-123'])
            ->assertSet('sessionId', 'test-session-123')
            ->assertSet('initialEvents', [])
            ->assertSeeHtml('x-data="{')
            ->assertSeeHtml('@agent-stream-event.window="handleEvent"');
    }
}
