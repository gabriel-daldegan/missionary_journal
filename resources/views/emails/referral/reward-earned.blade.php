<x-layouts.email>
    <x-slot name="preview">
        {{ __('Great news! Your referral was successful.') }}
    </x-slot>

    <div style="color: #000">
        <h1>{{ __('Congratulations! You Earned a Referral Reward!') }}</h1>

        <p>{{ __('Great news! Your referral was successful.') }}</p>

        <p>{{ __('Your friend :name has joined us, and you\'ve earned a reward!', [
            'name' => $referral->referredUser->name,
        ]) }}</p>

        <div style="padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h2 style="margin-top: 0;">{{ __('Your Reward Coupon Code:') }}</h2>
            <p style="font-size: 24px; font-weight: bold; color: #10b981; margin: 10px 0;">
                {{ $discountCode->code }}
            </p>
        </div>

        <p>{{ __('Keep referring friends to earn more rewards!') }}</p>

        <p>
            <a href="{{ route('dashboard') }}" style="margin-top: 24px; margin-bottom: 24px; display: inline-block; border-radius: 16px; background-color: {{config('app.email_color_tint')}}; padding: 8px 24px; font-size: 20px; color: #fff; text-decoration-line: none">
                {{ __('View Dashboard') }}
            </a>
        </p>
    </div>
</x-layouts.email>
