<?php

namespace App\Constants;

enum PlanType: string
{
    case FLAT_RATE = 'flat_rate';
    case SEAT_BASED = 'seat_based';
    case USAGE_BASED = 'usage_based';
}
