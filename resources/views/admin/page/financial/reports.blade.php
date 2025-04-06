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
                            <li class="breadcrumb-item"><a href="{{ route('financial.index') }}">Manajemen Keuangan</a></li>
                            <li class="breadcrumb-item active">{{ $page }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <!-- Filter Card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Parameter Laporan</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('financial.reports') }}" method="GET">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="start_date">Tanggal Awal</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date"
                                            value="{{ $startDate }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="end_date">Tanggal Akhir</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date"
                                            value="{{ $endDate }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="report_type">Jenis Laporan</label>
                                        <select name="report_type" id="report_type" class="form-control">
                                            <option value="summary" {{ $reportType == 'summary' ? 'selected' : '' }}>
                                                Ringkasan</option>
                                            <option value="detail" {{ $reportType == 'detail' ? 'selected' : '' }}>Detail
                                                Transaksi</option>
                                            <option value="category" {{ $reportType == 'category' ? 'selected' : '' }}>Per
                                                Kategori</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <div class="form-group">
                                        <button type="submit" name="generate" value="true" class="btn btn-primary"><i
                                                class="fas fa-search mr-1"></i> Tampilkan</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="my-3">
                    <a href="{{ route('financial.index') }}" class="btn btn-primary">
                        <i class="fas fa-tachometer-alt mr-1"></i> Dashboard Keuangan
                    </a>
                    <a href="{{ route('financial.transactions') }}" class="btn btn-info">
                        <i class="fas fa-list mr-1"></i> Daftar Transaksi
                    </a>

                    @if (isset($report))
                        <div class="float-right">
                            <form action="{{ route('financial.export.pdf') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="start_date" value="{{ $startDate }}">
                                <input type="hidden" name="end_date" value="{{ $endDate }}">
                                <input type="hidden" name="report_type" value="{{ $reportType }}">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-file-pdf mr-1"></i> Export PDF
                                </button>
                            </form>
                            <form action="{{ route('financial.export.excel') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="start_date" value="{{ $startDate }}">
                                <input type="hidden" name="end_date" value="{{ $endDate }}">
                                <input type="hidden" name="report_type" value="{{ $reportType }}">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-file-excel mr-1"></i> Export Excel
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                @if (isset($report))
                    @if ($reportType == 'summary')
                        <!-- Summary Report -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Laporan Ringkasan Keuangan</h3>
                                        <div class="card-tools">
                                            <span class="badge badge-info">Periode:
                                                {{ date('d/m/Y', strtotime($report['startDate'])) }} -
                                                {{ date('d/m/Y', strtotime($report['endDate'])) }}</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="info-box bg-success">
                                                    <span class="info-box-icon"><i
                                                            class="fas fa-money-bill-wave"></i></span>
                                                    <div class="info-box-content">
                                                        <span class="info-box-text">Total Pemasukan</span>
                                                        <span class="info-box-number">Rp
                                                            {{ number_format($report['totalIncome'], 0, ',', '.') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-box bg-danger">
                                                    <span class="info-box-icon"><i
                                                            class="fas fa-shopping-cart"></i></span>
                                                    <div class="info-box-content">
                                                        <span class="info-box-text">Total Pengeluaran</span>
                                                        <span class="info-box-number">Rp
                                                            {{ number_format($report['totalExpense'], 0, ',', '.') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="info-box bg-info">
                                                    <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                                                    <div class="info-box-content">
                                                        <span class="info-box-text">Profit Bersih</span>
                                                        <span
                                                            class="info-box-number {{ $report['netProfit'] < 0 ? 'text-warning' : '' }}">Rp
                                                            {{ number_format($report['netProfit'], 0, ',', '.') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h3 class="card-title">Pemasukan per Kategori</h3>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="chart-container"
                                                            style="position: relative; height:250px;">
                                                            <canvas id="incomeCategoryChart"></canvas>
                                                        </div>
                                                        <div class="mt-3">
                                                            <table class="table table-bordered table-striped">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Kategori</th>
                                                                        <th>Jumlah</th>
                                                                        <th>Persentase</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach ($report['incomeByCategory'] as $category)
                                                                        <tr>
                                                                            <td>{{ $category->kategori }}</td>
                                                                            <td>Rp
                                                                                {{ number_format($category->total, 0, ',', '.') }}
                                                                            </td>
                                                                            <td>{{ number_format(($category->total / $report['totalIncome']) * 100, 2) }}%
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h3 class="card-title">Pengeluaran per Kategori</h3>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="chart-container"
                                                            style="position: relative; height:250px;">
                                                            <canvas id="expenseCategoryChart"></canvas>
                                                        </div>
                                                        <div class="mt-3">
                                                            <table class="table table-bordered table-striped">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Kategori</th>
                                                                        <th>Jumlah</th>
                                                                        <th>Persentase</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach ($report['expenseByCategory'] as $category)
                                                                        <tr>
                                                                            <td>{{ $category->kategori }}</td>
                                                                            <td>Rp
                                                                                {{ number_format($category->total, 0, ',', '.') }}
                                                                            </td>
                                                                            <td>{{ number_format(($category->total / $report['totalExpense']) * 100, 2) }}%
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mt-4">
                                            <div class="col-md-12">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h3 class="card-title">Tren Keuangan Harian</h3>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="chart-container"
                                                            style="position: relative; height:300px;">
                                                            <canvas id="dailyTransactionChart"></canvas>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif($reportType == 'detail')
                        <!-- Detail Report -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Laporan Detail Transaksi Keuangan</h3>
                                <div class="card-tools">
                                    <span class="badge badge-info">Periode:
                                        {{ date('d/m/Y', strtotime($report['startDate'])) }} -
                                        {{ date('d/m/Y', strtotime($report['endDate'])) }}</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="info-box bg-success">
                                            <span class="info-box-icon"><i class="fas fa-money-bill-wave"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total Pemasukan</span>
                                                <span class="info-box-number">Rp
                                                    {{ number_format($report['totalIncome'], 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box bg-danger">
                                            <span class="info-box-icon"><i class="fas fa-shopping-cart"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total Pengeluaran</span>
                                                <span class="info-box-number">Rp
                                                    {{ number_format($report['totalExpense'], 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box bg-info">
                                            <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Profit Bersih</span>
                                                <span
                                                    class="info-box-number {{ $report['netProfit'] < 0 ? 'text-warning' : '' }}">Rp
                                                    {{ number_format($report['netProfit'], 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Tanggal</th>
                                                <th>Kode</th>
                                                <th>Kategori</th>
                                                <th>Deskripsi</th>
                                                <th>Tipe</th>
                                                <th>Jumlah</th>
                                                <th>Metode</th>
                                                <th>User</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $no = 1; @endphp
                                            @foreach ($report['transactions'] as $transaction)
                                                <tr>
                                                    <td>{{ $no++ }}</td>
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
                                                    <td>{{ $transaction->user->name }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @elseif($reportType == 'category')
                        <!-- Category Report -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Laporan Keuangan per Kategori</h3>
                                <div class="card-tools">
                                    <span class="badge badge-info">Periode:
                                        {{ date('d/m/Y', strtotime($report['startDate'])) }} -
                                        {{ date('d/m/Y', strtotime($report['endDate'])) }}</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="info-box bg-success">
                                            <span class="info-box-icon"><i class="fas fa-money-bill-wave"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total Pemasukan</span>
                                                <span class="info-box-number">Rp
                                                    {{ number_format($report['totalIncome'], 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box bg-danger">
                                            <span class="info-box-icon"><i class="fas fa-shopping-cart"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total Pengeluaran</span>
                                                <span class="info-box-number">Rp
                                                    {{ number_format($report['totalExpense'], 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box bg-info">
                                            <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Profit Bersih</span>
                                                <span
                                                    class="info-box-number {{ $report['netProfit'] < 0 ? 'text-warning' : '' }}">Rp
                                                    {{ number_format($report['netProfit'], 0, ',', '.') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header bg-success">
                                                <h3 class="card-title">Kategori Pemasukan</h3>
                                            </div>
                                            <div class="card-body">
                                                <div class="chart-container" style="position: relative; height:250px;">
                                                    <canvas id="incomeCategoryChart"></canvas>
                                                </div>
                                                <div class="mt-3">
                                                    <table class="table table-bordered table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>Kategori</th>
                                                                <th>Jumlah Transaksi</th>
                                                                <th>Total (Rp)</th>
                                                                <th>Persentase</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($report['incomeByCategory'] as $category)
                                                                <tr>
                                                                    <td>{{ $category->kategori }}</td>
                                                                    <td>{{ $category->count }}</td>
                                                                    <td>Rp
                                                                        {{ number_format($category->total, 0, ',', '.') }}
                                                                    </td>
                                                                    <td>{{ number_format(($category->total / $report['totalIncome']) * 100, 2) }}%
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header bg-danger">
                                                <h3 class="card-title">Kategori Pengeluaran</h3>
                                            </div>
                                            <div class="card-body">
                                                <div class="chart-container" style="position: relative; height:250px;">
                                                    <canvas id="expenseCategoryChart"></canvas>
                                                </div>
                                                <div class="mt-3">
                                                    <table class="table table-bordered table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>Kategori</th>
                                                                <th>Jumlah Transaksi</th>
                                                                <th>Total (Rp)</th>
                                                                <th>Persentase</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($report['expenseByCategory'] as $category)
                                                                <tr>
                                                                    <td>{{ $category->kategori }}</td>
                                                                    <td>{{ $category->count }}</td>
                                                                    <td>Rp
                                                                        {{ number_format($category->total, 0, ',', '.') }}
                                                                    </td>
                                                                    <td>{{ number_format(($category->total / $report['totalExpense']) * 100, 2) }}%
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info"></i> Informasi!</h5>
                        Pilih periode dan klik tombol "Tampilkan" untuk melihat laporan keuangan.
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection

@section('scripts')
    @if (isset($report))
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            $(function() {
                // Chart configuration
                Chart.defaults.font.family = "'Source Sans Pro', 'Helvetica', 'Arial', sans-serif";
                Chart.defaults.font.size = 12;

                @if ($reportType == 'summary' || $reportType == 'category')
                    // Income by category chart
                    @if (isset($report['incomeByCategory']) && count($report['incomeByCategory']) > 0)
                        var incomeCategoryChart = new Chart(document.getElementById('incomeCategoryChart'), {
                            type: 'pie',
                            data: {
                                labels: [
                                    @foreach ($report['incomeByCategory'] as $category)
                                        '{{ $category->kategori }}',
                                    @endforeach
                                ],
                                datasets: [{
                                    data: [
                                        @foreach ($report['incomeByCategory'] as $category)
                                            {{ $category->total }},
                                        @endforeach
                                    ],
                                    backgroundColor: [
                                        'rgba(40, 167, 69, 0.8)',
                                        'rgba(23, 162, 184, 0.8)',
                                        'rgba(0, 123, 255, 0.8)',
                                        'rgba(108, 117, 125, 0.8)',
                                        'rgba(255, 193, 7, 0.8)',
                                        'rgba(111, 66, 193, 0.8)',
                                        'rgba(253, 126, 20, 0.8)',
                                        'rgba(32, 201, 151, 0.8)'
                                    ]
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'right',
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                var label = context.label || '';
                                                var value = context.raw || 0;
                                                var percentage = ((value / {{ $report['totalIncome'] }}) *
                                                    100).toFixed(2);
                                                return label + ': Rp ' + value.toString().replace(
                                                        /\B(?=(\d{3})+(?!\d))/g, '.') + ' (' + percentage +
                                                    '%)';
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    @endif

                    // Expense by category chart
                    @if (isset($report['expenseByCategory']) && count($report['expenseByCategory']) > 0)
                        var expenseCategoryChart = new Chart(document.getElementById('expenseCategoryChart'), {
                            type: 'pie',
                            data: {
                                labels: [
                                    @foreach ($report['expenseByCategory'] as $category)
                                        '{{ $category->kategori }}',
                                    @endforeach
                                ],
                                datasets: [{
                                    data: [
                                        @foreach ($report['expenseByCategory'] as $category)
                                            {{ $category->total }},
                                        @endforeach
                                    ],
                                    backgroundColor: [
                                        'rgba(220, 53, 69, 0.8)',
                                        'rgba(253, 126, 20, 0.8)',
                                        'rgba(255, 193, 7, 0.8)',
                                        'rgba(108, 117, 125, 0.8)',
                                        'rgba(52, 58, 64, 0.8)',
                                        'rgba(111, 66, 193, 0.8)',
                                        'rgba(23, 162, 184, 0.8)',
                                        'rgba(32, 201, 151, 0.8)'
                                    ]
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'right',
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                var label = context.label || '';
                                                var value = context.raw || 0;
                                                var percentage = ((value / {{ $report['totalExpense'] }}) *
                                                    100).toFixed(2);
                                                return label + ': Rp ' + value.toString().replace(
                                                        /\B(?=(\d{3})+(?!\d))/g, '.') + ' (' + percentage +
                                                    '%)';
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    @endif
                @endif

                @if ($reportType == 'summary')
                    // Daily transactions chart
                    @if (isset($report['dailyTransactions']) && count($report['dailyTransactions']) > 0)
                        var dailyTransactionChart = new Chart(document.getElementById('dailyTransactionChart'), {
                            type: 'bar',
                            data: {
                                labels: [
                                    @foreach ($report['dailyTransactions'] as $day)
                                        '{{ date('d/m', strtotime($day->tanggal)) }}',
                                    @endforeach
                                ],
                                datasets: [{
                                        label: 'Pemasukan',
                                        data: [
                                            @foreach ($report['dailyTransactions'] as $day)
                                                {{ $day->total_income }},
                                            @endforeach
                                        ],
                                        backgroundColor: 'rgba(40, 167, 69, 0.7)',
                                        borderColor: 'rgba(40, 167, 69, 1)',
                                        borderWidth: 1
                                    },
                                    {
                                        label: 'Pengeluaran',
                                        data: [
                                            @foreach ($report['dailyTransactions'] as $day)
                                                {{ $day->total_expense }},
                                            @endforeach
                                        ],
                                        backgroundColor: 'rgba(220, 53, 69, 0.7)',
                                        borderColor: 'rgba(220, 53, 69, 1)',
                                        borderWidth: 1
                                    },
                                    {
                                        label: 'Profit',
                                        type: 'line',
                                        data: [
                                            @foreach ($report['dailyTransactions'] as $day)
                                                {{ $day->total_income - $day->total_expense }},
                                            @endforeach
                                        ],
                                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                                        borderColor: 'rgba(0, 123, 255, 1)',
                                        borderWidth: 2,
                                        fill: false
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
                                                return 'Rp ' + value.toString().replace(
                                                    /\B(?=(\d{3})+(?!\d))/g, ".");
                                            }
                                        }
                                    }
                                },
                                plugins: {
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return context.dataset.label + ': Rp ' + context.raw
                                                    .toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    @endif
                @endif
            });
        </script>
    @endif
@endsection
