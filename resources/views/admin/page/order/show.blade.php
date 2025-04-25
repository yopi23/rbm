<!-- resources/views/admin/page/order/show.blade.php (Dimodifikasi) -->

<div class="row">
    <div class="col-md-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-clipboard-list mr-1"></i>
                    Detail Pesanan #{{ $order->kode_order }}
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-info"><i class="fas fa-info-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Informasi Pesanan</span>
                                <table class="mt-2">
                                    <tr>
                                        <td><strong>Kode Pesanan</strong></td>
                                        <td class="pl-3">: {{ $order->kode_order }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal</strong></td>
                                        <td class="pl-3">: {{ date('d/m/Y', strtotime($order->tanggal_order)) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total Item</strong></td>
                                        <td class="pl-3">: {{ $order->total_item }} item</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-truck"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Supplier</span>
                                <table class="mt-2">
                                    <tr>
                                        <td><strong>Nama Supplier</strong></td>
                                        <td class="pl-3">:
                                            {{ $order->supplier ? $order->supplier->nama_supplier : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status Pesanan</strong></td>
                                        <td class="pl-3">:
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
                                    </tr>
                                    <tr>
                                        <td><strong>Catatan</strong></td>
                                        <td class="pl-3">: {{ $order->catatan ?? '-' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <h5 class="card-title">Daftar Item Pesanan</h5>

                        <!-- Tombol aksi untuk semua item yang dipilih -->
                        <div class="mb-3">
                            <button type="button" class="btn btn-primary btn-sm" id="btnUpdateStatus" disabled>
                                <i class="fas fa-edit mr-1"></i> Update Status
                            </button>

                            @if (in_array($order->status_order, ['menunggu_pengiriman', 'selesai']))
                                <button type="button" class="btn btn-warning btn-sm" id="btnTransferItems" disabled>
                                    <i class="fas fa-exchange-alt mr-1"></i> Transfer ke Pesanan Baru
                                </button>
                            @endif
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width: 40px">
                                            <div class="icheck-primary">
                                                <input type="checkbox" id="checkAll">
                                                <label for="checkAll"></label>
                                            </div>
                                        </th>
                                        <th width="5%">No</th>
                                        <th>Nama Item</th>
                                        <th width="10%" class="text-center">Jumlah</th>
                                        <th width="15%" class="text-right">Harga Perkiraan</th>
                                        <th width="15%" class="text-right">Subtotal</th>
                                        <th>Catatan Item</th>
                                        <th width="15%" class="text-center">Status</th>
                                        <th width="10%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($order->listOrders as $index => $item)
                                        <tr>
                                            <td>
                                                <div class="icheck-primary">
                                                    <input type="checkbox" class="item-checkbox"
                                                        id="itemCheck{{ $item->id }}" value="{{ $item->id }}"
                                                        data-status="{{ $item->status_item }}">
                                                    <label for="itemCheck{{ $item->id }}"></label>
                                                </div>
                                            </td>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $item->nama_item }}</td>
                                            <td class="text-center">{{ $item->jumlah }}</td>
                                            <td class="text-right">
                                                @if ($item->harga_perkiraan)
                                                    Rp {{ number_format($item->harga_perkiraan, 0, ',', '.') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                @if ($item->harga_perkiraan)
                                                    Rp
                                                    {{ number_format($item->harga_perkiraan * $item->jumlah, 0, ',', '.') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $item->catatan_item ?? '-' }}</td>
                                            <td class="text-center">
                                                @if ($item->status_item == 'pending')
                                                    <span class="badge badge-warning">Menunggu</span>
                                                @elseif($item->status_item == 'dikirim')
                                                    <span class="badge badge-info">Dikirim</span>
                                                @elseif($item->status_item == 'diterima')
                                                    <span class="badge badge-success">Diterima</span>
                                                @elseif($item->status_item == 'tidak_tersedia')
                                                    <span class="badge badge-danger">Tidak Tersedia</span>
                                                @elseif($item->status_item == 'ditransfer')
                                                    <span class="badge badge-secondary">Ditransfer</span>
                                                @elseif($item->status_item == 'dibatalkan')
                                                    <span class="badge badge-dark">Dibatalkan</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-primary btn-xs"
                                                        onclick="showUpdateStatusModal('{{ $item->id }}', '{{ $item->nama_item }}', '{{ $item->status_item }}')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>

                                                    @if ($item->status_item == 'pending' && in_array($order->status_order, ['menunggu_pengiriman', 'selesai']))
                                                        <button type="button" class="btn btn-warning btn-xs"
                                                            onclick="showTransferModal('{{ $item->id }}', '{{ $item->nama_item }}')">
                                                            <i class="fas fa-exchange-alt"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center">Tidak ada item dalam pesanan.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3">Total</th>
                                        <th class="text-center">{{ $order->listOrders->sum('jumlah') }}</th>
                                        <th></th>
                                        <th class="text-right">
                                            @php
                                                $totalHarga = 0;
                                                foreach ($order->listOrders as $item) {
                                                    if ($item->harga_perkiraan) {
                                                        $totalHarga += $item->harga_perkiraan * $item->jumlah;
                                                    }
                                                }
                                            @endphp
                                            @if ($totalHarga > 0)
                                                Rp {{ number_format($totalHarga, 0, ',', '.') }}
                                            @else
                                                -
                                            @endif
                                        </th>
                                        <th colspan="3"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="col-md-6">
                        <a href="{{ route('order.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Kembali
                        </a>

                        @if ($order->status_order == 'draft')
                            <a href="{{ route('order.edit', $order->id) }}" class="btn btn-primary ml-2">
                                <i class="fas fa-edit mr-1"></i> Edit Pesanan
                            </a>
                        @endif
                    </div>
                    <div class="col-md-6 text-right">
                        @if ($order->status_order == 'draft')
                            <a href="{{ route('order.finalize', $order->id) }}" class="btn btn-warning"
                                onclick="return confirm('Apakah Anda yakin ingin memfinalisasi pesanan ini?')">
                                <i class="fas fa-check-circle mr-1"></i> Finalisasi Pesanan
                            </a>
                        @endif

                        @if (in_array($order->status_order, ['menunggu_pengiriman', 'selesai']))
                            <a href="{{ route('order.convert-to-purchase', $order->id) }}" class="btn btn-success"
                                onclick="return confirm('Konversi pesanan ini menjadi pembelian?')">
                                <i class="fas fa-exchange-alt mr-1"></i> Konversi ke Pembelian
                            </a>
                        @endif

                        @if ($order->status_order != 'dibatalkan' && $order->status_order != 'selesai')
                            <a href="{{ route('order.cancel', $order->id) }}" class="btn btn-danger ml-2"
                                onclick="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini?')">
                                <i class="fas fa-times mr-1"></i> Batalkan Pesanan
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Update Status Banyak Item -->
<div class="modal fade" id="modalUpdateBulkStatus">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h4 class="modal-title">Update Status Item</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('order.bulk-update-status') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="order_id" value="{{ $order->id }}">
                    <div id="selectedItemsContainer"></div>

                    <div class="form-group">
                        <label for="bulk_kode_supplier">Supplier untuk Pesanan Baru:</label>
                        <select name="kode_supplier" id="bulk_kode_supplier" class="form-control" required>
                            <option value="">-- Pilih Supplier --</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}"
                                    {{ $order->kode_supplier == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->nama_supplier }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="bulk_transfer_catatan">Catatan untuk Pesanan Baru (Opsional):</label>
                        <textarea name="catatan" id="bulk_transfer_catatan" class="form-control" rows="3"
                            placeholder="Tambahkan catatan untuk pesanan baru"></textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Transfer ke Pesanan Baru</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(function() {
        // Inisialisasi tabel
        // $('.table').DataTable({
        //     "paging": true,
        //     "lengthChange": true,
        //     "searching": true,
        //     "ordering": true,
        //     "info": true,
        //     "autoWidth": false,
        //     "responsive": true,
        //     "pageLength": 10,
        //     "language": {
        //         "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json"
        //     }
        // });

        // Toggle semua checkbox
        $('#checkAll').change(function() {
            $('.item-checkbox').prop('checked', $(this).prop('checked'));
            updateBulkButtons();
        });

        // Update tombol bulk action saat checkbox berubah
        $('.item-checkbox').change(function() {
            updateBulkButtons();
        });

        // Fungsi untuk mengupdate status tombol bulk action
        function updateBulkButtons() {
            const checkedCount = $('.item-checkbox:checked').length;
            $('#btnUpdateStatus').prop('disabled', checkedCount === 0);
            $('#btnTransferItems').prop('disabled', checkedCount === 0);

            // Jika tidak ada yang dipilih, batalkan pilih semua
            if (checkedCount === 0) {
                $('#checkAll').prop('checked', false);
            }
            // Jika semua dipilih, centang pilih semua
            else if (checkedCount === $('.item-checkbox').length) {
                $('#checkAll').prop('checked', true);
            }
        }

        // Event handler untuk tombol Update Status
        $('#btnUpdateStatus').click(function() {
            const selectedItems = $('.item-checkbox:checked');
            const selectedIds = [];

            selectedItems.each(function() {
                selectedIds.push($(this).val());
            });

            // Buat input hidden untuk setiap item yang dipilih
            let inputFields = '';
            selectedIds.forEach(function(id) {
                inputFields += `<input type="hidden" name="selected_items[]" value="${id}">`;
            });

            $('#selectedItemsContainer').html(inputFields);
            $('#modalUpdateBulkStatus').modal('show');
        });

        // Event handler untuk tombol Transfer Items
        $('#btnTransferItems').click(function() {
            const selectedItems = $('.item-checkbox:checked');
            const selectedIds = [];
            const pendingItems = [];

            // Filter hanya item dengan status "pending"
            selectedItems.each(function() {
                const id = $(this).val();
                const status = $(this).data('status');

                if (status === 'pending') {
                    selectedIds.push(id);
                    pendingItems.push(id);
                }
            });

            if (pendingItems.length === 0) {
                Swal.fire({
                    title: 'Perhatian',
                    text: 'Hanya item dengan status "Menunggu" yang dapat ditransfer ke pesanan baru.',
                    icon: 'warning',
                    confirmButtonText: 'Tutup'
                });
                return;
            }

            // Buat input hidden untuk setiap item yang dipilih
            let inputFields = '';
            pendingItems.forEach(function(id) {
                inputFields += `<input type="hidden" name="selected_items[]" value="${id}">`;
            });

            $('#transferSelectedItemsContainer').html(inputFields);
            $('#modalTransferBulkItems').modal('show');
        });
    });

    // Fungsi untuk menampilkan modal update status item tunggal
    function showUpdateStatusModal(itemId, itemName, itemStatus) {
        $('#updateItemId').val(itemId);
        $('#updateItemName').text(itemName);
        $('#single_status_item').val(itemStatus);
        $('#modalUpdateStatus').modal('show');
    }

    // Fungsi untuk menampilkan modal transfer item tunggal
    function showTransferModal(itemId, itemName) {
        $('#transferItemId').val(itemId);
        $('#transferItemName').text(itemName);
        $('#modalTransferItem').modal('show');
    }
</script>Update status</label>
    <select name="status_item" id="status_item" class="form-control" required>
        <option value="pending">Menunggu</option>
        <option value="dikirim">Dikirim</option>
        <option value="diterima">Diterima</option>
        <option value="tidak_tersedia">Tidak Tersedia</option>
        <option value="dibatalkan">Dibatalkan</option>
    </select>
    </div>

    <div class="form-group">
        <label for="catatan_item">Catatan (Opsional):</label>
        <textarea name="catatan_item" id="catatan_item" class="form-control" rows="3"
            placeholder="Tambahkan catatan tentang perubahan status"></textarea>
    </div>
    </div>
    <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </div>
    </form>
    </div>
    </div>
    </div>

    <!-- Modal Update Status Item Tunggal -->
    <div class="modal fade" id="modalUpdateStatus">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h4 class="modal-title">Update Status Item</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('order.update-item-status') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="order_id" value="{{ $order->id }}">
                        <input type="hidden" name="item_id" id="updateItemId">

                        <div class="form-group">
                            <label>Nama Item:</label>
                            <p id="updateItemName" class="font-weight-bold"></p>
                        </div>

                        <div class="form-group">
                            <label for="single_status_item">Status Baru:</label>
                            <select name="status_item" id="single_status_item" class="form-control" required>
                                <option value="pending">Menunggu</option>
                                <option value="dikirim">Dikirim</option>
                                <option value="diterima">Diterima</option>
                                <option value="tidak_tersedia">Tidak Tersedia</option>
                                <option value="dibatalkan">Dibatalkan</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="single_catatan_item">Catatan (Opsional):</label>
                            <textarea name="catatan_item" id="single_catatan_item" class="form-control" rows="3"
                                placeholder="Tambahkan catatan tentang perubahan status"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Transfer Item Tunggal -->
    <div class="modal fade" id="modalTransferItem">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h4 class="modal-title">Transfer Item ke Pesanan Baru</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('order.transfer-items') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="order_id" value="{{ $order->id }}">
                        <input type="hidden" name="item_id" id="transferItemId">

                        <div class="form-group">
                            <label>Nama Item:</label>
                            <p id="transferItemName" class="font-weight-bold"></p>
                        </div>

                        <div class="form-group">
                            <label for="kode_supplier">Supplier untuk Pesanan Baru:</label>
                            <select name="kode_supplier" id="kode_supplier" class="form-control" required>
                                <option value="">-- Pilih Supplier --</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ $order->kode_supplier == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->nama_supplier }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="transfer_catatan">Catatan untuk Pesanan Baru (Opsional):</label>
                            <textarea name="catatan" id="transfer_catatan" class="form-control" rows="3"
                                placeholder="Tambahkan catatan untuk pesanan baru"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">Transfer ke Pesanan Baru</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Transfer Banyak Item -->
    <div class="modal fade" id="modalTransferBulkItems">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h4 class="modal-title">Transfer Item ke Pesanan Baru</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('order.transfer-items') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="order_id" value="{{ $order->id }}">
                        <div id="transferSelectedItemsContainer"></div>

                        <div class="form-group">
                            <label for="bulk_kode_supplier">Supplier untuk Pesanan Baru:</label>
                            <select name="kode_supplier" id="bulk_kode_supplier" class="form-control" required>
                                <option value="">-- Pilih Supplier --</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ $order->kode_supplier == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->nama_supplier }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="bulk_transfer_catatan">Catatan untuk Pesanan Baru (Opsional):</label>
                            <textarea name="catatan" id="bulk_transfer_catatan" class="form-control" rows="3"
                                placeholder="Tambahkan catatan untuk pesanan baru"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">Transfer ke Pesanan Baru</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(function() {
            // Inisialisasi tabel
            if (!$.fn.DataTable.isDataTable(this)) {
                $('.table').DataTable({
                    "paging": true,
                    "lengthChange": true,
                    "searching": true,
                    "ordering": true,
                    "info": true,
                    "autoWidth": false,
                    "responsive": true,
                    "pageLength": 10,
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json"
                    }
                });
            }

            // Toggle semua checkbox
            $('#checkAll').change(function() {
                $('.item-checkbox').prop('checked', $(this).prop('checked'));
                updateBulkButtons();
            });

            // Update tombol bulk action saat checkbox berubah
            $('.item-checkbox').change(function() {
                updateBulkButtons();
            });

            // Fungsi untuk mengupdate status tombol bulk action
            function updateBulkButtons() {
                const checkedCount = $('.item-checkbox:checked').length;
                $('#btnUpdateStatus').prop('disabled', checkedCount === 0);
                $('#btnTransferItems').prop('disabled', checkedCount === 0);

                // Jika tidak ada yang dipilih, batalkan pilih semua
                if (checkedCount === 0) {
                    $('#checkAll').prop('checked', false);
                }
                // Jika semua dipilih, centang pilih semua
                else if (checkedCount === $('.item-checkbox').length) {
                    $('#checkAll').prop('checked', true);
                }
            }

            // Event handler untuk tombol Update Status
            $('#btnUpdateStatus').click(function() {
                const selectedItems = $('.item-checkbox:checked');
                const selectedIds = [];

                selectedItems.each(function() {
                    selectedIds.push($(this).val());
                });

                // Buat input hidden untuk setiap item yang dipilih
                let inputFields = '';
                selectedIds.forEach(function(id) {
                    inputFields += `<input type="hidden" name="selected_items[]" value="${id}">`;
                });

                $('#selectedItemsContainer').html(inputFields);
                $('#modalUpdateBulkStatus').modal('show');
            });

            // Event handler untuk tombol Transfer Items
            $('#btnTransferItems').click(function() {
                const selectedItems = $('.item-checkbox:checked');
                const selectedIds = [];
                const pendingItems = [];

                // Filter hanya item dengan status "pending"
                selectedItems.each(function() {
                    const id = $(this).val();
                    const status = $(this).data('status');

                    if (status === 'pending') {
                        selectedIds.push(id);
                        pendingItems.push(id);
                    }
                });

                if (pendingItems.length === 0) {
                    Swal.fire({
                        title: 'Perhatian',
                        text: 'Hanya item dengan status "Menunggu" yang dapat ditransfer ke pesanan baru.',
                        icon: 'warning',
                        confirmButtonText: 'Tutup'
                    });
                    return;
                }

                // Buat input hidden untuk setiap item yang dipilih
                let inputFields = '';
                pendingItems.forEach(function(id) {
                    inputFields += `<input type="hidden" name="selected_items[]" value="${id}">`;
                });

                $('#transferSelectedItemsContainer').html(inputFields);
                $('#modalTransferBulkItems').modal('show');
            });
        });

        // Fungsi untuk menampilkan modal update status item tunggal
        function showUpdateStatusModal(itemId, itemName, itemStatus) {
            $('#updateItemId').val(itemId);
            $('#updateItemName').text(itemName);
            $('#single_status_item').val(itemStatus);
            $('#modalUpdateStatus').modal('show');
        }

        // Fungsi untuk menampilkan modal transfer item tunggal
        function showTransferModal(itemId, itemName) {
            $('#transferItemId').val(itemId);
            $('#transferItemName').text(itemName);
            $('#modalTransferItem').modal('show');
        }
    </script>
