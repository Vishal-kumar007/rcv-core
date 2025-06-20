<?php


namespace Rcv\Core\Console\Commands\Actions;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

class ModuleUnuseCommand extends Command
{
    protected $signature = 'module:unuse';
    protected $description = 'Unset the active module for the current CLI session';
 
    public function handle()
    {
        // Remove the active module from the configuration
        Config::set('app.active_module', null);

        // Update the .env file to remove the active module setting
        $this->updateEnvFile('ACTIVE_MODULE', '');

        $this->info('Active module has been unset.');
        return 0;
    }

    protected function updateEnvFile($key, $value)
    {
        $path = base_path('.env');
        if (!file_exists($path)) {
            touch($path);
        }

        $oldValue = env($key);
        if (isset($oldValue)) {
            file_put_contents($path, str_replace(
                "{$key}=" . env($key),
                "{$key}={$value}",
                file_get_contents($path)
            ));
        } else {
            file_put_contents($path, file_get_contents($path) . "\n{$key}={$value}");
        }
    }
}
