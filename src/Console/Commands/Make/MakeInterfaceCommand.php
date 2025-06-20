<?php

namespace Rcv\Core\Console\Commands\Make;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeInterfaceCommand extends Command
{
    protected $signature = 'module:make-interface {module} {interface}';
    protected $description = 'Create a new interface class for the specified module';

    public function handle()
    {
        $path = $this->getDestinationFilePath();
        $contents = $this->getTemplateContents();

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $contents);

        $this->info("Interface created at: {$path}");
    }

    protected function getTemplateContents(): string
    {
        $module = $this->getModuleName();
        $className = Str::studly($this->argument('interface'));


        $stubPath = __DIR__ . '/../stubs/interface.stub';

        if (!file_exists($stubPath)) {
            $this->error("Stub file not found: {$stubPath}");
            exit(1);
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
        $className = Str::studly($this->argument('interface'));

        return base_path("Modules/{$module}/src/Contracts/{$className}.php");
    }

    protected function getModuleName(): string
    {
        return Str::studly($this->argument('module'));
    }
}
