@guest()
    <div class="mb-4">

        <x-heading.h2 class="text-primary-900 text-xl!">
            {{ __('Enter your details') }}
        </x-heading.h2>

        <div class="relative rounded-2xl border border-neutral-200 mt-4 overflow-hidden p-6">

            @if (!empty($intro))
                <div class="mb-4 text-sm">
                    {{ $intro }}
                </div>
            @endif

            <div class="absolute top-0 right-0 p-2">
                    <span wire:loading>
                        <span class="loading loading-spinner loading-xs"></span>
                    </span>
            </div>

            @if($otpEnabled)
                @include('livewire.checkout.partials.one-time-password')
            @else
                @include('livewire.checkout.partials.traditional-login-or-register')
            @endif

            @if(empty($email))
                <x-auth.social-login>
                    <x-slot name="before">
                        <div class="flex flex-col w-full">
                            <div class="divider">{{ __('or') }}</div>
                        </div>
                    </x-slot>
                </x-auth.social-login>
            @endif

        </div>
    </div>

@endguest
