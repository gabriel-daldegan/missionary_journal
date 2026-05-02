<x-layouts.email>
    <x-slot name="preview">
        {{ __('Your One-time Password') }}
    </x-slot>

    <tr>
        <td class="sm-px-6" style="border-radius: 4px; padding: 48px; font-size: 16px; color: #334155; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05)" bgcolor="#ffffff">
            <h1 class="sm-leading-8" style="margin: 0 0 24px; font-size: 24px; font-weight: 600; color: #000">
                {{ __('One time login code') }}
            </h1>
            <p style="margin: 0; line-height: 24px">
                {{ __('This is your one-time login code to use on :url:', ['url' => config('app.url')]) }}
                <br>
                <br>
                <strong>{{ $oneTimePassword->password }}</strong>
            </p>
            <p style="margin-top: 16px; line-height: 24px">
                {{ __('To protect your account, do not share this code with anyone. If you didn\'t make this request, you can safely ignore this email.') }}
            </p>
        </td>
    </tr>
</x-layouts.email>
