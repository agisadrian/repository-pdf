@extends('layouts.app')

@section('title', 'Terjadi Kesalahan - Repository PDF')

@section('meta')
    <meta name="description" content="Terjadi kesalahan pada server.">
    <meta name="robots" content="noindex, follow">
@endsection

@section('content')

    <div class="error-page">
        <div class="error-code">500</div>
        <h1 class="error-title">Terjadi Kesalahan</h1>
        <p class="error-message">
            Maaf, ada masalah di server kami. Silakan coba lagi beberapa saat lagi.
        </p>

        <div class="error-actions">
            <a href="{{ url('/') }}" class="btn btn-primary">Kembali ke Home</a>
        </div>
    </div>

@endsection
