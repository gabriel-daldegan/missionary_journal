<div>
    <div x-data="{ resendText: '{{ __('Resend Code') }}', isResending: false }">
        <h2 class="text-lg font-medium">
            {{ __('Login using one-time password') }}
        </h2>
        <form wire:submit="submitOneTimePassword" class="mt-4 space-y-6">
            <div>
                <x-input.field label="{{ __('One-time Password') }}" type="text" id="one_time_password" wire:model="oneTimePassword" required max-width="w-full"/>

                @error('oneTimePassword')
                    <span class="text-xs text-red-500" role="alert">
                        {{ $message }}
                    </span>
                @enderror

                <p class="mt-2 text-sm text-gray-600">
                    {{ __('Enter the one-time password sent to your email address.') }}
                </p>
            </div>

            <div>
                <x-button-link.primary class="inline-block w-full! my-2" elementType="button" type="submit">
                    {{ __('Login') }}
                </x-button-link.primary>

            </div>

            <button
                type="button"
                @click="
                if (!isResending) {
                    isResending = true;
                    resendText = '{{ __('Code sent') }}';
                    $wire.resendCode();
                    setTimeout(() => {
                        resendText = '{{ __('Resend code') }}';
                        isResending = false;
                    }, 2000);
                }
            "
                class="text-sm text-gray-600 dark:text-gray-400 cursor-pointer bg-transparent border-0 p-0 m-0 text-left transition-opacity duration-300"
                :class="{ 'underline': !isResending }"
                x-text="resendText"
            ></button>
        </form>
    </div>

</div>
