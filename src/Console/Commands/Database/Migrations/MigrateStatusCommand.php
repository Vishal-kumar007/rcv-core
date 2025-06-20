<?php

namespace Rcv\Core\Console\Commands\Database\Migrations;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MigrateStatusCommand extends Command
{
    protected $signature = 'module:migrate-status';
    protected $description = 'Show the status of each module\'s migrations';

    public function handle()
    {
        $modulesPath = base_path('Modules');
        $ranMigrations = DB::table('migrations')->pluck('migration')->toArray();

        $this->info("Migration Status for Modules:\n");

        foreach (File::directories($modulesPath) as $modulePath) {
            $moduleName = basename($modulePath);
            $migrationPath = $modulePath . '/src/Database/Migrations';

            if (!File::exists($migrationPath)) {
                $this->warn("No migrations found for module: {$moduleName}");
                continue;
            }

            $files = File::files($migrationPath);
            if (count($files) === 0) {
                $this->warn("No migrations in directory for module: {$moduleName}");
                continue;
            }

            $this->line("\n<fg=blue>{$moduleName}</>");

            foreach ($files as $file) {
                $migrationName = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $status = in_array($migrationName, $ranMigrations) ? '<fg=green>Yes</>' : '<fg=red>No</>';
                $this->line("  [{$status}] {$migrationName}");
            }
        }

        return 0;
    }
}
