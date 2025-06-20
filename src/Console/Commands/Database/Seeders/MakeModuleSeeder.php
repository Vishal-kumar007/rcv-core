<?php


namespace Rcv\Core\Console\Commands\Database\Seeders;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleSeeder extends Command
{
    protected $signature = 'module:make-seeder {seeder_name} {module_name}';
    protected $description = 'Create a new seeder inside a module folder';

    public function handle()
    {
        $seederName = Str::studly($this->argument('seeder_name'));
        $moduleName = Str::studly($this->argument('module_name'));

        $seederPath = base_path("Modules/{$moduleName}/src/Database/Seeders");

        if (!File::exists($seederPath)) {
            File::makeDirectory($seederPath, 0755, true);
        }

        $seederFile = "{$seederPath}/{$seederName}.php";

        if (File::exists($seederFile)) {
            $this->error("Seeder '{$seederName}' already exists in module '{$moduleName}'.");
            return 1;
        }

        $stub = $this->getSeederStub($seederName, $moduleName);
        File::put($seederFile, $stub);

        $this->info("Seeder '{$seederName}' created successfully in module '{$moduleName}'.");
        return 0;
    }

    protected function getSeederStub($seederName, $moduleName)
    {
        return <<<PHP
<?php

namespace Modules\\{$moduleName}\\Database\\Seeders;

use Illuminate\Database\Seeder;

class {$seederName} extends Seeder
{
    public function run()
    {
        //
    }
}
PHP;
    }
}
