<!-- resources/views/admin/page/inventory/restock_report.blade.php -->

<div class="row">
    <div class="col-md-6">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-filter mr-1"></i>
                    Filter
                </h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.inventory.restock-report') }}" method="GET">
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <input type="number" class="form-control" id="threshold" name="threshold"
                                    value="{{ request('threshold', 10) }}" min="1" max="100">
                                <small class="form-text text-muted">
                                    Tampilkan produk dengan stok di bawah atau sama dengan nilai ini
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col d-flex align-items-end">
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-search mr-1"></i> Terapkan Filter
                                </button>
                                <a href="{{ route('admin.inventory.home') }}" class="btn btn-secondary ml-2 btn-sm">
                                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Dashboard
                                </a>
                                <button type="button" class="btn btn-success ml-2 btn-sm" onclick="exportToExcel()">
                                    <i class="fas fa-file-excel mr-1"></i> Export Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle mr-1"></i>
                    Informasi
                </h3>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="info-box bg-danger">
                            {{-- <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span> --}}
                            <div class="info-box-content">
                                <span class="info-box-text">Stok Kritis</span>
                                <span
                                    class="info-box-number">{{ $lowStockItems->where('stok_sparepart', '<=', 2)->count() }}</span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 100%"></div>
                                </div>
                                <span class="progress-description">
                                    Stok <= 2 (Perlu segera direstock) </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-warning">
                            {{-- <span class="info-box-icon"><i class="fas fa-exclamation"></i></span> --}}
                            <div class="info-box-content">
                                <span class="info-box-text">Perlu Restock</span>
                                <span
                                    class="info-box-number">{{ $lowStockItems->whereIn('stok_sparepart', [3, 4, 5])->count() }}</span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 100%"></div>
                                </div>
                                <span class="progress-description">
                                    Stok 3-5 (Segera siapkan pemesanan)
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-info">
                            {{-- <span class="info-box-icon"><i class="fas fa-bell"></i></span> --}}
                            <div class="info-box-content">
                                <span class="info-box-text">Stok Rendah</span>
                                <span
                                    class="info-box-number">{{ $lowStockItems->where('stok_sparepart', '>', 5)->where('stok_sparepart', '<=', request('threshold', 10))->count() }}</span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 100%"></div>
                                </div>
                                <span class="progress-description">
                                    Stok > 5 (Mulai pertimbangkan pemesanan)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
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
                <!-- Modifikasi pada bagian card tools di header -->
                <div class="card-tools">
                    <a href="{{ route('order.index') }}" class="btn btn-info btn-sm mr-2">
                        <i class="fas fa-clipboard-list mr-1"></i> Lihat Daftar Pesanan
                    </a>
                    <button type="button" class="btn btn-danger btn-sm mr-2" data-toggle="modal"
                        data-target="#createOrderModal">
                        <i class="fas fa-plus-circle mr-1"></i> Buat Pesanan Baru
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="restockTable">
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
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-info"
                                                onclick="getReorderRecommendation({{ $item->id }})">
                                                <i class="fas fa-calculator mr-1"></i> Hitung Reorder
                                            </button>
                                            <button class="btn btn-sm btn-success ml-1"
                                                onclick="addToOrder({{ $item->id }}, '{{ $item->nama_sparepart }}', {{ $item->harga_beli }})">
                                                <i class="fas fa-cart-plus mr-1"></i> Tambah ke Pesanan
                                            </button>
                                        </div>
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

<!-- Tambahkan Modal untuk Pesanan Baru -->
<div class="modal fade" id="createOrderModal" tabindex="-1" aria-labelledby="createOrderModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createOrderModalLabel">Buat Pesanan Baru</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('order.create') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="kode_supplier">Supplier</label>
                        <select class="form-control" id="kode_supplier" name="kode_supplier" required>
                            <option value="">-- Pilih Supplier --</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->nama_supplier }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="catatan">Catatan (opsional)</label>
                        <textarea class="form-control" id="catatan" name="catatan" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Buat Pesanan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal untuk Tambah ke Pesanan yang Ada -->
<div class="modal fade" id="addToOrderModal" tabindex="-1" aria-labelledby="addToOrderModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addToOrderModalLabel">Tambahkan ke Pesanan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addToOrderForm" action="{{ route('order.add-low-stock-item', 0) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="modal_sparepart_id" name="sparepart_id">

                    <div class="form-group">
                        <label>Item</label>
                        <input type="text" class="form-control" id="modal_item_name" readonly>
                    </div>

                    <div class="form-group">
                        <label for="modal_jumlah">Jumlah</label>
                        <input type="number" class="form-control" id="modal_jumlah" name="jumlah" min="1"
                            value="10" required>
                    </div>

                    <div class="form-group">
                        <label for="order_id">Pilih Pesanan</label>
                        <select class="form-control" id="order_id" name="order_id" required>
                            <option value="">-- Pilih Pesanan --</option>
                            @foreach ($activeOrders as $activeOrder)
                                <option value="{{ $activeOrder->id }}">
                                    {{ $activeOrder->kode_order }}
                                    ({{ $activeOrder->supplier ? $activeOrder->supplier->nama_supplier : 'Tanpa Supplier' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-1"></i>
                            Jika belum ada pesanan, silakan buat pesanan baru terlebih dahulu.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnAddToOrder">Tambahkan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tambahkan script untuk fungsi tambah ke pesanan -->
<script>
    // Fungsi untuk menambahkan item ke pesanan
    function addToOrder(sparepartId, sparepartName, price) {
        // Set nilai di modal
        $('#modal_sparepart_id').val(sparepartId);
        $('#modal_item_name').val(sparepartName);

        // Tampilkan modal
        $('#addToOrderModal').modal('show');
    }

    // Update formulir saat pesanan dipilih
    $('#order_id').change(function() {
        const orderId = $(this).val();
        if (orderId) {
            const newAction = "{{ url('admin/order/add-low-stock-item') }}/" + orderId;
            $('#addToOrderForm').attr('action', newAction);
            $('#btnAddToOrder').prop('disabled', false);
        } else {
            $('#btnAddToOrder').prop('disabled', true);
        }
    });
</script>

<script>
    $(function() {
        // Initialize DataTable
        $('#restockTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "pageLength": 10,
            "lengthMenu": [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Semua"]
            ],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json"
            },
            "columnDefs": [{
                    "orderable": false,
                    "targets": 8
                } // Disable sorting on actions column
            ],
            "createdRow": function(row, data, dataIndex) {
                // This function allows us to maintain the original row classes
                // even when DataTables redraws the rows
                if ($(row).hasClass('bg-danger')) {
                    $(row).find('td').addClass('text-white');
                }
            }
        });
    });

    // Fungsi untuk mendapatkan rekomendasi jumlah pemesanan
    function getReorderRecommendation(itemId) {
        const modal = $('#reorderModal');
        modal.modal('show');

        $('#reorderSpinner').show();
        $('#reorderContent').hide();

        // Simpan itemId di variabel global untuk recalculate
        window.currentItemId = itemId;

        const leadTime = $('#leadTime').val();
        const safetyStock = $('#safetyStock').val();

        $.ajax({
            url: `{{ url('admin/inventory/reorder-recommendation') }}/${itemId}`,
            data: {
                lead_time: leadTime,
                safety_stock: safetyStock
            },
            method: 'GET',
            success: function(data) {
                $('#reorderSpinner').hide();
                $('#reorderContent').show();

                $('#itemName').text(data.item.nama_sparepart);
                $('#currentStock').text(data.current_stock);
                $('#periodSales').text(data.period_sales);
                $('#dailyAverage').text(data.daily_average);
                $('#reorderQuantity').text(data.reorder_quantity);
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mengambil data.');
            }
        });
    }

    // Fungsi untuk menghitung ulang berdasarkan parameter yang diubah
    function recalculateReorder() {
        const itemId = window.currentItemId;
        const leadTime = $('#leadTime').val();
        const safetyStock = $('#safetyStock').val();

        $('#reorderSpinner').show();
        $('#reorderContent').hide();

        $.ajax({
            url: `{{ url('admin/inventory/reorder-recommendation') }}/${itemId}`,
            data: {
                lead_time: leadTime,
                safety_stock: safetyStock
            },
            method: 'GET',
            success: function(data) {
                $('#reorderSpinner').hide();
                $('#reorderContent').show();

                $('#currentStock').text(data.current_stock);
                $('#periodSales').text(data.period_sales);
                $('#dailyAverage').text(data.daily_average);
                $('#reorderQuantity').text(data.reorder_quantity);
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mengambil data.');
            }
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
        link.download = 'restock_report_' + new Date().toISOString().slice(0, 10) + '.xls';
        link.click();
    }
</script>
