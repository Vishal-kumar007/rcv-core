<?php

namespace Rcv\Core\Console\Commands;
use Illuminate\Console\Command;
use Rcv\Core\Services\ModuleDependencyGraph;

class ModuleDependencyGraphCommand extends Command
{
    protected $signature = 'module:dependency-graph 
        {--format=dot : Output format (dot, json)}
        {--output= : Output file path}
        {--validate : Validate dependencies}
        {--circular : Check for circular dependencies}';

    protected $description = 'Generate and visualize module dependency graph';

    protected $dependencyGraph;

    public function __construct(ModuleDependencyGraph $dependencyGraph)
    {
        parent::__construct();
        $this->dependencyGraph = $dependencyGraph;
    }

    public function handle()
    {
        if ($this->option('validate')) {
            $this->validateDependencies();
            return;
        }

        if ($this->option('circular')) {
            $this->checkCircularDependencies();
            return;
        }

        $format = $this->option('format');
        $output = $this->option('output');

        switch ($format) {
            case 'dot':
                $content = $this->dependencyGraph->generateDotGraph();
                break;
            case 'json':
                $content = $this->dependencyGraph->generateJsonGraph();
                break;
            default:
                $this->error("Unsupported format: {$format}");
                return 1;
        }

        if ($output) {
            file_put_contents($output, $content);
            $this->info("Graph written to: {$output}");
        } else {
            $this->line($content);
        }

        return 0;
    }

    protected function validateDependencies()
    {
        $this->info("Validating module dependencies...");
        
        $issues = $this->dependencyGraph->validateDependencies();
        
        if (empty($issues)) {
            $this->info("No dependency issues found.");
            return;
        }

        $this->warn("Found " . count($issues) . " dependency issues:");
        $this->newLine();

        foreach ($issues as $issue) {
            $this->line("Module: {$issue['module']}");
            $this->line("Issue: {$issue['issue']}");
            $this->newLine();
        }
    }

    protected function checkCircularDependencies()
    {
        $this->info("Checking for circular dependencies...");
        
        $circularDeps = $this->dependencyGraph->findCircularDependencies();
        
        if (empty($circularDeps)) {
            $this->info("No circular dependencies found.");
            return;
        }

        $this->warn("Found " . count($circularDeps) . " circular dependencies:");
        $this->newLine();

        foreach ($circularDeps as $dep) {
            $this->line("Cycle detected:");
            $this->line("  " . implode(" -> ", $dep['cycle']) . " -> " . $dep['cycle'][0]);
            $this->newLine();
        }
    }
} 