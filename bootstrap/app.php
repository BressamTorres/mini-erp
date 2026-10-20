<?php

use App\Http\Middleware\SetBusinessContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'abilities' => CheckAbilities::class,
        ]);
        $middleware->prependToPriorityList(SubstituteBindings::class, SetBusinessContext::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if (! $request->routeIs([
                'customers.store',
                'customers.update',
                'products.store',
                'products.update',
                'sellers.store',
                'sellers.update',
            ])) {
                return false;
            }

            $message = 'A imagem excede o limite técnico do servidor. Selecione um arquivo menor.';

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => $message], 413);
            }

            return response()->view('errors.upload-too-large', ['message' => $message], 413);
        });
    })->create();
