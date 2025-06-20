<?php

namespace Rcv\Core\Console\Commands\Actions;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class ModuleEnableCommand extends Command
{
    protected $signature = 'module:enable {name : The name of the module}';

    protected $description = 'Enable a module from modules or vendor/rcv';

    public function handle()
    {
        $name = $this->argument('name');
        $this->info("Enabling module [{$name}]...");

        try {
            // Determine the module path (modules/ or vendor/rcv/)
            $modulePath = base_path("modules/{$name}");
            $isVendor = false;

            if (!File::exists($modulePath)) {
                $vendorPath = base_path("vendor/rcv/{$name}");
                if (File::exists($vendorPath)) {
                    $modulePath = $vendorPath;
                    $isVendor = true;
                } else {
                    $this->error("Module [{$name}] not found in 'modules/' or 'vendor/rcv/' directories.");
                    return 1;
                }
            }

            // Prepare default values
            $version = '1.0.0';
            $description = "{$name} module for the application";

            // Try to read from module.json if exists
            $moduleJsonPath = "{$modulePath}/module.json";
            if (File::exists($moduleJsonPath)) {
                $json = json_decode(File::get($moduleJsonPath), true);
                $version = $json['version'] ?? $version;
                $description = $json['description'] ?? $description;
            }

            // Update or create DB record
            $existing = DB::table('module_states')->where('name', $name)->first();
            if ($existing) {
                DB::table('module_states')
                    ->where('name', $name)
                    ->update([
                        'enabled' => true,
                        'status' => 'enabled',
                        'last_enabled_at' => now(),
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('module_states')->insert([
                    'name' => $name,
                    'version' => $version,
                    'description' => $description,
                    'enabled' => true,
                    'status' => 'enabled',
                    'last_enabled_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Optionally update the module.json to reflect enabled status
            if (File::exists($moduleJsonPath)) {
                $json['enabled'] = true;
                $json['last_enabled_at'] = now()->toIso8601String();
                File::put($moduleJsonPath, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }

            // Composer dump-autoload and Laravel package discovery
            $this->info('Running composer dump-autoload...');
            exec('composer dump-autoload');

            $this->info('Running package discovery...');
            $this->call('package:discover');

            $this->info("Module [{$name}] enabled successfully.");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to enable module [{$name}]: " . $e->getMessage());
            return 1;
        }
    }
}
