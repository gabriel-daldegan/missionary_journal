<?php

namespace App\Constants;

enum PlanPriceType: string
{
    case FLAT_RATE = 'flat_rate';
    case USAGE_BASED_PER_UNIT = 'usage_based_per_unit';
    case USAGE_BASED_TIERED_VOLUME = 'usage_based_tiered_volume';
    case USAGE_BASED_TIERED_GRADUATED = 'usage_based_tiered_graduated';
    case SEAT_BASED_WITH_INCLUDED_SEATS = 'seat_based_with_included_seats';
}
