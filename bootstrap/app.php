<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

    $middleware->trustProxies(at: '*');

    $middleware->alias([
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'iot.key' => \App\Http\Middleware\IotKeyMiddleware::class,
    ]);
})
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response, \Throwable $e) {
            if ($e instanceof TokenMismatchException) {
                return redirect()
                    ->route('login')
                    ->with('info', 'Tu sesión expiró por seguridad. Ingresa de nuevo.');
            }

            return $response;
        });
    })
    ->create();
