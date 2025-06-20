<?php

namespace Rcv\Core\Console\Commands\Make;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeChannel extends Command
{
    protected $signature = 'module:make-channel {module} {channel}';
    protected $description = 'Create a new channel class for the specified module';

    public function handle()
    {
        $path = $this->getDestinationFilePath();
        $contents = $this->getTemplateContents();

        // Ensure directory exists
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $contents);

        $this->info("Channel created: {$path}");
    }

   protected function getTemplateContents(): string
{
    $module = $this->getModuleName();
    $className = Str::studly($this->argument('channel'));

    // Updated path
    $stubPath = __DIR__ . '/../stubs/channel.stub';
    // $stubPath = base_path('Modules/Core/src/Console/Commands/stubs/channel.stub');

    if (!file_exists($stubPath)) {
        $this->error("Stub file not found: {$stubPath}");
        exit(1); // Stop execution with error
    }

    $stub = file_get_contents($stubPath);

    return str_replace(
        ['{{module}}', '{{class}}'],
        [$module, $className],
        $stub
    );
}

    protected function getDestinationFilePath(): string
    {
        $module = $this->getModuleName();
        $className = Str::studly($this->argument('channel'));

        return base_path("Modules/{$module}/src/Channels/{$className}.php");
    }

    protected function getModuleName(): string
    {
        return Str::studly($this->argument('module'));
    }
}
