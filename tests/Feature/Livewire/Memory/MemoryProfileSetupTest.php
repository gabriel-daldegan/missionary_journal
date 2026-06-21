<?php

namespace Tests\Feature\Livewire\Memory;

use App\Livewire\Memory\MemoryProfileSetup;
use App\Models\MemoryProfile;
use Livewire\Livewire;
use Tests\Feature\FeatureTest;

class MemoryProfileSetupTest extends FeatureTest
{
    public function test_profile_setup_defaults_to_english_when_profile_is_missing(): void
    {
        app()->setLocale('pt');

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $this->actingAs($user);

        Livewire::test(MemoryProfileSetup::class, [
            'tenant' => $tenant,
        ])
            ->assertSet('preferredLocale', 'en')
            ->assertSee('Set up your memory profile')
            ->assertSee('English')
            ->assertDontSee('Configure seu perfil de memórias');
    }

    public function test_profile_setup_requires_display_name_and_supported_locale(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $this->actingAs($user);

        Livewire::test(MemoryProfileSetup::class, [
            'tenant' => $tenant,
        ])
            ->set('displayName', '')
            ->set('preferredLocale', 'fr')
            ->call('save')
            ->assertHasErrors([
                'displayName' => 'required',
                'preferredLocale' => 'in',
            ]);
    }

    public function test_successful_profile_completion_persists_values_and_redirects_to_timeline(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $this->actingAs($user);

        Livewire::test(MemoryProfileSetup::class, [
            'tenant' => $tenant,
        ])
            ->set('displayName', 'Maria Santos')
            ->set('preferredLocale', 'pt')
            ->set('missionContext', 'Portugal mission')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('memories.timeline', [
                'tenant' => $tenant,
            ]));

        $this->assertDatabaseHas('memory_profiles', [
            'user_id' => $user->id,
            'display_name' => 'Maria Santos',
            'preferred_locale' => 'pt',
            'mission_context' => 'Portugal mission',
        ]);

        $this->assertNotNull($user->fresh()->memoryProfile->completed_at);
    }

    public function test_mission_context_is_optional_for_completion(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $this->actingAs($user);

        Livewire::test(MemoryProfileSetup::class, [
            'tenant' => $tenant,
        ])
            ->set('displayName', 'Maria Santos')
            ->set('preferredLocale', 'es')
            ->set('missionContext', '')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('memories.timeline', [
                'tenant' => $tenant,
            ]));

        $this->assertDatabaseHas('memory_profiles', [
            'user_id' => $user->id,
            'display_name' => 'Maria Santos',
            'preferred_locale' => 'es',
            'mission_context' => null,
        ]);
    }

    public function test_existing_profile_values_are_prefilled(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create([
            'display_name' => 'Maria Santos',
            'preferred_locale' => 'pt',
            'mission_context' => 'Portugal mission',
        ]);
        $this->actingAs($user);

        Livewire::test(MemoryProfileSetup::class, [
            'tenant' => $tenant,
        ])
            ->assertSet('displayName', 'Maria Santos')
            ->assertSet('preferredLocale', 'pt')
            ->assertSet('missionContext', 'Portugal mission');
    }

    public function test_existing_profile_locale_can_be_changed_and_updates_next_memory_page(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create([
            'display_name' => 'Maria Santos',
            'preferred_locale' => 'pt',
            'mission_context' => 'Portugal mission',
        ]);
        $this->actingAs($user);

        $this->get(route('memories.profile.setup', [
            'tenant' => $tenant,
        ]))
            ->assertOk()
            ->assertSee('Configure seu perfil de memórias')
            ->assertDontSee('Set up your memory profile');

        Livewire::test(MemoryProfileSetup::class, [
            'tenant' => $tenant,
        ])
            ->assertSet('preferredLocale', 'pt')
            ->set('preferredLocale', 'es')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('memories.timeline', [
                'tenant' => $tenant,
            ]));

        $this->assertDatabaseHas('memory_profiles', [
            'user_id' => $user->id,
            'display_name' => 'Maria Santos',
            'preferred_locale' => 'es',
            'mission_context' => 'Portugal mission',
        ]);

        $this->get(route('memories.timeline', [
            'tenant' => $tenant,
        ]))
            ->assertOk()
            ->assertSee('Línea de tiempo de memorias')
            ->assertDontSee('Linha do tempo de memórias');
    }
}
