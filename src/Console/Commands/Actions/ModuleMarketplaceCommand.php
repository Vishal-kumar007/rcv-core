<?php

namespace Rcv\Core\Console\Commands\Actions;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Rcv\Core\Models\ModuleState;
use Rcv\Core\Services\MarketplaceService;

class ModuleMarketplaceCommand extends Command
{
    protected $signature = 'module:marketplace
        {action : The action to perform (list|install|remove|update|cleanup)}
        {name? : The name of the module}
        {--force : Force the action}';

    protected $description = 'Manage modules through the marketplace';

    protected MarketplaceService $marketplaceService;

    public function __construct(MarketplaceService $marketplaceService)
    {
        parent::__construct();
        $this->marketplaceService = $marketplaceService;
    }

    public function handle(): int
    {
        $action = $this->argument('action');
        $name = $this->argument('name');
        $force = $this->option('force');

        return match ($action) {
            'list' => $this->listModules(),
            'install' => $this->installModule($name),
            'remove' => $this->removeModule($name, $force),
            'update' => $this->updateModule($name),
            'cleanup' => $this->cleanup(),
            default => $this->error("Unknown action: {$action}") || 1,
        };
    }

    protected function listModules(): int
    {
        try {
            $modules = DB::table('module_states')->get();

            if ($modules->isEmpty()) {
                $this->info('No modules found.');
                return 0;
            }

            $this->table(
                ['Name', 'Version', 'Description', 'Status'],
                $modules->map(fn($m) => [
                    $m->name,
                    $m->version,
                    $m->description,
                    $m->enabled ? 'enabled' : 'disabled',
                ])->toArray()
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to list modules: {$e->getMessage()}");
            return 1;
        }
    }

    protected function installModule(?string $name): int
    {
        if (!$name) {
            $this->error('Module name is required.');
            return 1;
        }

        try {
            $modulePath = $this->resolveModulePath($name);
            if (!$modulePath) {
                $this->error("Module [{$name}] not found.");
                return 1;
            }

            $this->info("Installing module [{$name}]...");

            $this->call('module:enable', ['name' => $name]);
            $this->runComposerDumpAutoload();

            $this->runModuleMigrations($name, $modulePath);

            $this->info("Module [{$name}] installed successfully.");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to install module [{$name}]: {$e->getMessage()}");
            return 1;
        }
    }

    protected function updateModule(?string $name): int
    {
        if (!$name) {
            $this->error('Module name is required.');
            return 1;
        }

        try {
            $module = $this->marketplaceService->getModuleDetails($name);
            if ($module['status'] !== 'enabled') {
                $this->error("Module [{$name}] is not currently enabled.");
                return 1;
            }

            $this->info("Updating module [{$name}]...");
            $this->call('module:disable', ['name' => $name]);
            $this->call('module:enable', ['name' => $name]);

            $this->info("Module [{$name}] updated successfully.");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to update module [{$name}]: {$e->getMessage()}");
            return 1;
        }
    }

    protected function removeModule(?string $name, bool $force = false): int
    {
        if (!$name) {
            $this->error('Module name is required.');
            return 1;
        }

        try {
            $this->info("Removing module [{$name}]...");

            $moduleState = DB::table('module_states')->where('name', $name)->first();
            if ($moduleState && $moduleState->enabled) {
                $this->call('module:disable', ['name' => $name, '--remove' => true]);
            }

            $modulePath = base_path("modules/{$name}");
            if (File::exists($modulePath)) {
                File::deleteDirectory($modulePath);
            }

            $this->removeFromModulesConfig($name);
            $this->removeFromComposer($name);
            $this->removeFromCoreConfig($name);

            $this->runComposerDumpAutoload(true);
            DB::table('module_states')->where('name', $name)->delete();

            $this->info("Module [{$name}] has been completely removed.");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to remove module: {$e->getMessage()}");
            return 1;
        }
    }

    protected function cleanup(): int
    {
        try {
            $this->info('Cleaning up orphaned module states...');

            $states = ModuleState::all();
            $removed = 0;

            foreach ($states as $state) {
                if (!File::exists(base_path("modules/{$state->name}")) &&
                    !File::exists(base_path("vendor/rcv/{$state->name}"))) {
                    $this->info("Removing state for missing module [{$state->name}]...");
                    $state->delete();
                    $removed++;
                }
            }

            $this->info($removed
                ? "Removed {$removed} orphaned module state(s)."
                : 'No orphaned module states found.');

            return 0;
        } catch (\Exception $e) {
            $this->error("Cleanup failed: {$e->getMessage()}");
            return 1;
        }
    }

    // Helpers

    protected function resolveModulePath(string $name): ?string
    {
        $paths = [
            base_path("modules/{$name}"),
            base_path("vendor/rcv/{$name}"),
        ];

        foreach ($paths as $path) {
            if (File::exists($path)) {
                return $path;
            }
        }

        return null;
    }

    protected function runModuleMigrations(string $name, string $modulePath): void
    {
        $migrationsPath = "{$modulePath}/src/Database/Migrations";

        if (!File::exists($migrationsPath)) return;

        foreach (File::glob("{$migrationsPath}/*.php") as $file) {
            $migrationName = pathinfo($file, PATHINFO_FILENAME);
            if (!$this->migrationExists($migrationName)) {
                $this->runMigration($file, $migrationName);
            }
        }
    }

    protected function migrationExists(string $name): bool
    {
        return DB::table('migrations')->where('migration', $name)->exists();
    }

    protected function runMigration(string $file, string $name): void
    {
        $migration = include $file;
        if (is_object($migration) && method_exists($migration, 'up')) {
            $migration->up();
            DB::table('migrations')->insert([
                'migration' => $name,
                'batch' => DB::table('migrations')->max('batch') + 1,
            ]);
        }
    }

    protected function runComposerDumpAutoload(bool $optimize = false): void
    {
        exec('composer dump-autoload' . ($optimize ? ' -o' : ''));
    }

    protected function removeFromModulesConfig(string $name): void
    {
        $path = base_path('modules/Core/src/Config/modules.php');
        if (!File::exists($path)) return;

        $config = require $path;
        $providerClass = "Modules\\{$name}\\Providers\\{$name}ServiceProvider::class";

        $config['modules'] = array_values(array_filter(
            $config['modules'] ?? [],
            fn($provider) => $provider !== $providerClass
        ));

        File::put($path, "<?php\n\nreturn " . var_export($config, true) . ";\n");
    }

    protected function removeFromComposer(string $name): void
    {
        $composerPath = base_path('composer.json');
        if (!File::exists($composerPath)) return;

        $composer = json_decode(File::get($composerPath), true);

        unset($composer['autoload']['psr-4']["Modules\\{$name}\\"]);
        $composer['repositories'] = array_values(array_filter(
            $composer['repositories'] ?? [],
            fn($repo) => !isset($repo['url']) || $repo['url'] !== "modules/{$name}"
        ));
        unset($composer['require']["modules/" . strtolower($name)]);

        File::put($composerPath, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected function removeFromCoreConfig(string $name): void
    {
        $path = base_path('modules/Core/src/Config/config.php');
        if (!File::exists($path)) return;

        $config = require $path;
        $config['modules'] = array_values(array_filter(
            $config['modules'] ?? [],
            fn($module) => $module !== $name
        ));

        File::put($path, "<?php\n\nreturn " . var_export($config, true) . ";\n");
    }
}
