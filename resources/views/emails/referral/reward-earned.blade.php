<x-layouts.email>
    <h1>{{ __('Congratulations! You Earned a Referral Reward!') }}</h1>

    <p>{{ __('Great news! Your referral was successful.') }}</p>

    <p>{{ __('Your friend :name has :action, and you\'ve earned a reward!', [
        'name' => $referral->referredUser->name,
        'action' => $referral->status === 'verified' ? __('verified their account') : __('made their first payment')
    ]) }}</p>

    <div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
        <h2 style="margin-top: 0;">{{ __('Your Reward Coupon Code:') }}</h2>
        <p style="font-size: 24px; font-weight: bold; color: #10b981; margin: 10px 0;">
            {{ $discountCode->code }}
        </p>
        <p style="margin-bottom: 0;">
            {{ __('Discount: :name', ['name' => $discountCode->discount->name]) }}
        </p>
    </div>

    <p>{{ __('You can use this coupon code on your next purchase.') }}</p>

    <p>{{ __('Keep referring friends to earn more rewards!') }}</p>

    <p>
        <a href="{{ url('/dashboard') }}" style="background-color: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
            {{ __('View Dashboard') }}
        </a>
    </p>
</x-layouts.email>
