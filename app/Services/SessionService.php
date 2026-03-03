<?php

namespace App\Services;

use App\Constants\SessionConstants;
use App\Dto\CartDto;
use App\Dto\SmsVerificationDto;
use App\Dto\SubscriptionCheckoutDto;

class SessionService
{
    public function saveSubscriptionCheckoutDto(SubscriptionCheckoutDto $subscriptionCheckoutDto): void
    {
        session()->put(SessionConstants::SUBSCRIPTION_CHECKOUT_DTO, $subscriptionCheckoutDto);
    }

    public function getSubscriptionCheckoutDto(): SubscriptionCheckoutDto
    {
        return session()->get(SessionConstants::SUBSCRIPTION_CHECKOUT_DTO) ?? new SubscriptionCheckoutDto;
    }

    public function resetSubscriptionCheckoutDto(): SubscriptionCheckoutDto
    {
        session()->forget(SessionConstants::SUBSCRIPTION_CHECKOUT_DTO);

        return new SubscriptionCheckoutDto;
    }

    public function getCartDto(): CartDto
    {
        return session()->get(SessionConstants::CART_DTO) ?? new CartDto;
    }

    public function saveCartDto(CartDto $cartDto): void
    {
        session()->put(SessionConstants::CART_DTO, $cartDto);
    }

    public function clearCartDto(): CartDto
    {
        session()->forget(SessionConstants::CART_DTO);

        return new CartDto;
    }

    public function setCreateTenantForFreePlanUser(bool $shouldCreateTenantAfterRegistration): void
    {
        session()->put(SessionConstants::SHOULD_CREATE_TENANT_FOR_FREE_PLAN_USER, $shouldCreateTenantAfterRegistration);
    }

    public function shouldCreateTenantForFreePlanUser(): bool
    {
        return session()->get(SessionConstants::SHOULD_CREATE_TENANT_FOR_FREE_PLAN_USER, false);
    }

    public function resetCreateTenantForFreePlanUser()
    {
        session()->forget(SessionConstants::SHOULD_CREATE_TENANT_FOR_FREE_PLAN_USER);
    }

    public function saveSmsVerificationDto(SmsVerificationDto $smsVerificationDto): void
    {
        session()->put(SessionConstants::SMS_VERIFICATION_DTO, $smsVerificationDto);
    }

    public function getSmsVerificationDto(): ?SmsVerificationDto
    {
        return session()->get(SessionConstants::SMS_VERIFICATION_DTO);
    }

    public function clearSmsVerificationDto()
    {
        session()->forget(SessionConstants::SMS_VERIFICATION_DTO);
    }

    public function getCouponCode(): ?string
    {
        $data = session(SessionConstants::COUPON_CODE);

        if ($data === null) {
            return null;
        }

        if (! is_array($data) || ! isset($data['code'], $data['stored_at'])) {
            session()->forget(SessionConstants::COUPON_CODE);

            return null;
        }

        if (now()->diffInHours($data['stored_at'], absolute: true) >= 8) { // expire coupon code after 8 hours
            session()->forget(SessionConstants::COUPON_CODE);

            return null;
        }

        return $data['code'];
    }

    public function saveCouponCode(string $code): void
    {
        session([SessionConstants::COUPON_CODE => [
            'code' => $code,
            'stored_at' => now(),
        ]]);
    }

    public function clearCouponCode(): void
    {
        session()->forget(SessionConstants::COUPON_CODE);
    }
}
