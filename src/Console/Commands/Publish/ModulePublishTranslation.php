<?php

namespace Rcv\Core\Console\Commands\Publish;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ModulePublishTranslation extends Command
{
    protected $signature = 'module:publish-translation {module}';
    protected $description = 'Publish a module\'s translations to the application';

    public function handle()
    {
        $module = $this->argument('module');
        $modulePath = base_path("Modules/{$module}");
        $translationsPath = "{$modulePath}/Resources/lang";

        if (!File::exists($translationsPath)) {
            $this->error("No translations found for module: {$module}");
            return;
        }

        $destinationPath = resource_path("lang/vendor/{$module}");

        // Ensure the destination directory exists
        File::ensureDirectoryExists($destinationPath);

        // Copy translation files
        $files = File::allFiles($translationsPath);
        foreach ($files as $file) {
            File::copy($file->getRealPath(), "{$destinationPath}/{$file->getFilename()}");
        }

        $this->info("Translations for module '{$module}' have been published.");
    }
}
