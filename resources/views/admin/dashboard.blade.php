@extends('layouts.admin')

@section('title', 'Dashboard - Repository PDF')

@section('admin-content')

    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Ringkasan repository dokumen.</p>

    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-number">{{ $stats['documents'] }}</div>
            <div class="stat-label">Total Dokumen</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $stats['categories'] }}</div>
            <div class="stat-label">Kategori</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $stats['users'] }}</div>
            <div class="stat-label">User Terdaftar</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $stats['total_views'] }}</div>
            <div class="stat-label">Total Dilihat</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $stats['total_downloads'] }}</div>
            <div class="stat-label">Total Diunduh</div>
        </div>
    </div>

    <div class="admin-panel">
        <div class="admin-panel-header">
            <h3>Dokumen Terbaru</h3>
            <a href="{{ route('admin.documents.index') }}" class="btn btn-primary btn-sm">Kelola Semua &rarr;</a>
        </div>

        @if ($recentDocuments->isEmpty())
            <p class="empty-hint">Belum ada dokumen.</p>
        @else
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Tahun</th>
                        <th>Dilihat</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentDocuments as $doc)
                        <tr>
                            <td>{{ $doc->title }}</td>
                            <td>{{ $doc->category?->name ?? '-' }}</td>
                            <td>{{ $doc->year }}</td>
                            <td>{{ $doc->view_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

@endsection
