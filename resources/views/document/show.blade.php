@extends('layouts.app')

@section('title', $document->title . ' - Repository PDF')

@section('meta')
    @php
        $ogDescription = \Illuminate\Support\Str::limit(
            $document->display_abstract ?? 'Dokumen ' . $document->title . ' tersedia di Repository PDF.',
            160
        );
        $ogImage = $document->cover
            ? \Illuminate\Support\Facades\Storage::url($document->cover)
            : null;
    @endphp

    <meta name="description" content="{{ $ogDescription }}">

    {{-- Canonical: URL resmi buat halaman ini, biar Google nggak bingung sama
         variasi query string lain yang mungkin muncul di masa depan. --}}
    <link rel="canonical" href="{{ route('document.show', $document->slug) }}">

    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $document->title }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endif

    <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $document->title }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    @if ($ogImage)
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif

    {{-- JSON-LD: kasih tau Google ini dokumen apa (bukan cuma teks biasa),
         siapa penulisnya, kapan diterbitkan, dan file PDF-nya di mana. --}}
    @php
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            'name' => $document->title,
            'description' => $ogDescription,
            'url' => route('document.show', $document->slug),
            'inLanguage' => 'id',
        ];

        if ($document->author) {
            $jsonLd['author'] = ['@type' => 'Person', 'name' => $document->author];
        }

        if ($document->year) {
            $jsonLd['datePublished'] = $document->month
                ? sprintf('%04d-%02d', $document->year, $document->month)
                : (string) $document->year;
        }

        if ($document->keywords) {
            $jsonLd['keywords'] = $document->keywords;
        }

        if ($document->category) {
            $jsonLd['about'] = $document->category->name;
        }

        if ($ogImage) {
            $jsonLd['thumbnailUrl'] = $ogImage;
        }

        if ($document->pdf_file && $document->pdf_file !== 'placeholder.pdf') {
            $jsonLd['associatedMedia'] = [
                '@type' => 'MediaObject',
                'contentUrl' => route('document.download', $document->slug),
                'encodingFormat' => 'application/pdf',
            ];
        }
    @endphp
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

    {{-- BreadcrumbList: biar Google bisa nampilin jalur breadcrumb (Home / Kategori / Judul)
         di hasil pencarian, bukan cuma URL mentah. Urutannya ngikutin breadcrumb visual di halaman. --}}
    @php
        $breadcrumbItems = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
        ];

        if ($document->category) {
            $breadcrumbItems[] = [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $document->category->name,
                'item' => route('document.search', ['category' => $document->category->id]),
            ];
        }

        $breadcrumbItems[] = [
            '@type' => 'ListItem',
            'position' => count($breadcrumbItems) + 1,
            'name' => $document->title,
            'item' => route('document.show', $document->slug),
        ];

        $breadcrumbLd = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbItems,
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($breadcrumbLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

@section('content')

    <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="{{ url('/') }}">Home</a>
        <span class="breadcrumb-sep">/</span>
        @if ($document->category)
            <a href="{{ route('document.search', ['category' => $document->category->id]) }}">{{ $document->category->name }}</a>
            <span class="breadcrumb-sep">/</span>
        @endif
        <span class="breadcrumb-current">{{ $document->title }}</span>
    </nav>

    <a href="{{ url('/') }}" class="back-link">&larr; Kembali</a>

    <div class="detail-card">
        <div class="detail-header">
            @if ($document->cover)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($document->cover) }}" alt="Sampul dokumen {{ $document->title }}" class="detail-cover">
            @endif

            <div class="detail-header-info">
                <h1 class="page-title">{{ $document->title }}</h1>

                <div class="detail-meta">
                    <span><strong>Penulis:</strong> {{ $document->author ?? '-' }}</span>
                    <span><strong>Tahun:</strong> {{ $document->period_label }}</span>
                </div>

                <div class="detail-badges">
                    @if ($document->category)
                        <span class="badge">{{ $document->category->name }}</span>
                    @endif
                </div>
            </div>
        </div>

        <h3 class="section-heading">Abstrak</h3>
        <p class="abstract-text">{{ $document->display_abstract ?? 'Belum ada abstrak untuk dokumen ini.' }}</p>
        @if ($document->abstract_is_auto)
            <p class="abstract-auto-hint">📄 Ini ringkasan otomatis dari isi dokumen.</p>
        @endif

        @if ($document->keywords)
            <p class="keywords-text"><strong>Kata kunci:</strong> {{ $document->keywords }}</p>
        @endif

        <div class="detail-stats">
            <span>👁️ {{ $document->view_count }} dilihat</span>
            <span>⬇️ {{ $document->download_count }} diunduh</span>
        </div>

        <div class="detail-actions">
            @if ($document->pdf_file && $document->pdf_file !== 'placeholder.pdf')
                <button type="button" id="toggle-viewer-btn" class="btn btn-primary" onclick="togglePdfViewer()">
                    👁️ Baca Online
                </button>
                <a href="{{ route('document.download', $document->slug) }}" class="btn btn-secondary">
                    ⬇️ Download PDF
                </a>
                @if ($document->total_pages)
                    <span class="page-count-hint">{{ $document->total_pages }} halaman</span>
                @endif
            @else
                <button class="btn btn-disabled" disabled title="File PDF belum diupload oleh admin">
                    📄 File PDF belum tersedia
                </button>
            @endif
        </div>

        <div class="detail-share">
            <span class="detail-share-label">Bagikan:</span>
            <button type="button" class="btn btn-share" onclick="copyDocLink(this)">
                🔗 Copy Link
            </button>
            <a
                class="btn btn-share"
                target="_blank"
                rel="noopener"
                href="https://wa.me/?text={{ urlencode($document->title . ' - ' . route('document.show', $document->slug)) }}"
            >
                💬 WhatsApp
            </a>
        </div>

        @if ($document->pdf_file && $document->pdf_file !== 'placeholder.pdf')
            <div id="pdf-viewer-wrapper" class="pdf-viewer-wrapper hidden">
                <iframe
                    id="pdf-viewer-iframe"
                    class="pdf-viewer-iframe"
                    data-src="{{ route('document.preview', $document->slug) }}"
                    title="Preview {{ $document->title }}"
                ></iframe>
            </div>
        @endif
    </div>

    @if ($relatedDocuments->isNotEmpty())
        <h3 class="section-heading related-heading">Dokumen Terkait</h3>

        <div class="document-grid">
            @foreach ($relatedDocuments as $doc)
                <a href="{{ url('/dokumen/' . $doc->slug) }}" class="document-card">
                    <div class="doc-cover">
                        @if ($doc->cover)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($doc->cover) }}" alt="Sampul dokumen {{ $doc->title }}" loading="lazy">
                        @else
                            <span class="doc-cover-placeholder">{{ substr($doc->title, 0, 1) }}</span>
                        @endif
                    </div>
                    <div class="doc-title">{{ $doc->title }}</div>
                    <div class="doc-meta">{{ $doc->author ?? 'Penulis tidak diketahui' }} &middot; {{ $doc->period_label }}</div>
                    @if ($doc->category)
                        <span class="badge">{{ $doc->category->name }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    @endif

    <script>
        function togglePdfViewer() {
            const wrapper = document.getElementById('pdf-viewer-wrapper');
            const iframe = document.getElementById('pdf-viewer-iframe');
            const btn = document.getElementById('toggle-viewer-btn');
            const isHidden = wrapper.classList.contains('hidden');

            if (isHidden) {
                // Baru diisi src-nya pas pertama kali dibuka (biar nggak load PDF
                // kalau pengunjung nggak pernah klik "Baca Online")
                if (! iframe.getAttribute('src')) {
                    iframe.src = iframe.dataset.src;
                }
                wrapper.classList.remove('hidden');
                btn.textContent = '✖️ Tutup Viewer';
                wrapper.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                wrapper.classList.add('hidden');
                btn.textContent = '👁️ Baca Online';
            }
        }

        function copyDocLink(btn) {
            const url = window.location.href;
            const original = btn.textContent;

            navigator.clipboard.writeText(url).then(function () {
                btn.textContent = '✅ Tersalin!';
                setTimeout(function () {
                    btn.textContent = original;
                }, 2000);
            }).catch(function () {
                // Fallback kalau browser tidak dukung Clipboard API (misal http, bukan https)
                window.prompt('Salin link ini secara manual:', url);
            });
        }
    </script>

@endsection
