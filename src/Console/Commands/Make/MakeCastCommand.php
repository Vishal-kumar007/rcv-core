<?php

namespace Rcv\Core\Console\Commands\Make;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeCastCommand extends Command
{
    protected $signature = 'module:make-cast 
                            {module : The name of the module} 
                            {name : The name of the cast class}';

    protected $description = 'Create a new Eloquent cast class for the specified module';

    protected Filesystem $files;

    public function __construct()
    {
        parent::__construct();
        $this->files = new Filesystem();
    }

    public function handle()
    {
        $module = $this->argument('module'); // e.g. Student
        $name = $this->argument('name');     // e.g. FormatDate
        $className = ucfirst($name);

        // Path where cast class will be created
        $castDir = base_path("Modules/{$module}/src/database/casts");

        if (! $this->files->isDirectory($castDir)) {
            $this->files->makeDirectory($castDir, 0755, true);
        }

        $castFile = "{$castDir}/{$className}.php";

        if ($this->files->exists($castFile)) {
            $this->error("Cast class '{$className}' already exists in module '{$module}'!");
            return 1;
        }

        // Stub path from Core module
      
        $stubPath = __DIR__ . '/../stubs/cast.stub';

        if (! $this->files->exists($stubPath)) {
            $this->error("Stub file not found at {$stubPath}");
            return 1;
        }

        $stub = $this->files->get($stubPath);

        // Replace placeholders
      
        $stub = str_replace('{{ module_name }}', $module, $stub);
        $stub = str_replace('{{ class_name }}', $name, $stub);

        $this->files->put($castFile, $stub);

        $this->info("Cast class '{$className}' created successfully in module '{$module}'.");
        return 0;
    }
}
