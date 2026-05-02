<div>
    <div class="my-1 relative">
        <label class="form-control w-full max-w-xs">
            <fieldset class="fieldset">
                <legend class="fieldset-legend">{{ __('Number of seats:') }}</legend>
                <input type="number" min="1"
                       {{ $maxQuantity > 0 ? 'max=' . $maxQuantity : '' }}
                       wire:model.live.debounce.300ms="quantity"
                       class="input input-bordered md:w-2/3 max-w-xs">
            </fieldset>

            <div class="absolute top-0 right-0 p-2">
                <span wire:loading>
                    <span class="loading loading-spinner loading-xs"></span>
                </span>
            </div>

            @if($includedSeats)
                <div class="text-xs text-neutral-500 mt-1">
                    {{ __(':count seats included, additional seats at :price each', ['count' => $includedSeats, 'price' => money($extraSeatPrice, $extraSeatCurrencyCode)]) }}
                </div>
            @endif

            @error('quantity')
                <span class="text-xs text-red-500 mt-1" role="alert">
                    {{ $message }}
                </span>
            @enderror
        </label>
    </div>
</div>
