<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Announcement;
use Livewire\Livewire;
use Tests\Feature\FeatureTest;

class HomeControllerTest extends FeatureTest
{
    public function test_announcement_is_displayed()
    {
        Announcement::query()->delete();

        $announcement = Announcement::factory()->create([
            'title' => 'Test Announcement',
            'content' => 'Test content 1',
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        Livewire::withoutLazyLoading();

        $response = $this->get(route('home'));

        $response->assertStatus(200);

        $response->assertSee($announcement->content);
    }

    public function test_announcement_is_not_displayed_when_inactive()
    {
        Announcement::query()->delete();

        $announcement = Announcement::factory()->create([
            'title' => 'Test Announcement',
            'content' => 'Test content 1',
            'is_active' => false,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        Livewire::withoutLazyLoading();

        $response = $this->get(route('home'));

        $response->assertStatus(200);

        $response->assertDontSee($announcement->content);
    }
}
