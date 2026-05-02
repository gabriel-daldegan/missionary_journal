<?php

namespace App\Dto;

use Carbon\Carbon;

class SmsVerificationDto
{
    public string $phoneNumber;

    public string $code;

    public Carbon $generatedAt;
}
