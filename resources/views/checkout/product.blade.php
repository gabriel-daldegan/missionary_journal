<x-layouts.focus-center>

    <x-slot name="title">
        {{ __('Complete Purchase') }}
    </x-slot>

    <div class="text-center mx-4">
        <x-heading.h6 class="text-primary-500">
            {{ __('Pay securely.') }}
        </x-heading.h6>
        <x-heading.h2 class="text-primary-900">
            {{ __('Complete your purchase') }}
        </x-heading.h2>
    </div>

    <livewire:checkout.product-checkout-form />

</x-layouts.focus-center>
