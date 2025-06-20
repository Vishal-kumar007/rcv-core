<?php

namespace Rcv\Core\Console\Commands\Make;

use Illuminate\Console\Command;
use Rcv\Core\Services\ModuleMiddlewareManager;

class ModuleMiddlewareCommand extends Command
{
    protected $signature = 'module:middleware 
        {action : The action to perform (register, unregister, list, validate)}
        {module? : The name of the module}';

    protected $description = 'Manage module middleware registration and validation';

    protected $middlewareManager;

    public function __construct(ModuleMiddlewareManager $middlewareManager)
    {
        parent::__construct();
        $this->middlewareManager = $middlewareManager;
    }

    public function handle()
    {
        $action = $this->argument('action');
        $module = $this->argument('module');

        switch ($action) {
            case 'register':
                $this->registerMiddleware($module);
                break;
            case 'unregister':
                $this->unregisterMiddleware($module);
                break;
            case 'list':
                $this->listMiddleware($module);
                break;
            case 'validate':
                $this->validateMiddleware($module);
                break;
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }

        return 0;
    }

    protected function registerMiddleware(?string $module): void
    {
        if ($module) {
            $this->middlewareManager->registerModuleMiddleware($module);
            $this->info("Registered middleware for module: {$module}");
        } else {
            $this->error('Module name is required for registration');
        }
    }

    protected function unregisterMiddleware(?string $module): void
    {
        if ($module) {
            $this->middlewareManager->unregisterModuleMiddleware($module);
            $this->info("Unregistered middleware for module: {$module}");
        } else {
            $this->error('Module name is required for unregistration');
        }
    }

    protected function listMiddleware(?string $module): void
    {
        $middleware = $this->middlewareManager->getRegisteredMiddleware();
        
        if ($module) {
            $this->info("Middleware for module: {$module}");
            $this->displayModuleMiddleware($middleware, $module);
        } else {
            $this->info('All registered middleware:');
            $this->displayAllMiddleware($middleware);
        }
    }

    protected function validateMiddleware(?string $module): void
    {
        $issues = $this->middlewareManager->validateMiddleware();
        
        if (empty($issues)) {
            $this->info('No middleware issues found.');
            return;
        }

        $this->error('Found middleware issues:');
        
        $headers = ['Group', 'Name', 'Class', 'Issue'];
        $rows = [];

        foreach ($issues as $issue) {
            if (!$module || strpos($issue['name'], $module . '.') === 0) {
                $rows[] = [
                    $issue['group'],
                    $issue['name'],
                    $issue['class'],
                    $issue['issue']
                ];
            }
        }

        $this->table($headers, $rows);
    }

    protected function displayModuleMiddleware(array $middleware, string $module): void
    {
        $modulePrefix = strtolower($module) . '.';
        
        foreach ($middleware as $group => $groupMiddleware) {
            $this->line("\n<fg=cyan>{$group} middleware:</>");
            
            $rows = [];
            foreach ($groupMiddleware as $name => $class) {
                if (strpos($name, $modulePrefix) === 0) {
                    $rows[] = [$name, $class];
                }
            }
            
            if (!empty($rows)) {
                $this->table(['Name', 'Class'], $rows);
            } else {
                $this->line('No middleware found for this group.');
            }
        }
    }

    protected function displayAllMiddleware(array $middleware): void
    {
        foreach ($middleware as $group => $groupMiddleware) {
            $this->line("\n<fg=cyan>{$group} middleware:</>");
            
            $rows = [];
            foreach ($groupMiddleware as $name => $class) {
                $rows[] = [$name, $class];
            }
            
            if (!empty($rows)) {
                $this->table(['Name', 'Class'], $rows);
            } else {
                $this->line('No middleware found for this group.');
            }
        }
    }
} 