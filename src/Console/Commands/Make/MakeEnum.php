<?php

namespace Rcv\Core\Console\Commands\Make;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeEnum extends Command
{
    protected $signature = 'module:make-enum {module} {name}';
    protected $description = 'Create a new enum class for the specified module';

    protected string $namespace;
    protected string $enumName;

    public function handle(): int
    {
        $module = Str::studly($this->argument('module'));
        $nameInput = str_replace('\\', '/', $this->argument('name'));

        $this->enumName = Str::studly(class_basename($nameInput));
        $subPath = dirname($nameInput) !== '.' ? dirname($nameInput) : '';
        $this->namespace = "Modules\\{$module}\\src" . ($subPath ? '\\' . str_replace('/', '\\', $subPath) : '');

        $destinationPath = $this->getDestinationFilePath();
        $contents = $this->getTemplateContents();

        if (File::exists($destinationPath)) {
            $this->error("Enum already exists at: {$destinationPath}");
            return static::FAILURE;
        }

        File::ensureDirectoryExists(dirname($destinationPath));
        File::put($destinationPath, $contents);

        $this->info("Enum created at: {$destinationPath}");
        return static::SUCCESS;
    }

    protected function getTemplateContents(): string
    {
        $stubPath = __DIR__ . '/../stubs/enum.stub';

        if (!File::exists($stubPath)) {
            $this->error("Stub file not found at: {$stubPath}");
            return '';
        }

        $stub = File::get($stubPath);

        return str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$this->namespace, $this->enumName],
            $stub
        );
    }

    protected function getDestinationFilePath(): string
    {
        $module = Str::studly($this->argument('module'));
        $nameInput = str_replace('\\', '/', $this->argument('name'));
        $enumName = Str::studly(class_basename($nameInput));
        $subPath = dirname($nameInput) !== '.' ? dirname($nameInput) : '';

        $directory = base_path("Modules/{$module}/src" . ($subPath ? '/' . $subPath : ''));
        return "{$directory}/{$enumName}.php";
    }
}
