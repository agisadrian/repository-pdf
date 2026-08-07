@extends('layouts.admin')

@section('title', 'Kelola Dokumen - Repository PDF')

@section('admin-content')

    <div class="admin-panel-header">
        <h1 class="page-title">Kelola Dokumen</h1>
        <div class="table-actions">
            <a href="{{ route('admin.documents.bulkCreate') }}" class="btn btn-secondary">Upload Banyak Dokumen</a>
            <a href="{{ route('admin.documents.create') }}" class="btn btn-primary">+ Tambah Dokumen</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div id="cover-batch-alert" class="alert alert-success" style="display:none;"></div>

    <form action="{{ route('admin.documents.index') }}" method="GET" class="search-form">
        <input
            type="text"
            name="q"
            value="{{ $keyword }}"
            placeholder="Cari judul dokumen..."
            class="search-input"
        >
        <button type="submit" class="btn btn-primary">Cari</button>
    </form>

    <div class="admin-panel">
        @if ($documents->isEmpty())
            <p class="empty-hint">Belum ada dokumen.</p>
        @else
            @php
                $missingCoverCount = $documents->filter(fn ($d) => ! $d->cover && $d->pdf_file && $d->pdf_file !== 'placeholder.pdf')->count();
            @endphp

            @if ($missingCoverCount > 0)
                <button type="button" id="generate-all-covers" class="btn btn-secondary btn-sm" style="margin-bottom: 16px;">
                    Buat Sampul Otomatis untuk {{ $missingCoverCount }} Dokumen di Halaman Ini
                </button>
            @endif

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Sampul</th>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Tahun</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($documents as $doc)
                        <tr>
                            <td>
                                <img
                                    class="row-cover-thumb"
                                    id="thumb-{{ $doc->id }}"
                                    src="{{ $doc->cover ? \Illuminate\Support\Facades\Storage::url($doc->cover) : '' }}"
                                    style="{{ $doc->cover ? '' : 'display:none;' }}"
                                    alt=""
                                >
                                @unless ($doc->cover)
                                    <span class="row-cover-placeholder" id="placeholder-{{ $doc->id }}">{{ substr($doc->title, 0, 1) }}</span>
                                @endunless
                            </td>
                            <td>{{ $doc->title }}</td>
                            <td>{{ $doc->category?->name ?? '-' }}</td>
                            <td>{{ $doc->year ?? '-' }}</td>
                            <td class="table-actions">
                                <a href="{{ route('admin.documents.edit', $doc) }}" class="btn btn-sm btn-secondary">Edit</a>

                                @if ($doc->pdf_file && $doc->pdf_file !== 'placeholder.pdf')
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-secondary btn-generate-cover"
                                        data-id="{{ $doc->id }}"
                                        data-pdf-url="{{ \Illuminate\Support\Facades\Storage::url($doc->pdf_file) }}"
                                        data-endpoint="{{ route('admin.documents.generateCover', $doc) }}"
                                    >
                                        {{ $doc->cover ? 'Perbarui Sampul' : 'Buat Sampul' }}
                                    </button>
                                @endif

                                <form action="{{ route('admin.documents.destroy', $doc) }}" method="POST" class="inline-form" onsubmit="return confirm('Yakin mau hapus dokumen ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="pagination-wrap">
                {{ $documents->links() }}
            </div>
        @endif
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        // Ambil PDF dari URL-nya, render halaman pertama jadi gambar (data URL base64)
        async function renderFirstPageAsCover(pdfUrl) {
            const res = await fetch(pdfUrl);
            const buffer = await res.arrayBuffer();
            const pdf = await pdfjsLib.getDocument({ data: buffer }).promise;
            const page = await pdf.getPage(1);
            const viewport = page.getViewport({ scale: 0.7 });

            const canvas = document.createElement('canvas');
            canvas.width = viewport.width;
            canvas.height = viewport.height;

            await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;

            return canvas.toDataURL('image/jpeg', 0.85);
        }

        // Kirim hasilnya ke server buat disimpan
        async function generateCoverFor(button) {
            const id = button.dataset.id;
            const pdfUrl = button.dataset.pdfUrl;
            const endpoint = button.dataset.endpoint;

            button.disabled = true;
            const originalText = button.textContent;
            button.textContent = 'Membuat...';

            try {
                const dataUrl = await renderFirstPageAsCover(pdfUrl);

                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ cover_auto: dataUrl }),
                });

                if (!res.ok) throw new Error('Gagal menyimpan sampul');

                const result = await res.json();

                // Update thumbnail di baris tabel itu juga tanpa reload halaman
                const thumb = document.getElementById('thumb-' + id);
                const placeholder = document.getElementById('placeholder-' + id);
                if (thumb) {
                    thumb.src = result.cover_url;
                    thumb.style.display = 'block';
                }
                if (placeholder) {
                    placeholder.style.display = 'none';
                }

                button.textContent = 'Perbarui Sampul';
            } catch (err) {
                console.error(err);
                button.textContent = 'Gagal, coba lagi';
            } finally {
                button.disabled = false;
            }
        }

        document.querySelectorAll('.btn-generate-cover').forEach(function (btn) {
            btn.addEventListener('click', function () {
                generateCoverFor(btn);
            });
        });

        const generateAllBtn = document.getElementById('generate-all-covers');
        if (generateAllBtn) {
            generateAllBtn.addEventListener('click', async function () {
                generateAllBtn.disabled = true;
                const buttons = Array.from(document.querySelectorAll('.btn-generate-cover'))
                    .filter(function (btn) { return btn.textContent.trim() === 'Buat Sampul'; });

                generateAllBtn.textContent = 'Memproses 0/' + buttons.length + '...';

                for (let i = 0; i < buttons.length; i++) {
                    await generateCoverFor(buttons[i]);
                    generateAllBtn.textContent = 'Memproses ' + (i + 1) + '/' + buttons.length + '...';
                }

                const alertBox = document.getElementById('cover-batch-alert');
                alertBox.textContent = 'Selesai! Sampul untuk ' + buttons.length + ' dokumen sudah dibuat.';
                alertBox.style.display = 'block';
                generateAllBtn.style.display = 'none';
            });
        }
    </script>

@endsection
