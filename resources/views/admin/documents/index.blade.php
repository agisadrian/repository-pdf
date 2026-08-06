@extends('layouts.admin')

@section('title', 'Kelola Dokumen - Repository PDF')

@section('admin-content')

    <div class="admin-panel-header">
        <h1 class="page-title">Kelola Dokumen</h1>
        <a href="{{ route('admin.documents.create') }}" class="btn btn-primary">+ Tambah Dokumen</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

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
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Tahun</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($documents as $doc)
                        <tr>
                            <td>{{ $doc->title }}</td>
                            <td>{{ $doc->category?->name ?? '-' }}</td>
                            <td>{{ $doc->year ?? '-' }}</td>
                            <td class="table-actions">
                                <a href="{{ route('admin.documents.edit', $doc) }}" class="btn btn-sm btn-secondary">Edit</a>
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

@endsection
