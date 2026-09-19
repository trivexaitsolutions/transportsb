<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Never expose raw SQL / foreign-key exception text in the application UI.
        $exceptions->render(function (QueryException $e, Request $request) {
            $message = 'This action cannot be completed because the record is already in use or linked to another entry.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 409);
            }
            return back()->with('error', $message);
        });
    })->create();
