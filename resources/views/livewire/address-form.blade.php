<x-filament::card>
    <h2 class="font-bold mb-2">
        {{ __('Your Address') }}
    </h2>

    <form wire:submit.prevent="submit" class="space-y-6">

        {{ $this->form }}

        <div class="text-right">
            <x-filament::button type="submit" form="submit" class="align-right">
                {{ __('Save') }}
            </x-filament::button>
        </div>
    </form>
</x-filament::card>
