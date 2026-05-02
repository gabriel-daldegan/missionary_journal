<?php

namespace Tests\Feature\Http\Middleware;

use App\Events\User\UserSeen;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\Feature\FeatureTest;

class UpdateUserLastSeenAtTest extends FeatureTest
{
    public function test_event_is_dispatched_if_user_is_authenticated_and_last_seen_is_null()
    {
        Event::fake();

        $user = User::factory()->create(['last_seen_at' => null]);
        $this->actingAs($user)->get('/');

        Event::assertDispatched(UserSeen::class, fn ($event) => $event->user->id === $user->id);
    }

    public function test_event_is_dispatched_if_last_seen_is_older_than_10_minutes()
    {
        Event::fake();

        $user = User::factory()->create(['last_seen_at' => now()->subMinutes(11)]);
        $this->actingAs($user)->get('/');

        Event::assertDispatched(UserSeen::class);
    }

    public function test_event_is_not_dispatched_if_last_seen_is_recent()
    {
        Event::fake();

        $user = User::factory()->create(['last_seen_at' => now()->subMinutes(5)]);
        $this->actingAs($user)->get('/');

        Event::assertNotDispatched(UserSeen::class);
    }

    public function test_event_is_not_dispatched_for_ajax_request()
    {
        Event::fake();

        $user = User::factory()->create(['last_seen_at' => null]);
        $this->actingAs($user)->get('/', ['X-Requested-With' => 'XMLHttpRequest']);

        Event::assertNotDispatched(UserSeen::class);
    }

    public function test_event_is_not_dispatched_for_json_request()
    {
        Event::fake();

        $user = User::factory()->create(['last_seen_at' => null]);
        $this->actingAs($user)->getJson('/');

        Event::assertNotDispatched(UserSeen::class);
    }

    public function test_event_is_not_dispatched_for_livewire_request()
    {
        Event::fake();

        $user = User::factory()->create(['last_seen_at' => null]);
        $this->actingAs($user)->get('/', ['X-Livewire' => 'true']);

        Event::assertNotDispatched(UserSeen::class);
    }

    public function test_event_is_not_dispatched_if_user_is_not_authenticated()
    {
        Event::fake();

        $this->get('/');

        Event::assertNotDispatched(UserSeen::class);
    }
}
