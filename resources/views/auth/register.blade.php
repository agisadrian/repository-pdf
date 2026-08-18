@extends('layouts.app')

@section('title', 'Daftar Akun - Repository PDF')

@section('content')

    <div class="auth-wrap">
        <div class="auth-card">
            <h1 class="page-title">Daftar Akun</h1>
            <p class="page-subtitle">Buat akun baru untuk mengelola dokumen.</p>

            @if ($errors->any())
                <div class="alert alert-error">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('register') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="name">Nama</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus class="form-input">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required class="form-input">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required class="form-input">
                    <small class="field-hint">Minimal 8 karakter.</small>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Konfirmasi Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required class="form-input">
                </div>

                <div class="form-group form-checkbox">
                    <label>
                        <input type="checkbox" name="request_admin" value="1" {{ old('request_admin') ? 'checked' : '' }}>
                        Ajukan jadi Admin
                    </label>
                    <small class="field-hint">
                        Pengajuan akan menunggu persetujuan Super Admin. Kalau tidak dicentang,
                        akun kamu tetap dibuat sebagai user biasa.
                    </small>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Daftar</button>
            </form>

            <p class="auth-switch">
                Sudah punya akun? <a href="{{ route('login') }}">Login di sini</a>
            </p>
        </div>
    </div>

@endsection
