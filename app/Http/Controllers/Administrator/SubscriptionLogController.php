<?php

namespace App\Http\Controllers\Administrator;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionLog;
use Illuminate\Http\Request;

class SubscriptionLogController extends Controller
{
    public function index()
    {
        $page = "Log Aktivitas Langganan";

        // Ambil semua log, urutkan dari yang terbaru, dan gunakan pagination
        // 'with()' digunakan agar tidak terjadi N+1 problem saat mengambil data relasi
        $logs = SubscriptionLog::with('user', 'subscription.plan', 'performer')
            ->latest() // Mengurutkan berdasarkan created_at (terbaru dulu)
            ->paginate(30); // Menampilkan 30 log per halaman

        $content = view('admin.page.logs.index', compact('logs'))->render();

        return view('admin.layout.blank_page', compact('page', 'content'));
    }
}
