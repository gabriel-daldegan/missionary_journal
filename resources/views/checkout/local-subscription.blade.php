<x-layouts.focus-center>

    <x-slot name="title">
        {{ __('Complete Subscription') }}
    </x-slot>

    <div class="text-center my-4 mx-4">
        <x-heading.h6 class="text-primary-500">
            {{ __('Try it out for free, no strings attached.') }}
        </x-heading.h6>
        <x-heading.h2 class="text-primary-900">
            {{ __('Complete Subscription') }}
        </x-heading.h2>
    </div>

    <livewire:checkout.local-subscription-checkout-form />

</x-layouts.focus-center>
