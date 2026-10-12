# Registering the custom middleware (Laravel 12)

Laravel 12 no longer ships `app/Http/Kernel.php`. Register the two custom
middleware in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'permission' => \App\Http\Middleware\EnsureHasPermission::class,
        'log.audit'  => \App\Http\Middleware\LogAudit::class,
    ]);
})
```
