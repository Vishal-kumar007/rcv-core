<?php

namespace Rcv\Core\Console\Commands\Database\Seeders;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ModuleSeedCommand extends Command
{
    protected $signature = 'module:seed {module : The name of the module} {--class= : The seeder class to run}';
    protected $description = 'Seed the specific module’s database seeds';

    public function handle()
    {
        $module = $this->argument('module');
        $seederClass = $this->option('class');

        $modulePath = base_path("Modules/{$module}");

        if (!File::exists($modulePath)) {
            $this->error("Module '{$module}' does not exist.");
            return 1;
        }

        if ($seederClass) {
            $fullSeederClass = "Modules\\{$module}\\Database\\Seeders\\{$seederClass}";
        } else {
            $fullSeederClass = "Modules\\{$module}\\Database\\Seeders\\{$module}DatabaseSeeder";
        }

        if (!class_exists($fullSeederClass)) {
            $this->error("Seeder class {$fullSeederClass} not found.");
            return 1;
        }

        $this->info("Seeding: {$fullSeederClass}");
       Artisan::call('migrate:rollback', [
    '--path' => "modules/{$module}/src/database/migrations"
]);

        $this->info(Artisan::output());
        return 0;
    }
}
