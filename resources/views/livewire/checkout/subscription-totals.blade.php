<div>
    @if ($canAddDiscount)
        @php $isDiscountCodeAdded = !empty($addedCode); @endphp
        <div x-data="{ discountFormVisible: @js($isDiscountCodeAdded) }">
            <div class="text-end">
                <a href="#" class="text-primary-500 text-sm" x-on:click.prevent=" discountFormVisible = !discountFormVisible "
                   x-show="!discountFormVisible">{{ __('Have a coupon code?') }}</a>
            </div>

            <div class="my-6" x-show="discountFormVisible">
                <hr class="my-4  text-neutral-200"/>

                @if (session('success'))
                    <div class="text-xs flex flex-row gap-2 my-2">
                        @svg('check', 'h-4 w-4 stroke-primary-500')
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="text-xs flex flex-row gap-2 my-2">
                        @svg('error', 'h-4 w-4 stroke-primary-500')
                        <span>{{ session('error') }}</span>
                    </div>
                @endif


                @if ($isDiscountCodeAdded)
                    <div class="flex flex-row items-center gap-3 justify-end">
                        <div class="rounded border-primary-500 py-1 px-2 text-xs border border-dashed">
                            {{ $addedCode }}
                        </div>

                        <a wire:click.prevent="remove" class="!text-primary-500 !border-primary-500 text-xs! py-1! cursor-pointer">
                            {{ __('Remove Discount') }}
                        </a>
                    </div>
                @else
                    <div class="flex flex-row items-center gap-3 mt-6">
                        <x-input.field wire:model="code" placeholder="{{ __('Discount code') }}" type="text" class="input-sm mx-0! px-0!"
                               wire:keydown.enter.prevent="add"
                               value="{{$addedCode ?? ''}}" disabled="{{$isDiscountCodeAdded}}"/>

                        <x-button-link.primary-outline wire:click.prevent="add" tabindex="0"
                                                       @keydown.enter.prevent="$wire.add()"
                                                       class="!text-primary-500 !border-primary-500 text-xs! py-1! whitespace-nowrap">
                            {{ __('Add Discount') }}
                        </x-button-link.primary-outline>
                    </div>
                @endif

            </div>
        </div>
    @endif


    <hr class="mb-6 mt-4 text-neutral-200">

    @if ($planPriceType === \App\Constants\PlanPriceType::SEAT_BASED_WITH_INCLUDED_SEATS->value && $basePrice !== null)
        <div class="flex flex-row justify-between">
            <div class="text-primary-900">
                {{ __('Base price') }} <span class="text-xs text-neutral-400">({{ __('includes :count seats', ['count' => $includedSeats]) }})</span>
            </div>
            <div class="text-primary-900">
                @money($basePrice, $currencyCode)
            </div>
        </div>
        @if ($extraSeats > 0)
            <div class="flex flex-row justify-between mt-2">
                <div class="text-primary-900">
                    {{ __('Extra seats') }} <span class="text-xs text-neutral-400">({{ $extraSeats }} &times; @money($extraSeatPrice, $currencyCode))</span>
                </div>
                <div class="text-primary-900">
                    @money($extraSeats * $extraSeatPrice, $currencyCode)
                </div>
            </div>
        @endif
        <hr class="my-4 text-neutral-200">
        <div class="flex flex-row justify-between">
            <div class="text-primary-900">
                {{ __('Subscription price') }}
            </div>
            <div class="text-primary-900">
                @money($subtotal, $currencyCode)
            </div>
        </div>
    @elseif ($subtotal > 0)
        <div class="flex flex-row justify-between">
            <div class="text-primary-900">
                {{ __('Subscription price') }}
            </div>
            <div class="text-primary-900">
                @money($subtotal, $currencyCode)
            </div>
        </div>
    @endif

    @if ($setupFee > 0)
        <div class="flex flex-row justify-between mt-2">
            <div class="text-primary-900">
                {{ __('Setup fee') }} <span class="text-xs text-neutral-400">({{ __('one-time') }})</span>
            </div>
            <div class="text-primary-900">
                @money($setupFee, $currencyCode)
            </div>
        </div>
    @endif

    @if ($planPriceType === \App\Constants\PlanPriceType::USAGE_BASED_PER_UNIT->value)
        <div class="flex flex-row justify-between mt-2">
            <div class="text-primary-900">
                {{ __('Price / ') }} {{ __($unitMeterName) }}
            </div>
            <div class="text-primary-900">
                @money($pricePerUnit, $currencyCode)
            </div>
        </div>
    @elseif($planPriceType === \App\Constants\PlanPriceType::USAGE_BASED_TIERED_VOLUME->value || $planPriceType === \App\Constants\PlanPriceType::USAGE_BASED_TIERED_GRADUATED->value)
        <div class="text-primary-900 font-medium mt-3">
            {{ __('Tiered pricing') }}
        </div>
        <div class="flex flex-row justify-between mt-2">
            <div class="text-primary-900">
                @php $start = 0; $startingPhrase = __('From'); @endphp
                @foreach($tiers as $tier)
                    <div class="">
                         {{$startingPhrase}} {{ $start }} - {{ $tier[\App\Constants\PlanPriceTierConstants::UNTIL_UNIT] }} {{ __(str()->plural($unitMeterName)) }}
                         → <span class="text-primary-500"> @money($tier[\App\Constants\PlanPriceTierConstants::PER_UNIT], $currencyCode) / {{ __($unitMeterName) }}
                        @if ($tier[\App\Constants\PlanPriceTierConstants::FLAT_FEE] > 0)
                            + @money($tier['flat_fee'], $currencyCode)
                        @endif
                        </span>
                    </div>
                    @php $start = intval($tier[\App\Constants\PlanPriceTierConstants::UNTIL_UNIT]) + 1; @endphp

                    @if($planPriceType === \App\Constants\PlanPriceType::USAGE_BASED_TIERED_GRADUATED->value)
                        @php $startingPhrase = __('Next'); @endphp
                    @endif
                @endforeach
            </div>
        </div>
        @if ($planPriceType === \App\Constants\PlanPriceType::USAGE_BASED_TIERED_GRADUATED->value)
            <p class="text-xs text-neutral-600 pt-4">
                {{ __('Graduated pricing mimics the way income taxes are calculated, where you pay different rates on portions of your usage. The first tier is applied to the first units, the second tier to the next units, and so on.') }}
            </p>
        @endif
    @endif

    @if($discountAmount > 0)
        <div class="flex flex-row justify-between">
            <div class="text-primary-900">
                {{ __('Discount') }}
            </div>
            <div class="text-primary-900">
                @money($discountAmount, $currencyCode)
            </div>
        </div>

        <hr class="my-6 text-neutral-200">

        <div class="flex flex-row justify-between">
            <div class="text-primary-900">
                {{ __('Total') }}
            </div>
            <div class="text-primary-900">
                @money($amountDue, $currencyCode)
            </div>
        </div>

    @endif

    <hr class="my-6 text-neutral-200">
    <div class="flex flex-row justify-between">
        <div class="text-primary-500 text-xl font-bold">
            {{ __('Due now') }}
        </div>
        <div class="text-primary-500 text-xl font-bold">
            @if ($planHasTrial && !$isTrailSkipped)
                @money($setupFee, $currencyCode)
            @else
                @money($amountDue, $currencyCode)
            @endif
        </div>
    </div>


</div>
