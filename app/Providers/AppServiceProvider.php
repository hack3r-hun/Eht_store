<?php

namespace App\Providers;

use App\Models\Order;
use App\Policies\OrderPolicy;
use App\Services\CartService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Order::class, OrderPolicy::class);

        Password::defaults(function () {
            return $this->app->environment('production')
                ? Password::min(10)->letters()->mixedCase()->numbers()->symbols()->uncompromised()
                : Password::min(8);
        });

        if ($this->app->environment('production')) {
            URL::forceScheme('https');

            config([
                'session.secure' => true,
                'session.same_site' => 'lax',
                'session.domain' => null,
            ]);

            if (config('logging.default') === 'stack' || config('logging.default') === 'single') {
                config(['logging.default' => 'stderr']);
            }
        }

        View::composer('layouts.storefront', function ($view) {
            $view->with('cartCount', app(CartService::class)->count());
        });

        Event::listen(Login::class, function (Login $event) {
            app(CartService::class)->mergeGuestCart($event->user->id);
        });
    }
}
