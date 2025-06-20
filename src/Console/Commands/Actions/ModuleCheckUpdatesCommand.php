<?php

namespace Rcv\Core\Console\Commands\Actions;

use Illuminate\Console\Command;
use Rcv\Core\Services\MarketplaceService;

class ModuleCheckUpdatesCommand extends Command
{
    protected $signature = 'module:check-updates';
    protected $description = 'Check for available module updates';

    protected $marketplaceService;

    public function __construct(MarketplaceService $marketplaceService)
    {
        parent::__construct();
        $this->marketplaceService = $marketplaceService;
    }

    public function handle()
    {
        $this->info('Checking for module updates...');

        try {
            $updates = $this->marketplaceService->checkForUpdates();

            if (empty($updates)) {
                $this->info('All modules are up to date.');
                return 0;
            }

            $this->info('The following updates are available:');
            $this->table(
                ['Module', 'Current Version', 'Available Version'],
                collect($updates)->map(function ($update) {
                    return [
                        $update['name'],
                        $update['current_version'],
                        $update['version'],
                    ];
                })
            );

            if ($this->confirm('Would you like to update these modules now?')) {
                foreach ($updates as $update) {
                    $this->call('module:marketplace', [
                        'action' => 'update',
                        'name' => $update['name'],
                    ]);
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to check for updates: {$e->getMessage()}");
            return 1;
        }
    }
} 