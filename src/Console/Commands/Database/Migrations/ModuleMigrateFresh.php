<?php

namespace Rcv\Core\Console\Commands\Database\Migrations;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class ModuleMigrateFresh extends Command
{
    protected $signature = 'module:migrate-fresh';
    protected $description = 'Force drop all tables and re-run all module migrations';

    public function handle()
    {
        $this->info('🔁 Disabling foreign key checks...');
        Schema::disableForeignKeyConstraints();

        $this->info('🗒️ Fetching all tables...');
        $dbName = env('DB_DATABASE');
        $key = "Tables_in_{$dbName}";

        
        $tables = DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");

        if (empty($tables)) {
            $this->info('ℹ️ No tables found to drop.');
        } else {
            foreach ($tables as $table) {
                $tableName = $table->$key;

                

                DB::statement("DROP TABLE IF EXISTS `$tableName`");
                $this->line("🗑️ Dropped table: $tableName");
            }
        }

        $this->info('✅ All tables dropped.');

        $this->info('🔁 Re-enabling foreign key checks...');
        Schema::enableForeignKeyConstraints();

        // Clear migration cache
        $this->info('🧹 Clearing migration cache...');
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        // Run migrations forcefully
        $this->info('🚀 Running migrations...');
        Artisan::call('migrate', ['--force' => true]);
        $this->info(Artisan::output());

        $this->info('✅ Module migrations completed successfully.');
    }
}
