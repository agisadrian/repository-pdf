@extends('layouts.app')

@section('title', 'Login Admin - Repository PDF')

@section('content')

    <div class="auth-wrap">
        <div class="auth-card">
            <h1 class="page-title">Login</h1>
            <p class="page-subtitle">Masuk sebagai admin untuk mengelola dokumen.</p>

            @if ($errors->any())
                <div class="alert alert-error">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ url('/login') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus class="form-input">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required class="form-input">
                </div>

                <div class="form-group form-checkbox">
                    <label>
                        <input type="checkbox" name="remember"> Ingat saya
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>

            <p class="auth-hint">
                Coba pakai: <code>agisadrianh@gmail.com</code> / <code>agis12</code>
            </p>
        </div>
    </div>

@endsection
