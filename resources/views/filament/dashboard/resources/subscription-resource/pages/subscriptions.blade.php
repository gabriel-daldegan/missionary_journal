<x-filament-panels::page>
    <x-filament::card>
        <h2 class="text-xl text-center font-bold">
           {{ __('Subscribe to a Plan') }}
        </h2>
        <x-filament.plans.all :is-grouped="true" preselected-interval="year" buyRoute="checkout.subscription"/>
    </x-filament::card>

</x-filament-panels::page>
