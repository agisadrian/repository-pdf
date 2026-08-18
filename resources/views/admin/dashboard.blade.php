@extends('layouts.admin')

@section('title', 'Dashboard - Repository PDF')

@section('admin-content')

    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Ringkasan repository dokumen.</p>

    @if ($canBecomeSuperAdmin)
        <div class="alert alert-success" style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
            <span>
                Belum ada <strong>Super Admin</strong> di sistem ini. Super Admin bisa kelola Kategori
                & ubah role user lain, tanpa perlu buka database.
            </span>
            <form action="{{ route('admin.becomeSuperAdmin') }}" method="POST" onsubmit="return confirm('Jadikan akun kamu Super Admin sekarang?')">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap;">Jadikan Saya Super Admin</button>
            </form>
        </div>
    @endif

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

    <div class="admin-panel-row">
        <div class="admin-panel">
            <div class="admin-panel-header">
                <h3>Dokumen per Kategori</h3>
            </div>

            @if ($documentsPerCategory->isEmpty())
                <p class="empty-hint">Belum ada kategori.</p>
            @else
                @php $maxCategoryCount = max($documentsPerCategory->max('documents_count'), 1); @endphp
                <div class="bar-chart">
                    @foreach ($documentsPerCategory as $category)
                        <div class="bar-chart-row">
                            <span class="bar-chart-label">{{ $category->name }}</span>
                            <div class="bar-chart-track">
                                <div class="bar-chart-fill" style="width: {{ $category->documents_count / $maxCategoryCount * 100 }}%"></div>
                            </div>
                            <span class="bar-chart-value">{{ $category->documents_count }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="admin-panel">
            <div class="admin-panel-header">
                <h3>Upload per Bulan (6 Bulan Terakhir)</h3>
            </div>

            @php $maxMonthCount = max($uploadsPerMonth->max('count'), 1); @endphp
            <div class="bar-chart">
                @foreach ($uploadsPerMonth as $month)
                    <div class="bar-chart-row">
                        <span class="bar-chart-label">{{ $month['label'] }}</span>
                        <div class="bar-chart-track">
                            <div class="bar-chart-fill bar-chart-fill-alt" style="width: {{ $month['count'] / $maxMonthCount * 100 }}%"></div>
                        </div>
                        <span class="bar-chart-value">{{ $month['count'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="admin-panel">
        <div class="admin-panel-header">
            <h3>5 Dokumen Terpopuler</h3>
        </div>

        @if ($topDocuments->isEmpty())
            <p class="empty-hint">Belum ada dokumen.</p>
        @else
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Dilihat</th>
                        <th>Diunduh</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($topDocuments as $doc)
                        <tr>
                            <td>{{ $doc->title }}</td>
                            <td>{{ $doc->category?->name ?? '-' }}</td>
                            <td>{{ $doc->view_count }}</td>
                            <td>{{ $doc->download_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

@endsection
