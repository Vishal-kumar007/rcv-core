<?php

namespace Rcv\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MigrateV1ModulesToV2 extends Command
{
    protected $signature = 'module:v2:migrate';   // php artisan module:v2:migrate 
    
    protected $description = 'Migrate laravel-modules v1 modules to v2 structure';

    public function handle()
    {
        $oldPath = base_path('modules');
        $newPath = base_path('Modules');

        if (!File::exists($oldPath)) {
            $this->error("The 'modules' folder does not exist.");
            return;
        }

        if (!File::exists($newPath)) {
            File::makeDirectory($newPath, 0755, true);
        }

        $modules = File::directories($oldPath);

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            $targetPath = $newPath . '/' . $moduleName;

            if (File::exists($targetPath)) {
                $this->warn("Module already exists in v2 path: {$targetPath}");
                continue;
            }

            File::moveDirectory($modulePath, $targetPath);
            $this->info("Migrated: {$moduleName}");
        }

        // (Optional) Generate migration helper class
        $helperClass = $newPath . '/ModuleMigrationHelper.php';
        if (!File::exists($helperClass)) {
            $stubPath = __DIR__ . '/stubs/migration-helper.stub';
            if (File::exists($stubPath)) {
                $stub = file_get_contents($stubPath);
                File::put($helperClass, $stub);
                $this->info("Created migration helper: ModuleMigrationHelper.php");
            }
        }

        $this->info('Migration complete.');
    }
}
