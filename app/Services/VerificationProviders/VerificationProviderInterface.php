<?php

namespace App\Services\VerificationProviders;

interface VerificationProviderInterface
{
    public function sendSms(string $phoneNumber, string $sms): bool;

    public function getSlug(): string;
}
