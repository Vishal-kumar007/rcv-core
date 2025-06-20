<?php

namespace Rcv\Core\Console\Commands\Actions;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Collection;

class ModuleCheckLangCommand extends Command
{
    protected $signature = 'module:lang {module?}';
    protected $description = 'Check missing language keys in the specified module.';

    public function handle()
    {
        $moduleName = $this->argument('module');

        if ($moduleName) {
            $this->checkModuleLang($moduleName);
        } else {
            $this->checkAllModulesLang();
        }
    }

    protected function checkModuleLang($moduleName)
    {
        $moduleName = $this->argument('module');

         // Ensure module exists
        if (!File::exists(base_path("modules/{$moduleName}"))) {
            $this->error("Module {$moduleName} does not exist.");
            return 1;
        }

        $directories = $this->getLangDirectories($moduleName);

        if (!$directories) {
            return;
        }

        $this->checkMissingFiles($directories);
        $this->checkMissingKeys($directories);
    }

    protected function checkAllModulesLang()
    {
        $moduleName = $this->getAllModules();

        if ($moduleName->isEmpty()) {
            $this->error("No modules found.");
            return;
        }

        $moduleName->each(function ($moduleName) {
            $this->checkModuleLang($moduleName->getName());
        });
    }

    // protected function getModule($moduleName)
    // {
    //     // Retrieve the module by name
    //     return $this->laravel['modules']->find($moduleName);
    // }

    protected function getAllModules()
    {
        // Retrieve all modules
        return $this->laravel['modules']->all();
    }

    protected function getLangDirectories($moduleName)
    {
        // Define the base path where modules are stored
        $basePath = base_path('modules');

        // Construct the full path to the module's 'lang' directory
        $path = "{$basePath}/{$moduleName}/Resources/lang";

        // Check if the 'lang' directory exists
        if (!is_dir($path)) {
            $this->error("Language directory not found in module [{$moduleName}].");
            return null;
        }

        // Return the directories within the 'lang' directory
        return collect(File::directories($path))->map(function ($directory) use ($moduleName) {
            return [
                'name' => basename($directory),
                'module' => $moduleName,
                'path' => $directory,
                'files' => File::files($directory),
            ];
        });
    }

    protected function checkMissingFiles(Collection $directories)
    {
        $uniqueFiles = $directories->pluck('files')->flatten()->unique()->values();

        $directories->each(function ($directory) use ($uniqueFiles) {
            $missingFiles = $uniqueFiles->diff($directory['files']);

            if ($missingFiles->isNotEmpty()) {
                $this->components->error("Missing language files in {$directory['name']} directory:");
                $missingFiles->each(function ($file) use ($directory) {
                    $this->components->line(" - {$directory['module']}: {$directory['name']}/{$file->getFilename()}");
                });
                $this->newLine();
            }
        });
    }

    protected function checkMissingKeys(Collection $directories)
    {
        $uniqueFiles = $directories->pluck('files')->flatten()->unique();
        $langDirectories = $directories->pluck('name');

        $directories->each(function ($directory) use ($uniqueFiles, $langDirectories) {
            $uniqueFiles->each(function ($file) use ($directory, $langDirectories) {
                $langKeys = $this->getLangKeys($directory['path'] . '/' . $file->getFilename());

                if ($langKeys === false) {
                    return;
                }

                $langDirectories->each(function ($langDirectory) use ($directory, $file, $langKeys) {
                    if ($directory['name'] !== $langDirectory) {
                        $basePath = str_replace($directory['name'], $langDirectory, $directory['path']);
                        $otherLangKeys = $this->getLangKeys($basePath . '/' . $file->getFilename());

                        if ($otherLangKeys === false) {
                            return;
                        }

                        $missingKeys = $langKeys->diff($otherLangKeys);
                        if ($missingKeys->isNotEmpty()) {
                            $this->components->error("Missing language keys in {$langDirectory} for file {$file->getFilename()}:");
                            $missingKeys->each(function ($missingKey) use ($directory, $langDirectory, $file) {
                                $this->components->line(" - {$directory['module']}: {$langDirectory}/{$file->getFilename()} | key: {$missingKey}");
                            });
                            $this->newLine();
                        }
                    }
                });
            });
        });
    }

    protected function getLangKeys($file)
    {
        if (File::exists($file)) {
            $lang = require $file;
            return collect($lang)->keys();
        }

        return false;
    }
}
