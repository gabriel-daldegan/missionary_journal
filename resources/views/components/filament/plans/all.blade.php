@props([
    'buyRoute' => 'subscription.change-plan',
])

<section class="bg-white dark:bg-gray-900 p-5 dark:text-white">

        @if(isset($subscription))
            <div class="mx-auto max-w-(--breakpoint-md) text-center mb-8">
                <h2 class="mb-4 text-xl tracking-tight text-gray-900 dark:text-white">{{ __('You are currently on the') }} <div class="badge badge-primary badge-outline font-bold text-xl p-3">{{ $subscription->plan->product->name  }}</div> {{__('plan.')}}</h2>
            </div>
        @endif

        <div class="plan-switcher tabs tabs-box justify-center w-full bg-neutral-200  mb-4 dark:bg-gray-800 max-w-fit m-auto">
            @foreach($groupedPlans as $interval => $plans)
                <a class="tab dark:text-black {{$preselectedInterval == $interval ? 'tab-active': ''}}" data-target="plans-{{$interval}}" aria-selected="{{$preselectedInterval == $interval ? 'true' : 'false'}}">{{str($interval)->title()}}</a>
            @endforeach
        </div>

        @if($isGrouped)
            @foreach($groupedPlans as $interval => $plans)
                <div class="plans-container plans-{{$interval}} {{$preselectedInterval == $interval ? '': 'hidden'}}  grid max-w-md gap-10 row-gap-5 lg:max-w-(--breakpoint-lg) sm:row-gap-10 lg:grid-cols-3 xl:max-w-(--breakpoint-lg) sm:mx-auto dark:text-white pt-5 pb-5">
                    @foreach($plans as $plan)
                        <x-filament.plans.one :plan="$plan" :subscription="$subscription" :buyRoute="$buyRoute" />
                    @endforeach
                </div>
            @endforeach
        @else

            <div class="grid max-w-md gap-10 row-gap-5 lg:max-w-(--breakpoint-lg) sm:row-gap-10 lg:grid-cols-3 xl:max-w-(--breakpoint-lg) sm:mx-auto dark:text-white">
                @foreach($plans as $plan)
                        <x-filament.plans.one :plan="$plan" :subscription="$subscription" :featured="$featured == $plan->product->slug" :buyRoute="$buyRoute"/>
                @endforeach
            </div>
        @endif

</section>

