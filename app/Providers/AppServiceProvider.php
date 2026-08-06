<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
    }
}
