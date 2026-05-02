<?php

namespace App\Console\Commands;

use App\Services\TenantSubscriptionService;
use Illuminate\Console\Command;

class SyncSeatBasedSubscriptionQuantities extends Command
{
    public function __construct(
        private TenantSubscriptionService $tenantSubscriptionService,
    ) {
        parent::__construct();
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-seat-based-subscription-quantities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync seat based subscription quantities with provider to make sure that they are correct & up to date.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->tenantSubscriptionService->syncSubscriptionQuantities();
    }
}
