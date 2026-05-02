<x-layouts.focus>
    <x-slot name="left">
        <div class="flex flex-col py-2 md:p-10 gap-4 justify-center h-full items-center">
            <div class="card w-full md:max-w-xl bg-base-100 shadow-xl p-4 md:p-8">

                @if($isOtpLoginEnabled)
                    <livewire:auth.register.one-time-password-registration />
                @else
                    @include('auth.partials.traditional-registration-form')
                @endif
            </div>
        </div>
    </x-slot>


    <x-slot name="right">
        <div class="py-4 md:px-12 md:pt-36 h-full">
            <x-heading.h1 class="text-3xl! md:text-4xl! font-semibold!">
                {{ __('Register.') }}
            </x-heading.h1>
            <p class="mt-4">
                {{ __('Create an account to get started.') }}
            </p>
        </div>
    </x-slot>

</x-layouts.focus>
