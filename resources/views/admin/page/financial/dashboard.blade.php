@extends('admin.main')

@section('title', $page)

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>{{ $page }}</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item active">{{ $page }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <!-- Filter Periode -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Filter Periode</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('financial.index') }}" method="GET" class="form-inline">
                            <div class="form-group mr-3">
                                <label for="year" class="mr-2">Tahun:</label>
                                <select name="year" id="year" class="form-control">
                                    @for ($i = date('Y'); $i >= date('Y') - 5; $i--)
                                        <option value="{{ $i }}" {{ $i == $year ? 'selected' : '' }}>
                                            {{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="form-group mr-3">
                                <label for="month" class="mr-2">Bulan:</label>
                                <select name="month" id="month" class="form-control">
                                    <option value="">Semua Bulan</option>
                                    @foreach (['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'] as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ isset($month) && $month == $value ? 'selected' : '' }}>{{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </form>
                    </div>
                </div>

                <!-- Info Boxes -->
                <div class="row">
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="info-box">
                            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-money-bill-wave"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Pemasukan</span>
                                <span class="info-box-number">
                                    Rp {{ number_format($stats['totalIncome'], 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="info-box">
                            <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-shopping-cart"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Pengeluaran</span>
                                <span class="info-box-number">
                                    Rp {{ number_format($stats['totalExpense'], 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="info-box">
                            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-chart-line"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Profit Bersih</span>
                                <span
                                    class="info-box-number {{ $stats['netProfit'] < 0 ? 'text-danger' : 'text-success' }}">
                                    Rp {{ number_format($stats['netProfit'], 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sumber Pendapatan Boxes -->
                <div class="row">
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3>Rp {{ number_format($stats['serviceIncome'], 0, ',', '.') }}</h3>
                                <p>Service</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-tools"></i>
                            </div>
                            <a href="{{ route('financial.transactions', ['type' => 'Pemasukan', 'source' => 'service']) }}"
                                class="small-box-footer">
                                Detail <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3>Rp {{ number_format($stats['salesIncome'], 0, ',', '.') }}</h3>
                                <p>Penjualan</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                            <a href="{{ route('financial.transactions', ['type' => 'Pemasukan', 'source' => 'sales']) }}"
                                class="small-box-footer">
                                Detail <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3>Rp {{ number_format($stats['operationalExpense'], 0, ',', '.') }}</h3>
                                <p>Operasional</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                            <a href="{{ route('financial.transactions', ['type' => 'Pengeluaran', 'source' => 'operational']) }}"
                                class="small-box-footer">
                                Detail <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3>Rp {{ number_format($stats['storeExpense'], 0, ',', '.') }}</h3>
                                <p>Pengeluaran Toko</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-store"></i>
                            </div>
                            <a href="{{ route('financial.transactions', ['type' => 'Pengeluaran', 'source' => 'store']) }}"
                                class="small-box-footer">
                                Detail <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="btn-group float-right">
                            <a href="{{ route('financial.create') }}" class="btn btn-success">
                                <i class="fas fa-plus-circle mr-1"></i> Tambah Transaksi
                            </a>
                            <a href="{{ route('financial.transactions') }}" class="btn btn-primary">
                                <i class="fas fa-list mr-1"></i> Lihat Semua Transaksi
                            </a>
                            <a href="{{ route('financial.reports') }}" class="btn btn-warning">
                                <i class="fas fa-file-invoice mr-1"></i> Laporan Keuangan
                            </a>
                            <a href="{{ route('financial.categories') }}" class="btn btn-info">
                                <i class="fas fa-tags mr-1"></i> Kelola Kategori
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Monthly Chart -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Grafik Keuangan Bulanan {{ $year }}</h3>
                            </div>
                            <div class="card-body">
                                <canvas id="financialChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Income by Category -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Pemasukan per Kategori</h3>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="position: relative; height:300px;">
                                    <canvas id="incomePieChart"></canvas>
                                </div>
                                <div class="mt-3">
                                    @if (count($stats['topIncomeCategories']) > 0)
                                        <ul class="list-group">
                                            @foreach ($stats['topIncomeCategories'] as $category)
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center">
                                                    {{ $category->kategori }}
                                                    <span class="badge badge-success badge-pill">Rp.
                                                        {{ number_format($category->total, 0, ',', '.') }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="text-center">Tidak ada data pemasukan</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Expense by Category -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Pengeluaran per Kategori</h3>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="position: relative; height:300px;">
                                    <canvas id="expensePieChart"></canvas>
                                </div>
                                <div class="mt-3">
                                    @if (count($stats['topExpenseCategories']) > 0)
                                        <ul class="list-group">
                                            @foreach ($stats['topExpenseCategories'] as $category)
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center">
                                                    {{ $category->kategori }}
                                                    <span class="badge badge-danger badge-pill">Rp.
                                                        {{ number_format($category->total, 0, ',', '.') }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <p class="text-center">Tidak ada data pengeluaran</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Transaksi Terbaru</h3>
                            </div>
                            <div class="card-body table-responsive p-0">
                                <table class="table table-hover text-nowrap">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Kode</th>
                                            <th>Kategori</th>
                                            <th>Deskripsi</th>
                                            <th>Tipe</th>
                                            <th>Jumlah</th>
                                            <th>Metode</th>
                                            <th>Sumber</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($latestTransactions as $transaction)
                                            <tr>
                                                <td>{{ date('d/m/Y', strtotime($transaction->tanggal)) }}</td>
                                                <td>{{ $transaction->kode_transaksi }}</td>
                                                <td>{{ $transaction->kategori }}</td>
                                                <td>{{ $transaction->deskripsi }}</td>
                                                <td>
                                                    @if ($transaction->tipe_transaksi == 'Pemasukan')
                                                        <span class="badge badge-success">Pemasukan</span>
                                                    @else
                                                        <span class="badge badge-danger">Pengeluaran</span>
                                                    @endif
                                                </td>
                                                <td>Rp {{ number_format($transaction->jumlah, 0, ',', '.') }}</td>
                                                <td>{{ $transaction->metode_pembayaran }}</td>
                                                <td>
                                                    @if ($transaction->kode_referensi)
                                                        @if (strpos($transaction->kategori, 'DP Service') === 0 || strpos($transaction->kategori, 'Pengambilan Service') === 0)
                                                            <span class="badge badge-primary">Service</span>
                                                        @elseif(strpos($transaction->kategori, 'Penjualan') === 0)
                                                            <span class="badge badge-info">Penjualan</span>
                                                        @elseif(strpos($transaction->kategori, 'Operasional:') === 0)
                                                            <span class="badge badge-danger">Operasional</span>
                                                        @elseif(strpos($transaction->kategori, 'Pengeluaran Toko') === 0)
                                                            <span class="badge badge-warning">Toko</span>
                                                        @elseif(strpos($transaction->kategori, 'Laci:') === 0)
                                                            <span class="badge badge-secondary">Laci</span>
                                                        @else
                                                            <span class="badge badge-secondary">Sistem</span>
                                                        @endif
                                                    @else
                                                        <span class="badge badge-dark">Manual</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center">Tidak ada transaksi</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer">
                                <a href="{{ route('financial.transactions') }}" class="btn btn-sm btn-primary">Lihat
                                    Semua Transaksi</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(function() {
            // Monthly Chart
            var ctx = document.getElementById('financialChart').getContext('2d');
            var financialChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($monthlyData['labels']) !!},
                    datasets: [{
                            label: 'Pemasukan',
                            backgroundColor: 'rgba(40, 167, 69, 0.7)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 1,
                            data: {!! json_encode($monthlyData['income']) !!}
                        },
                        {
                            label: 'Pengeluaran',
                            backgroundColor: 'rgba(220, 53, 69, 0.7)',
                            borderColor: 'rgba(220, 53, 69, 1)',
                            borderWidth: 1,
                            data: {!! json_encode($monthlyData['expense']) !!}
                        },
                        {
                            label: 'Profit',
                            type: 'line',
                            backgroundColor: 'rgba(0, 123, 255, 0.2)',
                            borderColor: 'rgba(0, 123, 255, 1)',
                            borderWidth: 2,
                            fill: false,
                            data: {!! json_encode($monthlyData['profit']) !!}
                        }
                    ]
                },
                options: {
                    responsive: true,
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

            // Income Pie Chart
            @if (count($stats['topIncomeCategories']) > 0)
                var incomeData = {
                    labels: [
                        @foreach ($stats['topIncomeCategories'] as $category)
                            '{{ $category->kategori }}',
                        @endforeach
                    ],
                    datasets: [{
                        data: [
                            @foreach ($stats['topIncomeCategories'] as $category)
                                {{ $category->total }},
                            @endforeach
                        ],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.7)',
                            'rgba(23, 162, 184, 0.7)',
                            'rgba(0, 123, 255, 0.7)',
                            'rgba(108, 117, 125, 0.7)',
                            'rgba(255, 193, 7, 0.7)'
                        ],
                        borderColor: [
                            'rgba(40, 167, 69, 1)',
                            'rgba(23, 162, 184, 1)',
                            'rgba(0, 123, 255, 1)',
                            'rgba(108, 117, 125, 1)',
                            'rgba(255, 193, 7, 1)'
                        ],
                        borderWidth: 1
                    }]
                };

                var incomePieChart = new Chart(document.getElementById('incomePieChart'), {
                    type: 'pie',
                    data: incomeData,
                    options: {
                        responsive: true,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': Rp ' + context.raw.toString().replace(
                                            /\B(?=(\d{3})+(?!\d))/g, ".");
                                    }
                                }
                            }
                        }
                    }
                });
            @endif

            // Expense Pie Chart
            @if (count($stats['topExpenseCategories']) > 0)
                var expenseData = {
                    labels: [
                        @foreach ($stats['topExpenseCategories'] as $category)
                            '{{ $category->kategori }}',
                        @endforeach
                    ],
                    datasets: [{
                        data: [
                            @foreach ($stats['topExpenseCategories'] as $category)
                                {{ $category->total }},
                            @endforeach
                        ],
                        backgroundColor: [
                            'rgba(220, 53, 69, 0.7)',
                            'rgba(253, 126, 20, 0.7)',
                            'rgba(255, 193, 7, 0.7)',
                            'rgba(108, 117, 125, 0.7)',
                            'rgba(52, 58, 64, 0.7)'
                        ],
                        borderColor: [
                            'rgba(220, 53, 69, 1)',
                            'rgba(253, 126, 20, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(108, 117, 125, 1)',
                            'rgba(52, 58, 64, 1)'
                        ],
                        borderWidth: 1
                    }]
                };

                var expensePieChart = new Chart(document.getElementById('expensePieChart'), {
                    type: 'pie',
                    data: expenseData,
                    options: {
                        responsive: true,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': Rp ' + context.raw.toString().replace(
                                            /\B(?=(\d{3})+(?!\d))/g, ".");
                                    }
                                }
                            }
                        }
                    }
                });
            @endif
        });
    </script>
@endsection
