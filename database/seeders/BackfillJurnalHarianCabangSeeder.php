<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shift;
use App\Models\Cabang;
use App\Models\JurnalHarianCabang;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\Sevices;
use App\Models\Penjualan;
use App\Models\DetailPartServices;
use App\Models\DetailPartLuarService;
use App\Models\DetailBarangPenjualan;
use App\Models\DetailSparepartPenjualan;
use App\Models\PengeluaranToko;
use App\Models\PengeluaranOperasional;
use App\Models\ProfitPresentase;
use Illuminate\Support\Facades\DB;

class BackfillJurnalHarianCabangSeeder extends Seeder
{
    public function run()
    {
        $shifts = Shift::where('status', 'closed')->get();
        echo "Found " . $shifts->count() . " closed shifts to process.\n";

        foreach ($shifts as $shift) {
            // Find or create default branch for this owner
            $cabang = Cabang::firstOrCreate(
                ['kode_owner' => $shift->kode_owner, 'nama_cabang' => 'Cabang Utama'],
                ['alamat_cabang' => 'Kantor Pusat / Cabang Utama', 'is_active' => true]
            );

            // Update shift with cabang_id
            $shift->update(['cabang_id' => $cabang->id]);

            // Update shift user with cabang_id if not set
            $user = User::find($shift->user_id);
            if ($user && !$user->cabang_id) {
                $user->update(['cabang_id' => $cabang->id]);
            }

            // Check if journal already exists for this shift
            $exists = JurnalHarianCabang::where('shift_id', $shift->id)->exists();
            if ($exists) {
                continue;
            }

            // Calculate metrics
            $excludedTypes = [
                'App\Models\Pembelian',
                'App\Models\Hutang',
                'App\Models\AlokasiLaba',
                'App\Models\DistribusiLaba'
            ];

            $cashIn = $shift->kasPerusahaan()
                ->where('is_cash', true)
                ->whereNotIn('sourceable_type', $excludedTypes)
                ->sum('debit');

            $transferIn = $shift->kasPerusahaan()
                ->where('is_cash', false)
                ->whereNotIn('sourceable_type', $excludedTypes)
                ->sum('debit');

            // 1. Calculate HPP for shift
            $services = Sevices::where('shift_id', $shift->id)
                ->where('status_services', 'Diambil')
                ->pluck('id');
            
            $partTokoHPP = 0;
            $partLuarHPP = 0;
            if ($services->isNotEmpty()) {
                $partTokoHPP = DetailPartServices::join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')
                    ->whereIn('detail_part_services.kode_services', $services)
                    ->sum(DB::raw('CASE 
                        WHEN detail_part_services.detail_modal_part_service > 0 THEN detail_part_services.detail_modal_part_service 
                        ELSE spareparts.harga_beli 
                    END * detail_part_services.qty_part'));

                $partLuarHPP = DetailPartLuarService::whereIn('kode_services', $services)
                    ->sum(DB::raw('CASE WHEN harga_beli > 0 THEN harga_beli ELSE harga_part END * qty_part'));
            }

            $penjualans = Penjualan::where('shift_id', $shift->id)
                ->where('status_penjualan', '1')
                ->pluck('id');
            
            $barangHPP = 0;
            $sparepartHPP = 0;
            if ($penjualans->isNotEmpty()) {
                $barangHPP = DetailBarangPenjualan::whereIn('kode_penjualan', $penjualans)
                    ->sum(DB::raw('detail_harga_modal * qty_barang'));

                $sparepartHPP = DetailSparepartPenjualan::whereIn('kode_penjualan', $penjualans)
                    ->sum(DB::raw('detail_harga_modal * qty_sparepart'));
            }
            $hppTerjual = $partTokoHPP + $partLuarHPP + $barangHPP + $sparepartHPP;

            // 2. Calculate local operational expenses
            $biayaLokal = PengeluaranToko::where('shift_id', $shift->id)->sum('jumlah_pengeluaran') +
                           PengeluaranOperasional::where('shift_id', $shift->id)->whereNull('beban_operasional_id')->sum('jml_pengeluaran');

            // 3. Calculate technician commissions
            $serviceIds = Sevices::where('shift_id', $shift->id)
                ->whereIn('status_services', ['Selesai', 'Diambil'])
                ->pluck('id');
            $komisiTeknisi = ProfitPresentase::whereIn('kode_service', $serviceIds)->sum('profit');

            // 4. Create JurnalHarianCabang
            JurnalHarianCabang::create([
                'cabang_id' => $cabang->id,
                'shift_id' => $shift->id,
                'tanggal' => $shift->created_at->toDateString(),
                'omset_tunai' => $cashIn,
                'omset_non_tunai' => $transferIn,
                'hpp_terjual' => $hppTerjual,
                'biaya_operasional_lokal' => $biayaLokal,
                'komisi_teknisi' => $komisiTeknisi,
                'kas_seharusnya_disetor' => $cashIn - $biayaLokal,
                'kas_aktual_disetor' => $shift->saldo_akhir_aktual - $shift->modal_awal,
            ]);
        }

        echo "Backfill completed successfully.\n";
    }
}
