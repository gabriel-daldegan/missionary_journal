<div>
    <div class="my-1 relative">
        <fieldset class="fieldset">
            <legend class="fieldset-legend">{{ __('Pick a workspace:') }}</legend>
            <select wire:model.live="tenant" class="select select-bordered">
                @foreach ($userTenants as $tenant)
                    <option value="{{ $tenant->uuid }}">{{ $tenant->name }}</option>
                @endforeach
                <option value="">{{ __('Create a new workspace') }}</option>
            </select>
        </fieldset>

        @error('tenant')
        <span class="text-xs text-red-500 mt-1" role="alert">
                    {{ $message }}
                </span>
        @enderror
    </div>
</div>
