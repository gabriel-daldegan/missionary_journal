<x-filament-panels::page>
    <div class="flex gap-4 flex-col">
        <div>
            {!! $qrCode !!}
        </div>
        <div class="flex flex-col">
            <div class="">
                {{ __('To enable 2-factor authentication, scan the code above with your authenticator app.') }}
                <br/>
                <br/>
                {{ __('If you can\'t use a QR code, you can enter the code below.') }}
            </div>
            <div class="mt-4 p-3 border border-dashed border-black max-w-fit rounded font-bold">
                {{ $stringCode }}
            </div>
        </div>
    </div>

    <p>
        {{ __('In the next step, you will be asked to enter a 2-factor authentication code.') }}
    </p>

    <x-filament::button wire:click="confirmEnableTwoFactorAuth" size="md" class="max-w-fit">
        {{ __('Confirm Two-Factor Authentication Code') }}
    </x-filament::button>
</x-filament-panels::page>
