<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-5">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Distribusi Laba Harian</h3>
                    </div>
                    <form action="{{ route('distribusi.harian') }}" method="POST"
                        onsubmit="return confirm('Anda yakin ingin mendistribusikan laba untuk tanggal operasional ini?');">
                        @csrf
                        <div class="card-body">
                            <p>Pilih tanggal operasional untuk menghitung dan mendistribusikan laba bersih harian.
                            </p>
                            <div class="form-group">
                                <label for="tanggal">Tanggal Laporan</label>
                                <input type="date" name="tanggal" id="tanggal-laporan-harian" class="form-control"
                                    value="{{ now()->format('Y-m-d') }}" required>
                            </div>

                            {{-- 👇 AREA PREVIEW BARU DITAMBAHKAN DI SINI 👇 --}}
                            <div id="preview-laba-harian" class="mt-3" style="min-height: 100px;">
                                {{-- Konten preview akan dimuat di sini oleh JavaScript --}}
                            </div>
                            {{-- 👆 BATAS AREA PREVIEW BARU 👆 --}}

                            <small class="text-muted">Perhitungan laba akan didasarkan pada jam tutup buku harian
                                Anda.</small>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-success"><i class="fas fa-calculator mr-1"></i>
                                Hitung & Distribusikan Laba Harian</button>
                        </div>
                    </form>
                </div>

                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">1. Proses Tutup Buku Periodik</h3>
                    </div>
                    <form action="{{ route('distribusi.proses') }}" method="POST"
                        onsubmit="return confirm('Anda yakin ingin menjalankan proses Tutup Buku untuk periode ini? Aksi ini akan mencatat transaksi pengeluaran di buku besar dan tidak dapat dibatalkan.');">
                        @csrf
                        <div class="card-body">
                            <p>Pilih periode untuk menghitung laba yang akan didistribusikan.</p>
                            <div class="form-group">
                                <label for="start_date">Tanggal Awal</label>
                                <input type="date" name="start_date" class="form-control"
                                    value="{{ now()->startOfMonth()->format('Y-m-d') }}" required>
                            </div>
                            <div class="form-group">
                                <label for="end_date">Tanggal Akhir</label>
                                <input type="date" name="end_date" class="form-control"
                                    value="{{ now()->endOfMonth()->format('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-play-circle mr-1"></i>
                                Jalankan Proses Tutup Buku</button>
                        </div>
                    </form>
                </div>

                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">2. Pengaturan Persentase Distribusi</h3>
                    </div>
                    <form action="{{ route('distribusi.setting.store') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <p>Atur persentase pembagian laba. <strong>Total harus 100%.</strong></p>
                            @php
                                $roles = [
                                    'owner' => 'Owner',
                                    'investor' => 'Investor',
                                    'karyawan_bonus' => 'Bonus Karyawan',
                                    'kas_aset' => 'Kas Aset (Pengembangan)',
                                ];
                            @endphp
                            @foreach ($roles as $key => $label)
                                <div class="form-group row">
                                    <label class="col-sm-5 col-form-label">{{ $label }}</label>
                                    <div class="col-sm-7">
                                        <div class="input-group">
                                            <input type="number" step="0.01" name="persentase[{{ $key }}]"
                                                class="form-control persentase-input"
                                                value="{{ $settings[$key]->persentase ?? 0 }}" required>
                                            <div class="input-group-append"><span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            <hr>
                            <div class="form-group row">
                                <label class="col-sm-5 col-form-label font-weight-bold">Total</label>
                                <div class="col-sm-7">
                                    <div class="input-group">
                                        <input type="text" id="total-persen" class="form-control font-weight-bold"
                                            value="0" readonly>
                                        <div class="input-group-append"><span class="input-group-text">%</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-info"><i class="fas fa-save mr-1"></i> Simpan
                                Pengaturan</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">3. Histori & Laporan Distribusi Laba</h3>
                    </div>
                    <div class="card-body">
                        {{-- FORM FILTER BARU --}}
                        <form method="GET" action="{{ route('distribusi.index') }}">
                            <div class="row align-items-end">
                                <div class="col-md-5 form-group">
                                    <label for="start_date_filter">Tanggal Awal</label>
                                    <input type="date" id="start_date_filter" name="start_date" class="form-control"
                                        value="{{ $startDate }}">
                                </div>
                                <div class="col-md-5 form-group">
                                    <label for="end_date_filter">Tanggal Akhir</label>
                                    <input type="date" id="end_date_filter" name="end_date" class="form-control"
                                        value="{{ $endDate }}">
                                </div>
                                <div class="col-md-2 form-group">
                                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                                </div>
                            </div>
                        </form>
                        <hr>

                        {{-- SUMMARY/TOTAL BARU --}}
                        <h5>Summary Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} -
                            {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</h5>
                        <div class="row">
                            <div class="col-12 mb-2">
                                <div class="info-box bg-success">
                                    <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Laba Bersih Didistribusikan</span>
                                        <span class="info-box-number">Rp
                                            {{ number_format($summary->total_laba_bersih ?? 0) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-group">
                                    <li class="list-group-item d-flex justify-content-between"><span>Alokasi
                                            Owner</span> <strong>Rp
                                            {{ number_format($summary->total_alokasi_owner ?? 0) }}</strong></li>
                                    <li class="list-group-item d-flex justify-content-between"><span>Alokasi
                                            Investor</span> <strong>Rp
                                            {{ number_format($summary->total_alokasi_investor ?? 0) }}</strong></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-group">
                                    <li class="list-group-item d-flex justify-content-between"><span>Bonus
                                            Karyawan</span> <strong>Rp
                                            {{ number_format($summary->total_alokasi_karyawan ?? 0) }}</strong></li>
                                    <li class="list-group-item d-flex justify-content-between"><span>Kas Aset</span>
                                        <strong>Rp {{ number_format($summary->total_alokasi_kas_aset ?? 0) }}</strong>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <hr>

                        {{-- TABEL HISTORI YANG DIPERBARUI --}}
                        <h5 class="mt-4">Rincian Histori</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Periode</th>
                                        <th class="text-right">Laba Bersih</th>
                                        <th class="text-right">Owner</th>
                                        <th class="text-right">Investor</th>
                                        <th class="text-right">Bonus</th>
                                        <th class="text-right">Aset</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($histori as $item)
                                        <tr>
                                            <td>
                                                <small>
                                                    {{ \Carbon\Carbon::parse($item->tanggal_mulai)->format('d/m/y') }}
                                                    -
                                                    {{ \Carbon\Carbon::parse($item->tanggal_selesai)->format('d/m/y') }}
                                                    <br>
                                                    <span
                                                        class="text-muted">({{ $item->created_at->format('d/m/y H:i') }})</span>
                                                </small>
                                            </td>
                                            <td class="text-right"><span class="badge badge-success">Rp
                                                    {{ number_format($item->laba_bersih) }}</span></td>
                                            <td class="text-right"><small>Rp
                                                    {{ number_format($item->alokasi_owner) }}</small></td>
                                            <td class="text-right"><small>Rp
                                                    {{ number_format($item->alokasi_investor) }}</small></td>
                                            <td class="text-right"><small>Rp
                                                    {{ number_format($item->alokasi_karyawan) }}</small></td>
                                            <td class="text-right"><small>Rp
                                                    {{ number_format($item->alokasi_kas_aset) }}</small></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4">Tidak ada histori distribusi
                                                pada periode yang dipilih.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer clearfix">
                        {{-- Menambahkan parameter filter ke link paginasi --}}
                        {{ $histori->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>


<script>
    $(function() {
        // Fungsi untuk memformat angka menjadi format Rupiah
        function formatRupiah(angka, denganMinus = false) {
            if (denganMinus) {
                // Untuk menampilkan beban sebagai angka negatif dalam kurung
                return angka > 0 ?
                    `(${new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka)})` :
                    'Rp 0';
            }
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(angka);
        }

        // Fungsi utama untuk mengambil dan menampilkan preview
        function fetchPreview() {
            let tanggal = $('#tanggal-laporan-harian').val();
            let previewContainer = $('#preview-laba-harian');
            let previewUrl = "{{ route('distribusi.previewHarian') }}";

            if (!tanggal) return;

            // Tampilkan loading spinner
            previewContainer.html(`
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm mr-2" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <strong>Menghitung potensi laba...</strong>
                </div>
            `);

            // Lakukan AJAX request
            $.ajax({
                type: 'GET',
                url: previewUrl,
                data: {
                    tanggal: tanggal
                },
                success: function(response) {
                    let content = '<ul class="list-group list-group-flush">';

                    // Tampilkan Laba Kotor
                    content += `<li class="list-group-item d-flex justify-content-between align-items-center p-1">
                                    <strong>Potensi Laba Kotor</strong>
                                    <strong class="text-primary">${formatRupiah(response.laba_kotor)}</strong>
                                </li>`;

                    // Tampilkan Beban-Beban Pengurang
                    if (response.beban) {
                        content +=
                            '<li class="list-group-item p-1"><small>Beban-Beban Pengurang:</small></li>';
                        $.each(response.beban, function(namaBeban, jumlahBeban) {
                            if (jumlahBeban >
                                0) { // Hanya tampilkan beban yang ada nilainya
                                content += `<li class="list-group-item d-flex justify-content-between align-items-center p-1 pl-3">
                                                <small>${namaBeban}</small>
                                                <small class="text-danger">${formatRupiah(jumlahBeban, true)}</small>
                                            </li>`;
                            }
                        });
                    }

                    // Tampilkan Laba Bersih
                    let labaBersihClass = response.laba_bersih > 0 ? 'text-success' : 'text-danger';
                    content += `<li class="list-group-item d-flex justify-content-between align-items-center p-1 mt-2">
                                    <strong>Potensi Laba Bersih</strong>
                                    <strong class="${labaBersihClass}">${formatRupiah(response.laba_bersih)}</strong>
                                </li>`;


                    // Tampilkan Alokasi jika ada laba
                    if (response.status === 'success') {
                        content +=
                            '<li class="list-group-item p-1 mt-2"><small>Estimasi Alokasi:</small></li>';
                        $.each(response.potensi_alokasi, function(pos, jumlah) {
                            content += `<li class="list-group-item d-flex justify-content-between align-items-center p-1 pl-3">
                                            <small>${pos}</small>
                                            <span class="badge badge-info">${formatRupiah(jumlah)}</span>
                                        </li>`;
                        });
                    } else if (response.status === 'info') {
                        content +=
                            `<li class="list-group-item p-1 mt-2 text-center"><div class="alert alert-warning p-2 m-0">${response.message}</div></li>`;
                    }

                    content += '</ul>';
                    previewContainer.html(content);
                },
                error: function(xhr) {
                    let errorMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON
                        .message : 'Gagal memuat preview.';
                    let content = `<div class="alert alert-danger p-2">${errorMsg}</div>`;
                    previewContainer.html(content);
                }
            });
        }

        // Panggil fungsi saat input tanggal berubah
        $('#tanggal-laporan-harian').on('change', fetchPreview);

        // Panggil fungsi saat halaman pertama kali dimuat untuk tanggal default
        fetchPreview();
    });
</script>


<script>
    $(function() {
        function calculateTotal() {
            let total = 0;
            $('.persentase-input').each(function() {
                let value = parseFloat($(this).val()) || 0;
                total += value;
            });

            let totalEl = $('#total-persen');
            totalEl.val(total.toFixed(2));

            totalEl.removeClass('is-valid is-invalid');
            if (Math.round(total * 100) / 100 === 100) {
                totalEl.addClass('is-valid');
            } else {
                totalEl.addClass('is-invalid');
            }
        }

        calculateTotal();
        $('.persentase-input').on('input', calculateTotal);
    });
</script>
