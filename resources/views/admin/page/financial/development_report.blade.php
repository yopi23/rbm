{{-- resources/views/admin/page/financial/development_report.blade.php --}}
<div class="container-fluid pt-3">
    {{-- Filter Card (Tidak berubah) --}}
    <div class="card card-default">
        <div class="card-header">
            <h3 class="card-title">Filter Laporan</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('financial.developmentReport') }}" method="GET">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tahun</label>
                            <select name="year" class="form-control">
                                @foreach ($availableYears as $y)
                                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter mr-1"></i>
                                Tampilkan</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Chart Card (Tidak berubah) --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Grafik Perkembangan Kekayaan Tahun {{ $year }}</h3>
        </div>
        <div class="card-body">
            <div class="chart-container" style="position: relative; height:350px;">
                <canvas id="developmentChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Tabel Laporan Card (INI YANG DIPERBARUI) --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Tabel Laporan Perkembangan Kekayaan Tahun {{ $year }}</h3>
            <div class="card-tools">
                <a href="{{ route('financial.development.print', ['year' => $year]) }}" target="_blank"
                    class="btn btn-warning btn-sm">
                    <i class="fas fa-print mr-1"></i> Cetak / Simpan PDF
                </a>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th rowspan="2" class="text-center align-middle">Bulan</th>
                        <th colspan="4" class="text-center bg-lightblue">Komposisi Kekayaan (Snapshot Akhir Bulan)</th>
                        {{-- Judul Kolom Baru --}}
                        <th colspan="2" class="text-center" style="background-color: #ffe8cc;">Alur Konversi Aset (Bulanan)</th>
                        <th rowspan="2" class="text-center align-middle bg-olive">Profit Bersih (Bulanan)</th>
                        <th rowspan="2" class="text-center align-middle bg-purple">Perubahan Kekayaan (Bulanan)</th>
                    </tr>
                    <tr>
                        <th class="text-center bg-primary">Modal Uang (Kas)</th>
                        <th class="text-center bg-info">Modal Barang (Stok)</th>
                        <th class="text-center bg-secondary">Modal Aset Tetap</th>
                        <th class="text-center bg-dark">Total Kekayaan</th>
                        {{-- Sub-Judul Kolom Baru --}}
                        <th class="text-center" style="background-color: #fff2e6;"><i class="fas fa-shopping-cart text-danger"></i> Uang ➝ Barang</th>
                        <th class="text-center" style="background-color: #fff2e6;"><i class="fas fa-cash-register text-success"></i> Barang ➝ Uang</th>
                    </tr>
                </thead>
                <tbody>
                    @php $previousWealth = 0; @endphp
                    @forelse ($developmentData['table'] as $month => $data)
                        <tr>
                            <td><strong>{{ $data['monthName'] }}</strong></td>
                            <td class="text-right">Rp {{ number_format($data['saldoKas'], 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($data['totalNilaiBarang'], 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($data['totalNilaiAset'], 0, ',', '.') }}</td>
                            <td class="text-right font-weight-bold">Rp {{ number_format($data['totalKekayaan'], 0, ',', '.') }}</td>

                            {{-- Data Kolom Baru --}}
                            <td class="text-right text-danger">Rp {{ number_format($data['cashToGoods'], 0, ',', '.') }}</td>
                            <td class="text-right text-success">Rp {{ number_format($data['goodsToCash'], 0, ',', '.') }}</td>

                            <td class="text-right {{ $data['netProfit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                Rp {{ number_format($data['netProfit'], 0, ',', '.') }}
                            </td>
                            <td class="text-right">
                                @php
                                    $wealthChange = $previousWealth > 0 ? $data['totalKekayaan'] - $previousWealth : 0;
                                @endphp
                                <span class="{{ $wealthChange >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $wealthChange >= 0 ? '▲' : '▼' }} Rp {{ number_format(abs($wealthChange), 0, ',', '.') }}
                                </span>
                            </td>
                        </tr>
                        @php $previousWealth = $data['totalKekayaan']; @endphp
                    @empty
                        <tr>
                            {{-- Sesuaikan colspan menjadi 9 --}}
                            <td colspan="9" class="text-center">Belum ada data untuk tahun ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <small class="text-muted">
                <strong>Catatan:</strong>
                <ul>
                    <li><strong>Komposisi Kekayaan:</strong> Potret nilai aset Anda pada akhir setiap bulan.</li>
                    <li><strong>Uang ➝ Barang:</strong> Total uang yang dikeluarkan untuk membeli stok (pembelian) dalam sebulan.</li>
                    <li><strong>Barang ➝ Uang:</strong> Total uang yang diterima dari penjualan barang/jasa dalam sebulan.</li>
                    <li><strong>Perubahan Kekayaan:</strong> Pertumbuhan total aset dari bulan sebelumnya.</li>
                </ul>
            </small>
        </div>
    </div>
</div>

{{-- Bagian Script Chart (Tidak berubah) --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(function() {
        var ctx = document.getElementById('developmentChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($developmentData['chart']['labels']) !!},
                datasets: [{
                        label: 'Total Kekayaan',
                        borderColor: 'rgba(52, 58, 64, 1)',
                        backgroundColor: 'rgba(52, 58, 64, 0.1)',
                        data: {!! json_encode($developmentData['chart']['totalKekayaan']) !!},
                        fill: true,
                        tension: 0.1
                    },
                    {
                        label: 'Modal Uang (Kas)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        data: {!! json_encode($developmentData['chart']['saldoKas']) !!},
                    },
                    {
                        label: 'Modal Barang (Stok)',
                        borderColor: 'rgba(23, 162, 184, 1)',
                        data: {!! json_encode($developmentData['chart']['nilaiBarang']) !!},
                    },
                    {
                        label: 'Modal Aset Tetap',
                        borderColor: 'rgba(108, 117, 125, 1)',
                        data: {!! json_encode($developmentData['chart']['nilaiAset']) !!},
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g,
                                    ".");
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': Rp ' + context.raw.toString()
                                    .replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                            }
                        }
                    }
                }
            }
        });
    });
</script>
