<?php

namespace Rcv\Core\Console\Commands\Make;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModuleMakeHelperCommand extends Command
{
    protected $signature = 'module:make-helper {module} {name}';

    protected $description = 'Create a new helper class inside the specified module (in src/Helpers)';

    public function handle()
    {
        $module = Str::studly($this->argument('module'));
        $name = Str::studly($this->argument('name'));

        $basePath = base_path("Modules/{$module}/src/Helpers");
        $namespace = "Modules\\{$module}\\Helpers";

        // ✅ First get the actual path of the stub file
        $stubPath = __DIR__ . '/../stubs/helper.stub';

        // ✅ Check if the file exists before trying to read it
        if (!File::exists($stubPath)) {
            $this->error("Missing stub file at: {$stubPath}");
            return;
        }

        // ✅ Read the file contents after checking existence
        $stub = File::get($stubPath);

        // Replace namespace and class name
        $content = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $name],
            $stub
        );

        // Create directory if it doesn't exist
        if (!File::exists($basePath)) {
            File::makeDirectory($basePath, 0755, true);
        }

        $filePath = "{$basePath}/{$name}.php";

        if (File::exists($filePath)) {
            $this->error("Helper {$name} already exists in module {$module}.");
            return;
        }

        File::put($filePath, $content);

        $this->info("Helper {$name} created successfully in module {$module}.");
    }
}
