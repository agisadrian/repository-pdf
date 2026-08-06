@extends('layouts.app')

@section('title', 'Cari Dokumen - Repository PDF')

@section('content')

    <h1 class="page-title">Cari Dokumen</h1>
    <p class="page-subtitle">Cari berdasarkan judul dokumen.</p>

    <form action="{{ url('/cari') }}" method="GET" class="search-form">
        <input
            type="text"
            name="q"
            value="{{ $keyword }}"
            placeholder="Ketik judul dokumen..."
            class="search-input"
        >
        <button type="submit" class="btn btn-primary">Cari</button>
    </form>

    @if ($keyword)
        <p class="search-result-info">
            Menampilkan hasil untuk: <strong>"{{ $keyword }}"</strong>
            ({{ $documents->total() }} dokumen ditemukan)
        </p>
    @endif

    @if ($documents->isEmpty())
        <div class="empty-state">
            <p>Tidak ada dokumen ditemukan.</p>
        </div>
    @else
        <div class="document-grid">
            @foreach ($documents as $doc)
                <a href="{{ url('/dokumen/' . $doc->slug) }}" class="document-card">
                    <div class="doc-title">{{ $doc->title }}</div>
                    <div class="doc-meta">{{ $doc->author ?? 'Penulis tidak diketahui' }} &middot; {{ $doc->year }}</div>
                    @if ($doc->category)
                        <span class="badge">{{ $doc->category->name }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        <div class="pagination-wrap">
            {{ $documents->links() }}
        </div>
    @endif

@endsection
