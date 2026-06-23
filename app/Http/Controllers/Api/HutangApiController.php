<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hutang;
use App\Models\Pembelian;
use App\Models\Shift;
use App\Models\DetailPembelian;
use App\Models\ProductVariant;
use App\Models\Sparepart;
use App\Traits\ManajemenKasTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HutangApiController extends Controller
{
    use ManajemenKasTrait;

    private function getOwnerId(): int
    {
        $user = Auth::user();
        if ($user->userDetail->jabatan == '1') {
            return $user->id;
        }
        return $user->userDetail->id_upline;
    }

    public function index()
    {
        try {
            $hutang = Hutang::where('kode_owner', $this->getOwnerId())
                ->where('status', 'Belum Lunas')
                ->with('supplier') // PERUBAHAN 1: Memastikan data supplier ikut terambil
                ->orderBy('tgl_jatuh_tempo', 'asc')
                ->get();

            // PERUBAHAN 2: Mengembalikan response dalam format JSON
            return response()->json([
                'success' => true,
                'hutang' => $hutang
            ]);

        }
        catch (\Exception $e) {
            Log::error('Gagal mengambil data hutang: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data hutang.'
            ], 500); // Kode 500 untuk server error
        }
    }

    public function bayar(Request $request, $id)
    {
        // Check Active Shift
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $hutang = Hutang::with('pembelian')->findOrFail($id); // Load relasi pembelian

            // Cek jika sudah lunas untuk mencegah pembayaran ganda
            if ($hutang->status === 'Lunas') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hutang ini sudah dilunasi sebelumnya.'
                ], 422); // 422 Unprocessable Entity
            }

            // 1. Catat pengeluaran di kas perusahaan
            // Menggunakan $hutang sebagai source agar terdata sebagai "Pembayaran Hutang" di laporan
            $this->catatKas(
                $hutang,
                0,
                $hutang->total_hutang,
                'Pembayaran Hutang #' . $hutang->kode_nota,
                now(),
                false // is_cash = false, tidak ambil dari laci
            );

            // 2. Update status hutang & pembelian
            $hutang->update(['status' => 'Lunas']);
            if ($hutang->pembelian) {
                $hutang->pembelian->update(['status_pembayaran' => 'Lunas']);
            }

            DB::commit();

            // PERUBAHAN 3: Mengembalikan response sukses dalam format JSON
            return response()->json([
                'success' => true,
                'message' => 'Hutang berhasil dibayar.'
            ]);

        }
        catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal membayar hutang #' . $id . ': ' . $e->getMessage());

            // PERUBAHAN 4: Mengembalikan response error dalam format JSON
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(), // tampilkan pesan asli
            ], 500);
        }
    }

    public function tempoTracking(Request $request)
    {
        try {
            $ownerId = $this->getOwnerId();
            $cabangId = $request->input('cabang_id');

            // 1. Get unpaid hutang
            $hutangQuery = Hutang::where('kode_owner', $ownerId)
                ->where('status', 'Belum Lunas')
                ->with(['supplier', 'pembelian.shift']);

            if ($cabangId) {
                $hutangQuery->whereHas('pembelian.shift', function ($q) use ($cabangId) {
                    $q->where('cabang_id', $cabangId);
                });
            }

            $hutangs = $hutangQuery->orderBy('tgl_jatuh_tempo', 'asc')->get();

            // Collect all unique variant/sparepart IDs from these tempo batches
            $keys = [];
            foreach ($hutangs as $h) {
                if (!$h->pembelian) continue;
                $details = DetailPembelian::where('pembelian_id', $h->pembelian->id)->get();
                foreach ($details as $d) {
                    if ($d->product_variant_id) {
                        $keys[] = [
                            'type' => 'pv',
                            'id' => $d->product_variant_id
                        ];
                    } elseif ($d->sparepart_id) {
                        $keys[] = [
                            'type' => 'sp',
                            'id' => $d->sparepart_id
                        ];
                    }
                }
            }

            // Unique keys
            $uniqueKeys = [];
            foreach ($keys as $k) {
                $uniqueKeys[$k['type'] . '_' . $k['id']] = $k;
            }

            // Map to store calculated qty_sold and qty_remaining for each detail_pembelian ID
            $calculatedDetailsMap = [];

            // 2. Perform FIFO allocation for each item
            foreach ($uniqueKeys as $keyInfo) {
                $type = $keyInfo['type'];
                $id = $keyInfo['id'];

                $currentStock = 0;
                if ($type === 'pv') {
                    $variant = ProductVariant::find($id);
                    $currentStock = $variant ? $variant->stock : 0;
                } else {
                    $sparepart = Sparepart::find($id);
                    $currentStock = $sparepart ? $sparepart->stok_sparepart : 0;
                }

                // Get ALL purchases for this item (tempo + cash)
                $allDetailsQuery = DetailPembelian::select('detail_pembelians.*')
                    ->join('pembelians', 'pembelians.id', '=', 'detail_pembelians.pembelian_id')
                    ->where('pembelians.kode_owner', $ownerId);

                if ($type === 'pv') {
                    $allDetailsQuery->where('detail_pembelians.product_variant_id', $id);
                } else {
                    $allDetailsQuery->where('detail_pembelians.sparepart_id', $id)
                        ->whereNull('detail_pembelians.product_variant_id');
                }

                $allDetails = $allDetailsQuery->orderBy('pembelians.tanggal_pembelian', 'asc')
                    ->orderBy('detail_pembelians.id', 'asc')
                    ->get();

                $totalBought = $allDetails->sum('jumlah');
                $totalSold = max(0, $totalBought - $currentStock);

                $remainingSold = $totalSold;
                foreach ($allDetails as $detail) {
                    $qtyBought = $detail->jumlah;
                    $qtySold = min($qtyBought, $remainingSold);
                    $remainingSold -= $qtySold;
                    $qtyRemaining = $qtyBought - $qtySold;

                    $calculatedDetailsMap[$detail->id] = [
                        'qty_sold' => $qtySold,
                        'qty_remaining' => $qtyRemaining,
                    ];
                }
            }

            // 3. Format the batch-level results
            $batches = [];
            $totalCollected = 0.0;
            $totalProfit = 0.0;
            $totalOutstandingDebt = 0.0;

            foreach ($hutangs as $h) {
                $pembelian = $h->pembelian;
                if (!$pembelian) continue;

                $details = DetailPembelian::with('productVariant')
                    ->where('pembelian_id', $pembelian->id)
                    ->get();

                $batchCollected = 0.0;
                $batchProfit = 0.0;
                $itemsResult = [];

                foreach ($details as $d) {
                    $calc = $calculatedDetailsMap[$d->id] ?? [
                        'qty_sold' => 0,
                        'qty_remaining' => $d->jumlah
                    ];
                    $qtySold = $calc['qty_sold'];
                    $qtyRemaining = $calc['qty_remaining'];

                    $buyPrice = floatval($d->harga_beli);
                    $sellPrice = $d->productVariant ? floatval($d->productVariant->retail_price) : ($buyPrice * 1.3);

                    $cashCollected = $qtySold * $sellPrice;
                    $profit = $qtySold * ($sellPrice - $buyPrice);

                    $batchCollected += $cashCollected;
                    $batchProfit += $profit;

                    $itemsResult[] = [
                        'id' => $d->id,
                        'nama_item' => $d->nama_item,
                        'qty_bought' => intval($d->jumlah),
                        'qty_sold' => intval($qtySold),
                        'qty_remaining' => intval($qtyRemaining),
                        'buy_price' => $buyPrice,
                        'sell_price' => $sellPrice,
                        'cash_collected' => $cashCollected,
                        'profit' => $profit,
                    ];
                }

                $debtAmount = floatval($h->total_hutang);
                $totalCollected += $batchCollected;
                $totalProfit += $batchProfit;
                $totalOutstandingDebt += $debtAmount;

                // Resolve cabang_id for this batch
                $batchCabangId = null;
                if ($pembelian->shift) {
                    $batchCabangId = $pembelian->shift->cabang_id;
                }

                $batches[] = [
                    'id' => $h->id,
                    'kode_nota' => $h->kode_nota,
                    'supplier' => $h->supplier ? $h->supplier->nama_supplier : 'Supplier',
                    'tgl_jatuh_tempo' => $h->tgl_jatuh_tempo,
                    'total_hutang' => $debtAmount,
                    'cash_collected' => $batchCollected,
                    'profit' => $batchProfit,
                    'cabang_id' => $batchCabangId,
                    'items' => $itemsResult,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'batches' => $batches,
                    'summary' => [
                        'total_collected' => $totalCollected,
                        'total_profit' => $totalProfit,
                        'total_outstanding_debt' => $totalOutstandingDebt,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in tempoTracking: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghitung data tempo: ' . $e->getMessage()
            ], 500);
        }
    }
}
