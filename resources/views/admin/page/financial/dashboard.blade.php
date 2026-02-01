<div class="container-fluid pt-3">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Laporan Harian & Overview Keuangan</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('financial.index') }}" method="GET" class="form-inline mb-3">
                <div class="form-group mr-3">
                    <label for="date" class="mr-2">Pilih Tanggal:</label>
                    <input type="date" name="date" id="date" class="form-control"
                        value="{{ $filterDate }}">
                </div>
                <button type="submit" class="btn btn-primary">Tampilkan</button>
            </form>
            <div>
                <small class="text-muted">
                    Data di bawah ini berdasarkan perhitungan akrual (pendapatan & beban diakui saat terjadi) dan posisi keuangan per tanggal {{ \Carbon\Carbon::parse($filterDate)->format('d/m/Y') }}.
                    Jam tutup buku: <strong>{{ $closingTimeFormatted }}</strong>.
                </small>
            </div>
        </div>
    </div>

    {{-- STATS OVERVIEW --}}
    <h4 class="mb-3">Financial Overview</h4>
    <div class="row">
        {{-- 1. Pendapatan Total --}}
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>Rp {{ number_format($stats['totalRevenue'], 0, ',', '.') }}</h3>
                    <p>Pendapatan Total (Revenue)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <a href="#" class="small-box-footer">Omset Penjualan + Servis <i class="fas fa-info-circle"></i></a>
            </div>
        </div>

        {{-- 2. Nilai Inventory --}}
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>Rp {{ number_format($stats['inventoryValue'], 0, ',', '.') }}</h3>
                    <p>Nilai Inventory (Aset)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <a href="{{ route('stok_sparepart') }}" class="small-box-footer">Lihat Stok <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>

        {{-- 3. Saldo Kas --}}
        <div class="col-lg-3 col-6">
            <div class="small-box {{ $stats['saldoKas'] >= 0 ? 'bg-info' : 'bg-danger' }}">
                <div class="inner">
                    <h3>Rp {{ number_format($stats['saldoKas'], 0, ',', '.') }}</h3>
                    <p>Saldo Kas Tersedia</p>
                </div>
                <div class="icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <a href="{{ route('financial.transactions') }}" class="small-box-footer">Lihat Buku Besar <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>

        {{-- 4. Total Beban --}}
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>Rp {{ number_format($stats['totalExpenseDisplay'], 0, ',', '.') }}</h3>
                    <p>Total Beban</p>
                </div>
                <div class="icon">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <span class="small-box-footer">Ops: {{ number_format($stats['operatingExpenses'], 0, ',', '.') }} + Susut: {{ number_format($stats['depreciation'], 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- 5. Modal Disetor --}}
        <div class="col-lg-4 col-6">
            <div class="small-box bg-teal">
                <div class="inner">
                    <h3>Rp {{ number_format($stats['paidInCapital'], 0, ',', '.') }}</h3>
                    <p>Modal Disetor (Bersih)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-landmark"></i>
                </div>
                <a href="{{ route('modal.index') }}" class="small-box-footer">Lihat Riwayat Modal <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>

        {{-- 6. Laba Bersih --}}
        <div class="col-lg-4 col-6">
            <div class="small-box {{ $stats['netProfit'] >= 0 ? 'bg-primary' : 'bg-orange' }}">
                <div class="inner">
                    <h3>Rp {{ number_format($stats['netProfit'], 0, ',', '.') }}</h3>
                    <p>Laba Bersih Operasional</p>
                </div>
                <div class="icon">
                    <i class="fas fa-balance-scale"></i>
                </div>
                <span class="small-box-footer">Revenue - Total Beban</span>
            </div>
        </div>

        {{-- 7. Total Asset --}}
        <div class="col-lg-4 col-12">
            <div class="small-box bg-indigo">
                <div class="inner">
                    <h3>Rp {{ number_format($stats['totalAsset'], 0, ',', '.') }}</h3>
                    <p>Total Aset</p>
                </div>
                <div class="icon">
                    <i class="fas fa-building"></i>
                </div>
                <span class="small-box-footer">Kas + Inventory + Aset Tetap ({{ number_format($stats['asetTetap'], 0, ',', '.') }})</span>
            </div>
        </div>
    </div>

    <div class="row mb-3 mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Menu Cepat Keuangan</h3>
                </div>
                <div class="card-body">
                    <a href="{{ route('modal.index') }}" class="btn btn-dark m-1"><i class="fas fa-landmark mr-1"></i>
                        Manajemen Modal</a>
                    <a href="{{ route('hutang.index') }}" class="btn btn-danger m-1"><i
                            class="fas fa-file-invoice-dollar mr-1"></i>
                        Manajemen Hutang</a>
                    <a href="{{ route('distribusi.index') }}" class="btn btn-info m-1"><i
                            class="fas fa-chart-pie mr-1"></i>
                        Distribusi Laba</a>
                    <a href="{{ route('financial.transactions') }}" class="btn btn-primary m-1"><i
                            class="fas fa-book mr-1"></i>
                        Lihat Buku Besar</a>
                    <a href="{{ route('financial.developmentReport') }}" class="btn btn-success m-1"><i
                            class="fas fa-chart-bar mr-1"></i>
                        Laporan Perkembangan</a>

                    <a href="{{ route('financial.cashFlowReport') }}" class="btn btn-info m-1"><i
                            class="fas fa-exchange-alt mr-1"></i>
                        Laporan Arus Kas</a>
                    <a href="{{ route('financial.balanceSheetReport') }}" class="btn btn-dark m-1"><i
                            class="fas fa-balance-scale mr-1"></i>
                        Laporan Neraca</a>
                </div>
            </div>
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
