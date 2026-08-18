@extends('layouts.admin')

@section('title', 'Permintaan Admin - Repository PDF')

@section('admin-content')

    <div class="admin-panel-header">
        <h1 class="page-title">Permintaan Admin</h1>
    </div>
    <p class="page-subtitle">
        Daftar user yang mengajukan diri jadi Admin lewat halaman Daftar. Setujui untuk
        menaikkan role mereka jadi Admin, atau tolak kalau tidak sesuai.
    </p>

    <div class="admin-panel">
        @if ($pendingRequests->isEmpty())
            <p class="empty-hint">Belum ada permintaan yang menunggu persetujuan.</p>
        @else
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Diajukan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pendingRequests as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->admin_requested_at->diffForHumans() }}</td>
                            <td>
                                <div style="display:flex; gap:8px;">
                                    <form action="{{ route('admin.requests.approve', $user) }}" method="POST" onsubmit="return confirm('Setujui {{ $user->name }} jadi Admin?')">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm">Setujui</button>
                                    </form>
                                    <form action="{{ route('admin.requests.reject', $user) }}" method="POST" onsubmit="return confirm('Tolak permintaan {{ $user->name }}?')">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm">Tolak</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

@endsection
