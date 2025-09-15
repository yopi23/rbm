<div class="container-fluid pt-3">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Laporan Harian (Berdasarkan Jam Tutup Buku)</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('financial.index') }}" method="GET" class="form-inline">
                <div class="form-group mr-3">
                    <label for="date" class="mr-2">Pilih Tanggal Laporan:</label>
                    <input type="date" name="date" id="date" class="form-control"
                        value="{{ $filterDate }}">
                </div>
                <button type="submit" class="btn btn-primary">Tampilkan</button>
            </form>
            <div class="mt-2">
                <small class="text-muted">
                    Jam tutup buku saat ini: <strong>{{ $closingTimeFormatted }}</strong>.
                    <a href="{{ route('settings.index') }}">Ubah Pengaturan</a>
                </small>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-dark">
            <h3 class="card-title">Total Estimasi Kekayaan Perusahaan</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3>Rp {{ number_format($kekayaanStats['saldoKas'], 0, ',', '.') }}</h3>
                            <p>Modal Uang (Saldo Kas)</p>
                        </div>
                        <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                    </div>
                </div>
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-purple">
                        <div class="inner">
                            <h3>Rp {{ number_format($kekayaanStats['totalNilaiBarang'], 0, ',', '.') }}</h3>
                            <p>Modal Barang (Nilai Stok)</p>
                        </div>
                        <div class="icon"><i class="fas fa-boxes"></i></div>
                    </div>
                </div>
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-secondary">
                        <div class="inner">
                            <h3>Rp {{ number_format($kekayaanStats['totalNilaiAset'], 0, ',', '.') }}</h3>
                            <p>Modal Aset Tetap</p>
                        </div>
                        <div class="icon"><i class="fas fa-building"></i></div>
                    </div>
                </div>
            </div>
            <div class="alert alert-success text-center">
                <h4>Total Kekayaan: <strong>Rp
                        {{ number_format($kekayaanStats['totalKekayaan'], 0, ',', '.') }}</strong></h4>
            </div>
            <small class="text-muted">
                *Nilai kekayaan adalah estimasi berdasarkan: Saldo kas terakhir + Total harga beli semua stok barang +
                Total harga perolehan semua aset tetap.
            </small>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-sm-6 col-md-4">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-arrow-down"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pemasukan Hari Ini</span>
                    <span class="info-box-number">Rp
                        {{ number_format($stats['totalIncome'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <div class="info-box bg-danger">
                <span class="info-box-icon"><i class="fas fa-arrow-up"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pengeluaran Hari Ini</span>
                    <span class="info-box-number">Rp
                        {{ number_format($stats['totalExpense'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <div class="info-box {{ $stats['netProfit'] >= 0 ? 'bg-info' : 'bg-warning' }}">
                <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Profit Hari Ini</span>
                    <span class="info-box-number">Rp
                        {{ number_format($stats['netProfit'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('modal.index') }}" class="btn btn-dark"><i class="fas fa-landmark mr-1"></i>
                Manajemen Modal</a>
            <a href="{{ route('hutang.index') }}" class="btn btn-danger"><i
                    class="fas fa-file-invoice-dollar mr-1"></i>
                Manajemen Hutang</a>
            <a href="{{ route('distribusi.index') }}" class="btn btn-info"><i class="fas fa-chart-pie mr-1"></i>
                Distribusi Laba</a>
            <a href="{{ route('financial.transactions') }}" class="btn btn-primary"><i class="fas fa-book mr-1"></i>
                Lihat Buku Besar</a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Grafik Keuangan Bulanan Tahun {{ $year }}</h3>
                </div>
                <div class="card-body"><canvas id="financialChart" height="300"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Transaksi Terbaru (Laporan tgl:
                        {{ \Carbon\Carbon::parse($filterDate)->format('d/m/Y') }})</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Deskripsi</th>
                                <th>Debit</th>
                                <th>Kredit</th>
                                <th>Sumber</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($latestTransactions as $tx)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($tx->tanggal)->format('H:i') }}</td>
                                    <td>{{ Str::limit($tx->deskripsi, 50) }}</td>
                                    <td>
                                        @if ($tx->debit > 0)
                                            <span class="text-success">Rp
                                                {{ number_format($tx->debit, 0, ',', '.') }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($tx->kredit > 0)
                                            <span class="text-danger">Rp
                                                {{ number_format($tx->kredit, 0, ',', '.') }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if (str_contains($tx->sourceable_type, 'TransaksiModal'))
                                            <span class="badge badge-dark">Modal</span>
                                        @elseif (str_contains($tx->sourceable_type, 'Sevices') || str_contains($tx->sourceable_type, 'Pengambilan'))
                                            <span class="badge badge-primary">Service</span>
                                        @elseif (str_contains($tx->sourceable_type, 'Penjualan'))
                                            <span class="badge badge-info">Penjualan</span>
                                        @elseif (str_contains($tx->sourceable_type, 'PengeluaranOperasional'))
                                            <span class="badge badge-warning">Operasional</span>
                                        @elseif (str_contains($tx->sourceable_type, 'PengeluaranToko'))
                                            <span class="badge badge-secondary">Toko</span>
                                        @elseif (str_contains($tx->sourceable_type, 'DistribusiLaba'))
                                            <span class="badge badge-purple">Distribusi</span>
                                        @else
                                            <span class="badge badge-light">Manual</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada transaksi untuk hari
                                        operasional ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                 <div class="card-footer text-center">
                <a href="{{ route('financial.transactions') }}" class="uppercase">Lihat Semua Transaksi</a>
            </div>
            </div>
        </div>
    </div>
</div>



{{-- @section('scripts') --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(function() {
        var ctx = document.getElementById('financialChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($monthlyData['labels']) !!},
                datasets: [{
                        label: 'Pemasukan',
                        backgroundColor: 'rgba(40, 167, 69, 0.7)',
                        data: {!! json_encode($monthlyData['income']) !!}
                    },
                    {
                        label: 'Pengeluaran',
                        backgroundColor: 'rgba(220, 53, 69, 0.7)',
                        data: {!! json_encode($monthlyData['expense']) !!}
                    },
                    {
                        label: 'Profit',
                        type: 'line',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        fill: false,
                        data: {!! json_encode($monthlyData['profit']) !!}
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
{{-- @endsection --}}
