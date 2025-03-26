{{-- File: resources/views/admin/page/pembelian/edit.blade.php --}}
{{-- Pencarian dengan AJAX --}}

<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Detail Pembelian</h3>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">Kode Pembelian:</dt>
                    <dd class="col-sm-8">{{ $pembelian->kode_pembelian }}</dd>

                    <dt class="col-sm-4">Tanggal:</dt>
                    <dd class="col-sm-8">
                        <form id="formTanggal" method="POST" action="{{ route('pembelian.update', $pembelian->id) }}">
                            @csrf
                            @method('PATCH')
                            <input type="date" name="tanggal_pembelian" class="form-control"
                                value="{{ $pembelian->tanggal_pembelian }}"
                                onchange="document.getElementById('formTanggal').submit()">
                        </form>
                    </dd>

                    <dt class="col-sm-4">Supplier:</dt>
                    <dd class="col-sm-8">
                        <select name="supplier" id="supplier" class="form-control">
                            <option value="">--- Pilih ---</option>
                            @foreach ($supplier as $item)
                                <option value="{{ $item->id }}">{{ $item->nama_supplier }}</option>
                            @endforeach
                        </select>

                    </dd>

                    <dt class="col-sm-4">Kategori:</dt>
                    <dd class="col-sm-8">
                        <select name="kategori" id="kategori" class="form-control" required>
                            <option value="" disabled selected style="color: #a9a9a9;">---
                                Pilih ---</option>
                            @foreach ($kategori as $item)
                                <option value="{{ $item->id }}">{{ $item->nama_kategori }}
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">
                            Pilih kategori.
                        </div>
                    </dd>

                    <dt class="col-sm-4">Keterangan:</dt>
                    <dd class="col-sm-8">
                        <form id="formKeterangan" method="POST"
                            action="{{ route('pembelian.update', $pembelian->id) }}">
                            @csrf
                            @method('PATCH')
                            <textarea name="keterangan" class="form-control" rows="2" placeholder="Keterangan pembelian"
                                onchange="document.getElementById('formKeterangan').submit()">{{ $pembelian->keterangan }}</textarea>
                        </form>
                    </dd>

                    <dt class="col-sm-4">Total Harga:</dt>
                    <dd class="col-sm-8">
                        <h4>Rp {{ number_format($pembelian->total_harga, 0, ',', '.') }}</h4>
                    </dd>
                </dl>
            </div>
            <div class="card-footer">
                <a href="{{ route('pembelian.index') }}" class="btn btn-default">Kembali</a>
                <form id="finalize-form-{{ $pembelian->id }}"
                    action="{{ route('pembelian.finalize', $pembelian->id) }}" method="POST" style="display: inline;">
                    @csrf
                    <input type="hidden" id="finalize_supplier" name="supplier" value="">
                    <input type="hidden" id="finalize_kategori" name="kategori" value="">
                    <button type="button" class="btn btn-success float-right" onclick="finalizeForm()">
                        <i class="fas fa-check"></i> Selesaikan Pembelian
                    </button>
                </form>
            </div>
        </div>

        <!-- Card Pencarian Sparepart -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Cari Sparepart</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <div class="input-group">
                        <input type="text" id="search_sparepart" class="form-control"
                            placeholder="Ketik untuk mencari sparepart..." autocomplete="off">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="button" id="btn_search">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <small class="form-text text-muted">Ketik minimal 2 karakter untuk memulai pencarian</small>
                </div>

                <div id="search_results" class="mt-3" style="max-height: 300px; overflow-y: auto; display: none;">
                    <div class="d-flex align-items-center" id="loading_indicator" style="display: none !important;">
                        <div class="spinner-border spinner-border-sm text-primary mr-2" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <span>Mencari...</span>
                    </div>
                    <div id="search_results_list" class="list-group">
                        <!-- Hasil pencarian akan ditampilkan di sini -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <!-- Perbaikan pada Form Tambah/Edit Item -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tambah Item</h3>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5><i class="icon fas fa-check"></i> Sukses!</h5>
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error') || $errors->any())
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5><i class="icon fas fa-ban"></i> Error!</h5>
                        {{ session('error') ?? $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('pembelian.add-item', $pembelian->id) }}" method="POST" id="formAddItem">
                    @csrf
                    <!-- Tambahkan input hidden untuk mode edit -->
                    <input type="hidden" name="edit_mode" id="edit_mode" value="0">
                    <input type="hidden" id="edit_detail_id" name="edit_detail_id">

                    <div class="form-group">
                        <div class="custom-control custom-radio custom-control-inline">
                            <input class="custom-control-input" type="radio" id="customRadio1" name="is_new_item"
                                value="0" checked onclick="toggleNewItem(false)">
                            <label for="customRadio1" class="custom-control-label">Restock Item</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input class="custom-control-input" type="radio" id="customRadio2" name="is_new_item"
                                value="1" onclick="toggleNewItem(true)">
                            <label for="customRadio2" class="custom-control-label">Item Baru</label>
                        </div>
                    </div>

                    <input type="hidden" name="sparepart_id" id="sparepart_id">

                    <div class="form-group">
                        <label for="nama_item">Nama Item</label>
                        <input type="text" class="form-control" id="nama_item" name="nama_item" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="jumlah">Jumlah</label>
                                <input type="number" class="form-control" id="jumlah" name="jumlah"
                                    value="1" min="1" required onchange="hitungTotal()">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="total">Total</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="text" class="form-control" id="total" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Informasi Harga</h3>
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
                                        <label for="harga_beli">Harga Beli</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Rp</span>
                                            </div>
                                            <input type="number" class="form-control" id="harga_beli"
                                                name="harga_beli" min="0" required
                                                onchange="updateHargaOtomatis(); hitungTotal();">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="harga_jual">Harga Jual</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Rp</span>
                                            </div>
                                            <input type="number" class="form-control" id="harga_jual"
                                                name="harga_jual" min="0">
                                        </div>
                                        <span id="harga_jual_diff" class="ml-2 price-difference"
                                            style="font-weight: bold;"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="harga_ecer">Harga Ecer</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Rp</span>
                                            </div>
                                            <input type="number" class="form-control" id="harga_ecer"
                                                name="harga_ecer" min="0">
                                        </div>
                                        <span id="harga_ecer_diff" class="ml-2 price-difference"
                                            style="font-weight: bold;"></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="harga_pasang">Harga Pasang</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Rp</span>
                                            </div>
                                            <input type="number" class="form-control" id="harga_pasang"
                                                name="harga_pasang" min="0">
                                        </div>
                                        <span id="harga_pasang_diff" class="ml-2 price-difference"
                                            style="font-weight: bold;"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <!-- Tombol submit dengan id dinamis -->
                            <button type="submit" class="btn btn-primary btn-block" id="submitButton">Tambah
                                Item</button>

                            <!-- Tombol batal yang hanya muncul pada mode edit -->
                            <button type="button" class="btn btn-secondary btn-block mt-2" id="cancelButton"
                                style="display: none;" onclick="cancelEdit()">
                                Batal Edit
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar Item</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 10px">#</th>
                            <th>Nama Item</th>
                            <th>Jumlah</th>
                            <th>Harga Beli</th>
                            <th>Total</th>
                            <th style="width: 85px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($details as $index => $detail)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $detail->nama_item }}</td>
                                <td>{{ $detail->jumlah }}</td>
                                <td>Rp {{ number_format($detail->harga_beli, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($detail->total, 0, ',', '.') }}</td>
                                <td>
                                    <!-- Edit Button -->
                                    <button type="button" class="btn btn-primary btn-sm mr-1 my-2"
                                        onclick="editItem({{ $detail->id }}, '{{ $detail->nama_item }}', {{ $detail->jumlah }}, {{ $detail->harga_beli }}, {{ $detail->harga_jual ?? 0 }}, {{ $detail->harga_ecer ?? 0 }}, {{ $detail->harga_pasang ?? 0 }})">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <!-- Delete Button -->
                                    <form action="{{ route('pembelian.remove-item', $detail->id) }}" method="POST"
                                        onsubmit="return confirm('Yakin hapus item ini?')"
                                        style="display: inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Belum ada item</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Fungsi untuk trigger update selisih dengan delay
    function triggerPriceDifferenceUpdate() {
        // Menggunakan setTimeout untuk memastikan nilai sudah terupdate
        setTimeout(function() {
            calculatePriceDifferences();
        }, 50);
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Elemen-elemen yang diperlukan
        const searchInput = document.getElementById('search_sparepart');
        const searchButton = document.getElementById('btn_search');
        const searchResults = document.getElementById('search_results');
        const searchResultsList = document.getElementById('search_results_list');
        const loadingIndicator = document.getElementById('loading_indicator');

        // Variabel untuk timeout pencarian
        let searchTimeout = null;

        // Fungsi untuk melakukan pencarian
        function performSearch() {
            const query = searchInput.value.trim();

            // Minimal 2 karakter untuk pencarian
            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            // Tampilkan loading indicator
            loadingIndicator.style.display = 'flex';
            searchResults.style.display = 'block';
            searchResultsList.innerHTML = '';

            // URL untuk endpoint pencarian
            const searchUrl = '{{ route('pembelian.search-spareparts-ajax') }}';

            const csrfToken = '{{ csrf_token() }}';
            // Buat objek FormData untuk mengirim data
            const formData = new FormData();
            formData.append('search', query);
            formData.append('_token', csrfToken);

            // Lakukan request AJAX
            fetch(searchUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Sembunyikan loading indicator
                    loadingIndicator.style.display = 'none';

                    // Tampilkan hasil pencarian
                    if (data.success && data.results && data.results.length > 0) {
                        searchResultsList.innerHTML = '';

                        // Loop melalui hasil pencarian dan tampilkan dalam list
                        data.results.forEach(item => {
                            const listItem = document.createElement('a');
                            listItem.href = '#';
                            listItem.className = 'list-group-item list-group-item-action';
                            listItem.innerHTML =
                                // `${item.kode_sparepart} - ${item.nama_sparepart} <span class="badge badge-info">Stok: ${item.stok_sparepart}</span>`;
                                `${item.nama_sparepart} <span class="badge badge-info">Stok: ${item.stok_sparepart}</span>`;

                            // Event listener untuk memilih sparepart
                            listItem.addEventListener('click', function(e) {
                                e.preventDefault();
                                selectSparepart(
                                    item.id,
                                    item.nama_sparepart,
                                    item.harga_beli,
                                    item.harga_jual, // Add this
                                    item.harga_ecer, // Add this
                                    item.harga_pasang);
                            });

                            searchResultsList.appendChild(listItem);
                        });
                    } else {
                        searchResultsList.innerHTML =
                            '<div class="alert alert-info">Tidak ada hasil yang ditemukan</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    loadingIndicator.style.display = 'none';
                    searchResultsList.innerHTML =
                        '<div class="alert alert-danger">Terjadi kesalahan saat mencari sparepart.</div>';
                });
        }

        // Event listener untuk input pencarian (dengan debounce)
        searchInput.addEventListener('input', function() {
            // Clear timeout sebelumnya
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }

            // Set timeout baru untuk mencegah terlalu banyak request
            searchTimeout = setTimeout(performSearch, 500);
        });

        // Event listener untuk tombol search
        searchButton.addEventListener('click', performSearch);

        // Event listener untuk tombol Enter pada field pencarian
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });

        // Inisialisasi form
        hitungTotal();

        // Tambahkan event listener untuk semua input harga
        const priceInputs = ['harga_beli', 'harga_jual', 'harga_ecer', 'harga_pasang'];
        priceInputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            // Input event (saat sedang mengetik)
            input.addEventListener('input', triggerPriceDifferenceUpdate);
            // Change event (setelah selesai edit)
            input.addEventListener('change', triggerPriceDifferenceUpdate);
            // Blur event (saat input kehilangan fokus)
            input.addEventListener('blur', triggerPriceDifferenceUpdate);
        });

        // Tambahkan onchange event ke harga_beli
        document.getElementById('harga_beli').addEventListener('input', updateHargaOtomatis);

        // Hitung selisih harga setelah semua komponen dimuat
        setTimeout(function() {
            calculatePriceDifferences();
        }, 300);
    });

    // Hitung total
    function hitungTotal() {
        var jumlah = parseFloat(document.getElementById('jumlah').value) || 0;
        var harga = parseFloat(document.getElementById('harga_beli').value) || 0;
        var total = jumlah * harga;
        document.getElementById('total').value = formatRp(total);
    }

    // Format rupiah
    function formatRp(angka) {
        return new Intl.NumberFormat('id-ID').format(angka);
    }

    function updateHargaOtomatis() {
        const hargaBeli = parseFloat(document.getElementById('harga_beli').value) || 0;
        const isNewItem = document.getElementById('customRadio2').checked;

        // Hanya update otomatis jika ini item baru
        if (isNewItem) {
            // Set nilai
            document.getElementById('harga_jual').value = hargaBeli * 1.2; // Markup 20%
            document.getElementById('harga_ecer').value = hargaBeli * 1.3; // Markup 30%
            document.getElementById('harga_pasang').value = hargaBeli; // Default sama dengan harga beli

            // Trigger event setelah nilai diupdate
            triggerPriceDifferenceUpdate();
        } else {
            // Tetap hitung selisih untuk item restock
            triggerPriceDifferenceUpdate();
        }

        // Update total juga
        hitungTotal();
    }

    // Update event listener untuk select sparepart
    $('#sparepart_id').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        if (selectedOption.val() !== '') {
            $('#nama_item').val(selectedOption.data('nama'));
            $('#harga_beli').val(selectedOption.data('harga'));

            // Tambahkan data harga lainnya jika ada di option
            if (selectedOption.data('harga-jual')) {
                $('#harga_jual').val(selectedOption.data('harga-jual'));
            }
            if (selectedOption.data('harga-ecer')) {
                $('#harga_ecer').val(selectedOption.data('harga-ecer'));
            }
            if (selectedOption.data('harga-pasang')) {
                $('#harga_pasang').val(selectedOption.data('harga-pasang'));
            }

            hitungTotal();
            triggerPriceDifferenceUpdate(); // Hitung selisih harga setelah perubahan
        }
    });

    // Fungsi untuk select sparepart dari hasil pencarian
    function selectSparepart(id, nama, harga, hargaJual, hargaEcer, hargaPasang) {
        document.getElementById('sparepart_id').value = id;
        document.getElementById('nama_item').value = nama;
        document.getElementById('harga_beli').value = harga;

        // Set harga lainnya jika tersedia
        if (hargaJual) document.getElementById('harga_jual').value = hargaJual;
        if (hargaEcer) document.getElementById('harga_ecer').value = hargaEcer;
        if (hargaPasang) document.getElementById('harga_pasang').value = hargaPasang;

        document.getElementById('nama_item').readOnly = false;
        document.getElementById('customRadio1').checked = true; // Set sebagai restock
        hitungTotal();
        triggerPriceDifferenceUpdate(); // Hitung selisih harga setelah memilih sparepart

        // Sembunyikan hasil pencarian
        document.getElementById('search_results').style.display = 'none';

        // Scroll ke form
        document.getElementById('formAddItem').scrollIntoView({
            behavior: 'smooth'
        });
    }

    // Update toggle function untuk item baru vs restock
    function toggleNewItem(isNew) {
        if (isNew) {
            document.getElementById('sparepart_id').value = '';
            document.getElementById('nama_item').value = '';
            document.getElementById('harga_beli').value = '';
            document.getElementById('harga_jual').value = '';
            document.getElementById('harga_ecer').value = '';
            document.getElementById('harga_pasang').value = '';
            document.getElementById('nama_item').readOnly = false;
        } else {
            document.getElementById('nama_item').readOnly = true;
        }

        // Hitung selisih harga setelah toggle
        triggerPriceDifferenceUpdate();
    }

    // Fungsi untuk menghitung dan menampilkan selisih harga
    function calculatePriceDifferences() {
        // Get the purchase price
        const hargaBeli = parseFloat(document.getElementById('harga_beli').value) || 0;

        // Price input fields and their respective difference display elements
        const priceFields = [{
                input: 'harga_jual',
                diffElement: 'harga_jual_diff'
            },
            {
                input: 'harga_ecer',
                diffElement: 'harga_ecer_diff'
            },
            {
                input: 'harga_pasang',
                diffElement: 'harga_pasang_diff'
            }
        ];

        // Calculate and display the difference for each price field
        priceFields.forEach(field => {
            const price = parseFloat(document.getElementById(field.input).value) || 0;
            const diff = price - hargaBeli;
            const diffElement = document.getElementById(field.diffElement);

            // Format the difference with plus sign for positive values
            let formattedDiff;
            if (diff >= 0) {
                formattedDiff = `+Rp ${formatRp(diff)}`;
                diffElement.style.color = '#28a745'; // Green for positive
            } else {
                formattedDiff = `-Rp ${formatRp(Math.abs(diff))}`;
                diffElement.style.color = '#dc3545'; // Red for negative
            }

            // Set the text
            diffElement.textContent = formattedDiff;
        });
    }

    function finalizeForm() {
        // Get current values from selectors
        const supplierId = document.getElementById('supplier').value;
        const kategoriId = document.getElementById('kategori').value;

        // Check if supplier and kategori are selected
        if (!supplierId || !kategoriId) {
            // Ganti alert biasa dengan Sweet Alert
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: 'Mohon pilih Supplier dan Kategori sebelum menyelesaikan pembelian.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        // Set values to hidden fields
        document.getElementById('finalize_supplier').value = supplierId;
        document.getElementById('finalize_kategori').value = kategoriId;

        // Ganti confirm biasa dengan Sweet Alert
        Swal.fire({
            icon: 'question',
            title: 'Konfirmasi',
            text: 'Anda yakin ingin menyelesaikan pembelian? Ini akan memproses semua item ke dalam stok sparepart dan pembelian tidak dapat diedit lagi.',
            showCancelButton: true,
            confirmButtonText: 'Ya, Selesaikan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('finalize-form-{{ $pembelian->id }}').submit();
            }
        });
    }
</script>
{{-- untuk edit --}}
<script>
    function editItem(id, nama, jumlah, hargaBeli, hargaJual, hargaEcer, hargaPasang) {
        // Set form to edit mode
        document.getElementById('edit_mode').value = '1';
        document.getElementById('edit_detail_id').value = id;

        // Fill the form with the item's data
        document.getElementById('nama_item').value = nama;
        document.getElementById('nama_item').readOnly = false;
        document.getElementById('jumlah').value = jumlah;
        document.getElementById('harga_beli').value = hargaBeli;

        // Set other price fields if they exist
        if (hargaJual) document.getElementById('harga_jual').value = hargaJual;
        if (hargaEcer) document.getElementById('harga_ecer').value = hargaEcer;
        if (hargaPasang) document.getElementById('harga_pasang').value = hargaPasang;

        // Update total and price differences
        hitungTotal();
        triggerPriceDifferenceUpdate();

        // Change button text
        document.getElementById('submitButton').textContent = 'Perbarui Item';
        document.getElementById('cancelButton').style.display = 'block';

        // Scroll to form
        document.getElementById('formAddItem').scrollIntoView({
            behavior: 'smooth'
        });
    }

    function cancelEdit() {
        // Reset form to add mode
        document.getElementById('edit_mode').value = '0';
        document.getElementById('edit_detail_id').value = '';

        // Clear the form
        document.getElementById('formAddItem').reset();
        document.getElementById('sparepart_id').value = '';

        // Set restock radio button
        document.getElementById('customRadio1').checked = true;
        toggleNewItem(false);

        // Reset button text
        document.getElementById('submitButton').textContent = 'Tambah Item';
        document.getElementById('cancelButton').style.display = 'none';
    }

    // Add an event listener to the form submission
    document.getElementById('formAddItem').addEventListener('submit', function(e) {
        // If in edit mode, change the form action
        if (document.getElementById('edit_mode').value === '1') {
            const detailId = document.getElementById('edit_detail_id').value;
            this.action = "{{ route('pembelian.index') }}/update-item/" + detailId;
            // Add method override for PUT/PATCH
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'PATCH';
            this.appendChild(methodField);
        }
    });
</script>
