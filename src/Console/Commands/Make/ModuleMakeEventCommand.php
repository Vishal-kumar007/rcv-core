<?php

namespace Rcv\Core\Console\Commands\Make;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModuleMakeEventCommand extends Command
{
    protected $signature = 'module:make-event {module} {name}';

    protected $description = 'Create a new event class for the specified module';

    public function handle()
    {
        $module = Str::studly($this->argument('module'));
        $name = Str::studly($this->argument('name'));

        $basePath = base_path("Modules/{$module}/src/Events");
        $namespace = "Modules\\{$module}\\Events";

        $stub = File::get(__DIR__ . '/../stubs/event.stub');
        $stub = str_replace(
            ['{{ module_name }}', '{{ class }}'],
            [$namespace, $name],
            $stub
        );

        if (!File::exists($basePath)) {
            File::makeDirectory($basePath, 0755, true);
        }

        $filePath = "{$basePath}/{$name}.php";

        if (File::exists($filePath)) {
            $this->error("Event {$name} already exists in module {$module}.");
            return;
        }

        File::put($filePath, $stub);

        $this->info("Event {$name} created successfully in module {$module}.");
    }
}
