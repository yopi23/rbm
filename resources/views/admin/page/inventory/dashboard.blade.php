<!-- resources/views/admin/page/inventory/dashboard.blade.php -->

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-line mr-1"></i>
                    Statistik Stok dan Penjualan
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Info Box: Total Produk -->
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-info"><i class="fas fa-boxes"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Produk</span>
                                <span class="info-box-number">{{ \App\Models\Sparepart::count() }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Info Box: Produk Stok Rendah -->
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Stok Rendah</span>
                                <span class="info-box-number">{{ count($lowStockItems) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Info Box: Total Penjualan Hari Ini -->
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-shopping-cart"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Penjualan Hari Ini</span>
                                @php
                                    $todaySales = \App\Models\DetailSparepartPenjualan::whereDate(
                                        'created_at',
                                        date('Y-m-d'),
                                    )->count();
                                @endphp
                                <span class="info-box-number">{{ $todaySales }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Info Box: Total Service Hari Ini -->
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-danger"><i class="fas fa-tools"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Service Hari Ini</span>
                                @php
                                    $todayServices = \App\Models\DetailPartServices::whereDate(
                                        'created_at',
                                        date('Y-m-d'),
                                    )->count();
                                @endphp
                                <span class="info-box-number">{{ $todayServices }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Card untuk produk terlaris -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary">
                <h3 class="card-title">
                    <i class="fas fa-star mr-1"></i>
                    Produk Terlaris (30 Hari Terakhir)
                </h3>
                <div class="card-tools">
                    <a href="{{ route('admin.inventory.bestsellers') }}" class="btn btn-tool">
                        <i class="fas fa-list"></i> Lihat Semua
                    </a>
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover m-0" id="topselling-table">
                        <thead>
                            <tr>
                                <th>Nama Sparepart</th>
                                <th class="text-center">Terjual</th>
                                <th class="text-center">Stok</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topSellingItems as $item)
                                <tr>
                                    <td>{{ $item->nama_sparepart }}</td>
                                    <td class="text-center">{{ $item->sold_qty }}</td>
                                    <td class="text-center">{{ $item->stok_sparepart }}</td>
                                    <td class="text-center">
                                        @if ($item->stok_sparepart <= 2)
                                            <span class="badge badge-danger">Kritis</span>
                                        @elseif($item->stok_sparepart <= 5)
                                            <span class="badge badge-warning">Perlu Restock</span>
                                        @elseif($item->stok_sparepart <= 10)
                                            <span class="badge badge-info">Stok Rendah</span>
                                        @else
                                            <span class="badge badge-success">Stok Aman</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">Belum ada data penjualan</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Card untuk stok rendah -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning">
                <h3 class="card-title">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    Stok Rendah
                </h3>
                <div class="card-tools">
                    <a href="{{ route('admin.inventory.restock-report') }}" class="btn btn-tool">
                        <i class="fas fa-list"></i> Lihat Semua
                    </a>
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover m-0" id="lowstock-table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Sparepart</th>
                                <th class="text-center">Stok</th>
                                <th class="text-right">Harga Jual</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lowStockItems as $item)
                                <tr>
                                    <td>{{ $item->kode_sparepart }}</td>
                                    <td>{{ $item->nama_sparepart }}</td>
                                    <td
                                        class="text-center {{ $item->stok_sparepart <= 2 ? 'text-danger font-weight-bold' : 'text-warning' }}">
                                        {{ $item->stok_sparepart }}
                                    </td>
                                    <td class="text-right">Rp {{ number_format($item->harga_jual, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">Tidak ada item dengan stok rendah</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Grafik Penjualan vs Service -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-gradient-info">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar mr-1"></i>
                    Statistik Penjualan (7 Hari Terakhir)
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="chart">
                    <canvas id="salesChart"
                        style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    $(function() {
        // Initialize DataTables
        $('#topselling-table').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "pageLength": 5,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json"
            }
        });

        $('#lowstock-table').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "pageLength": 5,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json"
            }
        });

        // Data dari controller
        const salesData = @json($dailySales);

        // Persiapkan data untuk chart
        const dates = salesData.map(item => item.date);
        const salesCounts = salesData.map(item => item.sales_count);
        const serviceCounts = salesData.map(item => item.service_count);
        const totalItems = salesData.map(item => item.total_items);

        // Sales Chart
        const salesChartCanvas = document.getElementById('salesChart').getContext('2d');

        const salesChartData = {
            labels: dates,
            datasets: [{
                    label: 'Jumlah Penjualan',
                    backgroundColor: 'rgba(60,141,188,0.9)',
                    borderColor: 'rgba(60,141,188,0.8)',
                    pointRadius: 3,
                    pointColor: '#3b8bba',
                    pointStrokeColor: 'rgba(60,141,188,1)',
                    pointHighlightFill: '#fff',
                    pointHighlightStroke: 'rgba(60,141,188,1)',
                    data: salesCounts
                },
                {
                    label: 'Jumlah Service',
                    backgroundColor: 'rgba(210, 214, 222, 1)',
                    borderColor: 'rgba(210, 214, 222, 1)',
                    pointRadius: 3,
                    pointColor: 'rgba(210, 214, 222, 1)',
                    pointStrokeColor: '#c1c7d1',
                    pointHighlightFill: '#fff',
                    pointHighlightStroke: 'rgba(220,220,220,1)',
                    data: serviceCounts
                },
                {
                    label: 'Total Item Terjual',
                    type: 'line',
                    tension: 0.4,
                    backgroundColor: 'transparent',
                    borderColor: '#f39c12',
                    pointBorderColor: '#f39c12',
                    pointBackgroundColor: '#f39c12',
                    pointRadius: 4,
                    data: totalItems
                }
            ]
        };

        const salesChartOptions = {
            maintainAspectRatio: false,
            responsive: true,
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    grid: {
                        display: false
                    },
                    beginAtZero: true
                }
            }
        };

        new Chart(salesChartCanvas, {
            type: 'bar',
            data: salesChartData,
            options: salesChartOptions
        });
    });
</script>
