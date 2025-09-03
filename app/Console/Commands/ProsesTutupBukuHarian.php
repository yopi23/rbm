<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Admin\TutupBukuController; // Kita panggil controller
use Illuminate\Http\Request;

class ProsesTutupBukuHarian extends Command
{
    protected $signature = 'app:proses-tutup-buku-harian';
    protected $description = 'Menjalankan proses tutup buku harian untuk hari kemarin secara otomatis';

    public function handle()
    {
        $this->info('Memulai proses tutup buku harian otomatis...');

        // Kita "memalsukan" sebuah Request untuk memanggil method di controller
        // Ini cara mudah untuk tidak menulis ulang logika yang sama
        $request = new Request([
            'tanggal' => now()->subDay()->format('Y-m-d') // Proses untuk HARI KEMARIN
        ]);

        // Panggil controller untuk menjalankan proses
        $tutupBukuController = new TutupBukuController();
        $response = $tutupBukuController->proses($request);

        // Cek hasil dari redirect
        $session = $response->getSession();
        if ($session->has('success')) {
            $this->info('SUKSES: ' . $session->get('success'));
        } elseif ($session->has('error')) {
            $this->error('GAGAL: ' . $session->get('error'));
        } elseif ($session->has('info')) {
            $this->comment('INFO: ' . $session->get('info'));
        } else {
            $this->comment('Proses selesai tanpa pesan spesifik.');
        }

        return 0;
    }
}
