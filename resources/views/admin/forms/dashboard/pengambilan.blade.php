<form action="{{ route('update_pengembalian', $pengambilanKode->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="formTakeOut d-none">
        <div class="input-group my-2">
            <label class="input-group-text" for="id_kategorilaci">Penyimpanan</label>
            <select name="id_kategorilaci" class="form-control" required>
                <option value="" disabled selected>--Pilih Kategori Laci--</option>
                @foreach ($listLaci as $kategori)
                    <option value="{{ $kategori->id }}">{{ $kategori->name_laci }}</option>
                @endforeach
            </select>
        </div>
        {{-- <input type="text" id="jmldevices" class="form-control" value="122" readonly /> --}}
        <input type="text" value="" name="totalharga" class="form-control totalharga" hidden>
        <input type="hidden" id="pengambilan-id" value="{{ $pengambilanKode->id }}">

        @php
            $total_part_penjualan = 0;
            $totalitem = 0;
        @endphp
        @foreach ($detailsparepart as $detailpart)
            @php
                $totalitem += $detailpart->qty_sparepart;
                $total_part_penjualan += $detailpart->detail_harga_jual * $detailpart->qty_sparepart;
            @endphp
        @endforeach
        <div class="input-group my-2">
            <button class="btn btn-success" data-toggle="modal" data-target="#modal_pengambilan"><i
                    class="fas fa-plus"></i></button>
            <button class="input-group-text btn-primary" data-toggle="modal" data-target="#detail_pengambilan"
                for="Item" id="detail-button" data-id="{{ $pengambilanKode->id }}">Detail</button>
            <input type="number" id="jmlitem" class="form-control" readonly />
            <input type="date" value="{{ date('Y-m-d') }}" name="tgl_pengambilan" id="tgl_pengambilan"
                class="form-control" readonly>
        </div>
        <div class="view-gtotal"
            style="background-color: #e3ff96;border-radius: 5px ;height: 100px;display: flex; align-items: center; justify-content: center;">
            <h2><b>
                    <div id="gtotal-ambil"></div>
                    <input hidden name="total_pengambilan" id="total_pengambilan">
                </b>
            </h2>
        </div>
        <div class="input-group my-2">
            <label class="input-group-text" for="nama_pengambilan">Nama</label>
            <input type="text" name="nama_pengambilan" id="nama_pengambilan" class="form-control" required />
        </div>
        <div class="input-group my-2">
            <label class="input-group-text" for="total_bayar">Bayar</label>
            <input type="number" name="total_bayar" id="total_bayar" class="form-control total_bayar" hidden />
            <input type="text" name="in_bayar" id="in_bayar" class="form-control in_bayar" required />
        </div>
        <span style="display:none;" id="kembalian-value">Rp.
            0,-</span>
        <div class="d-flex align-item-center">
            <button type="submit" name="simpan" value="newbayar" class="btn btn-primary form-control">Simpan</button>
        </div>
    </div>
</form>
{{-- modal pencarian device --}}
<div class="modal fade" id="modal_pengambilan">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalTitle">List Device Selesai</h4>
                <div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

            </div>
            <div class="modal-body">
                <!-- Formulir untuk menambah/edit data sparepart -->

                <div class="card">
                    <div class="card-body"style="max-height: 300px; overflow-y: auto;">
                        <table class="table" id="TABLES_2">
                            <thead>
                                <th>No</th>
                                <th>Kode Service</th>
                                <th>Nama</th>
                                <th>No Telp</th>
                                <th>Unit</th>
                                <th>Keterangan</th>
                                <th>Harga</th>
                                <th>Aksi</th>
                            </thead>
                            <tbody>
                                @foreach ($done_service as $item)
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item->kode_service }}</td>
                                        <td>{{ $item->nama_pelanggan }}</td>
                                        <td>{{ $item->no_telp }}</td>
                                        <td>{{ $item->type_unit }}</td>
                                        <td>{{ $item->keterangan }}</td>
                                        <td>Rp.{{ number_format($item->total_biaya) }},-</td>
                                        <td>
                                            <form class="ambil-form"
                                                action="{{ route('store_detail_pengembalian', $pengambilanKode->id) }}"
                                                method="POST">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="id_service" id="id_service"
                                                    value="{{ $item->id }}">
                                                <button type="submit" name="ambil" id="ambil"
                                                    class="btn btn-success btn-sm">Ambil</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
{{-- modal end pencarian device --}}
{{-- modal detail device --}}
<div class="modal fade" id="detail_pengambilan" data-idp="">{{-- data idnya dari js --}}
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalTitle">Detail pengambilan</h4>
                <div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body">
                <!-- Formulir untuk menambah/edit data sparepart -->

                <div class="card">
                    <div class="card-body"style="max-height: 300px; overflow-y: auto;">
                        <table class="table" id="TABLES_1">
                            <thead>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Unit</th>
                                <th>Ket</th>
                                <th>Harga</th>
                                <th>Dp</th>
                                <th>Sisa Bayar</th>
                                <th>Aksi</th>
                            </thead>
                            <tbody id="services-tbody">

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
{{-- modal end detail device --}}
<script>
    $(document).on('submit', '.ambil-form', function(e) {
        e.preventDefault(); // Mencegah form dari reload halaman

        const form = $(this);
        const url = form.attr('action'); // Mengambil URL dari form

        $.ajax({
            url: url,
            type: 'POST', // Menggunakan POST sesuai dengan method yang digunakan
            data: form.serialize(),
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Data ditambahkan',
                    text: response.message,
                    showConfirmButton: false,
                    timer: 2500
                });
                // Mengisi input jumlah data
                $('#jmlitem').val(response.jumlahData);


                // Variabel untuk menyimpan total
                let totalPengambilan = 0;

                // Menghitung total biaya - DP untuk setiap item
                $.each(response.pengambilanServices, function(index, item) {
                    const totalItem = item.total_biaya - item
                        .dp; // Menghitung total untuk item ini
                    totalPengambilan += totalItem; // Menambahkan ke total keseluruhan
                });

                // Mengisi nilai total ke input hidden
                $('#gtotal-ambil').text('Rp. ' + new Intl.NumberFormat().format(totalPengambilan));
                $('.totalharga').val(totalPengambilan);

            },
            error: function(xhr) {
                alert('Terjadi kesalahan saat mengirim data.');
            }
        });
    });

    $(document).ready(function() {
        // Saat modal pengambilan dibuka ulang, reset tombol Ambil
        $('#modal_pengambilan').on('shown.bs.modal', function() {
            // Aktifkan semua tombol Ambil
            $('.ambil-form button[type="submit"]').each(function() {
                $(this).removeClass('disabled').prop('disabled', false);
            });
        });

        // Tangani event submit untuk mencegah pengiriman ganda
        $('.ambil-form').on('submit', function(event) {
            const form = $(this);
            const button = form.find('button[type="submit"]');

            // Cek jika tombol sudah disabled
            if (button.prop('disabled')) {
                event.preventDefault(); // Cegah pengiriman ulang
                return;
            }

            // Disable tombol setelah klik pertama
            button.addClass('disabled').prop('disabled', true);
        });
    });
</script>
<script>
    $(document).on('click', '#detail-button', function() {
        var pengambilanId = $(this).data('id'); // Mengambil ID dari data attribute

        $('#detail_pengambilan').data('idp', pengambilanId); // Menyisipkan ID ke modal


        $.ajax({
            url: '/services/detail/' + pengambilanId, // URL yang sesuai
            type: 'GET',
            success: function(data) {
                var newRows = '';
                $.each(data.pengambilanServices, function(index, item) {
                    newRows += '<tr>';
                    newRows += '<td>' + (index + 1) + '</td>';
                    newRows += '<td>' + item.nama_pelanggan + '</td>';
                    newRows += '<td>' + item.type_unit + '</td>';
                    newRows += '<td>' + item.keterangan + '</td>';
                    newRows += '<td>Rp. ' + item.total_biaya.toLocaleString() + ',-</td>';
                    newRows += '<td>Rp. ' + item.dp.toLocaleString() + ',-</td>';
                    newRows += '<td>Rp. ' + (item.total_biaya - item.dp).toLocaleString() +
                        ',-</td>';
                    newRows += '<td>';
                    newRows +=
                        '<button class="btn btn-danger btn-sm delete-btn" data-id="' + item
                        .id + '">';
                    newRows += '<i class="fas fa-trash"></i>';
                    newRows += '</button>';
                    newRows += '</td>';
                    newRows += '</tr>';
                });

                // Menghapus isi sebelumnya dan menambahkan yang baru

                $('#services-tbody').empty().append(newRows);
            },
            error: function(xhr) {
                alert('Terjadi kesalahan saat mengambil detail.');
            }
        });
    });

    // Event Listener untuk tombol hapus
    $(document).on('click', '.delete-btn', function() {
        var serviceId = $(this).data('id'); // Ambil ID dari tombol
        const pengambilanId = $('#pengambilan-id').val();
        Swal.fire({
            title: 'Apakah Kamu Yakin?',
            text: `Menghapus data ini?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Tidak, batalkan'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/pengembalian/' +
                        serviceId + '/detail_destroy', // Endpoint API untuk penghapusan
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}', // Token CSRF untuk keamanan
                        id_service: serviceId
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Data berhasil dihapus!',
                        });
                        // Hapus baris dari tabel tanpa reload halaman
                        $('button[data-id="' + serviceId + '"]').closest('tr').remove();
                        $.ajax({
                            url: '/pengembalian/' + pengambilanId +
                                '/pengambilan_detail', // Sesuaikan dengan rute backend
                            type: 'GET',
                            success: function(response) {
                                $('#jmlitem').val(response
                                    .jumlahData); // Perbarui jumlah item

                                let totalPengambilan = 0;
                                $.each(response.pengambilanServices, function(
                                    index, item) {
                                    const totalItem = item.total_biaya -
                                        item.dp;
                                    totalPengambilan += totalItem;
                                });

                                $('#gtotal-ambil').text('Rp. ' + new Intl
                                    .NumberFormat()
                                    .format(totalPengambilan));
                                $('.totalharga').val(
                                    totalPengambilan); // Perbarui total harga
                            },
                            error: function(xhr) {
                                alert(
                                    'Terjadi kesalahan saat memperbarui data.'
                                );
                            }
                        });
                    },
                    error: function(xhr) {
                        alert('Terjadi kesalahan saat menghapus data.');
                    }
                });
            }
        });
    });
</script>
