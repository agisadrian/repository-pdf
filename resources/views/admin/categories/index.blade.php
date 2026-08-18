@extends('layouts.admin')

@section('title', 'Kelola Kategori - Repository PDF')

@section('admin-content')

    <div class="admin-panel-header">
        <h1 class="page-title">Kelola Kategori</h1>
    </div>

    @if ($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <div class="admin-panel" style="margin-bottom: 20px;">
        <h2 class="panel-subtitle">Tambah Kategori Baru</h2>
        <form action="{{ route('admin.categories.store') }}" method="POST" class="inline-form-row">
            @csrf
            <input
                type="text"
                name="name"
                placeholder="Nama kategori, misal: Skripsi"
                value="{{ old('name') }}"
                class="form-input"
                required
            >
            <button type="submit" class="btn btn-primary">+ Tambah</button>
        </form>
    </div>

    <div class="admin-panel">
        @if ($categories->isEmpty())
            <p class="empty-hint">Belum ada kategori.</p>
        @else
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nama Kategori</th>
                        <th>Jumlah Dokumen</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr id="cat-row-{{ $category->id }}">
                            <td>
                                <span class="cat-name-display" id="cat-name-{{ $category->id }}">{{ $category->name }}</span>

                                <form
                                    action="{{ route('admin.categories.update', $category) }}"
                                    method="POST"
                                    class="inline-form-row cat-edit-form"
                                    id="cat-edit-form-{{ $category->id }}"
                                    style="display:none;"
                                >
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="name" value="{{ $category->name }}" class="form-input" required>
                                    <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                                    <button type="button" class="btn btn-sm btn-secondary cat-cancel-btn" data-id="{{ $category->id }}">Batal</button>
                                </form>
                            </td>
                            <td>{{ $category->documents_count }}</td>
                            <td class="table-actions">
                                <button type="button" class="btn btn-sm btn-secondary cat-edit-btn" data-id="{{ $category->id }}">Edit</button>

                                <form
                                    action="{{ route('admin.categories.destroy', $category) }}"
                                    method="POST"
                                    class="inline-form"
                                    onsubmit="return confirm('Yakin mau hapus kategori &quot;{{ $category->name }}&quot;?{{ $category->documents_count > 0 ? ' ' . $category->documents_count . ' dokumen yang pakai kategori ini nanti jadi Tanpa Kategori (dokumennya sendiri TIDAK ikut kehapus).' : '' }}')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <script>
        document.querySelectorAll('.cat-edit-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = btn.dataset.id;
                document.getElementById('cat-name-' + id).style.display = 'none';
                document.getElementById('cat-edit-form-' + id).style.display = 'inline-flex';
                btn.style.display = 'none';
            });
        });

        document.querySelectorAll('.cat-cancel-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = btn.dataset.id;
                document.getElementById('cat-name-' + id).style.display = '';
                document.getElementById('cat-edit-form-' + id).style.display = 'none';
                document.querySelector('.cat-edit-btn[data-id="' + id + '"]').style.display = '';
            });
        });
    </script>

@endsection
