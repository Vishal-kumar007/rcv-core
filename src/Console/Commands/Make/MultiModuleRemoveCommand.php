<?php

namespace Rcv\Core\Console\Commands\Make;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MultiModuleRemoveCommand extends Command
{
    protected $signature = 'module:remove {names}';
    protected $description = 'Remove (delete) one or more modules';

    public function handle()
    {
        $modules = array_map('trim', explode(',', $this->argument('names')));

        foreach ($modules as $module) {
            $modulePath = base_path("modules/{$module}");

            if (!File::exists($modulePath)) {
                $this->error(" Module [$module] not found.");
                continue;
            }

            File::deleteDirectory($modulePath);
            $this->info(" Module [$module] has been removed.");
        }

        $this->info(" Running composer dump-autoload...");
        exec('composer dump-autoload');
    }
}
