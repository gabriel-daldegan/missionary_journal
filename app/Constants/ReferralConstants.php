<?php

namespace App\Constants;

class ReferralConstants
{
    public const HTTP_PARAM_REFERRAL_CODE = 'referralCode';

    public const TRIGGER_VERIFIED_REGISTRATION = 'verified_registration';

    public const TRIGGER_FIRST_PAYMENT = 'first_payment';

    public const TRIGGERS = [
        self::TRIGGER_VERIFIED_REGISTRATION,
        self::TRIGGER_FIRST_PAYMENT,
    ];

    public const REWARD_TYPE_COUPON = 'coupon';

    public const REWARD_TYPE_CUSTOM_EVENT = 'custom_event';

    public const REWARD_TYPES = [
        self::REWARD_TYPE_COUPON,
        self::REWARD_TYPE_CUSTOM_EVENT,
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_PAID = 'paid';

    public const STATUS_REWARDED = 'rewarded';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_VERIFIED,
        self::STATUS_PAID,
        self::STATUS_REWARDED,
    ];

    public const CODE_LENGTH = 12;

    public const CODE_PREFIX = 'REF-';
}
