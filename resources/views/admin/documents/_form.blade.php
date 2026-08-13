@csrf

@if ($errors->any())
    <div class="alert alert-error">
        <ul style="margin-left: 18px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="form-group">
    <label for="title">Judul <span class="required">*</span></label>
    <input type="text" id="title" name="title" value="{{ old('title', $document->title ?? '') }}" required class="form-input">
</div>

<div class="form-row">
    <div class="form-group">
        <label for="author">Penulis</label>
        <input type="text" id="author" name="author" value="{{ old('author', $document->author ?? '') }}" class="form-input">
    </div>

    <div class="form-group">
        <label for="month">Bulan</label>
        <select id="month" name="month" class="form-input">
            <option value="">- Bulan -</option>
            @foreach (\App\Models\Document::MONTH_NAMES as $num => $name)
                <option value="{{ $num }}" @selected(old('month', $document->month ?? '') == $num)>
                    {{ $name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label for="year">Tahun</label>
        <input type="number" id="year" name="year" value="{{ old('year', $document->year ?? '') }}" class="form-input">
    </div>
</div>

<div class="form-group">
    <label for="publisher">Penerbit</label>
    <input type="text" id="publisher" name="publisher" value="{{ old('publisher', $document->publisher ?? '') }}" class="form-input">
</div>

<div class="form-group">
    <label for="category_id">Kategori</label>
    <select id="category_id" name="category_id" class="form-input">
        <option value="">- Pilih Kategori -</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(old('category_id', $document->category_id ?? '') == $category->id)>
                {{ $category->name }}
            </option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="keywords">Kata Kunci</label>
    <input type="text" id="keywords" name="keywords" value="{{ old('keywords', $document->keywords ?? '') }}" placeholder="pisahkan dengan koma" class="form-input">
</div>

<div class="form-group">
    <label for="abstract">Abstrak</label>
    <textarea id="abstract" name="abstract" rows="5" class="form-input">{{ old('abstract', $document->abstract ?? '') }}</textarea>
</div>

<div class="form-group">
    <label for="cover">Sampul Dokumen (opsional)</label>
    <input type="file" id="cover" name="cover" accept="image/*" class="form-input">
    <input type="hidden" name="cover_auto" id="cover_auto">

    <p class="field-hint">
        Kalau tidak diisi manual, sampul akan otomatis diambil dari halaman pertama file PDF yang kamu upload di bawah.
    </p>

    @if (isset($document) && $document->cover)
        <p class="field-hint">Sampul saat ini:</p>
    @endif

    <img id="cover_preview" src="{{ isset($document) && $document->cover ? \Illuminate\Support\Facades\Storage::url($document->cover) : '' }}"
         style="{{ isset($document) && $document->cover ? '' : 'display:none;' }} max-width: 140px; border-radius: 8px; border: 1px solid #eef0f4; margin-top: 8px;">
</div>

<div class="form-group">
    <label for="pdf_file">
        File PDF
        @if (! isset($document))
            <span class="required">*</span>
        @endif
    </label>
    <input type="file" id="pdf_file" name="pdf_file" accept="application/pdf" class="form-input">

    @if (isset($document) && $document->pdf_file && $document->pdf_file !== 'placeholder.pdf')
        <p class="field-hint">
            File saat ini: {{ basename($document->pdf_file) }} &mdash; kosongkan kalau tidak mau ganti file.
        </p>
    @else
        <p class="field-hint">Maksimal 100MB, format PDF.</p>
    @endif
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    (function () {
        const pdfInput = document.getElementById('pdf_file');
        const coverInput = document.getElementById('cover');
        const coverAutoField = document.getElementById('cover_auto');
        const coverPreview = document.getElementById('cover_preview');
        let manualCoverChosen = false;

        // Kalau admin upload sampul sendiri, itu diprioritaskan (bukan auto)
        coverInput.addEventListener('change', function () {
            manualCoverChosen = this.files && this.files.length > 0;
            coverAutoField.value = '';

            if (manualCoverChosen) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    coverPreview.src = e.target.result;
                    coverPreview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });

        // Begitu file PDF dipilih, otomatis render halaman pertamanya jadi gambar sampul
        pdfInput.addEventListener('change', async function () {
            if (manualCoverChosen) return; // jangan timpa kalau admin sudah pilih sampul manual

            const file = this.files[0];
            if (!file) return;

            try {
                const buffer = await file.arrayBuffer();
                const pdf = await pdfjsLib.getDocument({ data: buffer }).promise;
                const page = await pdf.getPage(1);
                const viewport = page.getViewport({ scale: 0.7 });

                const canvas = document.createElement('canvas');
                canvas.width = viewport.width;
                canvas.height = viewport.height;

                await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;

                const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
                coverAutoField.value = dataUrl;
                coverPreview.src = dataUrl;
                coverPreview.style.display = 'block';
            } catch (err) {
                console.error('Gagal membuat sampul otomatis dari PDF:', err);
            }
        });
    })();
</script>
