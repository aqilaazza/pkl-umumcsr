<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! Auth::check()) {
            return redirect('/login');
        }

        $role = Auth::user()->role;

        if (! empty($roles) && ! in_array($role, $roles, true)) {
            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }
}
