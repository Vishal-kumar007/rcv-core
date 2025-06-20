<?php

namespace Rcv\Core\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected $moduleName;
    protected $moduleNamespace;

    public function boot()
    {
        $this->routes(function () {
            $webRoutePath = module_path($this->moduleName, 'Routes/web.php');
            $apiRoutePath = module_path($this->moduleName, 'Routes/api.php');

            if (File::exists($webRoutePath)) {
                Route::middleware('web')
                    ->namespace($this->moduleNamespace)
                    ->group($webRoutePath);
            }

            if (File::exists($apiRoutePath)) {
                Route::prefix('api')
                    ->middleware('api')
                    ->namespace($this->moduleNamespace)
                    ->group($apiRoutePath);
            }
        });
    }
} 