<?php

namespace Rcv\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ModuleSetupCommand extends Command
{
    protected $signature = 'module:setup {name : The name of the module}';
    protected $description = 'Set up the folder structure for a new module';

    public function handle()
    {
        $name = $this->argument('name');
        $modulePath = base_path("Modules/{$name}");

        if (File::exists($modulePath)) {
            $this->error("Module '{$name}' already exists!");
            return;
        }

        $folders = [
            'Config',
            'Console',
            'Http/Controllers',
            'Http/Middleware',
            'Models',
            'Providers',
            'routes',
        ];

        foreach ($folders as $folder) {
            File::makeDirectory("{$modulePath}/{$folder}", 0755, true);
        }

        $this->info("Module '{$name}' structure created successfully.");
    }
}
