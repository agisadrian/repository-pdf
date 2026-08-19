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

            <div id="bulk-edit-bar" class="bulk-edit-bar" style="display:none;">
                <span id="bulk-edit-count" class="bulk-edit-count"></span>

                <select id="bulk-category" class="form-input bulk-edit-select">
                    <option value="">Kategori: Tidak Diubah</option>
                    <option value="none">Kategori: Kosongkan</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">Kategori: {{ $category->name }}</option>
                    @endforeach
                </select>

                <select id="bulk-month" class="form-input bulk-edit-select">
                    <option value="">Bulan: Tidak Diubah</option>
                    <option value="none">Bulan: Kosongkan</option>
                    @foreach (\App\Models\Document::MONTH_NAMES as $num => $name)
                        <option value="{{ $num }}">Bulan: {{ $name }}</option>
                    @endforeach
                </select>

                <select id="bulk-year" class="form-input bulk-edit-select">
                    <option value="">Tahun: Tidak Diubah</option>
                    <option value="none">Tahun: Kosongkan</option>
                    @foreach ($years as $y)
                        <option value="{{ $y }}">Tahun: {{ $y }}</option>
                    @endforeach
                </select>

                <button type="button" id="bulk-edit-apply" class="btn btn-primary btn-sm">Terapkan</button>
                <button type="button" id="bulk-delete-apply" class="btn btn-danger btn-sm">Hapus Terpilih</button>
                <button type="button" id="bulk-edit-clear" class="btn btn-secondary btn-sm">Batal Pilih</button>
            </div>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all-checkbox"></th>
                        <th>Sampul</th>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Bulan</th>
                        <th>Tahun</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($documents as $doc)
                        <tr>
                            <td>
                                <input type="checkbox" class="row-checkbox" value="{{ $doc->id }}">
                            </td>
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
                            <td>{{ $doc->month_name ?? '-' }}</td>
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

        // ===== Bulk edit: pilih banyak dokumen, set Kategori/Bulan sekaligus =====
        (function () {
            const selectAllCheckbox = document.getElementById('select-all-checkbox');
            const rowCheckboxes = Array.from(document.querySelectorAll('.row-checkbox'));
            const bulkBar = document.getElementById('bulk-edit-bar');
            const bulkCount = document.getElementById('bulk-edit-count');
            const bulkCategory = document.getElementById('bulk-category');
            const bulkMonth = document.getElementById('bulk-month');
            const bulkYear = document.getElementById('bulk-year');
            const bulkApplyBtn = document.getElementById('bulk-edit-apply');
            const bulkDeleteBtn = document.getElementById('bulk-delete-apply');
            const bulkClearBtn = document.getElementById('bulk-edit-clear');

            if (!bulkBar || rowCheckboxes.length === 0) return;

            function getCheckedIds() {
                return rowCheckboxes.filter(function (cb) { return cb.checked; })
                    .map(function (cb) { return cb.value; });
            }

            function updateBulkBar() {
                const ids = getCheckedIds();
                if (ids.length > 0) {
                    bulkBar.style.display = 'flex';
                    bulkCount.textContent = ids.length + ' dokumen dipilih';
                } else {
                    bulkBar.style.display = 'none';
                }

                selectAllCheckbox.checked = ids.length === rowCheckboxes.length;
                selectAllCheckbox.indeterminate = ids.length > 0 && ids.length < rowCheckboxes.length;
            }

            rowCheckboxes.forEach(function (cb) {
                cb.addEventListener('change', updateBulkBar);
            });

            selectAllCheckbox.addEventListener('change', function () {
                rowCheckboxes.forEach(function (cb) { cb.checked = selectAllCheckbox.checked; });
                updateBulkBar();
            });

            bulkClearBtn.addEventListener('click', function () {
                rowCheckboxes.forEach(function (cb) { cb.checked = false; });
                updateBulkBar();
            });

            bulkApplyBtn.addEventListener('click', async function () {
                const ids = getCheckedIds();
                const categoryValue = bulkCategory.value;
                const monthValue = bulkMonth.value;
                const yearValue = bulkYear.value;

                if (ids.length === 0) return;

                if (categoryValue === '' && monthValue === '' && yearValue === '') {
                    alert('Pilih dulu Kategori, Bulan, dan/atau Tahun yang mau diterapkan.');
                    return;
                }

                const confirmMsg = 'Terapkan perubahan ini ke ' + ids.length + ' dokumen yang dipilih?';
                if (!confirm(confirmMsg)) return;

                bulkApplyBtn.disabled = true;
                const originalText = bulkApplyBtn.textContent;
                bulkApplyBtn.textContent = 'Memproses...';

                try {
                    const res = await fetch('{{ route('admin.documents.bulkUpdate') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            document_ids: ids,
                            category_id: categoryValue,
                            month: monthValue,
                            year: yearValue,
                        }),
                    });

                    const result = await res.json();

                    if (!res.ok) {
                        alert(result.message || 'Gagal memperbarui dokumen.');
                        return;
                    }

                    location.reload();
                } catch (err) {
                    console.error(err);
                    alert('Terjadi kesalahan, coba lagi.');
                } finally {
                    bulkApplyBtn.disabled = false;
                    bulkApplyBtn.textContent = originalText;
                }
            });

            bulkDeleteBtn.addEventListener('click', async function () {
                const ids = getCheckedIds();
                if (ids.length === 0) return;

                const confirmMsg = 'Yakin mau HAPUS ' + ids.length + ' dokumen yang dipilih? File PDF & sampulnya juga ikut kehapus dan nggak bisa dibalikin lagi.';
                if (!confirm(confirmMsg)) return;

                bulkDeleteBtn.disabled = true;
                const originalText = bulkDeleteBtn.textContent;
                bulkDeleteBtn.textContent = 'Menghapus...';

                try {
                    const res = await fetch('{{ route('admin.documents.bulkDestroy') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ document_ids: ids }),
                    });

                    const result = await res.json();

                    if (!res.ok) {
                        alert(result.message || 'Gagal menghapus dokumen.');
                        return;
                    }

                    location.reload();
                } catch (err) {
                    console.error(err);
                    alert('Terjadi kesalahan, coba lagi.');
                } finally {
                    bulkDeleteBtn.disabled = false;
                    bulkDeleteBtn.textContent = originalText;
                }
            });
        })();
    </script>

@endsection