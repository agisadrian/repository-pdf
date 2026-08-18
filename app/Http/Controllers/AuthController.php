<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Maksimal percobaan login gagal sebelum kena lockout sementara
    private const MAX_ATTEMPTS = 5;

    // Tampilkan halaman form login
    public function showLogin()
    {
        // Kalau sudah login, lempar ke tempat yang sesuai (admin ke dashboard, user biasa ke home)
        if (Auth::check()) {
            return redirect(Auth::user()->isAdmin() ? '/admin/dashboard' : '/');
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

            // User biasa (belum admin) nggak punya akses ke /admin/dashboard,
            // jadi jangan diarahkan ke sana biar nggak kena 403 -- balikin ke home.
            if (! Auth::user()->isAdmin()) {
                return redirect('/');
            }

            return redirect()->intended('/admin/dashboard');
        }

        // Login gagal: catat 1 percobaan (lockout durasinya nambah tiap kena hit,
        // default RateLimiter::hit itu 1 menit per pelanggaran)
        RateLimiter::hit($throttleKey, 60);

        return back()
            ->withErrors(['email' => 'Email atau password salah.'])
            ->onlyInput('email');
    }

    // Tampilkan halaman form daftar akun baru
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect(Auth::user()->isAdmin() ? '/admin/dashboard' : '/');
        }

        return view('auth.register');
    }

    // Proses daftar akun baru. Semua akun baru mulai dengan role 'user'.
    // Kalau centang "Ajukan jadi Admin", dicatat waktu pengajuannya supaya
    // muncul di halaman "Permintaan Admin" milik Super Admin untuk disetujui/ditolak.
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'request_admin' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'user',
            'admin_requested_at' => $request->boolean('request_admin') ? now() : null,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->hasPendingAdminRequest()) {
            return redirect('/')
                ->with('success', 'Akun berhasil dibuat! Pengajuan kamu untuk jadi Admin sudah dikirim dan menunggu persetujuan Super Admin.');
        }

        return redirect('/')->with('success', 'Akun berhasil dibuat! Selamat datang.');
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
