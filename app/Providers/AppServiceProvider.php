<?php

namespace App\Providers;

use App\Models\ContactMessage;
use App\Models\Order;
use App\Policies\OrderPolicy;
use App\Services\CartService;
use App\Services\InventoryService;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One instance per request: all module switches come from a single cached query.
        $this->app->singleton(\App\Support\Modules\ModuleSettings::class);
    }

    public function boot(): void
    {
        $this->configureRateLimiting();

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
                'session.encrypt' => true,
                'session.same_site' => 'lax',
                'session.domain' => null,
            ]);

            if (config('logging.default') === 'stack' || config('logging.default') === 'single') {
                config(['logging.default' => 'stderr']);
            }
        }

        View::composer('layouts.storefront', function ($view) {
            $view->with('cartCount', app(CartService::class)->count());
            $view->with('footerPages', Cache::remember('cms.footer_pages', 300, function () {
                return \App\Models\Page::where('is_published', true)->get()
                    ->filter(fn ($page) => ! $page->isSystem() && $page->metaValue('show_in_footer'))
                    ->map(fn ($page) => ['title' => $page->title, 'slug' => $page->slug])
                    ->values()
                    ->all();
            }));
        });

        View::composer('layouts.admin', function ($view) {
            $view->with('sidebarBadges', Cache::remember('admin.sidebar.badges', 60, function () {
                return [
                    'pending_orders' => Order::whereIn('status', ['pending', 'awaiting_cod'])->count(),
                    'unread_messages' => ContactMessage::where('is_read', false)->count(),
                    'low_stock' => app(InventoryService::class)->lowStockCount(),
                    'pending_reviews' => \App\Models\Review::where('is_approved', false)->count(),
                    'new_custom_orders' => \App\Models\CustomOrder::where('status', 'new')->count(),
                ];
            }));
        });

        Event::listen(Login::class, function (Login $event) {
            app(CartService::class)->mergeGuestCart($event->user->id);

            if (request()->hasSession()) {
                app(\App\Services\WishlistService::class)->mergeGuest($event->user->id, session()->getId());
            }
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth-api', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('checkout-api', function (Request $request) {
            return Limit::perMinute(20)->by(
                $request->user()?->id ?: ($request->ip().'|'.$request->header('X-Guest-Cart-Token', 'guest'))
            );
        });
    }
}
