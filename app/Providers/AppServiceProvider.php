<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // One compact pagination component for every table in the app
        Paginator::defaultView('pagination.compact');
        Paginator::defaultSimpleView('pagination.compact');
    }
}
