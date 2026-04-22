<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('admin.login');
        }

        if (! $user->role->canAccessAdminPortal()) {
            abort(403, 'You are not authorized to access the admin portal.');
        }

        return $next($request);
    }
}
