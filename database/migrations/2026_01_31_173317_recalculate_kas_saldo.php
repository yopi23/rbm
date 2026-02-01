<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\KasPerusahaan;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all unique owners
        $owners = KasPerusahaan::distinct()->pluck('kode_owner');

        foreach ($owners as $ownerId) {
            DB::transaction(function () use ($ownerId) {
                // Ambil semua entri kas urut berdasarkan tanggal dan id
                // Kita gunakan lockForUpdate untuk mencegah race condition saat recalculate
                $entries = KasPerusahaan::where('kode_owner', $ownerId)
                    ->orderBy('tanggal', 'asc')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                $runningBalance = 0;

                foreach ($entries as $entry) {
                    $runningBalance = $runningBalance + $entry->debit - $entry->kredit;
                    
                    // Update saldo jika berbeda
                    if ($entry->saldo != $runningBalance) {
                        DB::table('kas_perusahaan')
                            ->where('id', $entry->id)
                            ->update(['saldo' => $runningBalance]);
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu reverse karena ini hanya recalculate nilai yang seharusnya benar
    }
};
