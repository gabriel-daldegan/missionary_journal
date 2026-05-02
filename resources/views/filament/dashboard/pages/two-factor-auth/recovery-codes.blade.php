<x-filament-panels::page>

    <p>
        {{ __('Below are your recovery codes. Please store them in a safe place as you can use them to recover access to your account if you lose your two-factor authentication device.') }}
    </p>

    <div class="mt-6">
        <textarea
            class="p-3 w-full text-black"
            rows="12"
            readonly
        >{{ implode(PHP_EOL, array_column($recoveryCodes->toArray(), 'code')) }}</textarea>

        <div class="mt-6">
            <x-filament::button wire:click="storedRecoveryCodes" size="md">
                {{ __('I have stored my recovery codes in a safe place') }}
            </x-filament::button>
        </div>

    </div>

</x-filament-panels::page>
