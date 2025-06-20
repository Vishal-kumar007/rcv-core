<?php

namespace Rcv\Core\Console\Commands\Make;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModuleMakeListener extends Command
{
   protected $signature = 'module:make-listener 
                            {module : The module name} 
                            {name : The listener class name} 
                            {event : The event class to listen for}';

    protected $description = 'Create a new event listener class for the specified module';

    public function handle()
    {
        $module = Str::studly($this->argument('module'));
        $name = Str::studly($this->argument('name'));
        $event = Str::studly($this->argument('event'));

        $basePath = base_path("Modules/{$module}/src/Listeners");
        $namespace = "Modules\\{$module}\\Listeners";
        $eventClass = "Modules\\{$module}\\Events\\{$event}";

        $filePath = "{$basePath}/{$name}.php";
        $stubPath = __DIR__ . '/../stubs/listener.stub';

        if (!File::exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            return;
        }

        if (!File::isDirectory($basePath)) {
            File::makeDirectory($basePath, 0755, true);
        }

        if (File::exists($filePath)) {
            $this->error("Listener {$name} already exists in module {$module}.");
            return;
        }

        $stub = File::get($stubPath);
        $stub = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ event }}','{{ event_name }}'],
            [$namespace, $name, $eventClass, $event],
            $stub
        );

        File::put($filePath, $stub);

        $this->info("Listener {$name} created successfully in module {$module}.");
    }
}
