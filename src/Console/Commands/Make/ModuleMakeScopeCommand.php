<?php

namespace Rcv\Core\Console\Commands\Make;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Filesystem\Filesystem;

use Rcv\Core\Console\Commands\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class ModuleMakeScopeCommand extends Command
{
    protected $signature = 'module:make-scope {name} {module}';
    protected $description = 'Create a new scope class for the specified module';

    protected string $qualifiedName;
    protected string $namespace;
    protected Filesystem $files;

    public function handle(): int
    {
        $this->qualifiedName = $this->qualifyClass($this->argument('name'));
        $this->namespace = $this->getNamespaceFromQualified($this->qualifiedName);

        return parent::handle(); // Calls handle() from GeneratorCommand
    }

    protected function getStub(): string
    {
        return File::get(__DIR__ . '/../stubs/scope.stub');

    }

    protected function getDefaultNamespace(string $module): string
    {
        return "Modules\\{$module}\\src\\Scopes";
    }

    protected function getPath(string $qualifiedClassName): string
    {
        $module = $this->getModuleName();
        $className = class_basename($qualifiedClassName);

        return base_path("Modules/{$module}/src/Scopes/{$className}.php");
    }

    protected function qualifyClass(string $name): string
    {
        return $this->getDefaultNamespace($this->getModuleName()) . '\\' . Str::studly($name);
    }

    protected function getNamespaceFromQualified(string $qualifiedClassName): string
    {
        return Str::beforeLast($qualifiedClassName, '\\');
    }

    protected function replaceClass(string $stub, string $qualifiedClassName): string
    {
        return str_replace(
            ['{{ class }}', '{{ namespace }}'],
            [class_basename($qualifiedClassName), $this->namespace],
            $stub
        );
    }

    protected function getTemplateContents(): string
    {
        $stub = $this->files->get($this->getStub());
        return $this->replaceClass($stub, $this->qualifiedName);
    }

    protected function getDestinationFilePath(): string
    {
        return $this->getPath($this->qualifiedName);
    }

    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the scope class'],
            ['module', InputArgument::REQUIRED, 'The name of the module'],
        ];
    }

    protected function getModuleName(): string
    {
        return Str::studly($this->argument('module'));
    }
}
