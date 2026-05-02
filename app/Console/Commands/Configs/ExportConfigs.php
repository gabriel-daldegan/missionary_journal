<?php

namespace App\Console\Commands\Configs;

use App\Services\ConfigService;
use Illuminate\Console\Command;

class ExportConfigs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:export-configs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export the app configs from database to cache.';

    /**
     * Execute the console command.
     */
    public function handle(ConfigService $configService)
    {
        $configService->exportAllConfigs();

        $this->info('Configs exported to cache successfully.');
    }
}
