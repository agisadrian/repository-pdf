<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Maksimal percobaan login gagal sebelum kena lockout sementara
    private const MAX_ATTEMPTS = 5;

    // Tampilkan halaman form login
    public function showLogin()
    {
        // Kalau sudah login, langsung lempar ke dashboard
        if (Auth::check()) {
            return redirect('/admin/dashboard');
        }

        return view('auth.login');
    }

    // Proses login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = $this->throttleKey($request);

        // Kalau udah kena lockout, tolak duluan sebelum sempet cek password
        // (biar nggak bisa dipake buat brute-force nebak password berkali-kali)
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            return redirect()->intended('/admin/dashboard');
        }

        // Login gagal: catat 1 percobaan (lockout durasinya nambah tiap kena hit,
        // default RateLimiter::hit itu 1 menit per pelanggaran)
        RateLimiter::hit($throttleKey, 60);

        return back()
            ->withErrors(['email' => 'Email atau password salah.'])
            ->onlyInput('email');
    }

    // Proses logout
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    // Key unik per kombinasi email + IP, biar brute-force dari 1 IP ke banyak
    // akun (atau 1 akun dari banyak IP) tetep kena batas masing-masing
    private function throttleKey(Request $request): string
    {
        return Str::lower($request->input('email')) . '|' . $request->ip();
    }
}
