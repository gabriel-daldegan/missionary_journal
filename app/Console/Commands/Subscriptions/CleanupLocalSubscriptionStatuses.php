<?php

namespace App\Console\Commands\Subscriptions;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class CleanupLocalSubscriptionStatuses extends Command
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
    protected $signature = 'app:cleanup-local-subscription-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update local subscription statuses to inactive if they are expired';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->subscriptionService->cleanupLocalSubscriptionStatuses();
    }
}
