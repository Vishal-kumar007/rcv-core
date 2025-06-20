<?php

namespace Rcv\Core\Console\Commands\Make;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ModuleRouteProviderMakeCommand extends Command
{
    protected $name = 'module:make-route-provider {module}';
    protected $description = 'Create a RouteServiceProvider for a given module.';

    public function handle()
    {
        $module = Str::studly($this->argument('module'));

        $filePath = base_path("Modules/{$module}/src/Providers/RouteServiceProvider.php");

        if (File::exists($filePath) && !$this->option('force')) {
            $this->error("RouteServiceProvider already exists at: {$filePath}");
            return;
        }

        File::ensureDirectoryExists(dirname($filePath));

        File::put($filePath, $this->getStubContent($module));

        $this->info("RouteServiceProvider created at: {$filePath}");
    }

    protected function getArguments()
    {
        return [
            ['module', InputArgument::REQUIRED, 'The name of the module.'],
        ];
    }

    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force overwrite if the file already exists.'],
        ];
    }

    protected function getStubContent(string $module): string
    {
        $namespace = "Modules\\{$module}\\Providers";
        $moduleLower = Str::kebab($module);

        return <<<PHP


PHP;
    }
}
