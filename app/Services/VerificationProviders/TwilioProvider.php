<?php

namespace App\Services\VerificationProviders;

use App\Constants\VerificationProviderConstants;
use Exception;
use Twilio\Rest\Client;

class TwilioProvider implements VerificationProviderInterface
{
    public function sendSms(string $phoneNumber, string $sms): bool
    {
        try {
            $client = new Client(config('services.twilio.sid'), config('services.twilio.token'));

            $client->messages->create(
                $phoneNumber,
                [
                    'from' => config('services.twilio.from'),
                    'body' => $sms,
                ]
            );
        } catch (Exception $e) {
            logger()->error($e->getMessage());

            return false;
        }

        return true;
    }

    public function getSlug(): string
    {
        return VerificationProviderConstants::TWILIO_SLUG;
    }
}
