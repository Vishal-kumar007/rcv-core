<?php

namespace Rcv\Core\Console\Commands\Actions;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Rcv\Core\Models\ModuleState;

class ModuleDisableCommand extends Command
{
    protected $signature = 'module:disable {name : The name of the module} {--force : Force disable even if there are dependencies} {--remove : Remove the module completely}';
    protected $description = 'Disable a module';

    public function handle()
    {
        $name = $this->argument('name');
        $force = $this->option('force');
        $remove = $this->option('remove');
        $modulePath = base_path("modules/{$name}");

        $this->info("Disabling module [{$name}]...");

        try {
            // Check if module exists
            if (!File::exists($modulePath)) {
                $this->error("Module [{$name}] not found in modules directory");
                return 1;
            }

            // Check if module state exists
            $moduleState = DB::table('module_states')->where('name', $name)->first();
            if (!$moduleState) {
                $this->error("Module [{$name}] is not registered");
                return 1;
            }

            // Check dependencies
            if (!$force) {
                $dependentModules = $this->checkDependencies($name);
                if (!empty($dependentModules)) {
                    $this->error("Cannot disable module [{$name}] as it is required by: " . implode(', ', $dependentModules));
                    $this->info("Use --force to disable anyway");
                    return 1;
                }
            }

            // Update module state
            DB::table('module_states')
                ->where('name', $name)
                ->update([
                    'enabled' => false,
                    'status' => 'disabled',
                    'last_disabled_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->updateModuleJsonState($name);

            // Run composer dump-autoload
            $this->info('Running composer dump-autoload...');
            exec('composer dump-autoload');

            // Rollback migrations with error handling
            if (File::exists("{$modulePath}/src/Database/Migrations")) {
                $this->info("Rolling back migrations...");

                $appliedMigrations = json_decode($moduleState->applied_migrations ?? '[]', true);
                $failedMigrations = json_decode($moduleState->failed_migrations ?? '[]', true);

                $migrations = array_reverse($appliedMigrations);
                $rolledBackMigrations = [];
                $failedRollbacks = [];

                foreach ($migrations as $migration) {
                    try {
                        $this->call('migrate:rollback', [
                            '--path' => "modules/{$name}/src/Database/Migrations/{$migration}"
                        ]);
                        $rolledBackMigrations[] = $migration;
                    } catch (\Exception $e) {
                        if (!$force) {
                            throw $e;
                        }
                        $failedRollbacks[] = $migration;
                        $this->warn("Migration rollback failed but continuing due to --force: {$migration}");
                    }
                }

                // Update module state
                DB::table('module_states')
                    ->where('name', $name)
                    ->update([
                        'status' => 'disabled',
                        'applied_migrations' => json_encode(array_diff($appliedMigrations, $rolledBackMigrations)),
                        'failed_migrations' => json_encode(array_merge($failedMigrations, $failedRollbacks)),
                        'last_disabled_at' => now()
                    ]);
            }
            
            if ($remove) {
                $this->info("Removing module [{$name}]...");

                // Remove from modules.php
                $this->removeFromModulesConfig($name);

                // Remove module files
                if (File::exists($modulePath)) {
                    File::deleteDirectory($modulePath);
                }

                // Remove from database
                DB::table('module_states')->where('name', $name)->delete();

                // Remove from composer.json
                $this->removeFromComposer($name);

                $this->info("Module [{$name}] has been completely removed from the system");
            } else {
                $this->info("Module [{$name}] has been disabled");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to disable module [{$name}]: " . $e->getMessage());
            return 1;
        }
    }

    protected function checkDependencies($name)
    {
        $dependentModules = [];
        $modules = File::directories(base_path('modules'));

        foreach ($modules as $modulePath) {
            $composerJson = "{$modulePath}/composer.json";
            if (File::exists($composerJson)) {
                $config = json_decode(File::get($composerJson), true);
                if (isset($config['require']["modules/" . strtolower($name)])) {
                    $dependentModules[] = basename($modulePath);
                }
            }
        }

        return $dependentModules;
    }

    protected function removeFromModulesConfig($name)
    {
        $configPath = base_path('modules/Core/src/Config/modules.php');
        if (File::exists($configPath)) {
            $config = require $configPath;
            if (isset($config['modules'])) {
                $providerClass = "Modules\\{$name}\\Providers\\{$name}ServiceProvider::class";
                $config['modules'] = array_filter($config['modules'], function ($provider) use ($providerClass) {
                    return $provider !== $providerClass;
                });

                $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
                File::put($configPath, $content);
            }
        }
    }

    protected function removeFromComposer($name)
    {
        $composerPath = base_path('composer.json');
        if (File::exists($composerPath)) {
            $composer = json_decode(File::get($composerPath), true);

            // Remove from autoload
            if (isset($composer['autoload']['psr-4']["Modules\\{$name}\\"])) {
                unset($composer['autoload']['psr-4']["Modules\\{$name}\\"]);
            }

            // Remove from repositories
            if (isset($composer['repositories'])) {
                $composer['repositories'] = array_filter($composer['repositories'], function ($repo) use ($name) {
                    return !isset($repo['url']) || $repo['url'] !== "modules/{$name}";
                });
            }

            File::put($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    protected function updateModuleJsonState($name)
    {
        $moduleJsonPath = base_path("modules/{$name}/module.json");

        if (File::exists($moduleJsonPath)) {
            $moduleJson = json_decode(File::get($moduleJsonPath), true);
            $moduleJson['enabled'] = false;
            $moduleJson['last_enabled_at'] = now()->toIso8601String();

            File::put($moduleJsonPath, json_encode($moduleJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }
}
