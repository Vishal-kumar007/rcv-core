<?php

namespace  Rcv\Core\Console\Commands\Make;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeComponentView extends Command
{
    protected $signature = 'module:make-component-view {module} {component}';
    protected $description = 'Create a new component-view for the specified module';

    public function handle()
    {
        $module = $this->argument('module');
        $component = $this->argument('component');

        $basePath = base_path("Modules/{$module}/src/Resources/views/components");

        if (!File::exists($basePath)) {
            File::makeDirectory($basePath, 0755, true);
        }

        $filePath = "{$basePath}/{$component}.blade.php";

        if (File::exists($filePath)) {
            $this->error("Component view already exists: {$filePath}");
            return;
        }

        File::put($filePath, $this->getStubContent($component));

        $this->info("Component view created: {$component}");
    }

    protected function getStubContent($component)
    {
        return <<<BLADE
{{-- Component View: {$component} --}}
<div>
    <!-- {$component} component content -->
</div>
BLADE;
    }
}
