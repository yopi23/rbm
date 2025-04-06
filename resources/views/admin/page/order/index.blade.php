<!-- resources/views/admin/page/order/index.blade.php -->

<div class="row">
    <div class="col-md-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-clipboard-list mr-1"></i>
                    Daftar Pesanan
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createOrderModal">
                        <i class="fas fa-plus mr-1"></i> Buat Pesanan Baru
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="orderTable">
                        <thead>
                            <tr>
                                <th>Kode Pesanan</th>
                                <th>Tanggal</th>
                                <th>Supplier</th>
                                <th class="text-center">Total Item</th>
                                <th class="text-center">Status</th>
                                <th>Catatan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                <tr>
                                    <td>{{ $order->kode_order }}</td>
                                    <td>{{ date('d/m/Y', strtotime($order->tanggal_order)) }}</td>
                                    <td>{{ $order->supplier ? $order->supplier->nama_supplier : '-' }}</td>
                                    <td class="text-center">{{ $order->total_item }}</td>
                                    <td class="text-center">
                                        @if ($order->status_order == 'draft')
                                            <span class="badge badge-secondary">Draft</span>
                                        @elseif($order->status_order == 'menunggu_pengiriman')
                                            <span class="badge badge-warning">Menunggu Pengiriman</span>
                                        @elseif($order->status_order == 'selesai')
                                            <span class="badge badge-success">Selesai</span>
                                        @elseif($order->status_order == 'dibatalkan')
                                            <span class="badge badge-danger">Dibatalkan</span>
                                        @endif
                                    </td>
                                    <td>{{ $order->catatan ?? '-' }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('order.show', $order->id) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if ($order->status_order == 'draft')
                                            <a href="{{ route('order.edit', $order->id) }}"
                                                class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger btn-sm"
                                                onclick="confirmCancel('{{ route('order.cancel', $order->id) }}')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @endif
                                        @if ($order->status_order == 'menunggu_pengiriman' || $order->status_order == 'selesai')
                                            <button type="button" class="btn btn-success btn-sm"
                                                onclick="confirmConvert('{{ route('order.convert-to-purchase', $order->id) }}')">
                                                <i class="fas fa-exchange-alt"></i> Konversi
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada data pesanan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Buat Pesanan -->
<div class="modal fade" id="createOrderModal" tabindex="-1" aria-labelledby="createOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('order.create') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createOrderModalLabel">Buat Pesanan Baru</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="kode_order">Kode Pesanan</label>
                        <input type="text" class="form-control" id="kode_order" value="{{ $kode_order }}"
                            readonly>
                        <small class="form-text text-muted">Kode akan dibuat otomatis</small>
                    </div>

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
                        <label for="catatan">Catatan (Opsional)</label>
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

<script>
    $(function() {
        // Initialize DataTable
        $('#orderTable').DataTable({
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
                "targets": 6
            }]
        });
    });

    function confirmCancel(url) {
        Swal.fire({
            title: 'Batalkan Pesanan?',
            text: "Apakah Anda yakin ingin membatalkan pesanan ini?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Batalkan!',
            cancelButtonText: 'Tidak'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }

    function confirmConvert(url) {
        Swal.fire({
            title: 'Konversi ke Pembelian?',
            text: "Pesanan ini akan dikonversi menjadi pembelian. Lanjutkan?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Konversi!',
            cancelButtonText: 'Tidak'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }
</script>
