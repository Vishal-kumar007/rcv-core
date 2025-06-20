<?php
namespace Rcv\Core\Console\Commands\Make;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

abstract class GeneratorCommand extends Command
{
    protected Filesystem $files;

    public function __construct()
    {
        parent::__construct();
        $this->files = new Filesystem;
    }

    abstract protected function getTemplateContents(): string;
    abstract protected function getDestinationFilePath(): string;

    public function handle(): int
    {
        $path = $this->getDestinationFilePath();
        $contents = $this->getTemplateContents();

        if ($this->files->exists($path)) {
            $this->error("File already exists at: {$path}");
            return static::FAILURE;
        }

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $contents);

        $this->info("Scope created at: {$path}");
        return static::SUCCESS;
    }
}
