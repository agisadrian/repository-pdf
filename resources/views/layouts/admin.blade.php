@extends('layouts.app')

@section('content')

    <div class="admin-layout">
        <aside class="admin-sidebar">
            <nav class="admin-nav">
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    📊 Dashboard
                </a>
                <a href="{{ route('admin.documents.index') }}" class="{{ request()->routeIs('admin.documents.*') ? 'active' : '' }}">
                    📄 Kelola Dokumen
                </a>
            </nav>
        </aside>

        <div class="admin-main">
            @yield('admin-content')
        </div>
    </div>

@endsection
