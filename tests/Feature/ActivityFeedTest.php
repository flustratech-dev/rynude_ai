<?php

namespace Tests\Feature;

use App\Livewire\ActivityFeed;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityFeedTest extends TestCase
{
    public function test_it_mounts_with_session_id()
    {
        Livewire::test(ActivityFeed::class, ['sessionId' => 'test-session-123'])
            ->assertSet('sessionId', 'test-session-123')
            ->assertSet('events', [])
            ->assertSeeHtml('x-data="{')
            ->assertSeeHtml('@agent-stream-event.window="handleEvent"');
    }
}
