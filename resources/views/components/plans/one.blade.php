@props(['plan'])

@inject('planService', 'App\Services\PlanService')

@php
    $price = $planService->getPlanPrice($plan);
@endphp

<div {{$attributes->merge(['class' => 'relative px-5 py-10 flex flex-col gap-4 mx-auto text-center border-2 border-primary-500 rounded-2xl shadow-xl hover:shadow-2xl hover:-translate-y-2 transition'])}}>
    @if ($plan->product->is_popular)
    <div class="absolute border-0 top-0 -mt-3 left-1/2 transform -translate-x-1/2 bg-primary-500 text-primary-50 mx-auto rounded z-0 text-xs px-2 py-1">
        {{ __('Most popular') }}
    </div>
    @endif

    <x-heading.h3>
        {{ $plan->product->name }}
    </x-heading.h3>

    <div class="flex flex-col gap-1">
        @if($price !== null)
            <div class="text-4xl">
                @money($price->price, $price->currency->code)
            </div>
            <div class="text-neutral-400 text-sm">
                @if($plan->type === \App\Constants\PlanType::SEAT_BASED->value && $price->type === \App\Constants\PlanPriceType::SEAT_BASED_WITH_INCLUDED_SEATS->value)
                    / {{$plan->interval_count > 1 ? $plan->interval_count : '' }} {{ __($plan->interval->name) }}
                    <div class="text-xs mt-1">
                        {{ __('Includes :count seats, +:price/extra seat', ['count' => $price->included_seats, 'price' => money($price->extra_seat_price, $price->currency->code)]) }}
                    </div>
                @else
                    @if($plan->type === \App\Constants\PlanType::SEAT_BASED->value)
                        <span class="text-sm">{{__('Per seat')}}</span>
                    @endif
                    / {{$plan->interval_count > 1 ? $plan->interval_count : '' }} {{ __($plan->interval->name) }}
                @endif
            </div>
            @if(($price->setup_fee ?? 0) > 0)
                <div class="text-sm text-neutral-500 mt-1">
                    + @money($price->setup_fee, $price->currency->code) {{ __('setup fee') }}
                </div>
            @endif
        @endif

        @if($price->type === \App\Constants\PlanPriceType::USAGE_BASED_PER_UNIT->value)
            <div class="text-sm mt-2">
                + @money($price->price_per_unit, $price->currency->code) / {{ __($plan->meter->name) }}
            </div>
        @elseif($price->type === \App\Constants\PlanPriceType::USAGE_BASED_TIERED_GRADUATED->value
                || $price->type === \App\Constants\PlanPriceType::USAGE_BASED_TIERED_VOLUME->value)
            <div class="mt-2">
                @php $start = 0; $startingPhrase = __('From'); @endphp
                @foreach($price->tiers as $tier)
                    <div class="flex justify-center items-center gap-4 mt-3">
                        <span class="font-medium text-xs ">{{$startingPhrase}}</span>
                        <span class="flex flex-col">
                            <span class="text-xl">{{ $start }} - {{ $tier[\App\Constants\PlanPriceTierConstants::UNTIL_UNIT] }}</span>
                            <span class="text-neutral-400 text-xs">{{ __(strtolower(str()->plural($plan->meter->name))) }}</span>
                        </span>
                        →
                        <span class="flex flex-col">
                            <span class=" text-sm">@money($tier[\App\Constants\PlanPriceTierConstants::PER_UNIT], $price->currency->code) / {{ __($plan->meter->name) }}</span>
                            @if ($tier[\App\Constants\PlanPriceTierConstants::FLAT_FEE] > 0)
                            <span class="text-neutral-400 text-xs">+ @money($tier['flat_fee'], $price->currency->code)</span>
                            @endif
                        </span>
                    </div>
                    @php $start = intval($tier[\App\Constants\PlanPriceTierConstants::UNTIL_UNIT]) + 1; @endphp

                    @if($price->type === \App\Constants\PlanPriceType::USAGE_BASED_TIERED_GRADUATED->value)
                        @php $startingPhrase = __('Next'); @endphp
                    @endif
                @endforeach
            </div>
        @endif

    </div>

    <div class="py-4">
        <ul class="flex flex-col items-center gap-4">
            @if($plan->product->features)
                @foreach($plan->product->features as $feature)
                    <x-features.li-item>{{$feature['feature']}}</x-features.li-item>
                @endforeach
            @endif
        </ul>
    </div>

    <x-button-link.primary href="{{route('checkout.subscription', $plan->slug)}}">
        {{ __('Buy') }} {{ $plan->product->name }}
    </x-button-link.primary>
</div>
