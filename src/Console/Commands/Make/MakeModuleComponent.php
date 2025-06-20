<?php

namespace  Rcv\Core\Console\Commands\Make;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleComponent extends Command
{
    protected $signature = 'module:make-component {module} {name}';
    protected $description = 'Create a new component-class for the specified module';

    public function handle()
    {
        $module = $this->argument('module'); // e.g. Blog
        $name = $this->argument('name');     // e.g. Alert

        $className = Str::studly(class_basename($name));
        $directory = base_path("Modules/{$module}/src/View/Components");
        $filePath = "{$directory}/{$className}.php";

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if (File::exists($filePath)) {
            $this->error("Component already exists: {$filePath}");
            return;
        }

         $stubPath = __DIR__ . '/../stubs/component.stub';
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found at: {$stubPath}");
            return;
        }

        $stub = file_get_contents($stubPath);

        $namespace = "Modules\\{$module}\\View\\Components";

        $content = str_replace(
            ['{{ module_name }}', '{{ class_name }}'],
            [$module, $className],
            $stub
        );

        File::put($filePath, $content);
        $this->info("Component class created: {$filePath}");
    }
}
