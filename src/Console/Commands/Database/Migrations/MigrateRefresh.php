<?php

namespace Rcv\Core\Console\Commands\Database\Migrations;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class MigrateRefresh extends Command
{
    protected $signature = 'module:migrate-refresh';

    protected $description = 'Rollback and re-run all module migrations';

    public function handle()
    {
        $this->info('Rolling back all module migrations...');
        $this->call('module:migrate-reset');

        $this->info('Re-running all module migrations...');
        $this->call('module:migrate');

        $this->info('Module migrations refreshed successfully.');
    }
}
