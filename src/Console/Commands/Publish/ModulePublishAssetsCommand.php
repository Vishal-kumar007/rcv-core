<?php

namespace Rcv\Core\Console\Commands\Publish;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;


class ModulePublishAssetsCommand extends Command
{
    protected $signature = 'module:publish-assets {module? : The name of the module} {--force : Overwrite existing assets}';
    protected $description = 'Publish module assets to the public directory';

    public function handle()
    {
        $moduleName = $this->argument('module');
        $force = $this->option('force');

        if ($moduleName) {
            $this->publishModuleAssets($moduleName, $force);
        } else {
            $this->publishAllModulesAssets($force);
        }
    }

    public function publishModuleAssets($moduleName, $force = false)
    {
        $this->info("Publishing assets for module: {$moduleName}");

        $sourcePath = base_path("Modules/{$moduleName}/Resources/assets");
        $destinationPath = public_path("modules/{$moduleName}");

        // Ensure the destination directory exists
        File::ensureDirectoryExists($destinationPath);

        if (!File::exists($sourcePath)) {
            $this->error("No assets found for module: {$moduleName}");
            return;
        }

        $files = File::allFiles($sourcePath);
        foreach ($files as $file) {
            $destination = $destinationPath . '/' . $file->getRelativePathname();
            if (File::exists($destination) && !$force) {
                $this->info("Asset already exists: {$destination}");
                continue;
            }

            File::copy($file->getRealPath(), $destination);
            $this->info("Published: {$destination}");
        }

        $this->info("Assets for module {$moduleName} published successfully.");
    }

    public function publishAllModulesAssets($force = false)
    {
        $modules = File::directories(base_path('Modules'));

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            $this->publishModuleAssets($moduleName, $force);
        }
    }
}

