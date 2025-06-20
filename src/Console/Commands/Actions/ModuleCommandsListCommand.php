<?php

namespace Rcv\Core\Console\Commands\Actions;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class ModuleCommandsListCommand extends Command
{
    protected $signature = 'list:commands {--debug}';
    protected $description = 'List all registered Artisan commands related to modules with optional debug information';

    public function handle(): int
    {
        $debug = $this->option('debug');

        $this->components->twoColumnDetail('<fg=gray>Command</>', '<fg=gray>Description</>');

        foreach ($this->getModuleCommands() as $command) {
            $this->components->twoColumnDetail($command['name'], $command['description']);
        }

        if ($debug) {
            $this->line('');
            $this->comment('Debug Information:');
            $this->line('');
            $this->line("Total Module Commands: " . count($this->getModuleCommands()));
        }

        return 0;
    }

    protected function getModuleCommands()
    {
        $commands = [];
        $moduleNamespace = 'Modules\\';

        foreach ($this->getApplication()->all() as $command) {
            if (strpos($command->getName(), 'module:') === 0) {
                $commands[] = [
                    'name' => $command->getName(),
                    'description' => $command->getDescription() ?: '<fg=yellow>No description available</>',
                ];
            }
        }

        return $commands;
    }

    protected function getOptions()
    {
        return [
            ['debug', 'd', InputOption::VALUE_NONE, 'Display debug information'],
        ];
    }
}