<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            if (! app()->environment('production')) {
                Route::middleware(['auth:sanctum', 'ability:admin', 'admin'])
                    ->get('/_diag', function () {
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

                        return response()->json(['data' => $checks], $failed ? 500 : 200);
                    });
            }

            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind a known reverse proxy (Railway, Cloudflare, nginx), "*" is fine.
        // Document TRUSTED_PROXIES if you need to restrict later.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'module' => \App\Http\Middleware\EnsureModuleEnabled::class,
            'verified.api' => \App\Http\Middleware\EnsureApiEmailVerified::class,
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/stripe',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();
