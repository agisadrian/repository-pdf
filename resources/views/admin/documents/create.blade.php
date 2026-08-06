@extends('layouts.admin')

@section('title', 'Tambah Dokumen - Repository PDF')

@section('admin-content')

    <a href="{{ route('admin.documents.index') }}" class="back-link">&larr; Kembali ke Kelola Dokumen</a>

    <div class="admin-panel">
        <h1 class="page-title">Tambah Dokumen Baru</h1>
        <p class="page-subtitle">Isi data dokumen dan upload file PDF asli-nya.</p>

        <form action="{{ route('admin.documents.store') }}" method="POST" enctype="multipart/form-data">
            @include('admin.documents._form')

            <button type="submit" class="btn btn-primary btn-block">Simpan Dokumen</button>
        </form>
    </div>

@endsection
