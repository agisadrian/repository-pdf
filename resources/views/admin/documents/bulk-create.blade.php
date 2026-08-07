@extends('layouts.admin')

@section('title', 'Upload Banyak Dokumen - Repository PDF')

@section('admin-content')

    <a href="{{ route('admin.documents.index') }}" class="back-link">&larr; Kembali ke Kelola Dokumen</a>

    <div class="admin-panel">
        <h1 class="page-title">Upload Banyak Dokumen Sekaligus</h1>
        <p class="page-subtitle">
            Pilih beberapa file PDF sekaligus. Judul otomatis diambil dari nama file, isi teks & sampul
            juga otomatis diproses. Kalau mau ubah judul/deskripsi lebih detail, edit lagi nanti satu-satu
            lewat Kelola Dokumen.
        </p>

        <div class="form-group">
            <label for="bulk_category">Kategori (opsional, berlaku buat semua file)</label>
            <select id="bulk_category" class="form-input">
                <option value="">- Tanpa Kategori -</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="bulk_files">File PDF (bisa pilih banyak sekaligus)</label>
            <input type="file" id="bulk_files" accept="application/pdf" multiple class="form-input">
            <p class="field-hint">Tips: di jendela pilih file, tahan Ctrl (atau Shift buat pilih berurutan) buat pilih banyak file sekaligus.</p>
        </div>

        <button type="button" id="bulk_start_btn" class="btn btn-primary" disabled>Mulai Upload</button>

        <div id="bulk_progress_list" style="margin-top: 24px;"></div>

        <div id="bulk_summary" class="alert alert-success" style="display:none; margin-top: 16px;"></div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        const fileInput = document.getElementById('bulk_files');
        const startBtn = document.getElementById('bulk_start_btn');
        const progressList = document.getElementById('bulk_progress_list');
        const summaryBox = document.getElementById('bulk_summary');
        const categorySelect = document.getElementById('bulk_category');

        fileInput.addEventListener('change', function () {
            startBtn.disabled = !(this.files && this.files.length > 0);
            progressList.innerHTML = '';
            summaryBox.style.display = 'none';

            Array.from(this.files).forEach(function (file, i) {
                const row = document.createElement('div');
                row.id = 'row-' + i;
                row.className = 'bulk-row';
                row.innerHTML = '<span class="bulk-row-name">' + file.name + '</span><span class="bulk-row-status">Menunggu</span>';
                progressList.appendChild(row);
            });
        });

        async function renderFirstPageAsCover(file) {
            try {
                const buffer = await file.arrayBuffer();
                const pdf = await pdfjsLib.getDocument({ data: buffer }).promise;
                const page = await pdf.getPage(1);
                const viewport = page.getViewport({ scale: 0.7 });

                const canvas = document.createElement('canvas');
                canvas.width = viewport.width;
                canvas.height = viewport.height;

                await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;

                return canvas.toDataURL('image/jpeg', 0.85);
            } catch (err) {
                // Kalau gagal render (PDF rusak/terenkripsi dll), lanjut tanpa sampul
                return null;
            }
        }

        function setStatus(index, text, isError) {
            const row = document.getElementById('row-' + index);
            if (!row) return;
            const statusEl = row.querySelector('.bulk-row-status');
            statusEl.textContent = text;
            statusEl.className = 'bulk-row-status' + (isError ? ' bulk-row-error' : '');
        }

        startBtn.addEventListener('click', async function () {
            const files = Array.from(fileInput.files);
            startBtn.disabled = true;
            fileInput.disabled = true;
            categorySelect.disabled = true;

            let successCount = 0;
            let failCount = 0;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                setStatus(i, 'Memproses sampul...', false);

                const coverDataUrl = await renderFirstPageAsCover(file);

                setStatus(i, 'Mengupload...', false);

                const formData = new FormData();
                formData.append('pdf_file', file);
                if (categorySelect.value) formData.append('category_id', categorySelect.value);
                if (coverDataUrl) formData.append('cover_auto', coverDataUrl);

                try {
                    const res = await fetch('{{ route('admin.documents.bulkStore') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    if (!res.ok) throw new Error('Upload gagal');

                    const result = await res.json();
                    setStatus(i, 'Berhasil: "' + result.title + '"', false);
                    successCount++;
                } catch (err) {
                    setStatus(i, 'Gagal diupload', true);
                    failCount++;
                }
            }

            summaryBox.textContent = 'Selesai! ' + successCount + ' berhasil' + (failCount > 0 ? ', ' + failCount + ' gagal' : '') + '. Judul otomatis dari nama file, bisa diedit lagi lewat Kelola Dokumen.';
            summaryBox.style.display = 'block';
            startBtn.textContent = 'Selesai';
        });
    </script>

@endsection
