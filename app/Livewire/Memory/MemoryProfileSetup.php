<?php

namespace App\Livewire\Memory;

use App\Models\MemoryProfile;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MemoryProfileService;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class MemoryProfileSetup extends Component
{
    public Tenant $tenant;

    public string $displayName = '';

    public string $preferredLocale = MemoryProfileService::DEFAULT_LOCALE;

    public ?string $missionContext = null;

    public function mount(Tenant $tenant, MemoryProfileService $memoryProfileService): void
    {
        $this->tenant = $tenant;

        $user = $this->authenticatedTenantMember();

        /** @var MemoryProfile|null $profile */
        $profile = $user->memoryProfile;

        $this->displayName = $profile?->display_name ?? (string) $user->getPublicName();
        $this->preferredLocale = $memoryProfileService->preferredLocale($user);
        $this->missionContext = $profile?->mission_context;

        app()->setLocale($this->preferredLocale);
    }

    public function save(MemoryProfileService $memoryProfileService): void
    {
        $validated = $this->validate();

        $memoryProfileService->completeProfile(
            user: $this->authenticatedTenantMember(),
            displayName: $validated['displayName'],
            preferredLocale: $validated['preferredLocale'],
            missionContext: $validated['missionContext'] ?? null,
        );

        $this->redirectRoute('memories.timeline', [
            'tenant' => $this->tenant,
        ]);
    }

    public function render(): View
    {
        return view('livewire.memory.memory-profile-setup', [
            'localeOptions' => $this->localeOptions(),
        ])->layout('components.layouts.memory', [
            'tenant' => $this->tenant,
            'title' => __('memory.profile_setup.title'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'displayName' => ['required', 'string', 'max:120'],
            'preferredLocale' => ['required', 'string', Rule::in(MemoryProfileService::SUPPORTED_LOCALES)],
            'missionContext' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'displayName' => __('memory.profile_setup.display_name'),
            'preferredLocale' => __('memory.profile_setup.preferred_locale'),
            'missionContext' => __('memory.profile_setup.mission_context'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function localeOptions(): array
    {
        return collect(MemoryProfileService::SUPPORTED_LOCALES)
            ->mapWithKeys(fn (string $locale): array => [
                $locale => __('memory.locale.'.$locale),
            ])
            ->all();
    }

    private function authenticatedTenantMember(): User
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user === null || ! $user->canAccessTenant($this->tenant)) {
            abort(404);
        }

        return $user;
    }
}
