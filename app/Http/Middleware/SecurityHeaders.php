<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Generated before the view renders so templates can attach it to their inline
        // <script> tags via nonce="{{ $cspNonce }}", letting script-src drop 'unsafe-inline'.
        $nonce = base64_encode(random_bytes(16));
        View::share('cspNonce', $nonce);

        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($nonce));

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    protected function contentSecurityPolicy(string $nonce): string
    {
        // 'unsafe-eval' is required by the standard `alpinejs` package (not the CSP-safe
        // build) — it evaluates x-data/x-on directive expressions via `new Function()`.
        // This is unrelated to 'unsafe-inline'/nonces, which only govern <script> elements;
        // without it every Alpine component site-wide (size picker, dropdowns, cropper)
        // throws a CSP violation and silently fails to initialize.
        $scriptSrc = ["'self'", "'unsafe-eval'", "'nonce-{$nonce}'", 'https://js.stripe.com'];
        $connectSrc = ["'self'", 'https://api.stripe.com'];
        $frameSrc = ["'self'", 'https://js.stripe.com', 'https://hooks.stripe.com'];
        // Inline `style="..."` attributes are still used by a couple of Breeze-scaffold
        // components (profile delete-account modal, admin dropdown). Removing 'unsafe-inline'
        // here would need those converted to Alpine-driven visibility and browser-verified,
        // which is out of scope for this pass — script-src is the higher-value fix since
        // script execution, not inline CSS, is the actual XSS payload vector.
        $styleSrc = ["'self'", "'unsafe-inline'"];
        $fontSrc = ["'self'", 'data:'];
        $imgSrc = ["'self'", 'data:', 'https:', 'blob:'];

        if (! app()->environment('production')) {
            $scriptSrc[] = 'http://localhost:5173';
            $scriptSrc[] = 'http://127.0.0.1:5173';
            $connectSrc[] = 'http://localhost:5173';
            $connectSrc[] = 'http://127.0.0.1:5173';
            $connectSrc[] = 'ws://localhost:5173';
            $connectSrc[] = 'ws://127.0.0.1:5173';
            $styleSrc[] = 'http://localhost:5173';
            $styleSrc[] = 'http://127.0.0.1:5173';
            // Scribe docs (non-production)
            $styleSrc[] = 'https://fonts.googleapis.com';
            $fontSrc[] = 'https://fonts.gstatic.com';
            $scriptSrc[] = 'https://cdn.jsdelivr.net';
        }

        // Storefront uses Bunny fonts in all environments
        $styleSrc[] = 'https://fonts.bunny.net';
        $fontSrc[] = 'https://fonts.bunny.net';
        $connectSrc[] = 'https://fonts.bunny.net';

        return implode('; ', [
            "default-src 'self'",
            'script-src '.implode(' ', $scriptSrc),
            'style-src '.implode(' ', $styleSrc),
            'img-src '.implode(' ', $imgSrc),
            'font-src '.implode(' ', $fontSrc),
            'connect-src '.implode(' ', $connectSrc),
            'frame-src '.implode(' ', $frameSrc),
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            'object-src \'none\'',
        ]);
    }
}
