<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Kalau belum login, lempar ke halaman login
        if (! Auth::check()) {
            return redirect('/login');
        }

        // Kalau login tapi bukan admin, tolak akses
        if (! Auth::user()->isAdmin()) {
            abort(403, 'Halaman ini khusus admin.');
        }

        return $next($request);
    }
}
