<div>
    <div class="my-4">
        <label class="form-control w-full max-w-xs flex flex-row gap-2">
            <div class="label">
                <span class="label-text">{{ __('Quantity:') }}</span>
            </div>
            <input type="number" min="1"
                  {{ $maxQuantity > 0 ? 'max=' . $maxQuantity : '' }}
                   wire:model.live.blur="quantity"
                   class="input input-bordered md:w-2/3 max-w-s">

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
