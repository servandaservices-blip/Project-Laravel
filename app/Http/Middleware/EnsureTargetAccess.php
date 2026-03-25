<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTargetAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canAccessTargetMenu()) {
            abort(403, 'Akses halaman ini hanya untuk user yang memiliki akses Target.');
        }

        return $next($request);
    }
}
