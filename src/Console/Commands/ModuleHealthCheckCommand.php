<?php

namespace Rcv\Core\Console\Commands;
use Illuminate\Console\Command;
use Rcv\Core\Services\ModuleHealthCheck;
use Rcv\Core\Services\MarketplaceService;

class ModuleHealthCheckCommand extends Command
{
    protected $signature = 'module:health {module? : The name of the module to check}';
    protected $description = 'Check the health of modules';

    protected $healthCheck;

    public function __construct(ModuleHealthCheck $healthCheck)
    {
        parent::__construct();
        $this->healthCheck = $healthCheck;
    }

    public function handle()
    {
        $moduleName = $this->argument('module');
        
        if ($moduleName) {
            $health = $this->healthCheck->checkModuleHealth($moduleName);
            $this->displayHealthResults($health);
        } else {
            $this->info('Checking health of all modules...');
            $results = $this->healthCheck->check();
            foreach ($results as $moduleName => $health) {
                $this->displayHealthResults($health);
            }
        }
    }

    protected function displayHealthResults($health)
    {
        $this->info("\nModule: {$health['name']}");
        $this->info("Status: {$health['status']}");
        
        foreach ($health['checks'] as $check => $result) {
            $this->line("  {$check}: {$result['status']}");
            if (isset($result['message'])) {
                $this->line("    {$result['message']}");
            }
            if (!empty($result['details'])) {
                $this->line("    Details: " . json_encode($result['details']));
            }
        }
    }
} 