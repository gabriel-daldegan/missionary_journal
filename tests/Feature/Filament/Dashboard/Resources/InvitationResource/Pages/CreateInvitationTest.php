<?php

namespace Tests\Feature\Filament\Dashboard\Resources\InvitationResource\Pages;

use App\Constants\InvitationStatus;
use App\Constants\SubscriptionStatus;
use App\Constants\TenancyPermissionConstants;
use App\Events\Tenant\UserInvitedToTenant;
use App\Filament\Dashboard\Resources\Invitations\Pages\CreateInvitation;
use App\Models\Invitation;
use App\Models\Plan;
use App\Models\Subscription;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\Feature\FeatureTest;

class CreateInvitationTest extends FeatureTest
{
    public function test_create()
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant, [TenancyPermissionConstants::PERMISSION_INVITE_MEMBERS]);
        $this->actingAs($user);

        Filament::setCurrentPanel(
            Filament::getPanel('dashboard'),
        );

        Filament::setTenant($tenant);

        Event::fake();

        Livewire::test(CreateInvitation::class)
            ->fillForm([
                'email' => 'email@email.com',
                'role' => TenancyPermissionConstants::ROLE_ADMIN,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Invitation::class, [
            'email' => 'email@email.com',
            'role' => TenancyPermissionConstants::ROLE_ADMIN,
        ]);

        Event::assertDispatched(UserInvitedToTenant::class);
    }

    public function test_create_can_only_invite_user_that_is_not_already_in_the_tenant()
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant, [TenancyPermissionConstants::PERMISSION_INVITE_MEMBERS]);
        $this->actingAs($user);

        Filament::setCurrentPanel(
            Filament::getPanel('dashboard'),
        );

        Filament::setTenant($tenant);

        Event::fake();

        Livewire::test(CreateInvitation::class)
            ->fillForm([
                'email' => $user->email,
                'role' => TenancyPermissionConstants::ROLE_ADMIN,
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => __('The user with email :email is already in the team.', ['email' => $user->email])]);

        Event::assertNotDispatched(UserInvitedToTenant::class);
    }

    public function test_create_can_only_invite_user_that_is_not_already_invited()
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant, [TenancyPermissionConstants::PERMISSION_INVITE_MEMBERS]);
        $this->actingAs($user);

        $fakeEmail = fake()->email;
        $invitation = Invitation::factory()->create([
            'user_id' => $user->id,
            'email' => $fakeEmail,
            'tenant_id' => $tenant->id,
            'status' => InvitationStatus::PENDING->value,
        ]);

        Filament::setCurrentPanel(
            Filament::getPanel('dashboard'),
        );

        Filament::setTenant($tenant);

        Event::fake();

        Livewire::test(CreateInvitation::class)
            ->fillForm([
                'email' => $fakeEmail,
                'role' => TenancyPermissionConstants::ROLE_ADMIN,
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => __('The email :email has already been invited.', ['email' => $fakeEmail])]);

        Event::assertNotDispatched(UserInvitedToTenant::class);
    }

    public function test_create_bulk()
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant, [TenancyPermissionConstants::PERMISSION_INVITE_MEMBERS]);
        $this->actingAs($user);

        Filament::setCurrentPanel(
            Filament::getPanel('dashboard'),
        );

        Filament::setTenant($tenant);

        Event::fake();

        Livewire::test(CreateInvitation::class)
            ->fillForm([
                'email' => "email1@email.com, email2@email.com\nemail3@email.com",
                'role' => TenancyPermissionConstants::ROLE_ADMIN,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Invitation::class, [
            'email' => 'email1@email.com',
            'role' => TenancyPermissionConstants::ROLE_ADMIN,
        ]);
        $this->assertDatabaseHas(Invitation::class, [
            'email' => 'email2@email.com',
            'role' => TenancyPermissionConstants::ROLE_ADMIN,
        ]);
        $this->assertDatabaseHas(Invitation::class, [
            'email' => 'email3@email.com',
            'role' => TenancyPermissionConstants::ROLE_ADMIN,
        ]);

        Event::assertDispatched(UserInvitedToTenant::class, 3);
    }

    public function test_create_bulk_fails_if_subscription_limit_is_reached()
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant, [TenancyPermissionConstants::PERMISSION_INVITE_MEMBERS]);
        $this->actingAs($user);

        // create a plan with a limit of 2 users
        $plan = Plan::factory()->create([
            'max_users_per_tenant' => 2,
        ]);

        // create a subscription for the tenant
        Subscription::factory()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
        ]);

        Filament::setCurrentPanel(
            Filament::getPanel('dashboard'),
        );

        Filament::setTenant($tenant);

        config()->set('app.allow_tenant_invitations', true);

        Event::fake();

        // 1 existing user + 2 invites = 3 users (limit is 2)
        Livewire::test(CreateInvitation::class)
            ->fillForm([
                'email' => 'email1@email.com, email2@email.com',
                'role' => TenancyPermissionConstants::ROLE_ADMIN,
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => __('You have reached the maximum number of users allowed for your subscription.')]);

        Event::assertNotDispatched(UserInvitedToTenant::class);
    }
}
