<!-- resources/views/admin/page/inventory/restock_report.blade.php -->

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
                <form action="{{ route('admin.inventory.restock-report') }}" method="GET" class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="threshold">Threshold Stok Rendah</label>
                            <input type="number" class="form-control" id="threshold" name="threshold"
                                value="{{ request('threshold', 10) }}" min="1" max="100">
                            <small class="form-text text-muted">
                                Tampilkan produk dengan stok di bawah atau sama dengan nilai ini
                            </small>
                        </div>
                    </div>
                    <div class="col-md-8 d-flex align-items-end">
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
            <div class="card-header bg-warning">
                <h3 class="card-title">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Daftar Item yang Perlu Di-restock
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered m-0" id="restockTable">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Sparepart</th>
                                <th>Kategori</th>
                                <th class="text-center">Stok Saat Ini</th>
                                <th class="text-center">Penjualan 30 Hari</th>
                                <th class="text-center">Estimasi Habis</th>
                                <th class="text-right">Harga Beli</th>
                                <th>Supplier</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lowStockItems as $item)
                                <tr
                                    class="{{ $item->stok_sparepart <= 2 ? 'bg-danger text-white' : ($item->stok_sparepart <= 5 ? 'bg-warning' : '') }}">
                                    <td>{{ $item->kode_sparepart }}</td>
                                    <td>{{ $item->nama_sparepart }}</td>
                                    <td>{{ $item->kode_kategori }}</td>
                                    <td class="text-center font-weight-bold">{{ $item->stok_sparepart }}</td>
                                    <td class="text-center">{{ $item->sales_last_30_days }}</td>
                                    <td class="text-center">
                                        @if ($item->estimated_days_left == 0)
                                            <span class="badge badge-danger">Stok Habis</span>
                                        @elseif($item->estimated_days_left <= 7)
                                            <span class="badge badge-danger">{{ $item->estimated_days_left }}
                                                hari</span>
                                        @elseif($item->estimated_days_left <= 14)
                                            <span class="badge badge-warning">{{ $item->estimated_days_left }}
                                                hari</span>
                                        @else
                                            <span class="badge badge-success">{{ $item->estimated_days_left }}
                                                hari</span>
                                        @endif
                                    </td>
                                    <td class="text-right">Rp {{ number_format($item->harga_beli, 0, ',', '.') }}</td>
                                    <td>{{ $item->kode_spl ?? 'Tidak ada' }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-info"
                                            onclick="getReorderRecommendation({{ $item->id }})">
                                            <i class="fas fa-calculator mr-1"></i> Hitung Reorder
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">Tidak ada item yang perlu di-restock.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Reorder Recommendation -->
<div class="modal fade" id="reorderModal" tabindex="-1" aria-labelledby="reorderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reorderModalLabel">Rekomendasi Pemesanan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center" id="reorderSpinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
                <div id="reorderContent" style="display: none;">
                    <h5 id="itemName" class="mb-3 text-center text-primary"></h5>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="leadTime" class="form-label">Lead Time (hari)</label>
                                <input type="number" class="form-control" id="leadTime" value="7"
                                    min="1" max="90">
                                <small class="form-text text-muted">Estimasi waktu pengiriman</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="safetyStock" class="form-label">Safety Stock</label>
                                <input type="number" class="form-control" id="safetyStock" value="5"
                                    min="1" max="100">
                                <small class="form-text text-muted">Stok minimum yang harus ada</small>
                            </div>
                        </div>
                    </div>
                    <table class="table table-bordered">
                        <tr>
                            <th>Stok Saat Ini</th>
                            <td id="currentStock" class="text-right"></td>
                        </tr>
                        <tr>
                            <th>Penjualan 90 Hari</th>
                            <td id="periodSales" class="text-right"></td>
                        </tr>
                        <tr>
                            <th>Rata-rata Per Hari</th>
                            <td id="dailyAverage" class="text-right"></td>
                        </tr>
                        <tr class="bg-info text-white">
                            <th>Jumlah Reorder</th>
                            <td id="reorderQuantity" class="text-right font-weight-bold"></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="recalculateBtn" onclick="recalculateReorder()">
                    <i class="fas fa-sync-alt mr-1"></i> Hitung Ulang
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Fungsi untuk mendapatkan rekomendasi jumlah pemesanan
    function getReorderRecommendation(itemId) {
        const modal = new bootstrap.Modal(document.getElementById('reorderModal'));
        modal.show();

        document.getElementById('reorderSpinner').style.display = 'block';
        document.getElementById('reorderContent').style.display = 'none';

        // Simpan itemId di variabel global untuk recalculate
        window.currentItemId = itemId;

        const leadTime = document.getElementById('leadTime').value;
        const safetyStock = document.getElementById('safetyStock').value;

        fetch(
                `{{ url('admin/inventory/reorder-recommendation') }}/${itemId}?lead_time=${leadTime}&safety_stock=${safetyStock}`
            )
            .then(response => response.json())
            .then(data => {
                document.getElementById('reorderSpinner').style.display = 'none';
                document.getElementById('reorderContent').style.display = 'block';

                document.getElementById('itemName').textContent = data.item.nama_sparepart;
                document.getElementById('currentStock').textContent = data.current_stock;
                document.getElementById('periodSales').textContent = data.period_sales;
                document.getElementById('dailyAverage').textContent = data.daily_average;
                document.getElementById('reorderQuantity').textContent = data.reorder_quantity;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mengambil data.');
            });
    }

    // Fungsi untuk menghitung ulang berdasarkan parameter yang diubah
    function recalculateReorder() {
        const itemId = window.currentItemId;
        const leadTime = document.getElementById('leadTime').value;
        const safetyStock = document.getElementById('safetyStock').value;

        document.getElementById('reorderSpinner').style.display = 'block';
        document.getElementById('reorderContent').style.display = 'none';

        fetch(
                `{{ url('admin/inventory/reorder-recommendation') }}/${itemId}?lead_time=${leadTime}&safety_stock=${safetyStock}`
            )
            .then(response => response.json())
            .then(data => {
                document.getElementById('reorderSpinner').style.display = 'none';
                document.getElementById('reorderContent').style.display = 'block';

                document.getElementById('currentStock').textContent = data.current_stock;
                document.getElementById('periodSales').textContent = data.period_sales;
                document.getElementById('dailyAverage').textContent = data.daily_average;
                document.getElementById('reorderQuantity').textContent = data.reorder_quantity;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mengambil data.');
            });
    }

    function exportToExcel() {
        let tableId = 'restockTable';
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
