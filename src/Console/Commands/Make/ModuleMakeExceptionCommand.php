<?php

namespace Rcv\Core\Console\Commands\Make;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModuleMakeExceptionCommand extends Command
{
    protected $signature = 'module:make-exception 
                            {module : The module name} 
                            {name : The name of the exception class}';

    protected $description = 'Create a new exception class for the specified module';

    public function handle()
    {
        $module = Str::studly($this->argument('module'));
        $name = Str::studly($this->argument('name'));

        $basePath = base_path("Modules/{$module}/src/Exceptions");
        $namespace = "Modules\\{$module}\\Exceptions";
        $filePath = "{$basePath}/{$name}.php";

        $stubPath = base_path(__DIR__ . '/../stubs/exception.stub');

        if (!File::exists($stubPath)) {
            $this->error("Missing stub: {$stubPath}");
            return;
        }

        if (!File::isDirectory($basePath)) {
            File::makeDirectory($basePath, 0755, true);
        }

        if (File::exists($filePath)) {
            $this->error("Exception {$name} already exists in module {$module}.");
            return;
        }

        $stub = File::get($stubPath);

        $content = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $name],
            $stub
        );

        File::put($filePath, $content);

        $this->info("✅ Exception {$name} created in module {$module}.");
    }
}
