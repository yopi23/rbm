<!-- View: admin.page.list_order -->
<div class="card card-success card-outline">
    <div class="card-header">
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
    </div>
</div>

{{-- list all --}}
<div class="card card-success card-outline">
    <div class="card-header">
        <h3>Detail order restock</h3>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane active" id="stok">

                <div class="row">
                    <div class="col-md-12">
                        <table class="table" id="TABLES_1">
                            <!-- resources/views/detail_order/index.blade.php -->

                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama Barang</th>
                                    <th>Qty</th>
                                    <th>Beli Terakhir</th>
                                    <th>Pasang Terakhir</th>
                                    <th>Ecer Terakhir</th>
                                    <th>Jasa Terakhir</th>
                                    <th>Nama Supplier</th>
                                    <th>Tindakan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $no = 1;
                                @endphp
                                @foreach ($detailOrders as $detail)
                                    <tr data-id-barang="{{ $detail->id_barang }}">
                                        <td>{{ $no++ }}</td>
                                        <td class="editable" data-column="nama_barang" contenteditable="true">
                                            {{ $detail->nama_barang }}</td>
                                        <td class="editable" data-column="qty" contenteditable="true">
                                            {{ $detail->qty }}</td>
                                        <td class="editable" data-column="beli_terakhir" contenteditable="true">
                                            {{ number_format($detail->beli_terakhir) }}</td>
                                        <td class="editable" data-column="pasang_terakhir" contenteditable="true">
                                            {{ number_format($detail->pasang_terakhir) }}</td>
                                        <td class="editable" data-column="ecer_terakhir" contenteditable="true">
                                            {{ number_format($detail->ecer_terakhir) }}</td>
                                        <td class="editable" data-column="jasa_terakhir" contenteditable="true">
                                            {{ number_format($detail->jasa_terakhir) }}</td>
                                        <!-- Menampilkan data supplier -->
                                        <td>{{ $detail->order->supplier->nama_supplier ?? 'Tidak ada supplier' }}</td>
                                        <td>
                                            <button class="btn btn-primary save-btn">Simpan</button>
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
{{-- list all --}}
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

<script>
    $(document).ready(function() {
        // Ketika tombol Simpan di klik
        $(".save-btn").on("click", function() {
            // Mendapatkan data dari row yang sedang di-edit
            var row = $(this).closest('tr');
            var id = row.data('id-barang'); // Ambil ID dari row
            var data = {};

            // Mengambil data dari kolom yang dapat diedit
            row.find('.editable').each(function() {
                var column = $(this).data('column');
                var value = $(this).text(); // Ambil nilai yang sudah diedit
                // Bersihkan nilai dari spasi, newline, dan koma (untuk angka)
                value = value.trim().replace(/[\n\r]/g, '').replace(/,/g, '');

                // Konversi nilai ke angka jika kolom tersebut adalah angka
                if (column === 'beli_terakhir' || column === 'pasang_terakhir' || column ===
                    'ecer_terakhir' || column === 'jasa_terakhir' || column === 'qty') {
                    value = parseFloat(value); // Mengonversi ke angka
                }

                data[column] = value; // Simpan data yang sudah diproses

            });
            // Kirim data ke server menggunakan AJAX
            $.ajax({
                url: '/detail-order/update/' + id, // URL untuk menyimpan perubahan
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}', // Token CSRF untuk keamanan
                    data: data // Data yang akan dikirim
                },
                success: function(response) {
                    // Menangani respons dari server (misalnya, memberi tahu pengguna jika sukses)
                    var button = row.find('.save-btn');
                    button.text('Terupdate'); // Ubah teks tombol
                    button.removeClass('btn-primary') // Hapus kelas btn-primary
                        .addClass(
                            'btn-secondary') // Tambahkan kelas btn-secondary (abu-abu)
                        .prop('disabled', true);
                    alert('Data berhasil diperbarui!');
                },
                error: function() {
                    // Menangani error jika terjadi kesalahan
                    alert('Terjadi kesalahan, coba lagi!');
                }
            });
        });
    });
</script>
