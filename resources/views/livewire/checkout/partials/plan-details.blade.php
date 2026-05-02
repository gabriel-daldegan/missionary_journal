<div class="md:sticky md:top-2">
    @php
        $canAddDiscount = $canAddDiscount ?? true;
        $isTrialSkipped = $isTrialSkipped ?? false;
        $isTenantPickerEnabled = $isTenantPickerEnabled ?? true;
    @endphp
    <x-heading.h2 class="text-primary-900 text-xl!">
        {{ __('Plan details') }}
    </x-heading.h2>

    <div class="rounded-2xl border border-neutral-200 mt-4 overflow-hidden p-6">

        <div class="flex flex-row gap-3">
            <div class="rounded-2xl text-5xl bg-primary-50 p-2 text-center w-24 h-24 text-primary-500 min-w-20 flex items-center justify-center">
                {{ substr($plan->name, 0, 1) }}
            </div>
            <div class="flex flex-col gap-1">
                <span class="text-xl font-semibold flex flex-row gap-2 flex-wrap">
                    <span class="py-1">
                        {{ $plan->product->name }}
                    </span>
                    @if (!$isTrialSkipped && $plan->has_trial)
                        <span class="text-xs rounded-full border border-primary-500 text-primary-500 px-2 md:px-4 font-semibold py-1 inline-block self-center">
                            {{ $plan->trial_interval_count }} {{ $plan->trialInterval()->firstOrFail()->name }} {{ __(' free trial included') }}
                        </span>
                    @endif
                </span>
                @if ($plan->interval_count > 1)
                    <span class="text-xs">{{ $plan->interval_count }} {{ mb_convert_case($plan->interval->name, MB_CASE_TITLE, 'UTF-8') }}</span>
                @else
                    <span class="text-xs">{{ mb_convert_case($plan->interval->adverb, MB_CASE_TITLE, 'UTF-8') }} {{ __('subscription.') }}</span>
                @endif

                <span class="text-xs">
                    {{ __('Starts immediately.') }}
                </span>

            </div>
        </div>

        <div class="flex gap-4">

            @inject('tenantCreationService', 'App\Services\TenantCreationService')

            @if ($isTenantPickerEnabled && $tenantCreationService->findUserTenantsForNewSubscription(auth()->user())->count() > 0)
                <livewire:checkout.subscription-tenant-picker />
            @endif

            @if ($plan->type === \App\Constants\PlanType::SEAT_BASED->value)
                <livewire:checkout.subscription-seats :plan="$plan" />
            @endif

        </div>

        <div class="text-primary-900 my-4">
            {{ __('What you get:') }}
        </div>
        <div>
            <ul class="flex flex-col items-start gap-3">
                @if ($plan->product->features)
                    @foreach($plan->product->features as $feature)
                        <x-features.li-item>{{ $feature['feature'] }}</x-features.li-item>
                    @endforeach
                @endif
            </ul>
        </div>

        <livewire:checkout.subscription-totals :totals="$totals" :plan="$plan" page="{{request()->fullUrl()}}" can-add-discount="{{$canAddDiscount}}" is-trail-skipped="{{$isTrialSkipped}}"/>

    </div>
</div>
