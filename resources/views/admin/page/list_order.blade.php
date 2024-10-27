<!-- View: admin.page.list_order -->

<div class="btn-group mb-3" role="group">
    @foreach ($activeSpls as $spl)
        <a href="{{ route('orders.view', ['filter_spl' => $spl->id]) }}"
            class="btn btn-secondary {{ $selectedSplId == $spl->id ? 'active' : '' }}">
            {{ $spl->nama_supplier }}
        </a>
    @endforeach
</div>


<table class="table">
    <thead>
        <tr>
            <th>No</th>
            <th>Nama Barang</th>
            <th>Qty</th>
            {{-- <th>Opsi</th> --}}
        </tr>
    </thead>
    <tbody>

        @foreach ($orders as $index => $order)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    {{ $order->nama_barang }}
                </td>
                <td>
                    ={{ $order->qty }}
                </td>
                {{-- <td>
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#editModal"
                        data-order-id="{{ $order->order->kode_order }}" data-spl-id="{{ $order->order->spl_kode }}">
                        <i class="fas fa-edit"></i>
                    </button>
                </td> --}}
            </tr>
        @endforeach
    </tbody>
</table>
<form method="POST" action="{{ route('orders.updateStatus') }}">
    @csrf
    @isset($order->order)
        <input type="hidden" name="kode_order" value="{{ $order->order->kode_order }}">
        <button type="submit" class="btn btn-primary"> Selesai</button>
    @endisset
</form>

@if ($orders->isEmpty())
    <div class="alert alert-warning text-center">
        Tidak ada data order untuk SPL ini.
    </div>
@endif
<!-- Modal Edit -->
{{-- <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="editForm" method="POST" action="{{ route('orders.updateSpl') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit SPL</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="order_id">
                    <div class="form-group">
                        <label for="spl_id">Pilih SPL</label>
                        <select name="spl_id" id="spl_id" class="form-control" required>
                            @foreach ($activeSpls as $spl)
                                <option value="{{ $spl->id }}">
                                    {{ $spl->nama_supplier }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $('#editModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget); // Tombol yang memicu modal
        var orderId = button.data('order-id'); // Ambil kode order
        var splId = button.data('spl-id'); // Ambil SPL ID

        var modal = $(this);
        modal.find('#order_id').val(orderId);
        modal.find('#spl_id').val(splId); // Set SPL yang dipilih
    });
</script> --}}
