<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockOpnamePeriod;
use App\Models\StockOpnameDetail;
use App\Models\StockOpnameAdjustment;
use App\Models\Sparepart;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class StockOpnameController extends Controller
{
    /**
     * Menampilkan daftar periode stock opname
     */
    public function index()
    {
        $page = 'Stock Opname';

        // Ambil semua periode stock opname
        $periods = StockOpnamePeriod::where('kode_owner', $this->getThisUser()->id_upline)
            ->orderBy('created_at', 'desc')
            ->get();

        // Generate view
        $content = view('admin.page.stock_opname.index', compact('periods'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Menampilkan form untuk membuat periode stock opname baru
     */
    public function create()
    {
        $page = 'Buat Periode Stock Opname Baru';

        // Generate kode periode
        $kode_periode = $this->generatePeriodeCode();

        // Generate view
        $content = view('admin.page.stock_opname.create', compact('kode_periode'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Menyimpan periode stock opname baru
     */
    public function store(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'kode_periode' => 'required|string|unique:stock_opname_periods',
            'nama_periode' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'catatan' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Buat periode baru
            $period = StockOpnamePeriod::create([
                'kode_periode' => $validated['kode_periode'],
                'nama_periode' => $validated['nama_periode'],
                'tanggal_mulai' => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'],
                'status' => 'draft',
                'catatan' => $validated['catatan'],
                'user_input' => $this->getThisUser()->id,
                'kode_owner' => $this->getThisUser()->id_upline,
            ]);

            // Generate detail opname untuk semua sparepart
            $spareparts = Sparepart::where('kode_owner', $this->getThisUser()->id_upline)->get();

            $detailsData = [];
            foreach ($spareparts as $sparepart) {
                $detailsData[] = [
                    'period_id' => $period->id,
                    'sparepart_id' => $sparepart->id,
                    'stock_tercatat' => $sparepart->stok_sparepart,
                    'status' => 'pending',
                    'kode_owner' => $this->getThisUser()->id_upline,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert batch detail opname
            StockOpnameDetail::insert($detailsData);

            DB::commit();

            return redirect()->route('stock-opname.show', $period->id)
                ->with('success', 'Periode stock opname berhasil dibuat');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Menampilkan detail periode stock opname
     */
    public function show($id)
    {
        $page = 'Detail Stock Opname';

        $period = StockOpnamePeriod::with(['details.sparepart'])->findOrFail($id);

        // Kelompokkan detail berdasarkan status
        $pendingItems = $period->details()->with('sparepart')->where('status', 'pending')->get();
        $checkedItems = $period->details()->with('sparepart')->whereIn('status', ['checked', 'adjusted'])->get();

        // Hitung statistik
        $totalItems = $period->details->count();
        $pendingCount = $pendingItems->count();
        $checkedCount = $period->details->where('status', 'checked')->count();
        $adjustedCount = $period->details->where('status', 'adjusted')->count();
        $progressPercentage = $totalItems > 0 ? round((($checkedCount + $adjustedCount) / $totalItems) * 100) : 0;

        // Statistik selisih
        $positiveSelisih = $period->details->where('selisih', '>', 0)->sum('selisih');
        $negativeSelisih = $period->details->where('selisih', '<', 0)->sum('selisih');
        $itemsWithSelisih = $period->details->whereNotNull('selisih')->where('selisih', '!=', 0)->count();

        $content = view('admin.page.stock_opname.show', compact(
            'period',
            'pendingItems',
            'checkedItems',
            'totalItems',
            'pendingCount',
            'checkedCount',
            'adjustedCount',
            'progressPercentage',
            'positiveSelisih',
            'negativeSelisih',
            'itemsWithSelisih'
        ))->render();

        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Memulai atau melanjutkan proses stock opname
     */
    public function startProcess($id)
    {
        try {
            $period = StockOpnamePeriod::findOrFail($id);

            // Verifikasi status periode
            if ($period->status === 'completed') {
                return back()->withErrors(['error' => 'Stock opname ini sudah selesai dan tidak dapat diubah.']);
            }

            if ($period->status === 'cancelled') {
                return back()->withErrors(['error' => 'Stock opname ini sudah dibatalkan.']);
            }

            // Update status periode
            $period->status = 'in_progress';
            $period->save();

            return redirect()->route('stock-opname.check-items', $period->id)
                ->with('success', 'Proses stock opname dimulai. Silakan periksa item satu per satu.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Menampilkan form untuk memeriksa item
     */
    public function checkItems($id)
    {
        $page = 'Pemeriksaan Stock Opname';

        $period = StockOpnamePeriod::findOrFail($id);

        // Verifikasi status periode
        if (!in_array($period->status, ['in_progress', 'draft'])) {
            return redirect()->route('stock-opname.show', $period->id)
                ->withErrors(['error' => 'Stock opname ini tidak dalam status yang dapat diperiksa.']);
        }

        // Ambil item yang belum diperiksa
        $pendingItems = $period->details()
            ->with('sparepart')
            ->where('status', 'pending')
            ->paginate(10);

        // Untuk tampilan scan barcode (jika ada)
        $lastScannedItem = null;

        $content = view('admin.page.stock_opname.check_items', compact(
            'period',
            'pendingItems',
            'lastScannedItem'
        ))->render();

        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Simpan hasil pemeriksaan item
     */
    public function saveItemCheck(Request $request, $periodId, $detailId)
    {
        // Validasi input
        $validated = $request->validate([
            'stock_aktual' => 'required|integer|min:0',
            'catatan' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $period = StockOpnamePeriod::findOrFail($periodId);
            $detail = StockOpnameDetail::findOrFail($detailId);

            // Verifikasi status periode
            if (!in_array($period->status, ['in_progress', 'draft'])) {
                return back()->withErrors(['error' => 'Stock opname ini tidak dalam status yang dapat diperiksa.']);
            }

            // Verifikasi status detail
            if ($detail->status !== 'pending') {
                return back()->withErrors(['error' => 'Item ini sudah diperiksa sebelumnya.']);
            }

            // Hitung selisih
            $selisih = $validated['stock_aktual'] - $detail->stock_tercatat;

            // Update detail
            $detail->stock_aktual = $validated['stock_aktual'];
            $detail->selisih = $selisih;
            $detail->status = 'checked';
            $detail->catatan = $validated['catatan'];
            $detail->user_check = $this->getThisUser()->id;
            $detail->checked_at = now();
            $detail->save();

            // Update status periode jika semua item sudah diperiksa
            $pendingCount = $period->details()->where('status', 'pending')->count();
            if ($pendingCount === 0) {
                $period->status = 'completed';
                $period->save();
            }

            DB::commit();

            return redirect()->route('stock-opname.check-items', $periodId)
                ->with('success', 'Item berhasil diperiksa.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Scan barcode item (jika fitur barcode diimplementasikan)
     */
    public function scanItem(Request $request, $periodId)
    {
        // Validasi input
        $validated = $request->validate([
            'barcode' => 'required|string',
        ]);

        try {
            $period = StockOpnamePeriod::findOrFail($periodId);

            // Verifikasi status periode
            if (!in_array($period->status, ['in_progress', 'draft'])) {
                return back()->withErrors(['error' => 'Stock opname ini tidak dalam status yang dapat diperiksa.']);
            }

            // Cari sparepart berdasarkan barcode/kode
            $sparepart = Sparepart::where('kode_sparepart', $validated['barcode'])
                ->where('kode_owner', $this->getThisUser()->id_upline)
                ->first();

            if (!$sparepart) {
                return back()->withErrors(['error' => 'Sparepart dengan kode barcode tersebut tidak ditemukan.']);
            }

            // Cari detail opname untuk sparepart ini
            $detail = StockOpnameDetail::where('period_id', $periodId)
                ->where('sparepart_id', $sparepart->id)
                ->first();

            if (!$detail) {
                return back()->withErrors(['error' => 'Item ini tidak terdaftar dalam periode stock opname ini.']);
            }

            // Jika sudah diperiksa, beri tahu
            if ($detail->status !== 'pending') {
                return back()->withErrors(['error' => 'Item ini sudah diperiksa sebelumnya.'])
                    ->with('scanned_item', $detail);
            }

            // Redirect ke form pemeriksaan untuk item ini
            return redirect()->route('stock-opname.check-items', $periodId)
                ->with('scanned_item', $detail);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Menampilkan form untuk penyesuaian stok
     */
    public function adjustmentForm($periodId, $detailId)
    {
        $page = 'Penyesuaian Stock Opname';

        $period = StockOpnamePeriod::findOrFail($periodId);
        $detail = StockOpnameDetail::with('sparepart')->findOrFail($detailId);

        // Verifikasi status
        if (!in_array($detail->status, ['checked', 'adjusted'])) {
            return redirect()->route('stock-opname.show', $periodId)
                ->withErrors(['error' => 'Item ini belum diperiksa atau tidak dapat disesuaikan.']);
        }

        // Ambil riwayat penyesuaian sebelumnya (jika ada)
        $adjustmentHistory = StockOpnameAdjustment::where('detail_id', $detailId)
            ->orderBy('created_at', 'desc')
            ->get();

        $content = view('admin.page.stock_opname.adjustment_form', compact(
            'period',
            'detail',
            'adjustmentHistory'
        ))->render();

        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Menyimpan penyesuaian stok
     */
    public function saveAdjustment(Request $request, $periodId, $detailId)
    {
        // Validasi input
        $validated = $request->validate([
            'alasan_adjustment' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $period = StockOpnamePeriod::findOrFail($periodId);
            $detail = StockOpnameDetail::with('sparepart')->findOrFail($detailId);

            // Verifikasi status
            if (!in_array($detail->status, ['checked', 'adjusted'])) {
                return back()->withErrors(['error' => 'Item ini belum diperiksa atau tidak dapat disesuaikan.']);
            }

            // Dapatkan sparepart
            $sparepart = $detail->sparepart;
            $currentStock = $sparepart->stok_sparepart;
            $adjustmentQty = $detail->selisih; // Selisih sudah berisi nilai positif atau negatif
            $newStock = $currentStock + $adjustmentQty;

            // Simpan riwayat penyesuaian
            StockOpnameAdjustment::create([
                'detail_id' => $detail->id,
                'stock_before' => $currentStock,
                'stock_after' => $newStock,
                'adjustment_qty' => $adjustmentQty,
                'alasan_adjustment' => $validated['alasan_adjustment'],
                'user_input' => $this->getThisUser()->id,
                'kode_owner' => $this->getThisUser()->id_upline,
            ]);

            // Update stok sparepart
            $sparepart->stok_sparepart = $newStock;
            $sparepart->save();

            // Update status detail
            $detail->status = 'adjusted';
            $detail->save();

            // Catat di stock history
            $this->logStockChange(
                $sparepart->id,
                $adjustmentQty,
                'stock_opname',
                $period->kode_periode,
                'Penyesuaian dari stock opname: ' . $validated['alasan_adjustment'],
                $this->getThisUser()->id,
                $currentStock,
                $newStock
            );

            DB::commit();

            return redirect()->route('stock-opname.show', $periodId)
                ->with('success', 'Penyesuaian stok berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Menyelesaikan periode stock opname
     */
    public function completePeriod($id)
    {
        try {
            $period = StockOpnamePeriod::findOrFail($id);

            // Verifikasi status
            if ($period->status === 'completed') {
                return back()->withErrors(['error' => 'Stock opname ini sudah selesai.']);
            }

            if ($period->status === 'cancelled') {
                return back()->withErrors(['error' => 'Stock opname ini sudah dibatalkan.']);
            }

            // Cek apakah masih ada item yang belum diperiksa
            $pendingCount = $period->details()->where('status', 'pending')->count();
            if ($pendingCount > 0) {
                return back()->withErrors(['error' => "Masih ada {$pendingCount} item yang belum diperiksa."]);
            }

            // Update status periode
            $period->status = 'completed';
            $period->save();

            return redirect()->route('stock-opname.show', $id)
                ->with('success', 'Stock opname berhasil diselesaikan.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Membatalkan periode stock opname
     */
    public function cancelPeriod($id)
    {
        try {
            $period = StockOpnamePeriod::findOrFail($id);

            // Verifikasi status
            if ($period->status === 'completed') {
                return back()->withErrors(['error' => 'Stock opname ini sudah selesai dan tidak dapat dibatalkan.']);
            }

            if ($period->status === 'cancelled') {
                return back()->withErrors(['error' => 'Stock opname ini sudah dibatalkan sebelumnya.']);
            }

            // Verifikasi jika ada penyesuaian yang sudah dilakukan
            $adjustedCount = $period->details()->where('status', 'adjusted')->count();
            if ($adjustedCount > 0) {
                return back()->withErrors(['error' => "Stock opname ini memiliki {$adjustedCount} item yang sudah disesuaikan dan tidak dapat dibatalkan."]);
            }

            // Update status periode
            $period->status = 'cancelled';
            $period->save();

            return redirect()->route('stock-opname.index')
                ->with('success', 'Stock opname berhasil dibatalkan.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Melihat laporan stock opname
     */
    public function report($id)
    {
        $page = 'Laporan Stock Opname';

        $period = StockOpnamePeriod::with(['details.sparepart', 'details.adjustments.user', 'user'])
            ->findOrFail($id);

        // Verifikasi status
        if (!in_array($period->status, ['completed'])) {
            return redirect()->route('stock-opname.show', $id)
                ->withErrors(['error' => 'Laporan hanya dapat dilihat untuk stock opname yang sudah selesai.']);
        }

        // Statistik
        $totalItems = $period->details->count();
        $itemsWithSelisih = $period->details->whereNotNull('selisih')->where('selisih', '!=', 0)->count();
        $positiveSelisih = $period->details->where('selisih', '>', 0)->sum('selisih');
        $negativeSelisih = $period->details->where('selisih', '<', 0)->sum('selisih');
        $totalAdjusted = $period->details->where('status', 'adjusted')->count();

        // Item dengan selisih terbesar (positif dan negatif)
        $largestPositive = $period->details->where('selisih', '>', 0)->sortByDesc('selisih')->take(5);
        $largestNegative = $period->details->where('selisih', '<', 0)->sortBy('selisih')->take(5);

        $content = view('admin.page.stock_opname.report', compact(
            'period',
            'totalItems',
            'itemsWithSelisih',
            'positiveSelisih',
            'negativeSelisih',
            'totalAdjusted',
            'largestPositive',
            'largestNegative'
        ))->render();

        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Export laporan ke Excel
     */
    public function exportExcel($id)
    {
        $period = StockOpnamePeriod::with(['details.sparepart', 'details.userCheck'])
            ->findOrFail($id);

        // Verifikasi status
        if (!in_array($period->status, ['completed'])) {
            return back()->withErrors(['error' => 'Laporan hanya dapat diekspor untuk stock opname yang sudah selesai.']);
        }

        // Persiapkan data untuk Excel
        $filename = "stock_opname_{$period->kode_periode}_" . date('Ymd') . ".xls";

        // Implementasi export Excel di sini
        // Anda bisa menggunakan library seperti PhpSpreadsheet atau cara sederhana berikut

        // Buat HTML sederhana yang akan diconvert ke Excel
        $html = '<html><head><meta charset="UTF-8"></head><body>';
        $html .= '<h2>Laporan Stock Opname: ' . $period->nama_periode . '</h2>';
        $html .= '<p>Kode: ' . $period->kode_periode . '</p>';
        $html .= '<p>Tanggal: ' . $period->tanggal_mulai->format('d/m/Y') . ' - ' . $period->tanggal_selesai->format('d/m/Y') . '</p>';

        // Tabel detail
        $html .= '<table border="1">';
        $html .= '<tr><th>No</th><th>Kode</th><th>Nama Sparepart</th><th>Stok Tercatat</th><th>Stok Aktual</th><th>Selisih</th><th>Status</th><th>Diperiksa Oleh</th><th>Tanggal Periksa</th><th>Catatan</th></tr>';

        foreach ($period->details as $index => $detail) {
            $html .= '<tr>';
            $html .= '<td>' . ($index + 1) . '</td>';
            $html .= '<td>' . $detail->sparepart->kode_sparepart . '</td>';
            $html .= '<td>' . $detail->sparepart->nama_sparepart . '</td>';
            $html .= '<td>' . $detail->stock_tercatat . '</td>';
            $html .= '<td>' . ($detail->stock_aktual ?? '-') . '</td>';
            $html .= '<td>' . ($detail->selisih ?? '-') . '</td>';
            $html .= '<td>' . $detail->status_text . '</td>';
            $html .= '<td>' . ($detail->userCheck ? $detail->userCheck->name : '-') . '</td>';
            $html .= '<td>' . ($detail->checked_at ? $detail->checked_at->format('d/m/Y H:i') : '-') . '</td>';
            $html .= '<td>' . ($detail->catatan ?? '-') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';
        $html .= '</body></html>';

        // Set header untuk download
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        return $html;
    }

    /**
     * Edit catatan periode
     */
    public function editNotes(Request $request, $id)
    {
        // Validasi input
        $validated = $request->validate([
            'catatan' => 'nullable|string',
        ]);

        try {
            $period = StockOpnamePeriod::findOrFail($id);

            // Update catatan
            $period->catatan = $validated['catatan'];
            $period->save();

            return redirect()->route('stock-opname.show', $id)
                ->with('success', 'Catatan berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Generate kode periode baru
     */
    private function generatePeriodeCode()
    {
        $prefix = 'SO-' . date('Ym') . '-';
        $lastPeriod = StockOpnamePeriod::where('kode_periode', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPeriod) {
            $lastNumber = (int) substr($lastPeriod->kode_periode, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Helper method untuk mencatat perubahan stok
     */
    private function logStockChange($sparepartId, $quantityChange, $referenceType, $referenceId, $notes = null, $userId, $stockBefore = 0, $stockAfter = null)
    {
        // Jika stockAfter tidak disetel, hitung berdasarkan stockBefore + quantityChange
        if ($stockAfter === null) {
            $stockAfter = $stockBefore + $quantityChange;
        }

        // Buat log stock history jika model ada
        if (class_exists('App\Models\StockHistory')) {
            StockHistory::create([
                'sparepart_id' => $sparepartId,
                'quantity_change' => $quantityChange,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'notes' => $notes,
                'user_input' => $userId,
            ]);
        }
    }
}
