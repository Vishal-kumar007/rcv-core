<?php

namespace Rcv\Core\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use SimpleXMLElement;

class UpdatePhpunitCoverage extends Command
{
    protected $signature = 'module:update-phpunit-coverage';     // php artisan module:update-phpunit-coverage
    
    protected $description = 'Update phpunit.xml source/include path with enabled modules';

    public function handle()
    {
        $phpunitFile = base_path('phpunit.xml');

        if (!File::exists($phpunitFile)) {
            $this->error("phpunit.xml not found.");
            return;
        }

        $modulesPath = base_path('Modules');
        if (!File::exists($modulesPath)) {
            $this->error("Modules folder not found.");
            return;
        }

        $enabledModules = collect(File::directories($modulesPath))
            ->filter(function ($path) {
                return File::exists($path . '/module.json');
            })
            ->map(function ($path) {
                return 'Modules/' . basename($path) . '/src';
            });

        $this->info('Enabled module paths:');
        foreach ($enabledModules as $modulePath) {
            $this->line("- $modulePath");
        }

        // Load phpunit.xml
        $xml = new SimpleXMLElement(File::get($phpunitFile));

        // Remove old include nodes
        unset($xml->coverage->include);

        // Create new include structure
        $coverage = $xml->coverage;
        $include = $coverage->addChild('include');
        foreach ($enabledModules as $path) {
            $dir = $include->addChild('directory', $path);
            $dir->addAttribute('suffix', '.php');
        }

        // Save updated file
        $formattedXml = $xml->asXML();
        File::put($phpunitFile, $formattedXml);

        $this->info('phpunit.xml coverage paths updated successfully.');

        // Optionally create helper class
        $stubPath = __DIR__ . '/stubs/coverage-helper.stub';
        $helperPath = base_path('Modules/PhpunitCoverageHelper.php');

        if (File::exists($stubPath) && !File::exists($helperPath)) {
            File::put($helperPath, file_get_contents($stubPath));
            $this->info("Helper class created: PhpunitCoverageHelper.php");
        }
    }
}
