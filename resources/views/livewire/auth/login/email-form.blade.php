<div>
    <p class="text-xs mt-2 text-end">{{__('No account?')}} <a class="text-primary-500 font-bold" href="{{ route('register') }}">{{__('Register')}}</a></p>

    <form wire:submit="submitEmail" class="mt-4 space-y-6">
        <div>
            <x-input.field label="{{ __('Email Address') }}" type="email" id="email"
                   wire:model="email" required
                   value="{{ old('email') }}" required autofocus="true"
                   autocomplete="email" max-width="w-full"/>

            @error('email')
                <span class="text-xs text-red-500" role="alert">
                    {{ $message }}
                </span>
            @enderror

            <p class="mt-2 text-sm text-gray-600">
                {{ __('Enter your email address and we will send you a one-time login code.') }}
            </p>

            @include('livewire.auth.partials.recaptcha')

        </div>

        <div>
            <x-button-link.primary class="inline-block w-full! my-2" elementType="button" type="submit">
                {{ __('Send One Time Password') }}
            </x-button-link.primary>
        </div>
    </form>
</div>
