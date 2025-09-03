<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

trait OperationalDateTrait
{
    /**
     * Mendapatkan jam tutup buku dari database untuk owner tertentu.
     * Menggunakan cache agar tidak selalu query ke database.
     */
    protected function getClosingTime($ownerId): string
    {
        return Cache::remember('closing_time_' . $ownerId, 60, function () use ($ownerId) {
            $setting = DB::table('close_book_setting')->where('kode_owner', $ownerId)->first();
            return $setting ? $setting->jam : '23:59:59'; // Default jika tidak diatur
        });
    }

    /**
     * Mendapatkan tanggal operasional berdasarkan timestamp transaksi dan jam tutup buku.
     * * @param string|Carbon $timestamp - Waktu transaksi.
     * @param string $closingTime - Jam tutup buku (format HH:mm:ss).
     * @return Carbon - Objek Carbon yang merepresentasikan tanggal operasional.
     */
    protected function getOperationalDate(string|Carbon $timestamp, string $closingTime): Carbon
    {
        $transactionTime = Carbon::parse($timestamp);
        $closingDateTime = Carbon::parse($transactionTime->format('Y-m-d') . ' ' . $closingTime);

        // Jika waktu transaksi MELEBIHI jam tutup buku hari itu,
        // maka transaksinya dihitung sebagai transaksi HARI BERIKUTNYA.
        if ($transactionTime->greaterThan($closingDateTime)) {
            return $transactionTime->addDay()->startOfDay();
        }

        // Jika tidak, transaksinya tetap dihitung di hari yang sama.
        return $transactionTime->startOfDay();
    }

    /**
     * Menambahkan klausa WHERE ke query Eloquent untuk memfilter berdasarkan rentang tanggal operasional.
     * Ini adalah cara yang benar untuk memfilter laporan harian.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $date - Tanggal yang ingin dicari (format Y-m-d).
     * @param int $ownerId
     * @param string $dateColumn - Nama kolom tanggal di database (default: 'tanggal').
     */
    protected function applyOperationalDateFilter($query, string $date, int $ownerId, string $dateColumn = 'tanggal')
    {
        $closingTime = $this->getClosingTime($ownerId);
        $targetDate = Carbon::parse($date);

        // Awal periode laporan adalah SETELAH jam tutup buku HARI SEBELUMNYA.
        $startRange = Carbon::parse($targetDate->copy()->subDay()->format('Y-m-d') . ' ' . $closingTime)->addSecond();

        // Akhir periode laporan adalah PADA jam tutup buku HARI INI.
        $endRange = Carbon::parse($targetDate->format('Y-m-d') . ' ' . $closingTime);

        return $query->whereBetween($dateColumn, [$startRange, $endRange]);
    }
}
