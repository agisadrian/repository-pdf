<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    private const ROLES = ['user', 'admin', 'super_admin'];

    // Daftar semua user + role-nya
    public function index()
    {
        $users = User::orderBy('name')->get();

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    // Ubah role 1 user (dari dropdown di baris tabel)
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => ['required', 'in:' . implode(',', self::ROLES)],
        ]);

        // Nggak boleh ubah role sendiri lewat sini -- biar nggak keklik "user" ke diri
        // sendiri terus kekunci nggak bisa akses admin panel lagi sama sekali
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Nggak bisa ubah role akun sendiri di sini. Minta Super Admin lain buat ubahin, atau turun dulu lewat akun Super Admin lain.');
        }

        // Kalau target user ini Super Admin, dan dia satu-satunya Super Admin yang tersisa,
        // jangan biarkan diturunkan -- supaya sistem nggak pernah kehabisan Super Admin sama sekali
        if ($user->isSuperAdmin() && $data['role'] !== 'super_admin') {
            $superAdminCount = User::where('role', 'super_admin')->count();
            if ($superAdminCount <= 1) {
                return back()->with('error', 'Nggak bisa ubah role ini -- dia satu-satunya Super Admin yang tersisa. Jadikan user lain Super Admin dulu sebelum menurunkan yang ini.');
            }
        }

        $oldRole = $user->role;
        $user->update(['role' => $data['role']]);

        AdminActivityLog::record('user.role_changed', "Mengubah role \"{$user->name}\" dari {$oldRole} menjadi {$data['role']}", $user);

        return back()->with('success', 'Role "' . $user->name . '" berhasil diubah jadi ' . $data['role'] . '.');
    }
}
