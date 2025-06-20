<?php

namespace Rcv\Core\Console\Commands\Make;



use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class ModuleMakeCommand extends Command
{
    protected $signature = 'module:make {name}';
    protected $description = 'Create a new module';

    protected $moduleName;           // Original name
    protected $moduleNameLower;      // lowercase name
    protected $moduleNameStudly;      // StudlyCase name
    protected $moduleNamePascal;      // PascalCase name
    protected $moduleNameUpper;       // UPPERCASE name


    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
            $this->moduleName = $this->argument('name');
            $this->moduleNameStudly = Str::studly($this->moduleName);                   // e.g., TestModule      
            $this->moduleNamePascal = $this->moduleNameStudly;                          // Same as StudlyCase   
            $this->moduleNameLower = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $this->moduleName)); // e.g., testmodule123   
            $this->moduleNameUpper = strtoupper($this->moduleNameLower);               // e.g., TESTMODULE             




        // Create module directory structure
        $this->createModuleDirectories();

        // Create module files
        $this->createModuleFiles();

        // Register module in composer.json
        $this->registerModuleInComposer();

        // Run composer dump-autoload
        $this->info('Running composer dump-autoload...');
        exec('composer dump-autoload');

        // Create module state
        $this->createModuleState();

        // Register module in Core's config
        $this->registerModuleInCoreConfig();

        $this->info("Module [{$this->moduleNameStudly}] created and registered successfully!");

        return 0;
    }

    /**
     * Create module directories.
     *
     * @return void
     */
    protected function createModuleDirectories()
{
    $basePath = "modules/{$this->moduleNameStudly}";

    // "module/{$this->moduleNameLower}/src" 

    $directories = [
        "$basePath",
        "$basePath/src",
        "$basePath/src/Config",
        "$basePath/src/Console",
        "$basePath/src/Database",
        "$basePath/src/Database/Migrations",
        "$basePath/src/Database/Seeders",
        "$basePath/src/Database/Factories",
        "$basePath/src/Http",
        "$basePath/src/Http/Controllers",
        "$basePath/src/Http/Controllers/Api",
        "$basePath/src/Http/Middleware",
        "$basePath/src/Http/Requests",
        "$basePath/src/Models",
        "$basePath/src/Providers",
        "$basePath/src/Repositories",
        "$basePath/src/Services",
        "$basePath/src/Resources",
        "$basePath/src/Resources/views",
        "$basePath/src/Resources/assets",
        "$basePath/src/Resources/assets/css",
        "$basePath/src/Resources/assets/js",
        "$basePath/src/Resources/assets/images",
        "$basePath/src/Resources/lang",
        "$basePath/src/Routes",
    ];

    foreach ($directories as $directory) {
        if (!File::exists(base_path($directory))) {
            File::makeDirectory(base_path($directory), 0755, true);
        }
    }
}

    /**
     * Create module files.
     *
     * @return void
     */
    protected function createModuleFiles()
    {
        // Create composer.json
        // $composerStub = File::get(base_path('modules/Core/src/Console/Commands/stubs/composer.stub'));
        $composerStub = File::get($this->getStubPath('composer.stub'));
        $composerStub = str_replace('{{ module_name }}', $this->moduleNameStudly, $composerStub);
        $composerStub = str_replace('{{ module_name_lower }}', $this->moduleNameLower, $composerStub);
        File::put(base_path("modules/{$this->moduleNameLower}/composer.json"), $composerStub);

        // Create service provider
        // $providerStub = File::get(base_path('modules/Core/src/Console/Commands/stubs/provider.stub'));
        $providerStub = File::get($this->getStubPath('provider.stub'));
        // $providerStub = File::get(base_path('modules/Core/src/Console/Commands/stubs/provider.stub'));
        $providerStub = str_replace('{{ module_name }}', $this->moduleNameStudly, $providerStub);
        $providerStub = str_replace('{{ module_name_lower }}', $this->moduleNameLower, $providerStub);
        File::put(base_path("modules/{$this->moduleNameLower}/src/Providers/{$this->moduleNameStudly}ServiceProvider.php"), $providerStub);

        // Create module config
        $configStub = File::get($this->getStubPath('config.stub'));
        // $configStub = File::get(base_path('modules/Core/src/Console/Commands/stubs/config.stub'));
        $configStub = str_replace('{{ module_name }}', $this->moduleNameStudly, $configStub);
        $configStub = str_replace('{{ module_name_lower }}', $this->moduleNameLower, $configStub);
        File::put(base_path("modules/{$this->moduleNameLower}/src/Config/config.php"), $configStub);

        // Create routes
        $webRoutesStub =  File::get($this->getStubPath('/routes/web.stub'));
        // $webRoutesStub = File::get(base_path('modules/Core/src/Console/Commands/stubs//routes/web.stub'));
        $webRoutesStub = str_replace('{{ module_name }}', $this->moduleNameStudly, $webRoutesStub);
        $webRoutesStub = str_replace('{{ module_name_lower }}', $this->moduleNameLower, $webRoutesStub);
        File::put(base_path("modules/{$this->moduleNameLower}/src/Routes/web.php"), $webRoutesStub);

        $apiRoutesStub = File::get($this->getStubPath('/routes/api.stub'));
        // $apiRoutesStub = File::get(base_path('modules/Core/src/Console/Commands/stubs//routes/api.stub'));
        $apiRoutesStub = str_replace('{{ module_name }}', $this->moduleNameStudly, $apiRoutesStub);
        $apiRoutesStub = str_replace('{{ module_name_lower }}', $this->moduleNameLower, $apiRoutesStub);
        File::put(base_path("modules/{$this->moduleNameLower}/src/Routes/api.php"), $apiRoutesStub);

        // Create base model
        $modelStub = File::get($this->getStubPath('model.stub'));
        // $modelStub = File::get(base_path('modules/Core/src/Console/Commands/stubs/model.stub'));
        $modelStub = str_replace('{{ module_name }}', $this->moduleNameStudly, $modelStub);
        $modelStub = str_replace('{{ class_name }}', 'BaseModel', $modelStub);
        File::put(base_path("modules/{$this->moduleNameLower}/src/Models/BaseModel.php"), $modelStub);

        // Create base repository
        $repositoryStub = File::get($this->getStubPath('repository.stub'));
        $repositoryStub = str_replace('{{ module_name }}', $this->moduleNameStudly, $repositoryStub);
        $repositoryStub = str_replace('{{ class_name }}', 'BaseRepository', $repositoryStub);
        File::put(base_path("modules/{$this->moduleNameLower}/src/Repositories/BaseRepository.php"), $repositoryStub);

        // Create base service
        // $serviceStub = File::get(base_path('modules/Core/src/Console/Commands/stubs/service.stub'));
        $serviceStub = File::get($this->getStubPath('service.stub'));
        $serviceStub = str_replace('{{ module_name }}', $this->moduleNameStudly, $serviceStub);
        $serviceStub = str_replace('{{ class_name }}', 'Base', $serviceStub);
        File::put(base_path("modules/{$this->moduleNameLower}/src/Services/BaseService.php"), $serviceStub);

        // Create HomeController for web
        $homeControllerStub = File::get($this->getStubPath('HomeController.stub'));
        // $homeControllerStub = File::get(base_path('modules/Core/src/Console/Commands/stubs/HomeController.stub'));
        $homeControllerStub = str_replace('{{ module_name }}', $this->moduleNameStudly, $homeControllerStub);
        File::put(base_path("modules/{$this->moduleNameLower}/src/Http/Controllers/HomeController.php"), $homeControllerStub);

        // Create Api\HomeController for API
        $apiHomeControllerStub = File::get($this->getStubPath('ApiHomeController.stub'));
        // $apiHomeControllerStub = File::get(base_path('modules/Core/src/Console/Commands/stubs/ApiHomeController.stub'));
        $apiHomeControllerStub = str_replace('{{ module_name }}', $this->moduleNameStudly, $apiHomeControllerStub);
        $apiControllerDir = base_path("modules/{$this->moduleNameLower}/src/Http/Controllers/Api");
        if (!File::exists($apiControllerDir)) {
            File::makeDirectory($apiControllerDir, 0755, true);
        }
        File::put("$apiControllerDir/HomeController.php", $apiHomeControllerStub);

        // Create EventServiceProvider
        $eventProviderStub = File::get($this->getStubPath('EventServiceProvider.stub'));
        // $eventProviderStub = File::get(base_path('modules/Core/src/Console/Commands/stubs/EventServiceProvider.stub'));
        $eventProviderStub = str_replace('{{ module_name }}', $this->moduleNameStudly, $eventProviderStub);
        File::put(base_path("modules/{$this->moduleNameLower}/src/Providers/{$this->moduleNameStudly}EventServiceProvider.php"), $eventProviderStub);
    }

    /**
     * Register module in composer.json
     *
     * @return void
     */
    protected function registerModuleInComposer()
    {
        $composerFile = base_path('composer.json');
        $composer = json_decode(File::get($composerFile), true);

        // Add module to autoload
        $composer['autoload']['psr-4']["Modules\\{$this->moduleNameStudly}\\"] = "modules/{$this->moduleNameLower}/src/";

        // Add module to require
        $composer['require']["modules/{$this->moduleNameLower}"] = "*";

        File::put($composerFile, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Create module state
     *
     * @return void
     */
    protected function createModuleState()
    {
        $moduleState = [
            'name' => $this->moduleNameStudly,
            'version' => '1.0.0',
            'enabled' => false,
            'last_enabled_at' => null,
            'last_disabled_at' => null,
            'applied_migrations' => [],
            'failed_migrations' => [],
            'dependencies' => [],
            'dependents' => [],
            'config' => []
        ];

        File::put(
            base_path("modules/{$this->moduleNameLower}/module.json"),
            json_encode($moduleState, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Register module in Core's config
     *
     * @return void
     */
    protected function registerModuleInCoreConfig()
    {
        $configFile = base_path('vendor/rcv-tech/core/src/Config/config.php');

        $config = require $configFile;

        if (!isset($config['modules'])) {
            $config['modules'] = [];
        }

        if (!in_array($this->moduleNameStudly, $config['modules'])) {
            $config['modules'][] = $this->moduleNameStudly;
        }

        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        File::put($configFile, $content);
    }


    protected function getStubPath($path = '')
    {
        return __DIR__ . '/../stubs' . ($path ? "/$path" : '');
    }



// File::get($this->getStubPath('repository.stub'))


}