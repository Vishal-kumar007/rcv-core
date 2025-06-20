<?php

namespace Rcv\Core\Console\Commands\Actions;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModulePruneCommand extends Command
{
    protected $signature = 'module:prune {module*}                                                      
                            {--all : Check all Modules}
                            {--model=* : Class names of the models to be pruned}
                            {--except=* : Class names of the models to be excluded from pruning}
                            {--path=* : Absolute path(s) to directories where models are located}
                            {--chunk=1000 : Number of models per chunk}
                            {--pretend : Show prunable count instead of deleting}';


# Prune specific models from Blog module
// php artisan module:prune Blog --model=Post --model=Comment

# Prune all models with 'prunable' in all modules
// php artisan module:prune --all

# Pretend mode (no delete, just count)
// php artisan module:prune Blog --pretend

# Chunked deletion
// php artisan module:prune Blog --chunk=500

# Skip specific models
// php artisan module:prune Blog --except=OldLog




    protected $description = 'Prune models by module that are no longer needed';

    public function handle()
    {
        $modules = $this->option('all') ? $this->getAllModules() : $this->argument('module');
        $paths = $this->option('path') ?: [];
        $only = $this->option('model');
        $except = $this->option('except');
        $pretend = $this->option('pretend');
        $chunkSize = (int) $this->option('chunk');

        foreach ($modules as $module) {
            $this->info("🔍 Scanning module: {$module}");

            $modulePath = base_path("Modules/{$module}/src");
            if (!File::exists($modulePath)) {
                $this->warn("⚠️ Module path not found: {$modulePath}");
                continue;
            }

            $searchPaths = $paths ?: [$modulePath];

            foreach ($searchPaths as $path) {
                if (!File::exists($path)) {
                    $this->warn("❌ Path does not exist: $path");
                    continue;
                }

                $files = File::allFiles($path);

                foreach ($files as $file) {
                    $class = $this->getClassFromFile($file->getRealPath());
                    if (!$class) continue;

                    if (!empty($only) && !in_array(class_basename($class), $only)) continue;
                    if (in_array(class_basename($class), $except)) continue;

                    if (!method_exists($class, 'prunable')) continue;

                    $model = new $class;

                    if ($pretend) {
                        $count = $model->prunable()->count();
                        $this->line("🔸 [Pretend] {$class}: {$count} prunable");
                    } else {
                        $this->line("🗑️ Pruning {$class}...");
                        $model->pruneAll($chunkSize);
                    }
                }
            }
        }

        // Optional: create stub helper class
        $helperStub = __DIR__ . '/stubs/prune-helper.stub';
        $helperPath = base_path('Modules/PruneHelper.php');
        if (File::exists($helperStub) && !File::exists($helperPath)) {
            File::put($helperPath, file_get_contents($helperStub));
            $this->info("📝 Generated helper: Modules/PruneHelper.php");
        }

        $this->info('✅ Pruning process complete.');
    }

    protected function getClassFromFile(string $path): ?string
    {
        $content = File::get($path);

        if (preg_match('/namespace\s+(.+);/', $content, $nsMatch) &&
            preg_match('/class\s+([^\s]+)/', $content, $classMatch)) {
            return $nsMatch[1] . '\\' . $classMatch[1];
        }

        return null;
    }

    protected function getAllModules(): array
    {
        $moduleDirs = File::directories(base_path('Modules'));
        return array_map(fn($dir) => basename($dir), $moduleDirs);
    }
}
