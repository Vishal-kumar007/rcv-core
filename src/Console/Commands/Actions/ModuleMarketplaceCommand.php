<?php

namespace Rcv\Core\Console\Commands\Actions;

use Illuminate\Console\Command;
use Rcv\Core\Models\ModuleState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Rcv\Core\Services\MarketplaceService;

class ModuleMarketplaceCommand extends Command
{
    protected $signature = 'module:marketplace {action : The action to perform (list|install|remove|update|cleanup)} {name? : The name of the module} {--force : Force the action}';
    protected $description = 'Manage modules through the marketplace';

    protected $marketplaceService;

    public function __construct(MarketplaceService $marketplaceService)
    {
        parent::__construct();
        $this->marketplaceService = $marketplaceService;
    }

    public function handle()
    {
        $action = $this->argument('action');
        $name = $this->argument('name');
        $force = $this->option('force');

        switch ($action) {
            case 'list':
                return $this->listModules();
            case 'install':
                return $this->installModule($name);
            case 'remove':
                return $this->removeModule($name, $force);
            case 'update':
                return $this->updateModule($name);
            case 'cleanup':
                return $this->cleanup();
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    protected function listModules()
    {
        try {
            $modules = DB::table('module_states')->get();

            if ($modules->isEmpty()) {
                $this->info('No modules found.');
                return 0;
            }

            $headers = ['Name', 'Version', 'Description', 'Status'];
            $rows = [];

            foreach ($modules as $module) {
                $rows[] = [
                    $module->name,
                    $module->version,
                    $module->description,
                    $module->enabled ? 'enabled' : 'disabled'
                ];
            }

            $this->table($headers, $rows);
        } catch (\Exception $e) {
            $this->error("Failed to list modules: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Install a module.
     *
     * @param string $name
     * @return bool
     */
    protected function installModule($name)
    {
        try {
            $this->info("Installing module [{$name}]...");

            // Enable the module first
            $this->info("Enabling module [{$name}]...");
            $this->call('module:enable', ['name' => $name]);

            // Run composer dump-autoload
            $this->info('Running composer dump-autoload...');
            $this->runComposerDumpAutoload();

            // Run migrations
            $this->info('Running migrations...');
            $migrationsPath = base_path("modules/{$name}/src/Database/Migrations");
            if (File::exists($migrationsPath)) {
                $migrationFiles = File::glob($migrationsPath . '/*.php');
                foreach ($migrationFiles as $file) {
                    $migrationName = pathinfo($file, PATHINFO_FILENAME);
                    if (!$this->migrationExists($migrationName)) {
                        try {
                            $this->runMigration($file);
                        } catch (\Exception $e) {
                            if (strpos($e->getMessage(), 'already exists') === false) {
                                throw $e;
                            }
                        }
                    }
                }
            }

            $this->info("Module [{$name}] installed successfully");
            return true;
        } catch (\Exception $e) {
            $this->error("Failed to install module [{$name}]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a migration exists in the migrations table.
     */
    protected function migrationExists(string $migration): bool
    {
        return DB::table('migrations')
            ->where('migration', $migration)
            ->exists();
    }

    /**
     * Run a specific migration file.
     */
    protected function runMigration(string $file): void
    {
        $migration = include $file;
        if (is_object($migration) && method_exists($migration, 'up')) {
            $migration->up();

            // Record the migration in the migrations table
            DB::table('migrations')->insert([
                'migration' => pathinfo($file, PATHINFO_FILENAME),
                'batch' =>
                DB::table('migrations')->max('batch') + 1,
            ]);
        }
    }

    /**
     * Run composer dump-autoload command.
     */
    protected function runComposerDumpAutoload()
    {
        exec('composer dump-autoload');
    }

    protected function updateModule($name)
    {
        if (!$name) {
            $this->error('Module name is required');
            return 1;
        }

        try {
            $moduleDetails = $this->marketplaceService->getModuleDetails($name);

            if ($moduleDetails['status'] !== 'enabled') {
                $this->error("Module [{$name}] is not installed");
                return 1;
            }

            $this->info("Updating module [{$name}]...");

            // Disable and re-enable the module to apply updates
            $this->call('module:disable', ['name' => $name]);
            $this->call('module:enable', ['name' => $name]);

            $this->info("Module [{$name}] updated successfully");
        } catch (\Exception $e) {
            $this->error("Failed to update module: {$e->getMessage()}");
            return 1;
        }
    }

    protected function removeModule($name, $force = false)
    {
        if (!$name) {
            $this->error('Module name is required');
            return 1;
        }

        try {
            $this->info("Removing module [{$name}]...");

            // First disable the module if it's enabled
            $moduleState = DB::table('module_states')->where('name', $name)->first();
            if ($moduleState && $moduleState->enabled) {
                $this->call('module:disable', ['name' => $name, '--remove' => true]);
            }

            // Remove the module directory
            $modulePath = base_path("modules/{$name}");
            if (File::exists($modulePath)) {
                File::deleteDirectory($modulePath);
            }

            // Remove from modules.php
            $this->removeFromModulesConfig($name);

            // Remove from composer.json
            $this->removeFromComposer($name);

            // Remove from Core module's config
            $this->removeFromCoreConfig($name);

            // Run composer dump-autoload to update autoloader
            $this->info('Running composer dump-autoload...');
            exec('composer dump-autoload -o');

            // Finally remove the module state
            DB::table('module_states')->where('name', $name)->delete();

            $this->info("Module [{$name}] has been completely removed from the system");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to remove module: {$e->getMessage()}");
            return 1;
        }
    }

    protected function removeFromModulesConfig($name)
    {
        $configPath = base_path('modules/Core/src/Config/modules.php');
        if (File::exists($configPath)) {
            $config = require $configPath;
            if (isset($config['modules'])) {
                $providerClass = "Modules\\{$name}\\Providers\\{$name}ServiceProvider::class";
                $config['modules'] = array_values(array_filter($config['modules'], function ($provider) use ($providerClass) {
                    return $provider !== $providerClass;
                }));

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
                $composer['repositories'] = array_values(array_filter($composer['repositories'], function ($repo) use ($name) {
                    return !isset($repo['url']) || $repo['url'] !== "modules/{$name}";
                }));
            }

            // Remove from require if exists
            if (isset($composer['require']["modules/" . strtolower($name)])) {
                unset($composer['require']["modules/" . strtolower($name)]);
            }

            File::put($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    protected function removeFromCoreConfig($name)
    {
        $configPath = base_path('modules/Core/src/Config/config.php');
        if (File::exists($configPath)) {
            $config = require $configPath;
            
            // Remove from modules array if exists
            if (isset($config['modules']) && is_array($config['modules'])) {
                $config['modules'] = array_values(array_filter($config['modules'], function($module) use ($name) {
                    return $module !== $name;
                }));
            }

            // Write back to config file
            $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
            File::put($configPath, $content);
        }
    }

    protected function cleanup()
    {
        try {
            $this->info('Cleaning up orphaned module states...');

            $modulePath = base_path('modules');
            $states = ModuleState::all();
            $removedCount = 0;

            foreach ($states as $state) {
                if (!File::exists("{$modulePath}/{$state->name}")) {
                    $this->info("Removing orphaned state for module [{$state->name}]...");
                    $state->delete();
                    $removedCount++;
                }
            }

            if ($removedCount > 0) {
                $this->info("Successfully removed {$removedCount} orphaned module state(s)");
            } else {
                $this->info('No orphaned module states found');
            }
        } catch (\Exception $e) {
            $this->error("Failed to cleanup module states: {$e->getMessage()}");
            return 1;
        }
    }
}
