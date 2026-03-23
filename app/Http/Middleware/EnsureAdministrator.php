<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdministrator()) {
            abort(403, 'Akses halaman ini hanya untuk Administrator.');
        }

        return $next($request);
    }
}
