<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSuperAdmin
{
    // Sama seperti EnsureUserIsAdmin, tapi lebih ketat: cuma role 'super_admin'
    // yang boleh lewat. Dipakai buat halaman Kelola Kategori & Kelola Pengguna.
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect('/login');
        }

        if (! Auth::user()->isSuperAdmin()) {
            abort(403, 'Halaman ini khusus Super Admin.');
        }

        return $next($request);
    }
}
