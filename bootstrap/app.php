<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            if (! app()->environment('production')) {
                Route::get('/_diag', function () {
                    $checks = [];

                    try {
                        \Illuminate\Support\Facades\DB::connection()->getPdo();
                        $checks['database'] = 'ok';
                    } catch (\Throwable $e) {
                        $checks['database'] = $e->getMessage();
                    }

                    $checks['spatie'] = class_exists(\Spatie\Permission\Traits\HasRoles::class) ? 'ok' : 'missing';

                    try {
                        \Illuminate\Support\Facades\Cache::put('_diag_probe', '1', 10);
                        $checks['cache'] = \Illuminate\Support\Facades\Cache::get('_diag_probe') === '1' ? 'ok' : 'fail';
                    } catch (\Throwable $e) {
                        $checks['cache'] = $e->getMessage();
                    }

                    $failed = collect($checks)->contains(fn ($v) => $v !== 'ok');

                    return response()->json($checks, $failed ? 500 : 200);
                });
            }

            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/stripe',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
