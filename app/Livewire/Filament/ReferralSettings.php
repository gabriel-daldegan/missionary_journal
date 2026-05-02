<?php

namespace App\Livewire\Filament;

use App\Constants\ReferralConstants;
use App\Models\Discount;
use App\Services\ConfigService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Component;

class ReferralSettings extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    private ConfigService $configService;

    public function render()
    {
        return view('livewire.filament.referral-settings');
    }

    public function boot(ConfigService $configService): void
    {
        $this->configService = $configService;
    }

    public function mount(): void
    {
        $this->form->fill([
            'referral_enabled' => $this->configService->get('app.referral.enabled', false),
            'referral_trigger' => $this->configService->get('app.referral.trigger', ReferralConstants::TRIGGER_VERIFIED_REGISTRATION),
            'referral_reward_type' => $this->configService->get('app.referral.reward_type', ReferralConstants::REWARD_TYPE_COUPON),
            'referral_discount_id' => $this->configService->get('app.referral.discount_id'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Referral System Configuration'))
                    ->description(__('Configure the referral program for your application.'))
                    ->schema([
                        Toggle::make('referral_enabled')
                            ->label(__('Enable Referral System'))
                            ->helperText(__('When enabled, users can refer others and earn rewards.'))
                            ->live()
                            ->required(),

                        Select::make('referral_trigger')
                            ->label(__('Reward Trigger'))
                            ->helperText(__('When should the referrer receive their reward?'))
                            ->options([
                                ReferralConstants::TRIGGER_VERIFIED_REGISTRATION => __('Verified User Registration (when referred user verifies email)'),
                                ReferralConstants::TRIGGER_FIRST_PAYMENT => __('First Payment (when referred user makes first payment)'),
                            ])
                            ->disabled(fn ($get) => ! $get('referral_enabled'))
                            ->required(),

                        Select::make('referral_reward_type')
                            ->label(__('Reward Type'))
                            ->helperText(__('How should referrers be rewarded?'))
                            ->options([
                                ReferralConstants::REWARD_TYPE_COUPON => __('Assign Coupon (auto-generate discount code)'),
                                ReferralConstants::REWARD_TYPE_CUSTOM_EVENT => __('Custom Event (dispatch ReferralSucceeded event)'),
                            ])
                            ->live()
                            ->disabled(fn ($get) => ! $get('referral_enabled'))
                            ->required(),

                        Select::make('referral_discount_id')
                            ->label(__('Discount for Coupon Reward'))
                            ->helperText(__('Select which discount to use when generating reward coupons.'))
                            ->options(function () {
                                return Discount::where('is_active', true)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->disabled(fn ($get) => ! $get('referral_enabled') || $get('referral_reward_type') !== ReferralConstants::REWARD_TYPE_COUPON)
                            ->required(fn ($get) => $get('referral_enabled') && $get('referral_reward_type') === ReferralConstants::REWARD_TYPE_COUPON)
                            ->visible(fn ($get) => $get('referral_reward_type') === ReferralConstants::REWARD_TYPE_COUPON),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->configService->set('app.referral.enabled', $data['referral_enabled']);
        $this->configService->set('app.referral.trigger', $data['referral_trigger'] ?? null);
        $this->configService->set('app.referral.reward_type', $data['referral_reward_type'] ?? null);
        $this->configService->set('app.referral.discount_id', $data['referral_discount_id'] ?? null);

        Notification::make()
            ->title(__('Referral Settings Saved'))
            ->success()
            ->send();
    }
}
