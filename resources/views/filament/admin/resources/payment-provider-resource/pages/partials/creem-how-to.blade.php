<div class="px-4">
    <p class="pb-4">
        {{__('To integrate Creem with your application, you need to do the following steps:')}}
    </p>
    <ol class="list-decimal ">
        <li class="pb-4">
            <strong>
                {{ __('Login to ') }} <a href="https://creem.io" target="_blank" class="text-blue-500 hover:underline">{{ __('Creem Dashboard') }}</a>
            </strong>
        </li>
        <li class="pb-4">
            <strong>
                {{ __('API Key') }}
            </strong>
            <p>
                {{ __('In the Dashboard, navigate to "API Keys". Create a new API key and copy it into the form in the field called "API Key".') }}
            </p>
            <p class="mt-2">
                {{ __('Test mode keys are prefixed with "creem_test_" and live keys are prefixed with "creem_".') }}
            </p>
        </li>
        <li class="pb-4">
            <strong>
                {{ __('Webhook Secret') }}
            </strong>
            <p>
                {{ __('In the Dashboard, navigate to "Webhooks". Create a new webhook endpoint with the URL below:') }}
                <code class="bg-gray-100 px-4 py-2 block my-4 overflow-x-scroll">
                    {{ route('payments-providers.creem.webhook') }}
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
                    checkout.completed
                </li>
                <li>
                    subscription.active
                </li>
                <li>
                    subscription.paid
                </li>
                <li>
                    subscription.trialing
                </li>
                <li>
                    subscription.canceled
                </li>
                <li>
                    subscription.scheduled_cancel
                </li>
                <li>
                    subscription.past_due
                </li>
                <li>
                    subscription.expired
                </li>
                <li>
                    subscription.paused
                </li>
                <li>
                    subscription.update
                </li>
                <li>
                    refund.created
                </li>
            </ul>
        </li>
        <li class="pb-4">
            <strong>
                {{ __('Test Mode') }}
            </strong>
            <p>
                {{ __('Enable "Is Test Mode" if you are using Creem test API keys. This will direct API calls to the Creem test environment.') }}
            </p>
        </li>
        <li class="pb-4">
            <strong>
                {{ __('Products') }}
            </strong>
            <p>
                {{ __('Create your products on the Creem Dashboard and copy the product IDs into the corresponding plans and one-time products in the admin panel (under "Payment Provider Data").') }}
            </p>
            <p class="mt-2">
                {{ __('Make sure the product pricing, billing interval, and trial period settings on Creem match the plan settings in your application.') }}
            </p>
        </li>
    </ol>
</div>
