<?php

namespace Rcv\Core\Console\Commands\Make;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleRule extends Command
{
    protected $signature = 'module:make-rule {module} {name}';
    protected $description = 'Create a new validation rule for the specified module';

    public function handle()
    {
        $module = $this->argument('module'); // e.g., Blog
        $name = $this->argument('name');     // e.g., UppercaseRule

        $className = Str::studly(class_basename($name));
        $directory = base_path("Modules/{$module}/src/Rules");
        $filePath = "{$directory}/{$className}.php";

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if (File::exists($filePath)) {
            $this->error("Rule already exists: {$filePath}");
            return;
        }

         $stubPath = __DIR__ . '/../stubs/rule.stub';
        if (!File::exists($stubPath)) {
            $this->error("Stub file not found at: {$stubPath}");
            return;
        }

        $stub = file_get_contents($stubPath);
        $namespace = "Modules\\{$module}\\Rules";

        $content = str_replace(
            ['{{namespace}}', '{{class}}'],
            [$namespace, $className],
            $stub
        );

        File::put($filePath, $content);
        $this->info("Validation rule created: {$filePath}");
    }
}
