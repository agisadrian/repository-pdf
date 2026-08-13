@extends('layouts.app')

@section('title', 'Home - Repository PDF')

@section('content')

    <h1 class="page-title">Selamat Datang di Repository PDF</h1>
    <p class="page-subtitle">Kumpulan dokumen pdf.</p>

    @if ($totalDocuments > 0)
        <div class="hero-stats">
            <span class="hero-stat">{{ $totalDocuments }} dokumen tersedia</span>
            <span class="hero-stat">{{ number_format($totalViews) }} kali dilihat</span>
            <span class="hero-stat">{{ number_format($totalDownloads) }} kali diunduh</span>
        </div>
    @endif

    @if ($documents->isEmpty())
        <div class="empty-state">
            <p>Belum ada dokumen. Coba jalankan <code>php artisan db:seed</code> dulu.</p>
        </div>
    @else
        <div class="document-grid">
            @foreach ($documents as $doc)
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
                    <div class="doc-meta">{{ $doc->author ?? 'Penulis tidak diketahui' }} &middot; {{ $doc->year }}</div>
                    @if ($doc->category)
                        <span class="badge">{{ $doc->category->name }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    @endif

@endsection
