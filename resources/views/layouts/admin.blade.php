@extends('layouts.app')

@section('content')

    <div class="admin-layout">
        <aside class="admin-sidebar">
            <nav class="admin-nav">
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                     Dashboard
                </a>
                <a href="{{ route('admin.documents.index') }}" class="{{ request()->routeIs('admin.documents.*') ? 'active' : '' }}">
                     Kelola Dokumen
                </a>
                <a href="{{ route('admin.statistics') }}" class="{{ request()->routeIs('admin.statistics') ? 'active' : '' }}">
                     Laporan Statistik
                </a>
                @if (auth()->user()?->isSuperAdmin())
                    <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                         Kelola Kategori
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                         Kelola Pengguna
                    </a>
                    <a href="{{ route('admin.requests.index') }}" class="{{ request()->routeIs('admin.requests.*') ? 'active' : '' }}">
                         Permintaan Admin
                        @if (($pendingAdminRequestsCount ?? 0) > 0)
                            <span class="nav-count-badge">{{ $pendingAdminRequestsCount }}</span>
                        @endif
                    </a>
                @endif
            </nav>
        </aside>

        <div class="admin-main">
            @yield('admin-content')
        </div>
    </div>

@endsection
