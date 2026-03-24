<?php

namespace App\Dto;

class TotalsDto
{
    /**
     * @var int total prices of products in cart (with tax)
     */
    public int $subtotal = 0;

    public int $discountAmount = 0;

    public int $setupFee = 0;

    /**
     * @var int total prices of products in cart (with tax) + shipping cost + fees + tax on fees - discount
     */
    public int $amountDue = 0;

    public string $currencyCode;

    public ?string $planPriceType = null;

    public ?string $pricePerUnit = null;

    public ?array $tiers = null;

    public ?int $basePrice = null;

    public ?int $includedSeats = null;

    public ?int $extraSeatPrice = null;

    public ?int $extraSeats = null;
}
