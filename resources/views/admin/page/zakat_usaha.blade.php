{{-- resources/views/admin/page/zakat_usaha.blade.php --}}

<div class="container-fluid">
    <!-- Form Input Manual -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calculator"></i> Input Data Manual</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('zakat_usaha') }}">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="kas">Total Kas/Uang Tunai (Rp)</label>
                                    <input type="number" class="form-control" id="kas" name="kas"
                                        value="{{ request('kas', 0) }}" min="0">
                                    <small class="form-text text-muted">Masukkan jumlah kas yang dimiliki saat
                                        ini</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="harga_emas">Harga Emas per Gram (Rp)</label>
                                    <input type="number" class="form-control" id="harga_emas" name="harga_emas"
                                        value="{{ request('harga_emas', 1000000) }}" min="0">
                                    <small class="form-text text-muted">Harga emas saat ini untuk menghitung
                                        nisab</small>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sync-alt"></i> Hitung Ulang Zakat
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Hasil Perhitungan Zakat -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header {{ $data_zakat['wajib_zakat'] ? 'bg-success' : 'bg-warning' }} text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-mosque"></i> Hasil Perhitungan Zakat Usaha
                        @if ($data_zakat['wajib_zakat'])
                            <span class="badge badge-light ml-2">WAJIB ZAKAT</span>
                        @else
                            <span class="badge badge-light ml-2">BELUM WAJIB</span>
                        @endif
                    </h4>
                </div>
                <div class="card-body">
                    <!-- Ringkasan Zakat -->
                    <div class="row mb-4">
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="card border-primary">
                                <div class="card-body text-center">
                                    <h5 class="card-title text-primary">Total Aset Kena Zakat</h5>
                                    <h3 class="text-primary">Rp {{ number_format($data_zakat['total_aset_zakat']) }}
                                    </h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <h5 class="card-title text-info">Nisab (85 gram emas)</h5>
                                    <h3 class="text-info">Rp {{ number_format($data_zakat['nisab']) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="card {{ $data_zakat['wajib_zakat'] ? 'border-success' : 'border-secondary' }}">
                                <div class="card-body text-center">
                                    <h5
                                        class="card-title {{ $data_zakat['wajib_zakat'] ? 'text-success' : 'text-secondary' }}">
                                        Jumlah Zakat ({{ $data_zakat['persentase_zakat'] }}%)
                                    </h5>
                                    <h3 class="{{ $data_zakat['wajib_zakat'] ? 'text-success' : 'text-secondary' }}">
                                        Rp {{ number_format($data_zakat['jumlah_zakat']) }}
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detail Perhitungan -->
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-plus-circle text-success"></i> Aset (+)</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td>Nilai Stok Barang Dagangan</td>
                                    <td class="text-right">Rp {{ number_format($data_zakat['total_stok_barang']) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>Piutang Dagang</td>
                                    <td class="text-right">
                                        {{-- Rp {{ number_format($data_zakat['total_piutang']) }} --}}
                                        0
                                    </td>
                                </tr>
                                <tr>
                                    <td>Kas/Uang Tunai</td>
                                    <td class="text-right">Rp {{ number_format($data_zakat['total_kas']) }}</td>
                                </tr>
                                <tr class="table-success font-weight-bold">
                                    <td>Total Aset</td>
                                    <td class="text-right">Rp
                                        {{ number_format(
                                            $data_zakat['total_stok_barang'] +
                                                //  +$data_zakat['total_piutang']
                                                $data_zakat['total_kas'],
                                        ) }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-minus-circle text-danger"></i> Hutang (-)</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td>Hutang Dagang</td>
                                    <td class="text-right">Rp {{ number_format($data_zakat['total_hutang']) }}</td>
                                </tr>
                                <tr class="table-danger font-weight-bold">
                                    <td>Total Hutang</td>
                                    <td class="text-right">Rp {{ number_format($data_zakat['total_hutang']) }}</td>
                                </tr>
                            </table>

                            <div
                                class="mt-3 p-3 {{ $data_zakat['wajib_zakat'] ? 'bg-success' : 'bg-warning' }} text-white rounded">
                                <h6>Status Zakat:</h6>
                                @if ($data_zakat['wajib_zakat'])
                                    <p class="mb-0">✅ <strong>WAJIB ZAKAT</strong><br>
                                        Aset Anda (Rp {{ number_format($data_zakat['total_aset_zakat']) }}) sudah
                                        mencapai nisab</p>
                                @else
                                    <p class="mb-0">⚠️ <strong>BELUM WAJIB ZAKAT</strong><br>
                                        Aset Anda masih di bawah nisab<br>
                                        Kekurangan: Rp
                                        {{ number_format($data_zakat['nisab'] - $data_zakat['total_aset_zakat']) }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Stok per Kategori -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-boxes"></i> Detail Nilai Stok per Kategori</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>No</th>
                                    <th>Kategori</th>
                                    <th>Total Qty</th>
                                    <th>Total Nilai (Harga Jual)</th>
                                    <th>Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $no = 1; @endphp
                                @foreach ($stok_per_kategori as $kategori)
                                    <tr>
                                        <td>{{ $no++ }}</td>
                                        <td>{{ $kategori->nama_kategori }}</td>
                                        <td>{{ number_format($kategori->total_qty) }} pcs</td>
                                        <td>Rp {{ number_format($kategori->total_nilai) }}</td>
                                        <td>
                                            @if ($data_zakat['total_stok_barang'] > 0)
                                                {{ number_format(($kategori->total_nilai / $data_zakat['total_stok_barang']) * 100, 1) }}%
                                            @else
                                                0%
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="table-primary font-weight-bold">
                                    <td colspan="2">TOTAL</td>
                                    <td>{{ number_format($stok_per_kategori->sum('total_qty')) }} pcs</td>
                                    <td>Rp {{ number_format($stok_per_kategori->sum('total_nilai')) }}</td>
                                    <td>100%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Informasi Tambahan -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Informasi Penting tentang Zakat Usaha</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Syarat Wajib Zakat Usaha:</h6>
                            <ul class="small">
                                <li>Modal usaha mencapai nisab (85 gram emas)</li>
                                <li>Telah berlalu 1 tahun hijriah (haul)</li>
                                <li>Modal adalah milik penuh (bukan hutang)</li>
                                <li>Digunakan untuk tujuan perdagangan</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Cara Pembayaran Zakat:</h6>
                            <ul class="small">
                                <li>Zakat sebesar 2,5% dari total aset bersih</li>
                                <li>Dapat dibayar dengan uang atau barang</li>
                                <li>Disalurkan kepada 8 golongan yang berhak</li>
                                <li>Sebaiknya dibayar segera setelah haul</li>
                            </ul>
                        </div>
                    </div>
                    <div class="alert alert-warning mt-3">
                        <small><i class="fas fa-exclamation-triangle"></i>
                            <strong>Catatan:</strong> Perhitungan ini menggunakan harga jual untuk stok barang.
                            Pastikan data kas dan harga emas sudah sesuai dengan kondisi terkini.
                            Untuk kepastian lebih lanjut, konsultasikan dengan ustadz atau lembaga zakat.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
