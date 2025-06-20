<?php

namespace Rcv\Core\Providers;

use Illuminate\Support\Str;
use Rcv\Core\Services\BaseService;
use Rcv\Core\Services\ModuleLoader;
use Illuminate\Support\Facades\File;
use Rcv\Core\Services\ModuleManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Rcv\Core\Contracts\ServiceInterface;
use Rcv\Core\Repositories\BaseRepository;
use Rcv\Core\Repositories\MainRepository;
use Rcv\Core\Services\MarketplaceService;
use Rcv\Core\Contracts\RepositoryInterface;
use Rcv\Core\Console\Commands\Make\MakeEnum;
use Rcv\Core\Console\Commands\Make\MakeAction;
use Rcv\Core\Console\Commands\Make\MakeChannel;
use Rcv\Core\Services\ModuleRegistrationService;
use Rcv\Core\Console\Commands\ModuleDebugCommand;
use Rcv\Core\Console\Commands\ModuleStateCommand;
use Rcv\Core\Console\Commands\Make\MakeJobCommand;
use Rcv\Core\Console\Commands\Make\MakeModuleRule;
use Rcv\Core\Console\Commands\Make\MakeCastCommand;
use Rcv\Core\Console\Commands\Make\MakeMailCommand;
use Rcv\Core\Console\Commands\Make\MakeModuleClass;
use Rcv\Core\Console\Commands\Make\MakeModuleTrait;
use Rcv\Core\Console\Commands\MigrateV1ModulesToV2;
use Rcv\Core\Console\Commands\Make\MakeModulePolicy;
use Rcv\Core\Console\Commands\ModuleAutoloadCommand;
use Rcv\Core\Console\Commands\UpdatePhpunitCoverage;
use Rcv\Core\Console\Commands\Make\MakeComponentView;
use Rcv\Core\Console\Commands\Make\MakeModuleRequest;
use Rcv\Core\Console\Commands\Make\ModuleAllCommands;
use Rcv\Core\Console\Commands\Make\ModuleMakeCommand;
use Rcv\Core\Console\Commands\Make\MakeModuleObserver;
use Rcv\Core\Console\Commands\Make\ModuleMakeListener;
use Rcv\Core\Console\Commands\Actions\ModuleUseCommand;
use Rcv\Core\Console\Commands\Make\MakeModuleComponent;
use Rcv\Core\Console\Commands\Make\MakeInterfaceCommand;
use Rcv\Core\Console\Commands\Actions\ModulePruneCommand;
use Rcv\Core\Console\Commands\Actions\ModuleUnuseCommand;
use Rcv\Core\Console\Commands\Make\ModuleMakeViewCommand;
use Rcv\Core\Console\Commands\Actions\ModuleEnableCommand;
use Rcv\Core\Console\Commands\Make\MakeModuleNotification;
use Rcv\Core\Console\Commands\Make\ModuleMakeEventCommand;
use Rcv\Core\Console\Commands\Make\ModuleMakeScopeCommand;
use Rcv\Core\Console\Commands\Make\ModuleModelMakeCommand;
use Rcv\Core\Console\Commands\Publish\ModulePublishConfig;
use Rcv\Core\Console\Commands\Actions\ModuleDisableCommand;
use Rcv\Core\Console\Commands\Database\Seeders\ListSeeders;
use Rcv\Core\Console\Commands\Make\ModuleMakeHelperCommand;
use Rcv\Core\Console\Commands\Make\ModuleMiddlewareCommand;
use Rcv\Core\Console\Commands\Make\MakeModuleArtisanCommand;
use Rcv\Core\Console\Commands\Make\ModuleServiceMakeCommand;
use Rcv\Core\Console\Commands\Make\MultiModuleEnableCommand;
use Rcv\Core\Console\Commands\Make\MultiModuleRemoveCommand;
use Rcv\Core\Console\Commands\Actions\ModuleCheckLangCommand;
use Rcv\Core\Console\Commands\Actions\ModuleShowModelCommand;
use Rcv\Core\Console\Commands\Make\ModuleMakeMultipleCommand;
use Rcv\Core\Console\Commands\Make\ModuleResourceMakeCommand;
use Rcv\Core\Console\Commands\Make\MultiModuleDisableCommand;
use Rcv\Core\Console\Commands\Publish\ModulePublishMigration;
use Rcv\Core\Console\Commands\Make\ModuleEventProviderCommand;
use Rcv\Core\Console\Commands\Make\ModuleMakeExceptionCommand;
use Rcv\Core\Console\Commands\Actions\ModuleMarketplaceCommand;
use Rcv\Core\Console\Commands\Make\ModuleControllerMakeCommand;
use Rcv\Core\Console\Commands\Make\ModuleRepositoryMakeCommand;
use Rcv\Core\Console\Commands\Publish\ModulePublishTranslation;
use Rcv\Core\Console\Commands\Actions\ModuleCheckUpdatesCommand;
use Rcv\Core\Console\Commands\Actions\ModuleCommandsListCommand;
use Rcv\Core\Console\Commands\Database\Seeders\MakeModuleSeeder;
use Rcv\Core\Console\Commands\Database\Migrations\MigrateRefresh;
// use Rcv\Core\Console\Commands\Database\Migrations\ModuleMigrateCommand;
use Rcv\Core\Console\Commands\Database\Seeders\ModuleSeedCommand;
use Rcv\Core\Console\Commands\Make\MultiModuleMarketplaceCommand;
use Rcv\Core\Console\Commands\Publish\ModulePublishAssetsCommand;
use Rcv\Core\Console\Commands\Make\ModuleRouteProviderMakeCommand;
use Rcv\Core\Console\Commands\Database\Factories\MakeModuleFactory;
use Rcv\Core\Console\Commands\Database\Migrations\MigrateStatusCommand;
use Rcv\Core\Console\Commands\Database\Migrations\ModuleMigrateCommand;
use Rcv\Core\Console\Commands\Database\Migrations\ModuleMigrateResetCommand;
use Rcv\Core\Console\Commands\Database\Migrations\ModuleMigrationMakeCommand;
use Rcv\Core\Console\Commands\Database\Migrations\MigrateSingleModuleMigration;
use Rcv\Core\Console\Commands\Database\Migrations\ModuleMigrateRollbackCommand;

class CoreServiceProvider extends ServiceProvider
{
    protected $moduleName = 'Core';
    protected $moduleNameLower = 'core';
    protected $moduleNamespace = 'Rcv\Core';

    protected $commands = [
        // Action Commands
        \Rcv\Core\Console\Commands\Actions\ModuleMarketplaceCommand::class,
        \Rcv\Core\Console\Commands\ModuleStateCommand::class,
        \Rcv\Core\Console\Commands\Actions\ModuleEnableCommand::class,
        \Rcv\Core\Console\Commands\Actions\ModuleDisableCommand::class,
        \Rcv\Core\Console\Commands\ModuleDebugCommand::class,
        \Rcv\Core\Console\Commands\Actions\ModuleCheckUpdatesCommand::class,
        \Rcv\Core\Console\Commands\Actions\ModulePruneCommand::class,
        \Rcv\Core\Console\Commands\Actions\ModuleUseCommand::class,
        \Rcv\Core\Console\Commands\Actions\ModuleUnuseCommand::class,
        \Rcv\Core\Console\Commands\Actions\ModuleCheckLangCommand::class,
        \Rcv\Core\Console\Commands\Actions\ModuleShowModelCommand::class,
        \Rcv\Core\Console\Commands\Actions\ModuleCommandsListCommand::class,
        \Rcv\Core\Console\Commands\ModuleBackupCommand::class,
        \Rcv\Core\Console\Commands\ModuleDependencyGraphCommand::class,
        \Rcv\Core\Console\Commands\ModuleHealthCheckCommand::class,
        \Rcv\Core\Console\Commands\ModuleSetupCommand::class,
        \Rcv\Core\Console\Commands\ModuleClearCompiled::class,
        \Rcv\Core\Console\Commands\DiscoverModulesCommand::class,

        // Make Commands
        \Rcv\Core\Console\Commands\Make\ModuleMakeCommand::class,
        \Rcv\Core\Console\Commands\Make\ModuleControllerMakeCommand::class,
        \Rcv\Core\Console\Commands\Make\ModuleModelMakeCommand::class,
        \Rcv\Core\Console\Commands\Make\ModuleResourceMakeCommand::class,
        \Rcv\Core\Console\Commands\Make\ModuleRepositoryMakeCommand::class,
        \Rcv\Core\Console\Commands\Make\ModuleMakeEventCommand::class,
        \Rcv\Core\Console\Commands\Make\ModuleMakeHelperCommand::class,
        \Rcv\Core\Console\Commands\Make\ModuleMakeExceptionCommand::class,
        \Rcv\Core\Console\Commands\Make\ModuleMakeScopeCommand::class,
        \Rcv\Core\Console\Commands\Make\MakeComponentView::class,
        \Rcv\Core\Console\Commands\Make\MakeChannel::class,
        \Rcv\Core\Console\Commands\Make\MakeModuleClass::class,
        \Rcv\Core\Console\Commands\Make\MakeModuleArtisanCommand::class,
        \Rcv\Core\Console\Commands\Make\MakeModuleObserver::class,
        \Rcv\Core\Console\Commands\Make\MakeModulePolicy::class,
        \Rcv\Core\Console\Commands\Make\MakeModuleRule::class,
        \Rcv\Core\Console\Commands\Make\MakeModuleTrait::class,
        \Rcv\Core\Console\Commands\Make\MakeEnum::class,
        \Rcv\Core\Console\Commands\ModuleAutoloadCommand::class,
        \Rcv\Core\Console\Commands\Make\MakeModuleComponent::class,
        \Rcv\Core\Console\Commands\Make\MakeModuleRequest::class,
        \Rcv\Core\Console\Commands\Make\ModuleMakeListener::class,
        \Rcv\Core\Console\Commands\Make\ModuleMakeViewCommand::class,
        \Rcv\Core\Console\Commands\Make\ModuleRouteProviderMakeCommand::class,
        \Rcv\Core\Console\Commands\Publish\ModulePublishAssetsCommand::class,
        \Rcv\Core\Console\Commands\Publish\ModulePublishConfig::class,
        \Rcv\Core\Console\Commands\Publish\ModulePublishMigration::class,
        \Rcv\Core\Console\Commands\Publish\ModulePublishTranslation::class,
        \Rcv\Core\Console\Commands\Make\ModuleEventProviderCommand::class,
        \Rcv\Core\Console\Commands\Make\ModuleServiceMakeCommand::class,
        \Rcv\Core\Console\Commands\Make\MakeCastCommand::class,
        \Rcv\Core\Console\Commands\Make\MakeJobCommand::class,
        \Rcv\Core\Console\Commands\Make\MakeMailCommand::class,
        \Rcv\Core\Console\Commands\Make\MakeModuleNotification::class,
        \Rcv\Core\Console\Commands\Make\MakeAction::class,
        \Rcv\Core\Console\Commands\Make\MakeInterfaceCommand::class,
        \Rcv\Core\Console\Commands\Make\ModuleMiddlewareCommand::class,
        \Rcv\Core\Console\Commands\Make\MultiModuleRemoveCommand::class,
        \Rcv\Core\Console\Commands\Make\ModuleAllCommands::class,
        \Rcv\Core\Console\Commands\Make\ModuleMakeMultipleCommand::class,
        \Rcv\Core\Console\Commands\Make\MultiModuleDisableCommand::class,
        \Rcv\Core\Console\Commands\Make\MultiModuleEnableCommand::class,
        \Rcv\Core\Console\Commands\Make\MultiModuleMarketplaceCommand::class,

        // Database Commands
        \Rcv\Core\Console\Commands\Database\Factories\MakeModuleFactory::class,
        \Rcv\Core\Console\Commands\Database\Migrations\MigrateRefresh::class,
        \Rcv\Core\Console\Commands\Database\Migrations\MigrateSingleModuleMigration::class,
        \Rcv\Core\Console\Commands\Database\Migrations\MigrateStatusCommand::class,
        \Rcv\Core\Console\Commands\Database\Migrations\ModuleMigrateResetCommand::class,
        \Rcv\Core\Console\Commands\Database\Migrations\ModuleMigrateRollbackCommand::class,
        \Rcv\Core\Console\Commands\Database\Migrations\ModuleMigrationMakeCommand::class,
        \Rcv\Core\Console\Commands\Database\Seeders\ListSeeders::class,
        \Rcv\Core\Console\Commands\Database\Seeders\MakeModuleSeeder::class,
        \Rcv\Core\Console\Commands\Database\Seeders\ModuleSeedCommand::class,
        \Rcv\Core\Console\Commands\Database\Migrations\ModuleMigrateCommand::class,
        \Rcv\Core\Console\Commands\Database\Migrations\ModuleMigrateFresh::class,

        // Other Commands
        \Rcv\Core\Console\Commands\MigrateV1ModulesToV2::class,
        \Rcv\Core\Console\Commands\UpdatePhpunitCoverage::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        parent::register();

        $this->app->bind(RepositoryInterface::class, BaseRepository::class);
        // $this->app->bind(Repository::class, MainRepository::class);
        $this->app->bind(ServiceInterface::class, BaseService::class);
        $this->registerConfig();

        $this->app->singleton(ModuleManager::class);
        $this->app->singleton(ModuleRegistrationService::class);
        $this->app->singleton(MarketplaceService::class);

        $this->app->singleton(ModuleLoader::class, function ($app) {
            return new ModuleLoader();
        });

        $this->commands($this->commands);

        $this->registerModuleProviders();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerConfig();
        $this->registerCommands();
        $this->registerRoutes();
        $this->registerViews();
        $this->registerTranslations();
        $this->registerMigrations();
        $this->bootModules();
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = __DIR__.'/../Config/config.php';
        $marketplaceConfigPath = __DIR__.'/../Config/marketplace.php';
        
        if (File::exists($configPath)) {
            $this->mergeConfigFrom($configPath, 'core');
        }

        if (File::exists($marketplaceConfigPath)) {
            $this->mergeConfigFrom($marketplaceConfigPath, 'marketplace');
        }

        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('core.php'),
        ], 'config');
    }

    /**
     * Register commands.
     */
    protected function registerCommands(): void
    {
        $configPath = __DIR__.'/../Config/config.php';
        if (File::exists($configPath)) {
            $config = require $configPath;
            if (isset($config['commands'])) {
                $this->commands($config['commands']);
            }
        }

        $this->commands([
            ModuleAutoloadCommand::class,
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
    }

    /**
     * Register routes.
     */
    protected function registerRoutes(): void
    {
        Route::group(['middleware' => ['web']], function () {
            $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        });

        Route::group(['middleware' => ['api']], function () {
            $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        });
    }

    /**
     * Register views.
     */
    protected function registerViews(): void
    {
        $viewPath = base_path('modules/Core/src/Resources/views');
        
        if (File::exists($viewPath)) {
            $this->loadViewsFrom($viewPath, 'core');
        }
    }

    /**
     * Register translations.
     */
    protected function registerTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'core');
    }

    /**
     * Register migrations.
     */
    protected function registerMigrations(): void
    {
        $migrationsPath = base_path(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__ . '/../Database/Migrations' => database_path('migrations/'),
        ], 'core-module-migrations');

        if (File::exists($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    /**
     * Register module service providers.
     */
    protected function registerModuleProviders(): void
    {
        try {
            $moduleManager = $this->app->make(ModuleManager::class);
            $modules = $moduleManager->getEnabledModules();
            
            \Illuminate\Support\Facades\Log::info('Enabled modules:', $modules);
            
            foreach ($modules as $module) {
                $studlyModule = Str::studly($module);
                $providerClass = "Modules\\{$studlyModule}\\Providers\\{$studlyModule}ServiceProvider";
                \Illuminate\Support\Facades\Log::info("Attempting to register provider: {$providerClass}");
                
                if (class_exists($providerClass)) {
                    try {
                        $provider = $this->app->resolveProvider($providerClass);
                        $this->app->register($provider);
                        \Illuminate\Support\Facades\Log::info("Successfully registered provider: {$providerClass}");
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to register provider {$providerClass}: " . $e->getMessage());
                        throw $e;
                    }
                } else {
                    \Illuminate\Support\Facades\Log::warning("Provider class not found: {$providerClass}");
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error in registerModuleProviders: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Boot registered modules.
     */
    protected function bootModules(): void
    {
        try {
            $moduleManager = $this->app->make(ModuleManager::class);
            $modules = $moduleManager->getEnabledModules();
            
            foreach ($modules as $module) {
                $studlyModule = Str::studly($module);
                $providerClass = "Modules\\{$studlyModule}\\Providers\\{$studlyModule}ServiceProvider";
                if (class_exists($providerClass)) {
                    try {
                        $provider = $this->app->resolveProvider($providerClass);
                        if (method_exists($provider, 'boot')) {
                            // Call boot only if it exists and is callable
                            call_user_func([$provider, 'boot']);
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to boot provider {$providerClass}: " . $e->getMessage());
                        throw $e;
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error in bootModules: " . $e->getMessage());
            throw $e;
        }
    }
} 
