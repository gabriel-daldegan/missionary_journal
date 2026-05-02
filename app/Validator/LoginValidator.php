<?php

namespace App\Validator;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LoginValidator
{
    public function validateRequest(Request $request)
    {
        return $request->validate(
            $this->getValidationRules(
                $request->all()
            ));
    }

    public function validate(array $fields)
    {
        return Validator::make($fields, $this->getValidationRules($fields));
    }

    private function getValidationRules(array $fields): array
    {
        $rules = [];

        if (! config('app.two_factor_auth_enabled') || ! isset($fields['2fa_code'])) {
            $rules = [
                'email' => 'required|string',
            ];

            if (! config('app.otp_login_enabled')) {
                $rules['password'] = 'required|string';
            }
        }

        if (config('app.recaptcha_enabled') && ! isset($fields['2fa_code'])) { // we want to avoid recaptcha validation when 2fa_code is present
            $rules[recaptchaFieldName()] = recaptchaRuleName();
        }

        return $rules;
    }
}
