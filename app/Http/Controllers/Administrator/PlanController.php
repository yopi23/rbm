<?php

namespace App\Http\Controllers\Administrator;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * Menampilkan daftar semua paket langganan. (Read)
     */
    public function index()
    {
        $page = "Manajemen Paket Langganan";
        $plans = SubscriptionPlan::orderBy('price')->get();

        $content = view('admin.page.plans.index', compact('plans'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Menampilkan form untuk membuat paket baru. (Create)
     */
    public function create()
    {
        $page = "Tambah Paket Baru";

        $content = view('admin.page.plans.create')->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Menyimpan paket baru ke database. (Create)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'duration_in_months' => 'required|integer|min:1',
        ]);

        SubscriptionPlan::create($request->all());

        return redirect()->route('administrator.tokens.plans.index')->with('success', 'Paket baru berhasil ditambahkan.');
    }

    /**
     * Menampilkan form untuk mengedit paket. (Update)
     */
    public function edit(SubscriptionPlan $plan)
    {
        $page = "Edit Paket: " . $plan->name;

        $content = view('admin.page.plans.edit', compact('plan'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Memperbarui data paket di database. (Update)
     */
    public function update(Request $request, SubscriptionPlan $plan)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'duration_in_months' => 'required|integer|min:1',
        ]);

        $plan->update($request->all());

        return redirect()->route('administrator.tokens.plans.index')->with('success', 'Paket berhasil diperbarui.');
    }

    /**
     * Menghapus paket dari database. (Delete)
     */
    public function destroy(SubscriptionPlan $plan)
    {
        // Tambahkan pengecekan keamanan jika diperlukan
        // Contoh: jangan hapus paket jika masih ada user yang berlangganan
        if ($plan->subscriptions()->exists()) {
             return redirect()->route('administrator.tokens.plans.index')->with('error', 'Paket tidak dapat dihapus karena masih ada pelanggan aktif.');
        }

        $plan->delete();

        return redirect()->route('administrator.tokens.plans.index')->with('success', 'Paket berhasil dihapus.');
    }
}
