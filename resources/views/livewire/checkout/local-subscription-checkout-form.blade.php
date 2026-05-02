<div>
    <form action="" method="post" wire:submit="checkout" class="mb-32">
        @csrf

        <x-section.columns class="max-w-none md:max-w-6xl flex-wrap-reverse">

            @guest
            <x-section.column>
                @include('livewire.checkout.partials.login-or-register')
            </x-section.column>
            @endguest

            <x-section.column>
                @include('livewire.checkout.partials.plan-details', ['canAddDiscount' => false])
            </x-section.column>

        </x-section.columns>


        <div class="fixed bottom-0 w-full bg-white shadow-black shadow-2xl z-50 py-4">
            <div class="flex flex-row flex-wrap justify-center items-center gap-2 md:gap-4">
                <p class="text-xxs text-neutral-600 text-center mx-6">
                    {{ __('By continuing, you agree to our') }} <a target="_blank" href="{{route('terms-of-service')}}" class="text-primary-900 underline">{{ __('Terms of Service') }}</a> {{ __('and') }} <a target="_blank" href="{{route('privacy-policy')}}" class="text-primary-900 underline">{{ __('Privacy Policy') }}</a>.
                </p>

                <x-button-link.primary
                    class="flex flex-row items-center justify-center gap-3  min-w-64! disabled:opacity-40"
                    elementType="button"
                    type="submit"
                    wire:loading.attr="disabled"
                    isDisabled="{{ !$this->isCheckoutButtonEnabled() }}"
                >
                    {{ __('Start Your Free Trial') }}
                    <div wire:loading class="max-w-fit max-h-fit">
                        <span class="loading loading-ring loading-xs"></span>
                    </div>
                </x-button-link.primary>
            </div>
        </div>

    </form>


</div>
