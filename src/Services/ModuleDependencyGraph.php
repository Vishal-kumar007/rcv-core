<?php

namespace Rcv\Core\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class ModuleDependencyGraph
{
    protected $modulePath;
    protected $cacheManager;
    protected $marketplaceService;
    protected $cacheEnabled;
    protected $cacheTtl;

    public function __construct(CacheManager $cacheManager, MarketplaceService $marketplaceService)
    {
        $this->modulePath = base_path('packages/modules');
        $this->cacheManager = $cacheManager;
        $this->marketplaceService = $marketplaceService;
        $this->cacheEnabled = Config::get('cache.enabled', false);
        $this->cacheTtl = Config::get('cache.ttl', 60);
    }

    public function generateGraph()
    {
        $cacheKey = 'module_dependency_graph';
        
        if ($this->cacheManager && $this->cacheEnabled) {
            $graph = $this->cacheManager->get($cacheKey);
            if ($graph) {
                return $graph;
            }
        }

        $graph = $this->buildGraph();

        if ($this->cacheManager && $this->cacheEnabled) {
            $this->cacheManager->put($cacheKey, $graph, $this->cacheTtl);
        }

        return $graph;
    }

    public function generateDotGraph(): string
    {
        $graph = $this->generateGraph();
        $dot = "digraph ModuleDependencies {\n";
        $dot .= "    rankdir=LR;\n";
        $dot .= "    node [shape=box, style=filled, fillcolor=lightblue];\n\n";

        // Add nodes
        foreach ($graph['nodes'] as $node) {
            $color = $node['enabled'] ? 'lightgreen' : 'lightpink';
            $dot .= "    \"{$node['name']}\" [fillcolor={$color}];\n";
        }

        $dot .= "\n";

        // Add edges
        foreach ($graph['edges'] as $edge) {
            $dot .= "    \"{$edge['from']}\" -> \"{$edge['to']}\";\n";
        }

        $dot .= "}\n";

        return $dot;
    }

    public function generateJsonGraph(): string
    {
        return json_encode($this->generateGraph(), JSON_PRETTY_PRINT);
    }

    protected function buildGraph(): array
    {
        $graph = [
            'nodes' => [],
            'edges' => []
        ];

        $modules = $this->marketplaceService->getAvailableModules();

        // Add nodes
        foreach ($modules as $module) {
            $graph['nodes'][] = [
                'name' => $module['name'],
                'enabled' => $module['status'] === 'enabled',
                'version' => $module['version'] ?? '1.0.0'
            ];
        }

        // Add edges
        foreach ($modules as $module) {
            $dependencies = $this->getModuleDependencies($module['name']);
            foreach ($dependencies as $dependency) {
                $graph['edges'][] = [
                    'from' => $module['name'],
                    'to' => $dependency,
                    'type' => 'requires'
                ];
            }
        }

        return $graph;
    }

    protected function getModuleDependencies(string $moduleName): array
    {
        $dependencies = [];
        $composerFile = "{$this->modulePath}/{$moduleName}/composer.json";

        if (File::exists($composerFile)) {
            $composer = json_decode(File::get($composerFile), true);
            if (isset($composer['require'])) {
                foreach ($composer['require'] as $package => $version) {
                    if (strpos($package, 'modules/') === 0) {
                        $dependencies[] = str_replace('modules/', '', $package);
                    }
                }
            }
        }

        return $dependencies;
    }

    public function findCircularDependencies(): array
    {
        $graph = $this->generateGraph();
        $visited = [];
        $recursionStack = [];
        $circularDeps = [];

        foreach ($graph['nodes'] as $node) {
            if (!isset($visited[$node['name']])) {
                $this->findCircularDependenciesDFS(
                    $node['name'],
                    $graph,
                    $visited,
                    $recursionStack,
                    $circularDeps
                );
            }
        }

        return $circularDeps;
    }

    protected function findCircularDependenciesDFS(
        string $module,
        array $graph,
        array &$visited,
        array &$recursionStack,
        array &$circularDeps
    ): void {
        $visited[$module] = true;
        $recursionStack[$module] = true;

        $edges = array_filter($graph['edges'], function($edge) use ($module) {
            return $edge['from'] === $module;
        });

        foreach ($edges as $edge) {
            $dependency = $edge['to'];

            if (!isset($visited[$dependency])) {
                $this->findCircularDependenciesDFS(
                    $dependency,
                    $graph,
                    $visited,
                    $recursionStack,
                    $circularDeps
                );
            } elseif (isset($recursionStack[$dependency])) {
                $circularDeps[] = [
                    'modules' => array_keys($recursionStack),
                    'cycle' => $this->extractCycle($recursionStack, $dependency)
                ];
            }
        }

        $recursionStack[$module] = false;
    }

    protected function extractCycle(array $recursionStack, string $start): array
    {
        $cycle = [];
        $current = $start;

        do {
            $cycle[] = $current;
            $current = array_search($current, $recursionStack);
        } while ($current !== $start);

        return $cycle;
    }

    public function getModuleDependencyTree(string $moduleName): array
    {
        $tree = [
            'name' => $moduleName,
            'dependencies' => []
        ];

        $dependencies = $this->getModuleDependencies($moduleName);
        foreach ($dependencies as $dependency) {
            $tree['dependencies'][] = $this->getModuleDependencyTree($dependency);
        }

        return $tree;
    }

    public function validateDependencies(): array
    {
        $issues = [];
        $graph = $this->generateGraph();

        foreach ($graph['nodes'] as $node) {
            if ($node['enabled']) {
                $dependencies = array_filter($graph['edges'], function($edge) use ($node) {
                    return $edge['from'] === $node['name'];
                });

                foreach ($dependencies as $dependency) {
                    $depNode = array_filter($graph['nodes'], function($n) use ($dependency) {
                        return $n['name'] === $dependency['to'];
                    });

                    if (!empty($depNode)) {
                        $depNode = reset($depNode);
                        if (!$depNode['enabled']) {
                            $issues[] = [
                                'module' => $node['name'],
                                'issue' => "Required dependency {$dependency['to']} is disabled"
                            ];
                        }
                    } else {
                        $issues[] = [
                            'module' => $node['name'],
                            'issue' => "Required dependency {$dependency['to']} is not installed"
                        ];
                    }
                }
            }
        }

        return $issues;
    }
} 