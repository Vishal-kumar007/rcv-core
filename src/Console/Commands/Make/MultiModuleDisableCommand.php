<?php

namespace Rcv\Core\Console\Commands\Make;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MultiModuleDisableCommand extends Command
{
    protected $signature = 'module:disable {names}';
    protected $description = 'Disable one or more modules';

    public function handle()
    {
        $modules = array_map('trim', explode(',', $this->argument('names')));

        foreach ($modules as $module) {
            $path = base_path("modules/{$module}/module.json");

            if (!File::exists($path)) {
                $this->error(" Module [$module] not found.");
                continue;
            }

            $data = json_decode(File::get($path), true);
            $data['enabled'] = false;
            $data['last_disabled_at'] = now()->toDateTimeString();

            File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->info(" Module [$module] disabled.");
        }
    }
}
