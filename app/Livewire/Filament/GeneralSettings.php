<?php

namespace App\Livewire\Filament;

use App\Models\EmailProvider;
use App\Models\VerificationProvider;
use App\Services\ConfigService;
use App\Services\CurrencyService;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class GeneralSettings extends Component implements HasForms
{
    private ConfigService $configService;

    use InteractsWithForms;

    public ?array $data = [];

    public function render()
    {
        return view('livewire.filament.general-settings');
    }

    public function boot(ConfigService $configService): void
    {
        $this->configService = $configService;
    }

    public function mount(): void
    {
        $this->form->fill([
            'site_name' => $this->configService->get('app.name'),
            'description' => $this->configService->get('app.description'),
            'support_email' => $this->configService->get('app.support_email'),
            'date_format' => $this->configService->get('app.date_format'),
            'datetime_format' => $this->configService->get('app.datetime_format'),
            'default_currency' => $this->configService->get('app.default_currency'),
            'google_tracking_id' => $this->configService->get('app.google_tracking_id'),
            'tracking_scripts' => $this->configService->get('app.tracking_scripts'),
            'payment_proration_enabled' => $this->configService->get('app.payment.proration_enabled'),
            'default_email_provider' => $this->configService->get('mail.default'),
            'default_email_from_name' => $this->configService->get('mail.from.name'),
            'default_email_from_email' => $this->configService->get('mail.from.address'),
            'show_subscriptions' => $this->configService->get('app.customer_dashboard.show_subscriptions', true),
            'show_orders' => $this->configService->get('app.customer_dashboard.show_orders', true),
            'show_transactions' => $this->configService->get('app.customer_dashboard.show_transactions', true),
            'social_links_facebook' => $this->configService->get('app.social_links.facebook') ?? '',
            'social_links_x' => $this->configService->get('app.social_links.x') ?? '',
            'social_links_linkedin' => $this->configService->get('app.social_links.linkedin-openid') ?? '',
            'social_links_instagram' => $this->configService->get('app.social_links.instagram') ?? '',
            'social_links_youtube' => $this->configService->get('app.social_links.youtube') ?? '',
            'social_links_github' => $this->configService->get('app.social_links.github') ?? '',
            'social_links_discord' => $this->configService->get('app.social_links.discord') ?? '',
            'roadmap_enabled' => $this->configService->get('app.roadmap_enabled', true),
            'recaptcha_enabled' => $this->configService->get('app.recaptcha_enabled', false),
            'recaptcha_api_site_key' => $this->configService->get('recaptcha.api_site_key', ''),
            'recaptcha_api_secret_key' => $this->configService->get('recaptcha.api_secret_key', ''),
            'otp_login_enabled' => $this->configService->get('app.otp_login_enabled', false),
            'cookie_consent_enabled' => $this->configService->get('cookie-consent.enabled', false),
            'two_factor_auth_enabled' => $this->configService->get('app.two_factor_auth_enabled', false),
            'trial_without_payment_enabled' => $this->configService->get('app.trial_without_payment.enabled', false),
            'trial_first_reminder_enabled' => $this->configService->get('app.trial_without_payment.first_reminder_enabled', true),
            'trial_second_reminder_enabled' => $this->configService->get('app.trial_without_payment.second_reminder_enabled', true),
            'trial_first_reminder_days' => $this->configService->get('app.trial_without_payment.first_reminder_days'),
            'trial_without_payment_sms_verification_enabled' => $this->configService->get('app.trial_without_payment.sms_verification_enabled'),
            'trial_second_reminder_days' => $this->configService->get('app.trial_without_payment.second_reminder_days'),
            'limit_user_trials_enabled' => $this->configService->get('app.limit_user_trials.enabled'),
            'default_verification_provider' => $this->configService->get('app.verification.default_provider'),
            'anonymization_enabled' => $this->configService->get('app.anonymization_enabled', false),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()->tabs([
                    Tab::make(__('Application'))
                        ->icon('heroicon-o-globe-alt')
                        ->schema([
                            TextInput::make('site_name')
                                ->label(__('Site Name'))
                                ->required(),
                            Textarea::make('description')
                                ->helperText(__('This will be used as the meta description for your site (for pages that have no description).')),
                            TextInput::make('support_email')
                                ->label(__('Support Email'))
                                ->required()
                                ->email(),
                            TextInput::make('date_format')
                                ->label(__('Date Format'))
                                ->rules([
                                    function () {
                                        return function (string $attribute, $value, Closure $fail) {
                                            // make sure that the date format is valid
                                            $timestamp = strtotime('2021-01-01');
                                            $date = date($value, $timestamp);

                                            if ($date === false) {
                                                $fail(__('The :attribute is invalid.'));
                                            }
                                        };
                                    },
                                ])
                                ->required(),
                            TextInput::make('datetime_format')
                                ->label(__('Date Time Format'))
                                ->rules([
                                    function () {
                                        return function (string $attribute, $value, Closure $fail) {
                                            // make sure that the date format is valid
                                            $timestamp = strtotime('2021-01-01 00:00:00');
                                            $date = date($value, $timestamp);

                                            if ($date === false) {
                                                $fail(__('The :attribute is invalid.'));
                                            }
                                        };
                                    },
                                ])
                                ->required(),
                        ]),
                    Tab::make(__('Payment'))
                        ->icon('heroicon-o-credit-card')
                        ->schema([
                            Select::make('default_currency')
                                ->label(__('Default Currency'))
                                ->options(function (CurrencyService $currencyService) {
                                    $currencies = [];
                                    foreach ($currencyService->getAllCurrencies() as $currency) {
                                        $currencies[$currency->code] = $currency->name.' ('.$currency->code.')';
                                    }

                                    return $currencies;
                                })
                                ->helperText(__('This is the currency that will be used for all payments.'))
                                ->required()
                                ->searchable(),
                            Toggle::make('payment_proration_enabled')
                                ->label(__('Payment Proration Enabled'))
                                ->helperText(__('If enabled, when a customer upgrades or downgrades their subscription, the amount they have already paid will be prorated and credited towards their new plan.')),
                        ]),
                    Tab::make(__('Email'))
                        ->icon('heroicon-o-envelope')
                        ->schema([
                            Select::make('default_email_provider')
                                ->label(__('Default Email Provider'))
                                ->options(function () {
                                    $providers = [
                                        'smtp' => 'SMTP',
                                    ];

                                    foreach (EmailProvider::all() as $provider) {
                                        $providers[$provider->slug] = $provider->name;
                                    }

                                    return $providers;
                                })
                                ->helperText(__('This is the email provider that will be used for all emails.'))
                                ->required()
                                ->searchable(),
                            TextInput::make('default_email_from_name')
                                ->label(__('Default "From" Email Name'))
                                ->helperText(__('This is the name that will be used as the "From" name for all emails.'))
                                ->required(),
                            TextInput::make('default_email_from_email')
                                ->label(__('Default "From" Email Address'))
                                ->helperText(__('This is the email address that will be used as the "From" address for all emails.'))
                                ->required()
                                ->email(),
                        ]),
                    Tab::make(__('Verification'))
                        ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
                        ->schema([
                            Select::make('default_verification_provider')
                                ->label(__('Default Verification Provider'))
                                ->options(function () {
                                    $providers = [];

                                    foreach (VerificationProvider::all() as $provider) {
                                        $providers[$provider->slug] = $provider->name;
                                    }

                                    return $providers;
                                })
                                ->helperText(__('This is the verification provider that will be used for all user phone SMS verifications.'))
                                ->required()
                                ->searchable(),
                        ]),
                    Tab::make(__('Analytics & Cookies'))
                        ->icon('heroicon-o-squares-2x2')
                        ->schema([
                            Toggle::make('cookie_consent_enabled')
                                ->label(__('Cookie Consent Bar Enabled'))
                                ->helperText(__('If enabled, the cookie consent bar will be shown to users.')),
                            TextInput::make('google_tracking_id')
                                ->helperText(__('Google analytics will only be inserted if either "Cookie Consent Bar" is disabled or in case user has consented to cookies.'))
                                ->label(__('Google Tracking ID')),
                            Textarea::make('tracking_scripts')
                                ->helperText(__('Paste in any other analytics or tracking scripts here. Those scripts will only be inserted if either "Cookie Consent Bar" is disabled or in case user has consented to cookies.'))
                                ->label(__('Other Tracking Scripts')),
                        ]),
                    Tab::make(__('Subscription Trials'))
                        ->icon('heroicon-s-eye-dropper')
                        ->schema([
                            Section::make(__('Trials without Payment'))->schema([
                                Toggle::make('trial_without_payment_enabled')
                                    ->label(__('Trial Without Payment Enabled'))
                                    ->helperText(__('If enabled, customers will be able to start subscription trials without entering payment details, and later they can enter payment details to continue their subscription.'))
                                    ->required(),
                                Toggle::make('trial_first_reminder_enabled')
                                    ->label(__('First Reminder Enabled'))
                                    ->helperText(__('If enabled, a reminder email will be sent to the user when the trial is ending soon.'))
                                    ->live()
                                    ->required(),
                                TextInput::make('trial_first_reminder_days')
                                    ->label(__('First Reminder Days'))
                                    ->helperText(__('This email will remind the user that the trial is ending soon. Enter the number of days before the trial ends that the first reminder email will be sent.'))
                                    ->disabled(fn ($get) => ! $get('trial_first_reminder_enabled'))
                                    ->integer(),
                                Toggle::make('trial_second_reminder_enabled')
                                    ->label(__('Second Reminder Enabled'))
                                    ->helperText(__('If enabled, a second reminder email will be sent to the user when the trial is ending soon.'))
                                    ->live()
                                    ->required(),
                                TextInput::make('trial_second_reminder_days')
                                    ->label(__('Second Reminder Days'))
                                    ->helperText(__('Enter the number of days before the trial ends that the second reminder email will be sent.'))
                                    ->disabled(fn ($get) => ! $get('trial_second_reminder_enabled'))
                                    ->integer(),
                                Toggle::make('trial_without_payment_sms_verification_enabled')
                                    ->label(__('SMS Verification Enabled'))
                                    ->helperText(__('If enabled, users will be required to verify their phone number via SMS before starting a trial without payment (to prevent abuse).'))
                                    ->required(),
                            ]),
                            Section::make(__('Limit User Trials'))->schema([
                                Toggle::make('limit_user_trials_enabled')
                                    ->label(__('Limit User Trials Enabled'))
                                    ->helperText(__('If enabled, users will only be able to start a limited number of trials (to prevent abuse).'))
                                    ->live()
                                    ->required(),
                                TextInput::make('limit_user_trials_max_count')
                                    ->label(__('Maximum Trial Count'))
                                    ->helperText(__('Enter the maximum number of trials a user can start. If a user reaches this limit, they will not be able to start any more trials and they will be required to enter payment details to start subscription.'))
                                    ->disabled(fn ($get) => ! $get('limit_user_trials_enabled'))
                                    ->integer(),
                            ]),
                        ]),
                    Tab::make(__('Customer Dashboard'))
                        ->icon('heroicon-s-user')
                        ->schema([
                            Toggle::make('show_subscriptions')
                                ->label(__('Show Subscriptions'))
                                ->default(false)
                                ->helperText(__('If enabled, customers will be able to see their subscriptions on the dashboard.'))
                                ->required(),
                            Toggle::make('show_orders')
                                ->label(__('Show Orders'))
                                ->default(false)
                                ->helperText(__('If enabled, customers will be able to see their orders on the dashboard.'))
                                ->required(),
                            Toggle::make('show_transactions')
                                ->label(__('Show Transactions'))
                                ->default(false)
                                ->helperText(__('If enabled, customers will be able to see their transactions on the dashboard.'))
                                ->required(),
                        ]),
                    Tab::make(__('Roadmap'))
                        ->icon('heroicon-o-bug-ant')
                        ->schema([
                            Toggle::make('roadmap_enabled')
                                ->label(__('Roadmap Enabled'))
                                ->helperText(__('If enabled, the roadmap will be visible to the public.'))
                                ->required(),
                        ]),
                    Tab::make(__('Authentication & Security'))
                        ->icon('heroicon-c-shield-check')
                        ->schema([
                            Section::make()
                                ->schema([
                                    Toggle::make('two_factor_auth_enabled')
                                        ->label(__('Two Factor Authentication Enabled'))
                                        ->helperText(__('If enabled, users will be able to enable two factor authentication on their account. If disabled, the 2FA field will not be shown on the login form even for users who have it enabled.'))
                                        ->required(),
                                ]),
                            Section::make()
                                ->schema([
                                    Toggle::make('otp_login_enabled')
                                        ->label(__('One-Time Password Login Enabled'))
                                        ->helperText(__('If enabled, checkout forms will use one-time passwords sent via email instead of traditional passwords for login and registration.'))
                                        ->required(),
                                ]),
                            Section::make()
                                ->schema([
                                    Toggle::make('recaptcha_enabled')
                                        ->label(__('Recaptcha Enabled'))
                                        ->helperText(new HtmlString(__('If enabled, recaptcha will be used on the registration & login forms. For more info on how to configure Recaptcha, see the <a class="text-primary-500" href=":url" target="_blank">documentation</a>.', ['url' => 'https://saasykit.com/docs/recaptcha'])))
                                        ->required(),
                                    TextInput::make('recaptcha_api_site_key')
                                        ->label(__('Recaptcha Site Key')),
                                    TextInput::make('recaptcha_api_secret_key')
                                        ->label(__('Recaptcha Secret Key')),
                                ]),
                            Section::make()
                                ->schema([
                                    Toggle::make('anonymization_enabled')
                                        ->label(__('Anonymization Enabled'))
                                        ->helperText(__('If enabled, you will be able to anonymize user data from the user edit page.'))
                                        ->required(),
                                ]),
                        ]),
                    Tab::make(__('Social Links'))
                        ->icon('heroicon-o-heart')
                        ->schema([
                            TextInput::make('social_links_facebook')
                                ->label(__('Facebook')),
                            TextInput::make('social_links_x')
                                ->label(__('X (Twitter)')),
                            TextInput::make('social_links_linkedin')
                                ->label(__('LinkedIn')),
                            TextInput::make('social_links_instagram')
                                ->label(__('Instagram')),
                            TextInput::make('social_links_youtube')
                                ->label(__('YouTube')),
                            TextInput::make('social_links_github')
                                ->label(__('GitHub')),
                            TextInput::make('social_links_discord')
                                ->label(__('Discord')),
                        ]),
                ])
                    ->persistTabInQueryString('settings-tab'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->configService->set('app.name', $data['site_name']);
        $this->configService->set('app.description', $data['description']);
        $this->configService->set('app.support_email', $data['support_email']);
        $this->configService->set('app.date_format', $data['date_format']);
        $this->configService->set('app.datetime_format', $data['datetime_format']);
        $this->configService->set('app.default_currency', $data['default_currency']);
        $this->configService->set('app.google_tracking_id', $data['google_tracking_id'] ?? '');
        $this->configService->set('app.tracking_scripts', $data['tracking_scripts'] ?? '');
        $this->configService->set('app.payment.proration_enabled', $data['payment_proration_enabled']);
        $this->configService->set('mail.default', $data['default_email_provider']);
        $this->configService->set('mail.from.name', $data['default_email_from_name']);
        $this->configService->set('mail.from.address', $data['default_email_from_email']);
        $this->configService->set('app.customer_dashboard.show_subscriptions', $data['show_subscriptions']);
        $this->configService->set('app.customer_dashboard.show_orders', $data['show_orders']);
        $this->configService->set('app.customer_dashboard.show_transactions', $data['show_transactions']);
        $this->configService->set('app.social_links.facebook', $data['social_links_facebook']);
        $this->configService->set('app.social_links.x', $data['social_links_x']);
        $this->configService->set('app.social_links.linkedin-openid', $data['social_links_linkedin']);
        $this->configService->set('app.social_links.instagram', $data['social_links_instagram']);
        $this->configService->set('app.social_links.youtube', $data['social_links_youtube']);
        $this->configService->set('app.social_links.github', $data['social_links_github']);
        $this->configService->set('app.social_links.discord', $data['social_links_discord']);
        $this->configService->set('app.roadmap_enabled', $data['roadmap_enabled']);
        $this->configService->set('app.recaptcha_enabled', $data['recaptcha_enabled']);
        $this->configService->set('recaptcha.api_site_key', $data['recaptcha_api_site_key']);
        $this->configService->set('recaptcha.api_secret_key', $data['recaptcha_api_secret_key']);
        $this->configService->set('cookie-consent.enabled', $data['cookie_consent_enabled']);
        $this->configService->set('app.two_factor_auth_enabled', $data['two_factor_auth_enabled']);
        $this->configService->set('app.otp_login_enabled', $data['otp_login_enabled']);
        $this->configService->set('app.trial_without_payment.enabled', $data['trial_without_payment_enabled']);
        $this->configService->set('app.trial_without_payment.first_reminder_days', $data['trial_first_reminder_days'] ?? 3);
        $this->configService->set('app.trial_without_payment.second_reminder_days', $data['trial_second_reminder_days'] ?? 1);
        $this->configService->set('app.trial_without_payment.first_reminder_enabled', $data['trial_first_reminder_enabled']);
        $this->configService->set('app.trial_without_payment.second_reminder_enabled', $data['trial_second_reminder_enabled']);
        $this->configService->set('app.trial_without_payment.sms_verification_enabled', $data['trial_without_payment_sms_verification_enabled']);
        $this->configService->set('app.limit_user_trials.enabled', $data['limit_user_trials_enabled']);
        $this->configService->set('app.limit_user_trials.max_count', $data['limit_user_trials_max_count'] ?? 1);
        $this->configService->set('app.verification.default_provider', $data['default_verification_provider']);
        $this->configService->set('app.anonymization_enabled', $data['anonymization_enabled']);

        Notification::make()
            ->title(__('Settings Saved'))
            ->success()
            ->send();
    }
}
