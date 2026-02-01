<?php

namespace App\Http\Controllers;

use App\Models\TokoSetting;
use App\Models\Sevices;
use App\Models\Garansi;
use App\Models\DetailPartServices;
use App\Models\DetailPartLuarService;
use Illuminate\Http\Request;
use Milon\Barcode\Facades\DNS1DFacade;

class PublicPageController extends Controller
{
    /**
     * Display the public page for an owner
     */
    public function index($slug)
    {
        $toko = TokoSetting::findBySlug($slug);

        if (!$toko) {
            abort(404, 'Halaman tidak ditemukan');
        }

        return view('public.cek-status', [
            'toko' => $toko,
            'slug' => $slug,
        ]);
    }

    /**
     * Check service status via AJAX
     */
    public function checkService(Request $request, $slug)
    {
        $toko = TokoSetting::findBySlug($slug);

        if (!$toko) {
            return response()->json(['success' => false, 'message' => 'Toko tidak ditemukan'], 404);
        }

        $request->validate([
            'kode' => 'required|string|max:50'
        ]);

        $service = Sevices::with(['teknisi', 'partToko', 'partLuar', 'garansi'])
            ->where('kode_service', $request->kode)
            ->where('kode_owner', $toko->id_owner)
            ->first();

        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Kode service tidak ditemukan'
            ]);
        }

        $barcode = DNS1DFacade::getBarcodeHTML($service->kode_service, "C39", 1, 60);

        return response()->json([
            'success' => true,
            'data' => [
                'kode_service' => $service->kode_service,
                'status' => $service->status_services,
                'nama_pelanggan' => $this->maskName($service->nama_pelanggan),
                'type_unit' => $service->type_unit,
                'teknisi' => $service->teknisi?->name ?? '-',
                'keterangan' => $service->keterangan,
                'total_biaya' => $service->total_biaya,
                'dp' => $service->dp,
                'sisa' => $service->total_biaya - $service->dp,
                'created_at' => $service->created_at?->format('d M Y H:i') ?? '-',
                'barcode' => $barcode,
                'parts' => $this->getPartsUsed($service),
                'garansi' => $service->garansi->map(fn($g) => [
                    'nama' => $g->nama_garansi,
                    'tgl_mulai' => $g->tgl_mulai_garansi,
                    'tgl_exp' => $g->tgl_exp_garansi,
                    'status' => now()->lt($g->tgl_exp_garansi) ? 'active' : 'expired',
                    'days_remaining' => max(0, now()->diffInDays($g->tgl_exp_garansi, false))
                ])
            ]
        ]);
    }

    /**
     * Check warranty status via AJAX
     */
    public function checkGaransi(Request $request, $slug)
    {
        $toko = TokoSetting::findBySlug($slug);

        if (!$toko) {
            return response()->json(['success' => false, 'message' => 'Toko tidak ditemukan'], 404);
        }

        $request->validate([
            'kode' => 'required|string|max:50'
        ]);

        $garansiList = Garansi::where('kode_garansi', $request->kode)
            ->where('kode_owner', $toko->id_owner)
            ->get();

        if ($garansiList->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Kode garansi tidak ditemukan'
            ]);
        }

        $barcode = DNS1DFacade::getBarcodeHTML($request->kode, "C39", 1, 60);

        return response()->json([
            'success' => true,
            'data' => [
                'kode_garansi' => $request->kode,
                'barcode' => $barcode,
                'items' => $garansiList->map(fn($g) => [
                    'type' => $g->type_garansi,
                    'nama' => $g->nama_garansi,
                    'catatan' => $g->catatan_garansi,
                    'tgl_mulai' => $g->tgl_mulai_garansi,
                    'tgl_exp' => $g->tgl_exp_garansi,
                    'status' => now()->lt($g->tgl_exp_garansi) ? 'active' : 'expired',
                    'days_remaining' => max(0, now()->diffInDays($g->tgl_exp_garansi, false))
                ])
            ]
        ]);
    }

    /**
     * Mask customer name for privacy
     */
    private function maskName($name)
    {
        if (empty($name)) return '-';
        if (strlen($name) <= 3) return $name;
        return substr($name, 0, 2) . str_repeat('*', strlen($name) - 3) . substr($name, -1);
    }

    /**
     * Get parts used in service
     */
    private function getPartsUsed($service)
    {
        $parts = [];

        // Parts from store inventory
        foreach ($service->partToko as $part) {
            $variant = $part->variant ?? null;
            $parts[] = [
                'nama' => $variant?->full_name ?? $part->nama_sparepart ?? 'Sparepart',
                'qty' => $part->qty_part
            ];
        }

        // External parts
        foreach ($service->partLuar as $part) {
            $parts[] = [
                'nama' => $part->nama_part,
                'qty' => $part->qty_part
            ];
        }

        return $parts;
    }
}
