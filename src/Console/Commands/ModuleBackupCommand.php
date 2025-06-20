<?php

namespace Rcv\Core\Console\Commands;

use Illuminate\Console\Command;
use Rcv\Core\Services\ModuleBackupManager;

class ModuleBackupCommand extends Command
{
    protected $signature = 'module:backup 
        {action : The action to perform (create, restore, list, delete, cleanup)}
        {module? : The name of the module}
        {--backup= : The backup path for restore/delete actions}';

    protected $description = 'Manage module backups';

    protected $backupManager;

    public function __construct(ModuleBackupManager $backupManager)
    {
        parent::__construct();
        $this->backupManager = $backupManager;
    }

    public function handle()
    {
        $action = $this->argument('action');
        $module = $this->argument('module');

        switch ($action) {
            case 'create':
                $this->createBackup($module);
                break;
            case 'restore':
                $this->restoreBackup();
                break;
            case 'list':
                $this->listBackups($module);
                break;
            case 'delete':
                $this->deleteBackup();
                break;
            case 'cleanup':
                $this->cleanupBackups();
                break;
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }

        return 0;
    }

    protected function createBackup(?string $module)
    {
        if (!$module) {
            $this->error('Module name is required for backup creation');
            return 1;
        }

        try {
            $backupPath = $this->backupManager->createBackup($module);
            $this->info("Backup created successfully: {$backupPath}");
        } catch (\Exception $e) {
            $this->error("Failed to create backup: " . $e->getMessage());
            return 1;
        }
    }

    protected function restoreBackup()
    {
        $backupPath = $this->option('backup');
        if (!$backupPath) {
            $this->error('Backup path is required for restore action');
            return 1;
        }

        if (!$this->confirm('Are you sure you want to restore this backup? This will overwrite the current module state.')) {
            return 0;
        }

        try {
            $this->backupManager->restoreBackup($backupPath);
            $this->info("Backup restored successfully");
        } catch (\Exception $e) {
            $this->error("Failed to restore backup: " . $e->getMessage());
            return 1;
        }
    }

    protected function listBackups(?string $module)
    {
        $backups = $this->backupManager->listBackups($module);

        if (empty($backups)) {
            $this->info("No backups found" . ($module ? " for module {$module}" : ""));
            return;
        }

        $headers = ['Module', 'Version', 'Created', 'Size', 'Path'];
        $rows = [];

        foreach ($backups as $backup) {
            $rows[] = [
                $backup['module'],
                $backup['version'],
                $backup['age'],
                $this->formatSize($backup['size']),
                $backup['path']
            ];
        }

        $this->table($headers, $rows);
    }

    protected function deleteBackup()
    {
        $backupPath = $this->option('backup');
        if (!$backupPath) {
            $this->error('Backup path is required for delete action');
            return 1;
        }

        if (!$this->confirm('Are you sure you want to delete this backup?')) {
            return 0;
        }

        try {
            $this->backupManager->deleteBackup($backupPath);
            $this->info("Backup deleted successfully");
        } catch (\Exception $e) {
            $this->error("Failed to delete backup: " . $e->getMessage());
            return 1;
        }
    }

    protected function cleanupBackups()
    {
        if (!$this->confirm('Are you sure you want to clean up old backups?')) {
            return 0;
        }

        try {
            $this->backupManager->cleanupOldBackups();
        } catch (\Exception $e) {
            $this->error("Failed to clean up backups: " . $e->getMessage());
            return 1;
        }
    }

    protected function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
} 