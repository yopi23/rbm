<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-7">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">1. Pengaturan Jam Tutup Buku</h3>
                    </div>
                    <form action="{{ route('settings.closebook.store') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <p>Atur jam di mana semua transaksi akan dihitung sebagai hari berikutnya. Pengaturan ini
                                akan digunakan oleh proses manual dan otomatis.</p>
                            <div class="form-group">
                                <label for="jam_tutup_buku">Jam Tutup Buku</label>
                                <input type="time" name="jam_tutup_buku" class="form-control"
                                    value="{{ $setting ? \Carbon\Carbon::parse($setting->jam)->format('H:i') : '17:00' }}"
                                    required>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan
                                Pengaturan</button>
                        </div>
                    </form>
                </div>

                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">2. Proses Tutup Buku Manual</h3>
                    </div>
                    <form action="{{ route('tutupbuku.proses') }}" method="POST"
                        onsubmit="return confirm('Anda yakin ingin memproses tutup buku untuk tanggal ini? Aksi ini tidak dapat dibatalkan.');">
                        @csrf
                        <div class="card-body">
                            <p>Gunakan fitur ini jika proses otomatis gagal atau jika Anda perlu memproses ulang data
                                untuk tanggal di masa lalu.</p>
                            <div class="form-group">
                                <label for="tanggal">Pilih Tanggal Operasional</label>
                                <input type="date" name="tanggal" class="form-control"
                                    value="{{ now()->subDay()->format('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-success"><i class="fas fa-archive mr-1"></i> Proses
                                Tutup Buku Manual</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Proses Otomatis (Cron Job)</h3>
                    </div>
                    <div class="card-body">
                        <p>Sistem ini dirancang untuk berjalan otomatis setiap hari menggunakan Cron Job.</p>
                        <ul>
                            <li><strong>Waktu Jalan:</strong> Setiap hari pada jam <strong>23:55</strong> (atau sesuai
                                jadwal Anda).</li>
                            <li><strong>Aksi:</strong> Sistem akan otomatis menjalankan proses tutup buku untuk
                                <strong>hari kemarin</strong>.</li>
                            <li><strong>Tombol Manual:</strong> Gunakan tombol di samping hanya jika diperlukan.</li>
                        </ul>
                        <div class="alert alert-warning">
                            <strong>Penting:</strong> Pastikan Anda sudah mengatur Cron Job di server hosting atau
                            aaPanel Anda agar proses otomatis berjalan.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
