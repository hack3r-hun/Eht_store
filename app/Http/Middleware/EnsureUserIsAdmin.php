<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            // A non-admin hitting an admin-only route is either a broken link or a
            // privilege-escalation probe — either way it's worth a durable record.
            Log::channel('security')->warning('admin_access_denied', [
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
