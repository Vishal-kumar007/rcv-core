<?php

namespace Rcv\Core\Console\Commands\Make;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModuleResourceMakeCommand extends Command
{
    protected $signature = 'module:make-resource {module} {name} {--type=all : The type of resource to create (js,css,all)}';
    protected $description = 'Create a new resource (JS/CSS) for a module';

    public function handle()
    {
        $module = $this->argument('module');
        $name = $this->argument('name');
        $type = $this->option('type');

        $modulePath = base_path("modules/{$module}/src/resources");

        // Create resources directory if it doesn't exist
        if (!File::exists($modulePath)) {
            File::makeDirectory($modulePath, 0755, true);
        }

        if ($type === 'all' || $type === 'js') {
            $this->createJsResource($modulePath, $name);
        }

        if ($type === 'all' || $type === 'css') {
            $this->createCssResource($modulePath, $name);
        }

        $this->info("Resource created successfully for module [{$module}]");
    }

    protected function createJsResource($modulePath, $name)
    {
        $jsPath = "{$modulePath}/js";
        if (!File::exists($jsPath)) {
            File::makeDirectory($jsPath, 0755, true);
        }

        $stub = <<<EOT
// {$name}.js
export default {
    init() {
        // Initialize your JavaScript here
    }
};
EOT;

        File::put("{$jsPath}/{$name}.js", $stub);
    }

    protected function createCssResource($modulePath, $name)
    {
        $cssPath = "{$modulePath}/css";
        if (!File::exists($cssPath)) {
            File::makeDirectory($cssPath, 0755, true);
        }

        $stub = <<<EOT
/* {$name}.css */
.{$name} {
    /* Add your styles here */
}
EOT;

        File::put("{$cssPath}/{$name}.css", $stub);
    }
} 