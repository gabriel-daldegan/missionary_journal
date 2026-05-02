<?php

namespace App\Constants;

enum PaymentProviderPlanPriceType: string
{
    case MAIN_PRICE = 'main_price';
    case NO_TRIAL_PRICE = 'no_trial_price';  // for Payment Providers that don't support skipping trials (like Paddle)
    case USAGE_BASED_PRICE = 'usage_based_price';
    case USAGE_BASED_FIXED_FEE_PRICE = 'usage_based_fixed_fee_price';
    case SETUP_FEE_PRICE = 'setup_fee_price';
    case EXTRA_SEAT_PRICE = 'extra_seat_price';
}
