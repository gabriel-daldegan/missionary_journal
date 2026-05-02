<?php

namespace App\Console\Commands\Subscriptions;

use App\Mail\Subscription\LocalSubscriptionExpiringSoon;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class LocalSubscriptionExpiringSoonReminder extends Command
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:local-subscription-expiring-soon-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a reminder to local subscriptions that are expiring soon.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $firstReminderDays = config('app.trial_without_payment.first_reminder_days');
        $secondReminderDays = config('app.trial_without_payment.second_reminder_days');
        $firstReminderEnabled = config('app.trial_without_payment.first_reminder_enabled');
        $secondReminderEnabled = config('app.trial_without_payment.second_reminder_enabled');

        if ($firstReminderEnabled) {
            $subscriptions = $this->subscriptionService->getLocalSubscriptionExpiringIn($firstReminderDays);

            foreach ($subscriptions as $subscription) {
                $user = User::find($subscription->user_id);
                Mail::to($user->email)->send(new LocalSubscriptionExpiringSoon($subscription));
            }
        }

        if ($secondReminderEnabled) {
            $subscriptions = $this->subscriptionService->getLocalSubscriptionExpiringIn($secondReminderDays);

            foreach ($subscriptions as $subscription) {
                $user = User::find($subscription->user_id);
                Mail::to($user->email)->send(new LocalSubscriptionExpiringSoon($subscription));
            }
        }
    }
}
