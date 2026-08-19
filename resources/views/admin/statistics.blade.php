@extends('layouts.admin')

@section('title', 'Laporan Statistik - Repository PDF')

@section('admin-content')

    <h1 class="page-title">Laporan Statistik</h1>
    <p class="page-subtitle">Ringkasan kunjungan situs dalam rentang waktu tertentu.</p>

    <div class="admin-panel-header" style="border:none; padding:0; margin-bottom:16px;">
        <div style="display:flex; gap:8px;">
            @foreach ([7 => '7 Hari', 30 => '30 Hari', 90 => '90 Hari'] as $value => $label)
                <a
                    href="{{ route('admin.statistics', ['days' => $value]) }}"
                    class="btn btn-sm {{ $days === $value ? 'btn-primary' : 'btn-secondary' }}"
                >
                    {{ $label }} Terakhir
                </a>
            @endforeach
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-number">{{ $totalViewsInRange }}</div>
            <div class="stat-label">Total Kunjungan ({{ $days }} Hari)</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $uniqueVisitorsInRange }}</div>
            <div class="stat-label">Pengunjung Unik ({{ $days }} Hari)</div>
        </div>
    </div>

    <div class="admin-panel">
        <div class="admin-panel-header">
            <h3>Kunjungan per Hari</h3>
        </div>

        @php $maxDailyCount = max($dailyChart->max('count'), 1); @endphp
        <div class="bar-chart">
            @foreach ($dailyChart as $day)
                <div class="bar-chart-row">
                    <span class="bar-chart-label">{{ $day['label'] }}</span>
                    <div class="bar-chart-track">
                        <div class="bar-chart-fill" style="width: {{ $day['count'] / $maxDailyCount * 100 }}%"></div>
                    </div>
                    <span class="bar-chart-value">{{ $day['count'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="admin-panel">
        <div class="admin-panel-header">
            <h3>Dokumen Terpopuler ({{ $days }} Hari Terakhir)</h3>
        </div>

        @if ($topDocumentsInRange->isEmpty())
            <p class="empty-hint">Belum ada kunjungan dalam rentang waktu ini.</p>
        @else
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Kunjungan ({{ $days }} Hari)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($topDocumentsInRange as $doc)
                        <tr>
                            <td>{{ $doc->title }}</td>
                            <td>{{ $doc->category?->name ?? '-' }}</td>
                            <td>{{ $doc->views_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

@endsection
