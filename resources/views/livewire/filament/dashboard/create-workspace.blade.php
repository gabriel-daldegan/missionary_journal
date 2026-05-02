<form wire:submit="create">
    {{ $this->form }}

    <div class="mt-4">
        <x-filament::button type="submit" size="lg">
            {{ __('Create Workspace') }}
        </x-filament::button>
    </div>
</form>