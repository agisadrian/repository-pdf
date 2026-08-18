<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Pakai style Bootstrap buat tombol pagination, bukan Tailwind
        // (soalnya project ini murni CSS biasa, nggak pakai Tailwind)
        Paginator::useBootstrapFive();

        // Badge jumlah pengajuan "jadi Admin" yang masih pending, dipakai di
        // menu "Permintaan Admin" pada sidebar admin. Cuma dihitung kalau yang
        // login Super Admin, biar nggak nambah query buat role lain.
        View::composer('layouts.admin', function ($view) {
            $count = 0;

            if (Auth::check() && Auth::user()->isSuperAdmin()) {
                $count = User::where('role', 'user')
                    ->whereNotNull('admin_requested_at')
                    ->count();
            }

            $view->with('pendingAdminRequestsCount', $count);
        });
    }
}
