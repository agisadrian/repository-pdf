@extends('layouts.app')

@section('title', $document->title . ' - Repository PDF')

@section('content')

    <a href="{{ url('/') }}" class="back-link">&larr; Kembali</a>

    <div class="detail-card">
        <div class="detail-header">
            @if ($document->cover)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($document->cover) }}" alt="" class="detail-cover">
            @endif

            <div class="detail-header-info">
                <h1 class="page-title">{{ $document->title }}</h1>

                <div class="detail-meta">
                    <span><strong>Penulis:</strong> {{ $document->author ?? '-' }}</span>
                    <span><strong>Tahun:</strong> {{ $document->year ?? '-' }}</span>
                    <span><strong>Penerbit:</strong> {{ $document->publisher ?? '-' }}</span>
                </div>

                <div class="detail-badges">
                    @if ($document->category)
                        <span class="badge">{{ $document->category->name }}</span>
                    @endif
                </div>
            </div>
        </div>

        <h3 class="section-heading">Abstrak</h3>
        <p class="abstract-text">{{ $document->abstract ?? 'Belum ada abstrak untuk dokumen ini.' }}</p>

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
    </script>

@endsection
