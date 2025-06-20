<?php

namespace Rcv\Core\Console\Commands\Make;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModuleModelMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make-model {name : The name of the model} {module : The name of the module} {--migration : Create a new migration file for the model} {--factory : Create a new factory for the model} {--seed : Create a new seeder for the model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model for a module';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->argument('name');
        $module = $this->argument('module');

        // Ensure module exists
        if (!File::exists(base_path("modules/{$module}"))) {
            $this->error("Module [{$module}] does not exist.");
            return 1;
        }

        // Create model directory if it doesn't exist
        $modelPath = base_path("modules/{$module}/src/Models");
        if (!File::exists($modelPath)) {
            File::makeDirectory($modelPath, 0755, true);
        }

        // Generate model file
        $modelFile = "{$modelPath}/{$name}.php";
        $stub = File::get(__DIR__ . '/../stubs/model.stub');
        // $stubPath = __DIR__ . '/../stubs/enum.stub';

        // Replace placeholders
        $content = str_replace(
            ['{{ module_name }}', '{{ class_name }}'],
            [$module, $name],
            $stub
        );

        // Create model file
        File::put($modelFile, $content);
        $this->info("Model [{$name}] created successfully.");
        $this->info("Path: {$modelFile}");

        // Create migration if requested
        if ($this->option('migration')) {
            $table = Str::snake(Str::pluralStudly($name));
            $this->call('module:make-migration', [
                'name' => "create_{$table}_table",
                'module' => $module
            ]);
        }

        // Create factory if requested
        if ($this->option('factory')) {
            $this->call('module:make-factory', [
                'name' => "{$name}Factory",
                'module' => $module
            ]);
        }

        // Create seeder if requested
        if ($this->option('seed')) {
            $this->call('module:make-seeder', [
                'name' => "{$name}Seeder",
                'module' => $module
            ]);
        }

        return 0;
    }
}
