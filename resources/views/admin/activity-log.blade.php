@extends('layouts.admin')

@section('title', 'Log Aktivitas Admin - Repository PDF')

@section('admin-content')

    <h1 class="page-title">Log Aktivitas Admin</h1>
    <p class="page-subtitle">Catatan aksi-aksi penting yang dilakukan admin & super admin.</p>

    <div class="admin-panel">
        @if ($logs->isEmpty())
            <p class="empty-hint">Belum ada aktivitas yang tercatat.</p>
        @else
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Admin</th>
                        <th>Aktivitas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td style="white-space: nowrap;">{{ $log->created_at->translatedFormat('d M Y, H:i') }}</td>
                            <td>{{ $log->user->name ?? '(akun sudah dihapus)' }}</td>
                            <td>{{ $log->description }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="pagination-wrap">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

@endsection
