@extends('layouts.app')

@section('title', 'Cari Dokumen - Repository PDF')

@section('content')

    <h1 class="page-title">Cari Dokumen</h1>
    <p class="page-subtitle">Cari berdasarkan judul, isi dokumen, atau filter per kategori.</p>

    <form action="{{ url('/cari') }}" method="GET" class="search-filter-form">
        <input
            type="text"
            name="q"
            value="{{ $keyword }}"
            placeholder="Ketik judul atau isi dokumen..."
            class="search-input"
        >

        <select name="category" class="form-input search-filter-select">
            <option value="">Semua Kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected($categoryId == $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>

        <select name="year" class="form-input search-filter-select">
            <option value="">Semua Tahun</option>
            @foreach ($years as $y)
                <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
            @endforeach
        </select>

        <select name="month" class="form-input search-filter-select">
            <option value="">Semua Bulan</option>
            @foreach ($months as $num => $name)
                <option value="{{ $num }}" @selected($month == $num)>{{ $name }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-primary">Cari</button>

        @if ($keyword || $categoryId || $year || $month || $sort !== 'newest')
            <a href="{{ url('/cari') }}" class="btn btn-secondary">Reset</a>
        @endif
    </form>

    <div class="search-sort-bar">
        <label for="sort-select">Urutkan:</label>
        <select id="sort-select" class="form-input search-filter-select" onchange="applySorting(this.value)">
            <option value="newest" @selected($sort === 'newest')>Terbaru</option>
            <option value="popular" @selected($sort === 'popular')>Terpopuler</option>
            <option value="title" @selected($sort === 'title')>Judul A-Z</option>
        </select>
    </div>

    <script>
        function applySorting(sort) {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', sort);
            window.location.href = url.toString();
        }
    </script>

    @if ($keyword || $categoryId || $year || $month)
        <p class="search-result-info">
            @if ($keyword)
                Menampilkan hasil untuk: <strong>"{{ $keyword }}"</strong>
            @endif
            @if ($categoryId)
                {{ $keyword ? 'di kategori' : 'Menampilkan kategori' }}
                <strong>{{ $categories->firstWhere('id', $categoryId)?->name }}</strong>
            @endif
            @if ($month)
                {{ ($keyword || $categoryId) ? 'bulan' : 'Menampilkan bulan' }}
                <strong>{{ $months[$month] }}</strong>
            @endif
            @if ($year)
                {{ ($keyword || $categoryId || $month) ? 'tahun' : 'Menampilkan tahun' }}
                <strong>{{ $year }}</strong>
            @endif
            ({{ $documents->total() }} dokumen ditemukan)
        </p>
    @endif

    @if ($documents->isEmpty())
        <div class="empty-state">
            <p>Tidak ada dokumen ditemukan.</p>
        </div>
    @else
        @foreach ($grouped as $periodLabel => $docsInPeriod)
            @if ($sort === 'newest')
                <h3 class="period-heading">{{ $periodLabel }}</h3>
            @endif

            <div class="document-grid">
                @foreach ($docsInPeriod as $doc)
                    <a href="{{ url('/dokumen/' . $doc->slug) }}" class="document-card">
                        <div class="doc-cover">
                            @if ($doc->cover)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($doc->cover) }}" alt="Sampul dokumen {{ $doc->title }}" loading="lazy">
                            @else
                                <span class="doc-cover-placeholder">{{ substr($doc->title, 0, 1) }}</span>
                            @endif
                            @if ($doc->created_at && $doc->created_at->gt(now()->subDays(7)))
                                <span class="badge-new">Baru</span>
                            @endif
                        </div>
                        <div class="doc-title">{{ $doc->title }}</div>
                        <div class="doc-meta">{{ $doc->author ?? 'Penulis tidak diketahui' }} &middot; {{ $doc->period_label }}</div>
                        @php $snippet = $keyword ? $doc->searchSnippet($keyword) : null; @endphp
                        @if ($snippet)
                            <p class="doc-snippet">{!! $snippet !!}</p>
                        @endif
                        @if ($doc->category)
                            <span class="badge">{{ $doc->category->name }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endforeach

        <div class="pagination-wrap">
            {{ $documents->links() }}
        </div>
    @endif

@endsection
