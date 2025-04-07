<!-- resources/views/admin/page/order/edit.blade.php -->

<div class="row">
    <div class="col-md-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-edit mr-1"></i>
                    Edit Pesanan #{{ $order->kode_order }}
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Daftar Item dalam Pesanan -->
                <div class="row mt-4 mb-3">
                    <div class="col-md-8">
                        <h4>Daftar Item dalam Pesanan</h4>
                    </div>
                    <div class="col-md-4 text-right">
                        <a href="{{ route('order.finalize', $order->id) }}" class="btn btn-primary"
                            onclick="return confirm('Apakah Anda yakin ingin memfinalisasi pesanan ini?')">
                            <i class="fas fa-check-circle mr-1"></i> Finalisasi Pesanan
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover orderItemsTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Item</th>
                                <th class="text-center">Jumlah</th>
                                <th class="text-right">Harga Perkiraan</th>
                                <th>Catatan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($listItems as $index => $item)
                                <tr>
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
                                    <td>{{ $item->catatan_item ?? '-' }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-primary edit-item-btn"
                                            data-id="{{ $item->id }}" data-nama="{{ $item->nama_item }}"
                                            data-jumlah="{{ $item->jumlah }}" data-harga="{{ $item->harga_perkiraan }}"
                                            data-catatan="{{ $item->catatan_item }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="{{ route('order.remove-item', $item->id) }}"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Apakah Anda yakin ingin menghapus item ini?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">Belum ada item dalam pesanan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                @if (count($listItems) > 0)
                    <div class="text-center">
                        <button id="copy-all-for-wa" class="btn btn-info">
                            <i class="fas fa-copy"></i> Copy Semua Item untuk WhatsApp
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>


    <div class="col-md-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-edit mr-1"></i>
                    Edit Pesanan #{{ $order->kode_order }}
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
                        <div class="form-group">
                            <label>Tanggal Pesanan:</label>
                            <p class="form-control-static">{{ date('d/m/Y', strtotime($order->tanggal_order)) }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Status:</label>
                            <p class="form-control-static">
                                <span class="badge badge-secondary">Draft</span>
                            </p>
                        </div>
                    </div>
                </div>

                <form action="{{ route('order.update', $order->id) }}" method="POST" class="mb-4">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="kode_supplier">Supplier:</label>
                                <select class="form-control" id="kode_supplier" name="kode_supplier" required>
                                    <option value="">-- Pilih Supplier --</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}"
                                            {{ $order->kode_supplier == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->nama_supplier }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="catatan">Catatan Pesanan:</label>
                                <textarea class="form-control" id="catatan" name="catatan" rows="2">{{ $order->catatan }}</textarea>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Update Pesanan
                    </button>
                </form>

                <hr>

                <!-- Tambah Item ke Pesanan -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <h4>Tambah Item ke Pesanan</h4>
                    </div>
                </div>

                <form action="{{ route('order.add-item', $order->id) }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="search_item">Cari Sparepart:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="search_item"
                                        placeholder="Ketik nama sparepart...">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" id="btnSearch">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="nama_item">Nama Item:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="nama_item" name="nama_item"
                                        required>
                                    <input type="hidden" id="sparepart_id" name="sparepart_id">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="jumlah">Jumlah:</label>
                                <input type="number" class="form-control" id="jumlah" name="jumlah"
                                    min="1" value="1" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="harga_perkiraan">Harga Perkiraan:</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" class="form-control" id="harga_perkiraan"
                                        name="harga_perkiraan" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="catatan_item">Catatan Item:</label>
                                <input type="text" class="form-control" id="catatan_item" name="catatan_item">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-plus mr-1"></i> Tambah Item
                            </button>
                        </div>
                    </div>
                </form>

                <div id="searchResults" class="mt-3" style="display: none;">
                    <h5>Hasil Pencarian</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama</th>
                                    <th>Stok</th>
                                    <th>Harga Beli</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="searchResultsBody">
                                <!-- Hasil pencarian akan ditampilkan di sini -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <hr>

                <!-- Rekomendasi Item Stok Rendah -->
                <div class="row mt-4 mb-3">
                    <div class="col-md-12">
                        <h4>Item dengan Stok Rendah</h4>
                        <p class="text-muted">Berikut adalah item dengan stok di bawah 10 yang mungkin perlu dipesan.
                        </p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-sm orderItemsTable">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Sparepart</th>
                                <th class="text-center">Stok</th>
                                <th>Supplier</th>
                                <th class="text-right">Harga Beli</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lowStockItems as $item)
                                <tr
                                    class="{{ $item->stok_sparepart <= 3 ? 'bg-danger text-white' : ($item->stok_sparepart <= 5 ? 'bg-warning' : '') }}">
                                    <td>{{ $item->kode_sparepart }}</td>
                                    <td>{{ $item->nama_sparepart }}</td>
                                    <td class="text-center font-weight-bold">{{ $item->stok_sparepart }}</td>
                                    <td>{{ $item->supplier ? $item->supplier->nama_supplier : 'Tidak ada' }}</td>
                                    <td class="text-right">Rp {{ number_format($item->harga_beli, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <form action="{{ route('order.add-low-stock-item', $order->id) }}"
                                            method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="sparepart_id" value="{{ $item->id }}">
                                            <input type="hidden" name="jumlah" value="10">
                                            <button type="submit" class="btn btn-sm btn-info">
                                                <i class="fas fa-plus-circle mr-1"></i> Tambah ke Pesanan
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">Tidak ada item dengan stok rendah.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <hr>


            </div>
        </div>
    </div>

    <!-- Modal Edit Item -->
    <div class="modal fade" id="editItemModal" tabindex="-1" aria-labelledby="editItemModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editItemForm" action="" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editItemModalLabel">Edit Item Pesanan</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="edit_nama_item">Nama Item:</label>
                            <input type="text" class="form-control" id="edit_nama_item" name="nama_item"
                                required>
                        </div>
                        <div class="form-group">
                            <label for="edit_jumlah">Jumlah:</label>
                            <input type="number" class="form-control" id="edit_jumlah" name="jumlah"
                                min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_harga_perkiraan">Harga Perkiraan:</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input type="number" class="form-control" id="edit_harga_perkiraan"
                                    name="harga_perkiraan" min="0">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit_catatan_item">Catatan Item:</label>
                            <input type="text" class="form-control" id="edit_catatan_item" name="catatan_item">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(function() {
            $('.orderItemsTable').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "pageLength": 10,
                "language": {
                    "lengthMenu": "Tampilkan _MENU_ data per halaman",
                    "zeroRecords": "Tidak ada data yang ditemukan",
                    "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    "infoEmpty": "Tidak ada data yang tersedia",
                    "infoFiltered": "(difilter dari _MAX_ total data)",
                    "search": "Cari:",
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": ">>",
                        "previous": "<<"
                    }
                }
            });

            // Inisialisasi pencarian
            $('#btnSearch').click(function() {
                const searchTerm = $('#search_item').val();
                if (searchTerm.length < 2) {
                    alert('Masukkan minimal 2 karakter untuk mencari');
                    return;
                }

                searchSpareparts(searchTerm);
            });

            // Trigger pencarian dengan tombol Enter
            $('#search_item').keypress(function(e) {
                if (e.which === 13) {
                    $('#btnSearch').click();
                    e.preventDefault();
                }
            });

            // Edit item modal
            $('.edit-item-btn').click(function() {
                const id = $(this).data('id');
                const nama = $(this).data('nama');
                const jumlah = $(this).data('jumlah');
                const harga = $(this).data('harga');
                const catatan = $(this).data('catatan');

                $('#edit_nama_item').val(nama);
                $('#edit_jumlah').val(jumlah);
                $('#edit_harga_perkiraan').val(harga);
                $('#edit_catatan_item').val(catatan);

                $('#editItemForm').attr('action', "{{ url('admin/order/update-item') }}/" + id);
                $('#editItemModal').modal('show');
            });
        });

        // Fungsi untuk mencari sparepart
        function searchSpareparts(searchTerm) {
            $.ajax({
                url: "{{ url('admin/sparepart/search-ajax') }}",
                type: "GET",
                data: {
                    search: searchTerm
                },
                dataType: "json",
                beforeSend: function() {
                    $('#searchResultsBody').html(
                        '<tr><td colspan="5" class="text-center">Mencari...</td></tr>');
                    $('#searchResults').show();
                },
                success: function(response) {
                    if (response.success && response.results.length > 0) {
                        let html = '';
                        response.results.forEach(function(item) {
                            html += `
                            <tr>
                                <td>${item.kode_sparepart}</td>
                                <td>${item.nama_sparepart}</td>
                                <td class="text-center">${item.stok_sparepart}</td>
                                <td>Rp ${numberWithCommas(item.harga_beli)}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-success"
                                        onclick="selectSparepart('${item.id}', '${item.nama_sparepart}', ${item.harga_beli})">
                                        <i class="fas fa-check mr-1"></i> Pilih
                                    </button>
                                </td>
                            </tr>
                        `;
                        });
                        $('#searchResultsBody').html(html);
                    } else {
                        $('#searchResultsBody').html(
                            '<tr><td colspan="5" class="text-center">Tidak ada hasil yang ditemukan</td></tr>'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    $('#searchResultsBody').html(
                        '<tr><td colspan="5" class="text-center">Terjadi kesalahan saat mencari</td></tr>');
                }
            });
        }

        // Fungsi untuk memilih sparepart dari hasil pencarian
        function selectSparepart(id, nama, harga) {
            $('#sparepart_id').val(id);
            $('#nama_item').val(nama);
            $('#harga_perkiraan').val(harga);
            $('#searchResults').hide();
        }

        // Format angka dengan separator ribuan
        function numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
    </script>
    <!-- Script untuk fungsi menyalin semua item ke clipboard -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tombol copy semua
            const copyAllButton = document.getElementById('copy-all-for-wa');
            if (copyAllButton) {
                copyAllButton.addEventListener('click', function() {
                    // Membuat array untuk menyimpan semua data
                    const items = [];

                    // Mengumpulkan semua data dari tabel
                    @foreach ($listItems as $index => $item)
                        items.push({
                            no: {{ $index + 1 }},
                            nama: "{{ $item->nama_item }}",
                            jumlah: {{ $item->jumlah }}
                        });
                    @endforeach

                    // Format teks untuk WhatsApp dengan baris baru
                    let copyText = "Daftar Pesanan:\n";
                    items.forEach(item => {
                        copyText += item.no + ". " + item.nama + " - " + item.jumlah + "\n";
                    });

                    // Menyalin ke clipboard
                    navigator.clipboard.writeText(copyText).then(function() {
                        // Pemberitahuan sukses
                        alert('Berhasil menyalin daftar pesanan!');
                    }).catch(function() {
                        alert('Gagal menyalin teks. Silakan coba lagi.');
                    });
                });
            }
        });
    </script>
