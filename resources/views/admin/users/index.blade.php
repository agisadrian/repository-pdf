@extends('layouts.admin')

@section('title', 'Kelola Pengguna - Repository PDF')

@section('admin-content')

    <div class="admin-panel-header">
        <h1 class="page-title">Kelola Pengguna</h1>
    </div>

    <div class="admin-panel">
        <p class="page-subtitle">
            Ubah role user di sini. <strong>Admin</strong> bisa kelola dokumen. <strong>Super Admin</strong>
            bisa kelola dokumen + Kategori + halaman ini juga. Role sendiri nggak bisa diubah dari sini
            (biar nggak kekunci nggak sengaja) — minta Super Admin lain kalau perlu ubah role akun sendiri.
        </p>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role Saat Ini</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="role-badge role-badge-{{ $user->role }}">
                                {{ match ($user->role) {
                                    'super_admin' => 'Super Admin',
                                    'admin' => 'Admin',
                                    default => 'User',
                                } }}
                            </span>
                        </td>
                        <td>
                            @if ($user->id === auth()->id())
                                <span class="field-hint">Ini akun kamu</span>
                            @else
                                <form action="{{ route('admin.users.update', $user) }}" method="POST" class="inline-form-row">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role" class="form-input" style="width: 160px;">
                                        <option value="user" @selected($user->role === 'user')>User</option>
                                        <option value="admin" @selected($user->role === 'admin')>Admin</option>
                                        <option value="super_admin" @selected($user->role === 'super_admin')>Super Admin</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

@endsection
