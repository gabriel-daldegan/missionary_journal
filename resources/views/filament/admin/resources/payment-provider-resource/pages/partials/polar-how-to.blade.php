<div class="px-4">
    <p class="pb-4">
        {{__('To integrate Polar with your application, you need to do the following steps:')}}
    </p>
    <ol class="list-decimal ">
        <li class="pb-4">
            <strong>
                {{ __('Login to ') }} <a href="https://polar.sh" target="_blank" class="text-blue-500 hover:underline">{{ __('Polar Dashboard') }}</a>
            </strong>
        </li>
        <li class="pb-4">
            <strong>
                {{ __('Access Token') }}
            </strong>
            <p>
                {{ __('In the Dashboard, navigate to "Settings" > "Developers" > "Access Tokens". Create a new Organization Access Token and copy it into the form in the field called "Access Token".') }}
            </p>
            <p class="mt-2">
                {{ __('Tokens are prefixed with "polar_oat_".') }}
            </p>
        </li>
        <li class="pb-4">
            <strong>
                {{ __('Webhook Secret') }}
            </strong>
            <p>
                {{ __('In the Dashboard, navigate to "Settings" > "Developers" > "Webhooks". Create a new webhook endpoint with the URL below:') }}
                <code class="bg-gray-100 px-4 py-2 block my-4 overflow-x-scroll">
                    {{ route('payments-providers.polar.webhook') }}
                </code>
            </p>
            <p class="mt-4">
                {{ __('Copy the webhook signing secret and paste it into the "Webhook Secret" field.') }}
            </p>
            <p class="mt-4">
                {{ __('Subscribe to the following webhook events:') }}
            </p>
            <ul class="list-disc ps-4 mt-4">
                <li>
                    subscription.created
                </li>
                <li>
                    subscription.active
                </li>
                <li>
                    subscription.updated
                </li>
                <li>
                    subscription.canceled
                </li>
                <li>
                    subscription.uncanceled
                </li>
                <li>
                    subscription.revoked
                </li>
                <li>
                    order.paid
                </li>
                <li>
                    order.refunded
                </li>
            </ul>
        </li>
        <li class="pb-4">
            <strong>
                {{ __('Sandbox Mode') }}
            </strong>
            <p>
                {{ __('Enable "Sandbox Mode" if you are using the Polar sandbox environment. This will direct API calls to the Polar sandbox API.') }}
            </p>
        </li>
        <li class="pb-4">
            <strong>
                {{ __('Products') }}
            </strong>
            <p>
                {{ __('Create your products on the Polar Dashboard and copy the product IDs into the corresponding plans and one-time products in the admin panel (under "Payment Provider Data").') }}
            </p>
            <p class="mt-2">
                {{ __('Make sure the product pricing, billing interval, and trial period settings on Polar match the plan settings in your application.') }}
            </p>
        </li>
    </ol>
</div>
