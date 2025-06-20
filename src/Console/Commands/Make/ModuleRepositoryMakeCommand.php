<?php

namespace Rcv\Core\Console\Commands\Make;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ModuleRepositoryMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make-repository {name} {module}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository for a module';

    /**jdkzfefas
     * Execute the console command.
     */
   public function handle()
{
    $name = $this->argument('name');
    $module = $this->argument('module');

    if (!File::exists(base_path("modules/{$module}"))) {
        $this->error("Module [{$module}] does not exist.");
        return 1;
    }

    $repositoryPath = base_path("modules/{$module}/src/Repositories");
    if (!File::exists($repositoryPath)) {
        File::makeDirectory($repositoryPath, 0755, true);
    }

    $repositoryFile = "{$repositoryPath}/{$name}.php";

    $stubPath = __DIR__ . '/../stubs/repository.stub';

    if (!File::exists($stubPath)) {
        $this->error("Stub file not found at {$stubPath}");
        return 1;
    }

    $stub = File::get($stubPath);

    $content = str_replace(
        ['{{ module_name }}', '{{ class_name }}'],
        [$module, str_replace('.php', '', $name)],
        $stub
    );

    File::put($repositoryFile, $content);

    $this->info("Repository [{$name}] created successfully.");
    $this->info("Path: {$repositoryFile}");

    return 0;
}

}
