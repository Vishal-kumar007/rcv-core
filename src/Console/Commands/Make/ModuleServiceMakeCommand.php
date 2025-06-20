<?php

namespace Rcv\Core\Console\Commands\Make;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModuleServiceMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make-service {name : The name of the service} {module : The name of the module} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a service for a module';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $serviceName = $this->argument('name');
        $module = $this->argument('module');

        // Ensure module exists
        if (!File::exists(base_path("modules/{$module}"))) {
            $this->error("Module {$module} does not exist.");
            return 1;
        }

        // Create service directory if it doesn't exist
        $servicePath = base_path("modules/{$module}/src/Services");
        if (!File::exists($servicePath)) {
            File::makeDirectory($servicePath, 0755, true);
        }

        // Generate service file
        $serviceFile = "{$servicePath}/{$serviceName}.php";
        $stub = File::get(__DIR__ . '/../stubs/service.stub');

        // Replace placeholders
        $content = str_replace(
            ['{{ module_name }}', '{{ class_name }}'],
            [$module, $serviceName],
            $stub
        );

        // Create service file
        File::put($serviceFile, $content);
        $this->info("Service {$serviceName} created successfully.");
        $this->info("Path: {$serviceFile}");

        return 0;
    }
} 