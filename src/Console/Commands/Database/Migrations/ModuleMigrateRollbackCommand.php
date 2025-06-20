<?php

namespace Rcv\Core\Console\Commands\Database\Migrations;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ModuleMigrateRollbackCommand extends Command
{
    protected $signature = 'module:migrate-rollback {--module= : The name of the module to rollback}';

    protected $description = 'Rollback migrations for a specific module or all modules';

    public function handle()
    {
        $module = $this->option('module');

        if ($module) {
            $this->rollbackModule($module);
        } else {
            $this->rollbackAllModules();
        }
    }

    protected function rollbackModule(string $module)
    {
        $path = base_path("modules/{$module}/src/Database/Migrations");

        if (!File::exists($path)) {
            $this->error("Migrations path not found for module: {$module}");
            return;
        }

        $this->info("Rolling back migrations for module: {$module}");
        Artisan::call('migrate:rollback', [
    '--path' => "modules/{$module}/src/database/migrations",
]);
        $this->line(Artisan::output());
    }

    protected function rollbackAllModules()
    {
        $modulesPath = base_path('modules');

        if (!File::exists($modulesPath)) {
            $this->error("Modules directory not found.");
            return;
        }

        $modules = File::directories($modulesPath);

        foreach ($modules as $modulePath) {
            $module = basename($modulePath);
            $this->rollbackModule($module);
        }
    }
}
