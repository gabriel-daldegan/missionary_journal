<x-filament-panels::page>
    @if($twoFactorAuthEnabled)
        <div>
            <div class="flex flex-row items-center gap-4">
                <div class="w-20">
                    <x-heroicon-c-shield-check />
                </div>
                <p>
                    {{ __('Two-factor authentication is already enabled for your account.') }}
                    <br/>
                    {{ __('You can disable two-factor authentication by clicking the button below.') }}
                    <br/>
                </p>
            </div>

            <div class="mt-6">
                <x-filament::button wire:click="disableTwoFactorAuth" size="md" color="danger">
                    {{ __('Disable Two-Factor Authentication') }}
                </x-filament::button>
            </div>
        </div>
    @else
        <div>
            <div class="flex flex-row items-center gap-4">
                <div class="w-20">
                    <x-heroicon-s-shield-exclamation />
                </div>
                <p>
                    {{ __('Two-factor authentication is an security feature that helps protect your account.') }}
                    <br/>
                    {{ __('You can enable two-factor authentication by clicking the button below.') }}
                    <br/>
                </p>

            </div>
            <div class="mt-6">
                <x-filament::button wire:click="enableTwoFactorAuth" size="md">
                    {{ __('Enable Two-Factor Authentication') }}
                </x-filament::button>
            </div>
        </div>
    @endif
</x-filament-panels::page>
