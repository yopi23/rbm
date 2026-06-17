<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shift;
use App\Models\Cabang;
use App\Models\Sparepart;
use App\Models\Sevices;
use App\Models\Penjualan;
use App\Models\User;

class BackfillCabangIdToTransactionsSeeder extends Seeder
{
    public function run()
    {
        // 1. Backfill spareparts
        $spareparts = Sparepart::whereNull('cabang_id')->get();
        echo "Found " . $spareparts->count() . " spareparts to backfill.\n";
        foreach ($spareparts as $sp) {
            $cabang = Cabang::firstOrCreate(
                ['kode_owner' => $sp->kode_owner, 'nama_cabang' => 'Cabang Utama'],
                ['alamat_cabang' => 'Kantor Pusat / Cabang Utama', 'is_active' => true]
            );
            $sp->update(['cabang_id' => $cabang->id]);
        }

        // 2. Backfill sevices (services)
        $services = Sevices::whereNull('cabang_id')->get();
        echo "Found " . $services->count() . " services to backfill.\n";
        foreach ($services as $srv) {
            $cabangId = null;
            if ($srv->shift_id) {
                $shift = Shift::find($srv->shift_id);
                if ($shift) {
                    $cabangId = $shift->cabang_id;
                }
            }
            if (!$cabangId) {
                $cabang = Cabang::firstOrCreate(
                    ['kode_owner' => $srv->kode_owner, 'nama_cabang' => 'Cabang Utama'],
                    ['alamat_cabang' => 'Kantor Pusat / Cabang Utama', 'is_active' => true]
                );
                $cabangId = $cabang->id;
            }
            $srv->update(['cabang_id' => $cabangId]);
        }

        // 3. Backfill penjualans
        $penjualans = Penjualan::whereNull('cabang_id')->get();
        echo "Found " . $penjualans->count() . " sales to backfill.\n";
        foreach ($penjualans as $pj) {
            $cabangId = null;
            if ($pj->shift_id) {
                $shift = Shift::find($pj->shift_id);
                if ($shift) {
                    $cabangId = $shift->cabang_id;
                }
            }
            if (!$cabangId) {
                $cabang = Cabang::firstOrCreate(
                    ['kode_owner' => $pj->kode_owner, 'nama_cabang' => 'Cabang Utama'],
                    ['alamat_cabang' => 'Kantor Pusat / Cabang Utama', 'is_active' => true]
                );
                $cabangId = $cabang->id;
            }
            $pj->update(['cabang_id' => $cabangId]);
        }

        echo "Cabang ID backfill completed.\n";
    }
}
