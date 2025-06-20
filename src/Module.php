<?php

namespace Rcv\Core;

use Illuminate\Support\Facades\File;
use Rcv\Core\Contracts\ModuleInterface;
use Rcv\Core\Install\BaseInstaller;

abstract class Module implements ModuleInterface
{
    protected $name;
    protected $version;
    protected $description;
    protected $enabled = false;
    protected $path;
    protected $installer;

    public function __construct()
    {
        $this->name = $this->getName();
        $this->version = $this->getVersion();
        $this->description = $this->getDescription();
        $this->path = $this->getPath();
        $this->installer = $this->getInstaller();

        // Load enabled state from module.json
        $moduleJsonPath = $this->path . '/module.json';
        if (File::exists($moduleJsonPath)) {
            $moduleJson = json_decode(File::get($moduleJsonPath), true);
            $this->enabled = $moduleJson['enabled'] ?? false;
        }
    }

    abstract public function getName(): string;
    abstract public function getVersion(): string;
    abstract public function getDescription(): string;
    abstract protected function getInstaller(): BaseInstaller;

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function enable(): bool
    {
        if ($this->enabled) {
            return true;
        }

        try {
            // Run installer
            $this->installer->install();

            // Update composer.json
            $this->updateComposerJson(true);

            // Update config/app.php
            $this->updateAppConfig(true);

            $this->enabled = true;
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function disable(): bool
    {
        if (!$this->enabled) {
            return true;
        }

        try {
            // Run uninstaller
            $this->installer->uninstall();

            // Update composer.json
            $this->updateComposerJson(false);

            // Update config/app.php
            $this->updateAppConfig(false);

            $this->enabled = false;
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getPath(): string
    {
        return module_path($this->getModuleNameLower());
    }

    protected function getModuleNameLower(): string
    {
        // Convert camel case to kebab case
        $name = preg_replace('/(?<!^)[A-Z]/', '-$0', $this->name);
        // Convert to lowercase
        return strtolower($name);
    }

    protected function getModuleNameStudly(): string
    {
        // Convert kebab case to studly case
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $this->getModuleNameLower())));
    }

    protected function updateComposerJson(bool $enable): void
    {
        $composerJson = json_decode(File::get(base_path('composer.json')), true);
        $moduleNameLower = $this->getModuleNameLower();
        $moduleNameStudly = $this->getModuleNameStudly();
        
        if ($enable) {
            // Add to require section if not exists
            if (!isset($composerJson['require']["modules/{$moduleNameLower}"])) {
                $composerJson['require']["modules/{$moduleNameLower}"] = "*";
            }
            
            // Add to autoload psr-4 section if not exists
            if (!isset($composerJson['autoload']['psr-4']["Modules\\{$moduleNameStudly}\\"])) {
                $composerJson['autoload']['psr-4']["Modules\\{$moduleNameStudly}\\"] = "packages/modules/{$moduleNameLower}/src/";
            }
        } else {
            // Remove from require section
            unset($composerJson['require']["modules/{$moduleNameLower}"]);
            
            // Remove from autoload psr-4 section
            unset($composerJson['autoload']['psr-4']["Modules\\{$moduleNameStudly}\\"]);
        }
        
        // Sort arrays to maintain consistency
        ksort($composerJson['require']);
        ksort($composerJson['autoload']['psr-4']);
        
        File::put(
            base_path('composer.json'),
            json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    protected function updateAppConfig(bool $enable): void
    {
        $configPath = config_path('app.php');
        $config = File::get($configPath);
        $moduleNameStudly = $this->getModuleNameStudly();
        $providerClass = "        Modules\\{$moduleNameStudly}\\Providers\\{$moduleNameStudly}ServiceProvider::class,";
        $searchPattern = "        /*\n         * Module Service Providers...\n         */\n";
        
        if ($enable) {
            if (strpos($config, $providerClass) === false) {
                $config = str_replace(
                    $searchPattern,
                    $searchPattern . $providerClass . "\n",
                    $config
                );
            }
        } else {
            $config = str_replace($providerClass . "\n", '', $config);
        }
        
        File::put($configPath, $config);

        // Update packages.php cache
        $this->updatePackagesCache($enable);
    }

    protected function updatePackagesCache(bool $enable): void
    {
        $cachePath = base_path('bootstrap/cache/packages.php');
        if (!File::exists($cachePath)) {
            return;
        }

        $moduleNameLower = $this->getModuleNameLower();
        $moduleNameStudly = $this->getModuleNameStudly();
        $providerClass = "Modules\\{$moduleNameStudly}\\Providers\\{$moduleNameStudly}ServiceProvider";

        $packages = require $cachePath;
        
        if ($enable) {
            // Add module to packages cache
            $packages["modules/{$moduleNameLower}"] = [
                'providers' => [$providerClass],
            ];
        } else {
            // Remove module from packages cache
            unset($packages["modules/{$moduleNameLower}"]);
        }

        // Write back to cache file
        $content = "<?php return " . var_export($packages, true) . ";\n";
        File::put($cachePath, $content);
    }
} 