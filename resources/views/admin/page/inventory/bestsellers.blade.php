<!-- resources/views/admin/page/inventory/bestsellers.blade.php -->

<div class="row">
    <div class="col-md-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-filter mr-1"></i>
                    Filter
                </h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.inventory.bestsellers') }}" method="GET" class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="days">Periode (Hari)</label>
                            <select class="form-control" id="days" name="days">
                                <option value="7" {{ request('days', 30) == 7 ? 'selected' : '' }}>7 Hari</option>
                                <option value="30" {{ request('days', 30) == 30 ? 'selected' : '' }}>30 Hari
                                </option>
                                <option value="90" {{ request('days', 30) == 90 ? 'selected' : '' }}>90 Hari
                                </option>
                                <option value="180" {{ request('days', 30) == 180 ? 'selected' : '' }}>6 Bulan
                                </option>
                                <option value="365" {{ request('days', 30) == 365 ? 'selected' : '' }}>1 Tahun
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="limit">Jumlah Data</label>
                            <select class="form-control" id="limit" name="limit">
                                <option value="10" {{ request('limit', 20) == 10 ? 'selected' : '' }}>10 Item
                                </option>
                                <option value="20" {{ request('limit', 20) == 20 ? 'selected' : '' }}>20 Item
                                </option>
                                <option value="50" {{ request('limit', 20) == 50 ? 'selected' : '' }}>50 Item
                                </option>
                                <option value="100" {{ request('limit', 20) == 100 ? 'selected' : '' }}>100 Item
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search mr-1"></i> Terapkan Filter
                            </button>
                            <a href="{{ route('admin.inventory.home') }}" class="btn btn-secondary ml-2">
                                <i class="fas fa-arrow-left mr-1"></i> Kembali ke Dashboard
                            </a>
                            <button type="button" class="btn btn-success ml-2" onclick="exportToExcel()">
                                <i class="fas fa-file-excel mr-1"></i> Export Excel
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-success">
                <h3 class="card-title">
                    <i class="fas fa-star mr-1"></i>
                    Produk Terlaris ({{ request('days', 30) }} Hari Terakhir)
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered m-0" id="bestsellersTable">
                        <thead>
                            <tr>
                                <th class="text-center">No</th>
                                <th>Kode</th>
                                <th>Nama Sparepart</th>
                                <th>Kategori</th>
                                <th class="text-center">Qty Terjual</th>
                                <th class="text-center">Stok Saat Ini</th>
                                <th class="text-right">Harga Jual</th>
                                <th class="text-right">Nilai Penjualan (Rp)</th>
                                <th class="text-center">Status Stok</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topSellingItems as $key => $item)
                                <tr>
                                    <td class="text-center">{{ $key + 1 }}</td>
                                    <td>{{ $item->kode_sparepart }}</td>
                                    <td>{{ $item->nama_sparepart }}</td>
                                    <td>{{ $item->kode_kategori }}</td>
                                    <td class="text-center font-weight-bold">{{ $item->sold_qty }}</td>
                                    <td class="text-center">{{ $item->stok_sparepart }}</td>
                                    <td class="text-right">{{ number_format($item->harga_jual, 0, ',', '.') }}</td>
                                    <td class="text-right">
                                        {{ number_format($item->sold_qty * $item->harga_jual, 0, ',', '.') }}</td>
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
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-info"
                                            onclick="showItemChart({{ $item->id }})">
                                            <i class="fas fa-chart-line mr-1"></i> Grafik
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">Belum ada data penjualan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Grafik -->
<div class="modal fade" id="chartModal" tabindex="-1" aria-labelledby="chartModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="chartModalLabel">Grafik Penjualan & Service</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center" id="chartSpinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
                <div id="chartContent" style="display: none;">
                    <h5 id="chartItemName" class="text-center mb-3"></h5>
                    <div class="chart">
                        <canvas id="itemStockChart"
                            style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Fungsi untuk menampilkan grafik item
    function showItemChart(itemId) {
        const modal = new bootstrap.Modal(document.getElementById('chartModal'));
        modal.show();

        document.getElementById('chartSpinner').style.display = 'block';
        document.getElementById('chartContent').style.display = 'none';

        if (window.itemChart) {
            window.itemChart.destroy();
        }

        fetch(`{{ url('admin/inventory/item-chart') }}/${itemId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('chartSpinner').style.display = 'none';
                document.getElementById('chartContent').style.display = 'block';

                document.getElementById('chartItemName').textContent = data.item.nama_sparepart;

                const chartCanvas = document.getElementById('itemStockChart').getContext('2d');

                // Prepare chart data
                const dates = data.chart_data.map(item => item.date);
                const salesData = data.chart_data.map(item => item.sales);
                const serviceData = data.chart_data.map(item => item.service);

                window.itemChart = new Chart(chartCanvas, {
                    type: 'line',
                    data: {
                        labels: dates,
                        datasets: [{
                                label: 'Penjualan',
                                data: salesData,
                                borderColor: 'rgba(60,141,188,1)',
                                backgroundColor: 'rgba(60,141,188,0.2)',
                                tension: 0.4,
                                fill: true
                            },
                            {
                                label: 'Service',
                                data: serviceData,
                                borderColor: 'rgba(210, 214, 222, 1)',
                                backgroundColor: 'rgba(210, 214, 222, 0.2)',
                                tension: 0.4,
                                fill: true
                            }
                        ]
                    },
                    options: {
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mengambil data.');
            });
    }

    function exportToExcel() {
        let tableId = 'bestsellersTable';
        let table = document.getElementById(tableId);

        if (!table) {
            console.error('Table with ID ' + tableId + ' not found');
            alert('Tabel tidak ditemukan.');
            return;
        }

        // Buat HTML sederhana tanpa tag XML kompleks
        let html = '<html><head><meta charset="UTF-8"></head><body>';
        html += '<table border="1">' + table.innerHTML + '</table>';
        html += '</body></html>';

        // Konversi ke Blob
        let blob = new Blob([html], {
            type: 'application/vnd.ms-excel'
        });

        // Buat link untuk download
        let link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'bestsellers_' + new Date().toISOString().slice(0, 10) + '.xls';
        link.click();
    }
</script>
