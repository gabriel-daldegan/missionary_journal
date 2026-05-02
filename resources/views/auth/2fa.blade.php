<x-layouts.focus>
    <x-slot name="left">
        <div class="flex flex-col py-2 md:p-10 gap-4 justify-center h-full items-center mx-4">
            <div class="card w-full md:max-w-xl bg-base-100 shadow-xl p-4 md:p-8">
                <form method="POST">
                    @csrf

                    <x-input.field label="{{ __('Two Factor Authentication Code') }}" name="{{ $input }}" id="{{ $input }}"
                                   required autofocus="true" class="my-2"
                                   minlength="6" placeholder="123456" required max-width="w-full"/>

                    @if($errors->isNotEmpty())
                        @foreach ($errors->all() as $error)
                            <span class="text-xs text-red-500" role="alert">
                                {{ $error }}
                            </span>
                        @endforeach
                    @endif

                    <p class="text-xs mt-2">
                        {{ __('Open your authenticator app and enter the code below to verify your identity.') }}
                    </p>

                    <p class="text-xs mt-2">
                        {{ __('If you lost access to your authentication device, you can use one of your recovery codes.') }}
                    </p>

                    <x-button-link.primary class="inline-block w-full! my-2" elementType="button" type="submit">
                        {{ __('Verify') }}
                    </x-button-link.primary>

                </form>
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
