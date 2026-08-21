<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;

class ActivityLogController extends Controller
{
    // Daftar aktivitas admin, terbaru duluan, dipaginate biar nggak berat kalau sudah banyak
    public function index()
    {
        $logs = AdminActivityLog::with('user')
            ->latest('created_at')
            ->paginate(30);

        return view('admin.activity-log', [
            'logs' => $logs,
        ]);
    }
}
