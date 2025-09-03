<?php

namespace App\Traits;

use App\Models\KasPerusahaan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait ManajemenKasTrait
{
    /**
     * Mencatat transaksi ke buku besar kas perusahaan secara atomik (aman).
     *
     * @param Model $sumberModel - Model asal transaksi (misal: instance dari Penjualan, Service, dll)
     * @param float $debit - Jumlah pemasukan (isi 0 jika pengeluaran)
     * @param float $kredit - Jumlah pengeluaran (isi 0 jika pemasukan)
     * @param string $deskripsi - Keterangan yang akan muncul di buku besar
     * @param string|null $tanggal - Tanggal transaksi. Jika null, akan memakai waktu sekarang.
     * @return void
     */
    protected function catatKas(Model $sumberModel, float $debit, float $kredit, string $deskripsi, $tanggal = null)
    {
        // Menggunakan DB::transaction untuk memastikan jika ada error,
        // semua proses di dalamnya akan dibatalkan (rollback).
        // Ini mencegah data korup (misal: penjualan tercatat tapi kas tidak).
        DB::transaction(function () use ($sumberModel, $debit, $kredit, $deskripsi, $tanggal) {

            // Ambil ID owner dari model sumber (Penjualan, Service, dll).
            // Ini memastikan setiap model sumber WAJIB punya kolom 'kode_owner'.
            $ownerId = $sumberModel->kode_owner;

            // Dapatkan saldo terakhir dari kas milik owner ini secara aman (mengunci baris terakhir).
            $saldoTerakhir = KasPerusahaan::where('kode_owner', $ownerId)
                                ->latest('id')
                                ->lockForUpdate() // Mencegah race condition jika ada 2 transaksi bersamaan
                                ->first()->saldo ?? 0;

            // Hitung saldo baru
            $saldoBaru = $saldoTerakhir + $debit - $kredit;

            // Siapkan data untuk entri kas baru
            $dataKas = [
                'kode_owner'      => $ownerId,
                'tanggal'         => now(),
                'deskripsi'       => $deskripsi,
                'debit'           => $debit,
                'kredit'          => $kredit,
                'saldo'           => $saldoBaru,
            ];

            // Simpan entri kas dan tautkan ke model sumbernya via relasi polimorfik.
            // $sumberModel->kas() adalah referensi ke relasi morphOne yang sudah Anda buat.
            $sumberModel->kas()->create($dataKas);
        });
    }
}
