<?php

namespace Rcv\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DiscoverModulesCommand extends Command
{
    protected $signature = 'module:discover';
    protected $description = 'Compile a class that registers all discovered modules.';

    public function handle()
    {
        $modulesPath = base_path('Modules');
        $compiledPath = base_path('bootstrap/cache/modules.php');

        $modules = [];

        foreach (File::directories($modulesPath) as $modulePath) {
            $moduleName = basename($modulePath);
            $serviceProvider = "Modules\\$moduleName\\Providers\\{$moduleName}ServiceProvider";

            if (File::exists("$modulePath/Providers/{$moduleName}ServiceProvider.php")) {
                $modules[$moduleName] = $serviceProvider;
            }
        }

        $stub = File::get(__DIR__.'/stubs/module_compiled.stub');

        $compiledClass = str_replace(
            '{{modules}}',
            var_export($modules, true),
            $stub
        );

        File::ensureDirectoryExists(dirname($compiledPath));
        File::put($compiledPath, $compiledClass);

        $this->info('Modules discovered and compiled successfully!');
    }
}

