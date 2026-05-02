<x-filament-panels::page>
    <p>
        {{ __('Confirm your two-factor authentication code.') }}
    </p>

    <div>
        <form wire:submit.prevent="confirmTwoFactorAuth">
            <div class="">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model.defer="code"
                        placeholder="{{ __('Enter your two-factor authentication code') }}"
                    />
                </x-filament::input.wrapper>

                @error('code')
                <span class="text-xs text-red-500" role="alert">
                        {{ $message }}
                    </span>
                @enderror
            </div>

            <div class="mt-6 flex flex-row gap-4">
                <x-filament::link href="{{ \App\Filament\Dashboard\Pages\TwoFactorAuth\TwoFactorAuth::getUrl() }}" color="gray">
                    {{ __('Cancel') }}
                </x-filament::link>
                <x-filament::button type="submit" size="md">
                    {{ __('Confirm Two-Factor Authentication Code') }}
                </x-filament::button>
            </div>
        </form>
    </div>

</x-filament-panels::page>
