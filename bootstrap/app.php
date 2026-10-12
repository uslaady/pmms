<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    //->withMiddleware(function (Middleware $middleware): void {
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'permission' => \App\Http\Middleware\EnsureHasPermission::class,
            'log.audit'  => \App\Http\Middleware\LogAudit::class,
            'directorate' => \App\Http\Middleware\EnsureDirectorateAccess::class,
        ]);

        // Every signed-in request: block non-active accounts and force a personal password.
        $middleware->web(append: [\App\Http\Middleware\EnsureAccountIsUsable::class]);

        // Route-model bindings must be resolved before the directorate check reads them.
        $middleware->appendToPriorityList(
            after: [\Illuminate\Routing\Middleware\SubstituteBindings::class],
            append: \App\Http\Middleware\EnsureDirectorateAccess::class,
        );
    })
    
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
