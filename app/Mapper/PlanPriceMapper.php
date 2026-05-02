<?php

namespace App\Mapper;

use App\Constants\PlanPriceType;
use App\Constants\PlanType;

class PlanPriceMapper
{
    public static function getPlanPriceTypes(string $planType): array
    {
        return [
            PlanType::FLAT_RATE->value => [
                PlanPriceType::FLAT_RATE->value => __('Flat Rate'),
            ],
            PlanType::SEAT_BASED->value => [
                PlanPriceType::FLAT_RATE->value => __('Per Seat'),
                PlanPriceType::SEAT_BASED_WITH_INCLUDED_SEATS->value => __('Base Price + Extra Seats'),
            ],
            PlanType::USAGE_BASED->value => [
                PlanPriceType::USAGE_BASED_PER_UNIT->value => __('Per Unit'),
                PlanPriceType::USAGE_BASED_TIERED_VOLUME->value => __('Tiered Volume'),
                PlanPriceType::USAGE_BASED_TIERED_GRADUATED->value => __('Tiered Graduated'),
            ],
        ][$planType];
    }
}
