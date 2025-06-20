<?php

namespace Rcv\Core\Console\Commands\Make;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MultiModuleMarketplaceCommand extends Command
{
    protected $signature = 'module:marketplace {action} {names}';
    protected $description = 'Marketplace tool to install, enable, or disable modules';

    public function handle()
    {
        $action = strtolower($this->argument('action'));
        $modules = array_map('trim', explode(',', $this->argument('names')));

        foreach ($modules as $module) {
            $modulePath = base_path("modules/{$module}");

            switch ($action) {
                case 'install':
                    $this->installModule($module);
                    break;
                case 'enable':
                    $this->enableModule($module);
                    break;
                case 'disable':
                    $this->disableModule($module);
                    break;
                default:
                    $this->error(" Unknown action: $action");
            }
        }

        $this->info(" Running composer dump-autoload...");
        exec('composer dump-autoload');
    }

    protected function installModule($module)
    {
        $targetPath = base_path("modules/{$module}");

        if (File::exists($targetPath)) {
            $this->warn(" Module [$module] already exists.");
            return;
        }

        // Simulate install
        File::makeDirectory($targetPath, 0755, true);
        File::put("$targetPath/module.json", json_encode([
            'name' => $module,
            'version' => '1.0.0',
            'enabled' => false
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info(" Installed module [$module]");
    }

    protected function enableModule($module)
    {
        $modulePath = base_path("modules/{$module}/module.json");

        if (!File::exists($modulePath)) {
            $this->error(" Module [$module] not found.");
            return;
        }

        $data = json_decode(File::get($modulePath), true);
        $data['enabled'] = true;
        File::put($modulePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info(" Enabled module [$module]");
    }

    protected function disableModule($module)
    {
        $modulePath = base_path("modules/{$module}/module.json");

        if (!File::exists($modulePath)) {
            $this->error(" Module [$module] not found.");
            return;
        }

        $data = json_decode(File::get($modulePath), true);
        $data['enabled'] = false;
        File::put($modulePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info(" Disabled module [$module]");
    }
}
