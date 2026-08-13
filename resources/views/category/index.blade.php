@extends('layouts.app')

@section('title', 'Kategori - Repository PDF')

@section('meta')
    <meta name="description" content="Jelajahi dokumen di Repository PDF berdasarkan kategori.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Kategori - Repository PDF">
    <meta property="og:description" content="Jelajahi dokumen di Repository PDF berdasarkan kategori.">
    <meta property="og:url" content="{{ url()->current() }}">
@endsection

@section('content')

    <h1 class="page-title">Kategori</h1>
    <p class="page-subtitle">Jelajahi dokumen berdasarkan kategori.</p>

    @if ($categories->isEmpty())
        <div class="empty-state">
            <p>Belum ada kategori.</p>
        </div>
    @else
        <div class="category-grid">
            @foreach ($categories as $category)
                <a href="{{ route('document.search', ['category' => $category->id]) }}" class="category-card">
                    <div class="category-cover">
                        @if ($category->cover_url)
                            <img src="{{ $category->cover_url }}" alt="" loading="lazy">
                        @else
                            <span class="category-cover-placeholder">{{ substr($category->name, 0, 1) }}</span>
                        @endif
                    </div>
                    <div class="category-name">{{ $category->name }}</div>
                    <div class="category-count">{{ $category->documents_count }} dokumen</div>
                </a>
            @endforeach
        </div>
    @endif

@endsection
