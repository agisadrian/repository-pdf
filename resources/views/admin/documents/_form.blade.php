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
        <p class="field-hint">Maksimal 20MB, format PDF.</p>
    @endif
</div>
