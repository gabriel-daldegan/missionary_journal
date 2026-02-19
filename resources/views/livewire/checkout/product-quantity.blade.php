<div>
    <div class="my-4">

        <div class="flex flex-row gap-3 items-center">
            <span class="font-medium text-sm">{{ __('Quantity') }}</span>
            <fieldset class="fieldset">
                <input type="number" min="1" class="input input-md" {{ $maxQuantity > 0 ? 'max=' . $maxQuantity : '' }}
                wire:model.live.debounce.300ms="quantity" />
            </fieldset>
        </div>

        <div class="absolute top-0 right-0 p-2">
                <span wire:loading>
                    <span class="loading loading-spinner loading-xs"></span>
                </span>
        </div>

        @error('quantity')
            <span class="text-xs text-red-500 mt-1" role="alert">
                {{ $message }}
            </span>
        @enderror
    </div>
</div>
