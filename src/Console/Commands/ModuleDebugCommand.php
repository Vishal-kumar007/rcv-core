<?php

namespace Rcv\Core\Console\Commands;

use Illuminate\Console\Command;
use Rcv\Core\Models\ModuleState;
use Illuminate\Support\Facades\DB;

class ModuleDebugCommand extends Command
{
    protected $signature = 'module:debug {module?}';
    protected $description = 'Debug module state information';

    public function handle()
    {
        $moduleName = $this->argument('module');

        if ($moduleName) {
            $this->debugModule($moduleName);
        } else {
            $this->debugAllModules();
        }
    }

    protected function debugModule($moduleName)
    {
        $moduleState = ModuleState::where('name', $moduleName)->first();
        
        if (!$moduleState) {
            $this->error("Module [{$moduleName}] not found in database");
            return;
        }

        $this->info("Module State for [{$moduleName}]:");
        $this->table(
            ['Field', 'Value'],
            [
                ['id', $moduleState->id],
                ['name', $moduleState->name],
                ['version', $moduleState->version],
                ['status', $moduleState->status],
                ['enabled', $moduleState->enabled ? 'true' : 'false'],
                ['last_enabled_at', $moduleState->last_enabled_at],
                ['last_disabled_at', $moduleState->last_disabled_at],
                ['created_at', $moduleState->created_at],
                ['updated_at', $moduleState->updated_at],
            ]
        );

        // Show raw database values
        $rawData = DB::table('module_states')->where('id', $moduleState->id)->first();
        $this->info("\nRaw Database Values:");
        $this->table(
            ['Field', 'Value'],
            collect((array)$rawData)->map(function($value, $key) {
                return [$key, is_null($value) ? 'NULL' : $value];
            })->toArray()
        );
    }

    protected function debugAllModules()
    {
        $modules = ModuleState::all();
        
        if ($modules->isEmpty()) {
            $this->error("No modules found in database");
            return;
        }

        $this->info("All Module States:");
        $this->table(
            ['Name', 'Status', 'Enabled', 'Last Enabled', 'Last Disabled'],
            $modules->map(function($module) {
                return [
                    $module->name,
                    $module->status,
                    $module->enabled ? 'Yes' : 'No',
                    $module->last_enabled_at,
                    $module->last_disabled_at
                ];
            })->toArray()
        );
    }
} 