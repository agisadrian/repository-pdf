<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Repository PDF')</title>

    @hasSection('meta')
        @yield('meta')
    @else
        <meta name="description" content="Kumpulan dokumen skripsi, tesis, jurnal, dan laporan penelitian di Repository PDF.">
        <meta property="og:type" content="website">
        <meta property="og:title" content="@yield('title', 'Repository PDF')">
        <meta property="og:description" content="Kumpulan dokumen skripsi, tesis, jurnal, dan laporan penelitian.">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="@yield('title', 'Repository PDF')">
        <meta name="twitter:description" content="Kumpulan dokumen skripsi, tesis, jurnal, dan laporan penelitian.">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>

    <header class="navbar">
        <div class="container navbar-inner">
            <a href="{{ url('/') }}" class="brand">Repository PDF</a>
            <nav>
                <a href="{{ url('/') }}">Home</a>
                <a href="{{ route('category.index') }}">Kategori</a>
                <a href="{{ route('document.search') }}">Cari Dokumen</a>
                @auth
                    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                    <form action="{{ route('logout') }}" method="POST" class="logout-form">
                        @csrf
                        <button type="submit" class="logout-btn">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}">Login</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="container content">
        @yield('content')
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; {{ date('Y') }} Repository PDF. Dibuat dengan Laravel.</p>
        </div>
    </footer>

</body>
</html>
