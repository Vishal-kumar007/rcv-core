<?php

namespace Rcv\Core\Console\Commands;    

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ModuleClearCompiled extends Command
{
    protected $signature = 'module:clear-compiled';
    protected $description = 'Remove the module compiled class file';

    public function handle()
    {
        $compiledPath = base_path('bootstrap/cache/modules.php');

        if (File::exists($compiledPath)) {
            File::delete($compiledPath);
            $this->info('The module compiled class file has been removed.');
        } else {
            $this->warn('No compiled module file found.');
        }
    }
}
