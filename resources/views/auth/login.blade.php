<x-layouts.focus>
    <x-slot name="left">
        <div class="flex flex-col py-2 md:p-10 gap-4 justify-center h-full items-center">
            <div class="card w-full md:max-w-xl bg-base-100 shadow-xl p-4 md:p-8">

                @if($isOtpLoginEnabled)
                    <livewire:auth.login.one-time-password-login />
                @else
                    @include('auth.partials.traditional-login-form')
                @endif

            </div>
        </div>
    </x-slot>


    <x-slot name="right">
        <div class="py-4 md:px-12 md:pt-36 h-full">
            <x-heading.h1 class="text-3xl! md:text-4xl! font-semibold!">
                {{ __('Login.') }}
            </x-heading.h1>
            <p class="mt-4">
                {{ __('It\'s great to see you back again :)') }}
            </p>
        </div>
    </x-slot>

</x-layouts.focus>
