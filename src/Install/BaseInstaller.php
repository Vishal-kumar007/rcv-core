<?php

namespace Rcv\Core\Install;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class BaseInstaller
{
    protected $moduleName;
    protected $modulePath;

    public function __construct()
    {
        $this->moduleName = $this->getModuleName();
        $this->modulePath = module_path($this->moduleName);
    }

    abstract protected function getModuleName(): string;

    public function install()
    {
        $this->runMigrations();
        $this->createSettings();
        $this->createRoles();
        $this->createDefaultData();
        $this->publishAssets();
    }

    public function uninstall()
    {
        $this->removeData();
        $this->removeSettings();
        $this->removeRoles();
        $this->removeAssets();
    }

    protected function runMigrations()
    {
        $migrationsPath = "{$this->modulePath}/Database/Migrations";
        
        if (File::exists($migrationsPath)) {
            $migrations = File::files($migrationsPath);
            
            foreach ($migrations as $migration) {
                $migrationClass = require $migration;
                $migrationClass->up();
            }
        }
    }

    protected function createSettings()
    {
        $configPath = "{$this->modulePath}/Config/config.php";
        
        if (File::exists($configPath)) {
            $config = require $configPath;
            
            if (isset($config['settings'])) {
                foreach ($config['settings'] as $key => $value) {
                    DB::table('settings')->updateOrInsert(
                        ['key' => "{$this->moduleName}.{$key}"],
                        ['value' => $value]
                    );
                }
            }
        }
    }

    protected function createRoles()
    {
        $configPath = "{$this->modulePath}/Config/config.php";
        
        if (File::exists($configPath)) {
            $config = require $configPath;
            
            if (isset($config['roles'])) {
                foreach ($config['roles'] as $role) {
                    DB::table('roles')->updateOrInsert(
                        ['name' => $role['name']],
                        $role
                    );
                }
            }
        }
    }

    protected function createDefaultData()
    {
        $seedsPath = "{$this->modulePath}/Database/Seeders";
        
        if (File::exists($seedsPath)) {
            $seeds = File::files($seedsPath);
            
            foreach ($seeds as $seed) {
                $seedClass = require $seed;
                $seedClass->run();
            }
        }
    }

    protected function publishAssets()
    {
        $assetsPath = "{$this->modulePath}/resources/assets";
        
        if (File::exists($assetsPath)) {
            File::copyDirectory(
                $assetsPath,
                public_path("modules/{$this->moduleName}")
            );
        }
    }

    protected function removeData()
    {
        $migrationsPath = "{$this->modulePath}/Database/Migrations";
        
        if (File::exists($migrationsPath)) {
            $migrations = File::files($migrationsPath);
            
            foreach ($migrations as $migration) {
                $migrationClass = require $migration;
                $migrationClass->down();
            }
        }
    }

    protected function removeSettings()
    {
        DB::table('settings')
            ->where('key', 'like', "{$this->moduleName}.%")
            ->delete();
    }

    protected function removeRoles()
    {
        $configPath = "{$this->modulePath}/Config/config.php";
        
        if (File::exists($configPath)) {
            $config = require $configPath;
            
            if (isset($config['roles'])) {
                foreach ($config['roles'] as $role) {
                    DB::table('roles')
                        ->where('name', $role['name'])
                        ->delete();
                }
            }
        }
    }

    protected function removeAssets()
    {
        $publicPath = public_path("modules/{$this->moduleName}");
        
        if (File::exists($publicPath)) {
            File::deleteDirectory($publicPath);
        }
    }
} 