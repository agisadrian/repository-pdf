@extends('layouts.admin')

@section('title', 'Edit Dokumen - Repository PDF')

@section('admin-content')

    <a href="{{ route('admin.documents.index') }}" class="back-link">&larr; Kembali ke Kelola Dokumen</a>

    <div class="admin-panel">
        <h1 class="page-title">Edit Dokumen</h1>
        <p class="page-subtitle">{{ $document->title }}</p>

        <form action="{{ route('admin.documents.update', $document) }}" method="POST" enctype="multipart/form-data">
            @method('PUT')
            @include('admin.documents._form')

            <button type="submit" class="btn btn-primary btn-block">Update Dokumen</button>
        </form>
    </div>

@endsection
