@if (count($groupedPlans) == 0)
    <x-section.columns class="max-w-none md:max-w-6xl mt-6 justify-center md:flex-wrap!">
    @foreach($plans as $plan)
        <x-section.column class="md:basis-1/3! px-4!">
            <x-plans.one :plan="$plan" />
        </x-section.column>
    @endforeach
    </x-section.columns>
@else
    <x-tab-slider class="mt-6 md:max-w-6xl">
        <x-slot name="tabNames">
            @php
                if (empty($preselectedInterval)) {
                    $preselectedInterval = array_keys($groupedPlans)[0] ?? null;
                }
            @endphp
            @foreach($groupedPlans as $interval => $intervalPlans)
                <x-tab-slider.tab-name controls="pricing-{{$interval}}" active="{{ $preselectedInterval == $interval ? 'true' : 'false' }}">

                    {{ ucfirst(__($intervalPlans[0]?->interval?->adverb)) }}

                    @if(isset($intervalSavingPercentage[$interval]) && $intervalSavingPercentage[$interval] > 0)
                        <x-pill class="text-primary-500 bg-primary-50 ml-">{{ __('Save ') . $intervalSavingPercentage[$interval] }} %</x-pill>
                    @endif
                </x-tab-slider.tab-name>

            @endforeach
        </x-slot>

        @foreach($groupedPlans as $interval => $plans)
            <x-tab-slider.tab-content id="pricing-{{$interval}}">
                <x-section.columns class="max-w-none md:max-w-6xl mt-6 justify-center md:flex-wrap!">
                    @foreach($plans as $plan)
                        <x-section.column class="md:basis-1/3! px-4!">
                            <x-plans.one :plan="$plan" />
                        </x-section.column>
                    @endforeach
                </x-section.columns>
            </x-tab-slider.tab-content>
        @endforeach

    </x-tab-slider>
@endif

@if (isset($defaultProduct))
    <div class="mx-4">
        <div class="max-w-none md:max-w-6xl border border-gray-200 rounded-2xl p-8 mt-6 mx-8 md:mx-auto">
            <div class="text-center">
                <x-heading.h3>{{ __('Start for FREE') }}</x-heading.h3>
                <p class="mt-4">{{ __('Start now and upgrade as you go. No credit card required!') }}</p>
                <ul class="flex flex-wrap md:flex-nowrap flex-row items-center justify-center gap-4 mt-4">
                    @if($defaultProduct->features)
                        @foreach($defaultProduct->features as $feature)
                            <x-features.li-item class="text-left">{{$feature['feature']}}</x-features.li-item>
                        @endforeach
                    @endif
                </ul>

                <x-button-link.primary href="{{route('plan.start')}}" class="mt-6 px-6! py-3!">
                    {{ __('Start Now') }}
                </x-button-link.primary>
            </div>
        </div>
    </div>
@endif
