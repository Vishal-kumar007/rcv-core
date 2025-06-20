<?php

namespace Rcv\Core\Console\Commands\Make;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ModuleAllCommands extends Command
{
    protected $signature = 'module:commands';
    protected $description = 'Display a table of module commands and allow selection';

    public const COLOR = '<fg=#684df4;options=bold>';

    public function handle()
    {
        $categories = [
            ['Number' => 1, 'Description' => 'marketplace'],
            ['Number' => 2, 'Description' => 'Database'],
            ['Number' => 3, 'Description' => 'Make'],
            ['Number' => 4, 'Description' => 'Publish'],
            ['Number' => 5, 'Description' => 'All Commands'],
        ];

        $this->table([self::COLOR . 'No', self::COLOR . 'Description'], $categories);
        $this->newLine();

        $choice = (int) $this->ask(self::COLOR . '👉 Select a number to view its commands');

        switch ($choice) {
            case 1:
                $this->handleGroup(self::COLOR . 'Actions Commands', ['module:marketplace']);
                break;

            case 2:
                $this->handleDatabaseSubMenu();
                break;

            case 3:
                $this->handleGroup(self::COLOR . 'Make Commands', ['module:make']);
                break;

            case 4:
                $this->handleGroup(self::COLOR . 'Publish Commands', ['module:publish']);
                break;

            case 5:
                $this->handleGroup(self::COLOR . 'All Module Commands', ['module:']);
                break;

            default:
                $this->error(self::COLOR . 'Invalid selection.');
        }
    }

    protected function handleDatabaseSubMenu()
    {
        $groups = [
            ['Number' => 1, 'Description' => 'Migrations'],
            ['Number' => 2, 'Description' => 'Seeders'],
            ['Number' => 3, 'Description' => 'Factories'],
            ['Number' => 4, 'Description' => 'All'],
        ];

        $this->table([self::COLOR . 'No', self::COLOR . 'Type'], $groups);
        $this->newLine();

        $subChoice = (int) $this->ask(self::COLOR . '👉 Select a database sub-category');

        switch ($subChoice) {
            case 1:
                $this->handleGroup(self::COLOR . 'Migration Commands', ['module:migrate']);
                break;
            case 2:
                $this->handleGroup(self::COLOR . 'Seeder Commands', ['module:seed']);
                break;
            case 3:
                $this->handleGroup(self::COLOR . 'Factory Commands', ['module:factory']);
                break;
            case 4:
                $this->handleGroup(self::COLOR . 'All Database Commands', ['module:migrate', 'module:seed', 'module:factory']);
                break;
            default:
                $this->error(self::COLOR . 'Invalid sub-category.');
        }
    }

    protected function handleGroup(string $title, array $prefixes)
    {
        $this->info($title);
        $commands = $this->getCommandsByPrefixes($prefixes);

        if (empty($commands)) {
            $this->warn(self::COLOR . 'No commands found.');
            return;
        }

        $indexed = [];
        foreach ($commands as $index => $command) {
            $number = $index + 1;
            $indexed[] = [self::COLOR . 'Index' => $number, self::COLOR . 'Command' => $command];
        }

        $this->table([self::COLOR . 'Index', self::COLOR . 'Command'], $indexed);

        $input = (int) $this->ask(self::COLOR . '👉 Enter the index of the command to run (or 0 to skip)');

        if ($input > 0 && isset($commands[$input - 1])) {
            $selectedCommand = $commands[$input - 1];

            // Ask for module and file name
            $module = $this->ask(self::COLOR . '📦 Enter the module name');
            $file = $this->ask(self::COLOR . '📄 Enter the file/class name');

            $this->newLine();
            $this->info(self::COLOR . "🚀 Running: php artisan $selectedCommand $file $module");

            // Run with arguments
            Artisan::call($selectedCommand, [
                'name' => $file,
                'module' => $module
            ]);

            $this->line(Artisan::output());
        } elseif ($input !== 0) {
            $this->error(self::COLOR . 'Invalid index.');
        }
    }

    protected function getCommandsByPrefixes(array $prefixes): array
    {
        $allCommands = Artisan::all();
        $matched = [];

        foreach ($allCommands as $name => $command) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($name, $prefix)) {
                    $matched[] = $name;
                    break;
                }
            }
        }

        sort($matched);
        return $matched;
    }
}
