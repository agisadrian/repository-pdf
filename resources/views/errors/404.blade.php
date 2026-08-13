@extends('layouts.app')

@section('title', 'Halaman Tidak Ditemukan - Repository PDF')

@section('meta')
    <meta name="description" content="Halaman yang kamu cari tidak ditemukan.">
    <meta name="robots" content="noindex, follow">
@endsection

@section('content')

    <div class="error-page">
        <div class="error-code">404</div>
        <h1 class="error-title">Halaman Tidak Ditemukan</h1>
        <p class="error-message">
            Halaman yang kamu cari mungkin sudah dipindahkan, dihapus, atau alamatnya salah ketik.
        </p>

        <div class="error-actions">
            <a href="{{ url('/') }}" class="btn btn-primary">Kembali ke Home</a>
            <a href="{{ route('document.search') }}" class="btn btn-secondary">Cari Dokumen</a>
        </div>
    </div>

@endsection
