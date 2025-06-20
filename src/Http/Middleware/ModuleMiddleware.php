<?php

namespace Rcv\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

abstract class ModuleMiddleware
{
    /**
     * The module name.
     *
     * @var string
     */
    protected $moduleName;

    /**
     * Create a new middleware instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->moduleName = $this->getModuleName();
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$this->isModuleEnabled()) {
            return $this->handleDisabledModule($request);
        }

        try {
            $response = $this->processRequest($request, $next);
            $this->logRequest($request, $response);
            return $response;
        } catch (\Exception $e) {
            $this->logError($request, $e);
            return $this->handleError($request, $e);
        }
    }

    /**
     * Process the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    abstract protected function processRequest(Request $request, Closure $next);

    /**
     * Handle a disabled module.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function handleDisabledModule(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Module is disabled',
                'module' => $this->moduleName
            ], 403);
        }

        return response()->view('core::errors.module-disabled', [
            'module' => $this->moduleName
        ], 403);
    }

    /**
     * Handle an error.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return mixed
     */
    protected function handleError(Request $request, \Exception $e)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Module error',
                'message' => $e->getMessage(),
                'module' => $this->moduleName
            ], 500);
        }

        return response()->view('core::errors.module-error', [
            'module' => $this->moduleName,
            'error' => $e->getMessage()
        ], 500);
    }

    /**
     * Check if the module is enabled.
     *
     * @return bool
     */
    protected function isModuleEnabled(): bool
    {
        return app('modules')->isEnabled($this->moduleName);
    }

    /**
     * Get the module name.
     *
     * @return string
     */
    protected function getModuleName(): string
    {
        $class = get_class($this);
        $parts = explode('\\', $class);
        return $parts[1] ?? 'unknown';
    }

    /**
     * Log the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $response
     * @return void
     */
    protected function logRequest(Request $request, $response): void
    {
        if (config('middleware.config.logging', true)) {
            Log::info('Module middleware request', [
                'module' => $this->moduleName,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'status' => $response->status() ?? 200
            ]);
        }
    }

    /**
     * Log an error.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return void
     */
    protected function logError(Request $request, \Exception $e): void
    {
        Log::error('Module middleware error', [
            'module' => $this->moduleName,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    /**
     * Get the middleware's priority.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return config("middleware.priority.{$this->moduleName}", 0);
    }
} 