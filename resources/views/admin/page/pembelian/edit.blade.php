{{-- File: resources/views/admin/page/pembelian/edit.blade.php --}}
{{-- @extends('admin.layout.blank_page')

@section('content') --}}
<div x-data="pembelianForm()">
    <div class="row">
        <div class="col-md-5">
            {{-- Card Detail Pembelian --}}
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
                            <form id="formTanggal" method="POST"
                                action="{{ route('pembelian.update', $pembelian->id) }}">
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
                                    <option value="{{ $item->id }}"
                                        {{ $pembelian->supplier == $item->nama_supplier ? 'selected' : '' }}>
                                        {{ $item->nama_supplier }}
                                    </option>
                                @endforeach
                            </select>
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
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Metode Pembayaran</label>
                                <select id="metode_pembayaran" class="form-control">
                                    <option value="Lunas">Lunas (Cash)</option>
                                    <option value="Hutang">Hutang (Tempo)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4" id="jatuh_tempo_div" style="display: none;">
                            <div class="form-group">
                                <label>Tanggal Jatuh Tempo</label>
                                <input type="date" id="tgl_jatuh_tempo" class="form-control"
                                    value="{{ now()->addDays(30)->format('Y-m-d') }}">
                            </div>
                        </div>
                    </div>
                    <hr>
                    <a href="{{ route('pembelian.index') }}" class="btn btn-default">Kembali</a>
                    <form id="finalize-form-{{ $pembelian->id }}"
                        action="{{ route('pembelian.finalize', $pembelian->id) }}" method="POST"
                        style="display: inline;">
                        @csrf
                        <input type="hidden" id="finalize_supplier" name="supplier" value="">
                        <input type="hidden" id="finalize_metode" name="metode_pembayaran" value="Lunas">
                        <input type="hidden" id="finalize_jatuh_tempo" name="tgl_jatuh_tempo" value="">
                        <button type="button" class="btn btn-success float-right" @click="finalizeForm()">
                            <i class="fas fa-check"></i> Selesaikan Pembelian
                        </button>
                    </form>
                </div>
            </div>

            {{-- Card Pencarian Sparepart --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Cari Varian Sparepart (Restock)</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <div class="input-group">
                            <input type="text" id="search_sparepart" class="form-control"
                                placeholder="Ketik untuk mencari varian..." autocomplete="off"
                                @input.debounce.500ms="performSearch($event)">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button" @click="performSearch($event, true)">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted">Ketik minimal 2 karakter untuk memulai pencarian</small>
                    </div>

                    <div id="search_results" class="mt-3" style="max-height: 300px; overflow-y: auto; display: none;">
                        <div class="d-flex align-items-center" id="loading_indicator"
                            style="display: none !important;">
                            <div class="spinner-border spinner-border-sm text-primary mr-2" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <span>Mencari...</span>
                        </div>
                        <div id="search_results_list" class="list-group">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            {{-- Form Tambah/Edit Item --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title" id="formItemTitle">Tambah Item</h3>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h5><i class="icon fas fa-check"></i> Sukses!</h5>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <h5><i class="icon fas fa-ban"></i> Error!</h5>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('pembelian.add-item', $pembelian->id) }}" method="POST" id="formAddItem"
                        @submit="isSubmitting = true">
                        @csrf
                        <input type="hidden" name="edit_mode" id="edit_mode" value="0">
                        <input type="hidden" id="edit_detail_id" name="edit_detail_id">

                        <div class="form-group">
                            <div class="custom-control custom-radio custom-control-inline">
                                <input class="custom-control-input" type="radio" id="customRadio1"
                                    name="is_new_item" value="0" checked @click="toggleNewItem(false)">
                                <label for="customRadio1" class="custom-control-label">Restock Varian</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input class="custom-control-input" type="radio" id="customRadio2"
                                    name="is_new_item" value="1" @click="toggleNewItem(true)">
                                <label for="customRadio2" class="custom-control-label">Item Baru</label>
                            </div>
                        </div>

                        <input type="hidden" name="product_variant_id" id="product_variant_id">

                        <div class="form-group">
                            <label for="nama_item">Nama Item Dasar</label>
                            <input type="text" class="form-control" id="nama_item" name="nama_item"
                                placeholder="Contoh: LCD iPhone 11" required>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="item_kategori">Kategori</label>
                                    <select name="item_kategori" id="item_kategori" class="form-control" required
                                        x-model="selectedCategory" @change="fetchAttributes()">
                                        <option value="">--- Pilih Kategori ---</option>
                                        @foreach ($kategori as $item)
                                            <option value="{{ $item->id }}">{{ $item->nama_kategori }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- ATRIBUT DINAMIS UNTUK ITEM BARU --}}
                        <div id="dynamic-attributes" class="row">
                            <div x-show="isLoading" class="col-12 text-muted mb-2">
                                <i class="fas fa-spinner fa-spin"></i> Memuat varian...
                            </div>
                            <template x-for="attribute in attributes" :key="attribute.id">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label :for="'attribute_' + attribute.id" x-text="attribute.name"></label>
                                        <select :name="'attributes[' + attribute.id + ']'"
                                            :id="'attribute_' + attribute.id" class="form-control">
                                            <option value="">--- Pilih <span
                                                    x-text="attribute.name.toLowerCase()"></span> ---</option>
                                            <template x-for="value in attribute.values" :key="value.id">
                                                <option :value="value.id" x-text="value.value"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="jumlah">Jumlah</label>
                                    <input type="number" class="form-control" id="jumlah" name="jumlah"
                                        value="1" min="1" required @input="hitungTotal()">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="harga_beli">Harga Beli Satuan (Modal)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input type="number" class="form-control" id="harga_beli" name="harga_beli"
                                            min="0" required @input="hitungTotal()">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="total">Total Harga Beli</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input type="text" class="form-control" id="total" readonly>
                            </div>
                            <small class="form-text text-info">
                                Harga Jual (Ecer, Grosir, Pasang) akan dihitung otomatis oleh sistem berdasarkan aturan
                                margin per kategori saat pembelian diselesaikan.
                            </small>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-block"
                                    id="submitButton":disabled="isSubmitting">
                                    {{-- Teks tombol akan berubah saat proses submit --}}
                                    <span x-show="!isSubmitting">
                                        <i class="fas fa-plus"></i> Tambah Item
                                    </span>
                                    <span x-show="isSubmitting">
                                        <i class="fas fa-spinner fa-spin"></i> Menyimpan...
                                    </span>
                                </button>
                                <button type="button" class="btn btn-secondary btn-block mt-2" id="cancelButton"
                                    style="display: none;" @click="cancelEdit()">Batal Edit</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Tabel Daftar Item --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar Item</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered" id="daftarItemTable">
                        <thead>
                            <tr>
                                <th style="width: 10px">#</th>
                                <th>Nama Item & Varian</th>
                                <th>Jml</th>
                                <th>Harga Beli</th>
                                <th>Total</th>
                                <th style="width: 85px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($details as $index => $detail)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        {{-- Anda perlu menyesuaikan ini untuk menampilkan nama varian dari relasi --}}
                                        {{ $detail->nama_item }}
                                        {{-- <br><small class="text-muted">{{ $detail->varian_details_string }}</small> --}}
                                    </td>
                                    <td>{{ $detail->jumlah }}</td>
                                    <td>{{ number_format($detail->harga_beli, 0, ',', '.') }}</td>
                                    <td>{{ number_format($detail->total, 0, ',', '.') }}</td>
                                    <td>
                                        {{-- Fungsi edit perlu disesuaikan untuk mengambil data varian --}}
                                        <button type="button" class="btn btn-primary btn-sm mr-1 my-1"
                                            @click="editItem({{ json_encode($detail) }})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('pembelian.remove-item', $detail->id) }}"
                                            method="POST" onsubmit="return confirm('Yakin hapus item ini?')"
                                            style="display: inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm my-1">
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
</div>
{{-- @endsection --}}

{{-- @push('scripts') --}}
{{-- Pastikan Anda punya @stack('scripts') di layout utama Anda --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<script>
    function pembelianForm() {

        return {
            // Data state
            selectedCategory: '{{ old('item_kategori') }}',
            attributes: [],
            isLoading: false,
            isSubmitting: false,

            // Functions
            fetchAttributes() {
                if (!this.selectedCategory) {
                    this.attributes = [];
                    // Kembalikan promise yang langsung selesai jika tidak ada kategori
                    return Promise.resolve();
                }
                this.isLoading = true;
                const url = `/admin/api/kategori/${this.selectedCategory}/attributes`;

                // ✅ KUNCI PERBAIKAN: Tambahkan 'return' di sini.
                // Ini membuat seluruh proses fetch bisa ditunggu oleh fungsi lain.
                return fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        this.attributes = data;
                        this.isLoading = false;
                    })
                    .catch(err => {
                        console.error('Error fetching attributes:', err);
                        this.isLoading = false;
                        Swal.fire('Error', 'Gagal memuat data varian untuk kategori ini.', 'error');
                        // Tolak promise jika terjadi error agar tidak lanjut
                        return Promise.reject(err);
                    });
            },

            formatRp(angka) {
                return new Intl.NumberFormat('id-ID').format(angka);
            },

            hitungTotal() {
                const jumlah = parseFloat(document.getElementById('jumlah').value) || 0;
                const harga = parseFloat(document.getElementById('harga_beli').value) || 0;
                const total = jumlah * harga;
                document.getElementById('total').value = this.formatRp(total);
            },

            toggleNewItem(isNew) {
                document.getElementById('nama_item').readOnly = !isNew;
                document.getElementById('item_kategori').disabled = !isNew;
                // ✅ Hapus baris yang menyembunyikan atribut, karena sekarang dikontrol manual
                // document.getElementById('dynamic-attributes').style.display = isNew ? 'flex' : 'none';

                if (isNew) {
                    // Jika benar-benar item baru, reset form
                    document.getElementById('item_kategori').disabled = false;
                    document.getElementById('dynamic-attributes').style.display = 'flex';
                    // Reset hanya jika tombol "Item Baru" yang di-klik
                    if (document.getElementById('customRadio2').checked) {
                        document.getElementById('formAddItem').reset();
                        document.getElementById('customRadio2').checked = true;
                        document.getElementById('product_variant_id').value = '';
                        this.selectedCategory = '';
                        this.attributes = [];
                    }
                } else {
                    // Jika mode restock, sembunyikan atribut HANYA jika form di-reset
                    document.getElementById('dynamic-attributes').style.display = 'none';
                    document.getElementById('customRadio1').checked = true;
                }
            },

            selectVariantForRestock(item) {
                // 1. Set form ke mode "Restock" dan pastikan panel atribut terlihat
                this.toggleNewItem(false); // Set radio button ke "Restock"
                document.getElementById('dynamic-attributes').style.display = 'flex'; // ✅ Tampilkan panel atribut

                // 2. Isi field dasar dari item yang dipilih
                document.getElementById('product_variant_id').value = item.id;
                document.getElementById('nama_item').value = item.sparepart.nama_sparepart;
                document.getElementById('harga_beli').value = item.purchase_price;
                document.getElementById('jumlah').value = 1; // Default jumlah ke 1

                // 3. Kunci field yang TIDAK BOLEH diubah saat restock (nama & kategori)
                document.getElementById('nama_item').readOnly = false;
                document.getElementById('item_kategori').disabled = false;

                // 4. ✅ LOGIKA UTAMA: Muat dan pilih kategori serta atribut yang benar
                if (item.sparepart && item.sparepart.kode_kategori) {
                    // Set nilai dropdown kategori
                    this.selectedCategory = item.sparepart.kode_kategori;
                    document.getElementById('item_kategori').value = item.sparepart.kode_kategori;

                    // Panggil fetchAttributes untuk memuat semua opsi atribut.
                    // Ini adalah promise, jadi kita bisa .then() setelah selesai.
                    this.fetchAttributes().then(() => {
                        // Setelah opsi dimuat, tunggu DOM di-render ulang oleh AlpineJS
                        this.$nextTick(() => {
                            // Kemudian, set nilai terpilih untuk setiap atribut
                            if (item.attribute_values) {
                                item.attribute_values.forEach(av => {
                                    // Cari elemen <select> untuk atribut ini (berdasarkan attribute_id)
                                    const selectElement = document.getElementById(
                                        `attribute_${av.attribute_id}`);
                                    if (selectElement) {
                                        // ✅ INI BAGIAN PENTING: Set nilainya sesuai dengan ID dari attribute_value
                                        selectElement.value = av.id;
                                    }
                                });
                            }
                        });
                    });
                }

                // 5. Hitung ulang total, bersihkan UI pencarian, dan scroll ke form
                this.hitungTotal();
                document.getElementById('search_results').style.display = 'none';
                document.getElementById('search_sparepart').value = '';
                document.getElementById('formAddItem').scrollIntoView({
                    behavior: 'smooth'
                });
            },

            editItem(detail) {
                const form = document.getElementById('formAddItem');
                const submitButton = document.getElementById('submitButton');

                // 1. Atur mode dan properti form
                form.action = `/admin/pembelian/update-item/${detail.id}`;
                document.getElementById('formItemTitle').textContent = 'Edit Item';
                submitButton.querySelector('span[x-show="!isSubmitting"]').innerHTML =
                    '<i class="fas fa-save"></i> Perbarui Item';
                document.getElementById('cancelButton').style.display = 'block';

                if (!form.querySelector('input[name="_method"]')) {
                    const methodField = document.createElement('input');
                    methodField.type = 'hidden';
                    methodField.name = '_method';
                    methodField.value = 'PATCH';
                    form.appendChild(methodField);
                }

                // 2. Isi data form berdasarkan jenis item
                if (detail.is_new_item) {
                    // --- Logika untuk ITEM BARU (Ini sudah benar) ---
                    this.toggleNewItem(true);
                    document.getElementById('customRadio2').checked = true;
                    document.getElementById('nama_item').value = detail.nama_item;
                    document.getElementById('jumlah').value = detail.jumlah;
                    document.getElementById('harga_beli').value = detail.harga_beli;
                    this.selectedCategory = detail.item_kategori;
                    document.getElementById('item_kategori').value = detail.item_kategori;

                    this.fetchAttributes().then(() => {
                        this.$nextTick(() => {
                            if (detail.attributes) {
                                try {
                                    const selectedAttrs = JSON.parse(detail.attributes);
                                    for (const attrId in selectedAttrs) {
                                        const selectElement = document.getElementById(
                                            `attribute_${attrId}`);
                                        if (selectElement && selectedAttrs[attrId] !== null) {
                                            selectElement.value = selectedAttrs[attrId];
                                        }
                                    }
                                } catch (e) {
                                    console.error("Gagal parse JSON atribut:", e);
                                }
                            }
                        });
                    });

                } else {
                    // --- PERBAIKAN TOTAL: Logika untuk ITEM RESTOCK ---
                    this.toggleNewItem(false); // Mulai dengan mode restock
                    document.getElementById('dynamic-attributes').style.display =
                        'flex'; // ✅ Tampilkan paksa div atribut
                    document.getElementById('customRadio1').checked = true;

                    // Kunci field yang tidak boleh diubah saat restock
                    document.getElementById('nama_item').readOnly = true;
                    document.getElementById('item_kategori').disabled = true;

                    // Isi field dasar
                    document.getElementById('product_variant_id').value = detail.product_variant_id;
                    document.getElementById('nama_item').value = detail.nama_item;
                    document.getElementById('jumlah').value = detail.jumlah;
                    document.getElementById('harga_beli').value = detail.harga_beli;

                    // Dapatkan data varian dari detail yang di-passing dari controller
                    const variant = detail.product_variant;

                    if (variant && variant.sparepart) {
                        // ✅ Set kategori dari data sparepart yang berelasi dengan varian
                        this.selectedCategory = variant.sparepart.kode_kategori;
                        document.getElementById('item_kategori').value = variant.sparepart.kode_kategori;

                        // ✅ Panggil fetchAttributes untuk memuat semua opsi atribut
                        this.fetchAttributes().then(() => {
                            // Setelah opsi dimuat, tunggu DOM di-render oleh Alpine
                            this.$nextTick(() => {
                                // ✅ Kemudian, set nilai terpilih untuk setiap atribut
                                if (variant.attribute_values) {
                                    variant.attribute_values.forEach(av => {
                                        const selectElement = document.getElementById(
                                            `attribute_${av.attribute_id}`);
                                        if (selectElement) {
                                            selectElement.value = av.id;
                                        }
                                    });
                                }
                            });
                        });
                    }
                }

                this.hitungTotal();
                form.scrollIntoView({
                    behavior: 'smooth'
                });
            },

            cancelEdit() {
                const form = document.getElementById('formAddItem');
                const submitButton = document.getElementById('submitButton');

                form.action = "{{ route('pembelian.add-item', $pembelian->id) }}"; // Kembalikan ke URL add
                document.getElementById('formItemTitle').textContent = 'Tambah Item';
                submitButton.querySelector('span[x-show="!isSubmitting"]').innerHTML =
                    '<i class="fas fa-plus"></i> Tambah Item';
                document.getElementById('cancelButton').style.display = 'none';

                // Hapus field _method
                const methodField = form.querySelector('input[name="_method"]');
                if (methodField) {
                    methodField.remove();
                }

                form.reset();
                this.toggleNewItem(false); // Kembali ke mode restock default
                document.getElementById('customRadio1').checked = true;
                this.selectedCategory = '';
                this.attributes = [];
                this.hitungTotal();
            },

            finalizeForm() {
                const supplierId = document.getElementById('supplier').value;
                if (!supplierId) {
                    Swal.fire('Perhatian', 'Mohon pilih Supplier terlebih dahulu.', 'warning');
                    return;
                }

                const metode = document.getElementById('metode_pembayaran').value;
                document.getElementById('finalize_metode').value = metode;
                if (metode === 'Hutang') {
                    document.getElementById('finalize_jatuh_tempo').value = document.getElementById('tgl_jatuh_tempo')
                        .value;
                }
                document.getElementById('finalize_supplier').value = supplierId;

                Swal.fire({
                    title: 'Konfirmasi',
                    text: 'Anda yakin ingin menyelesaikan pembelian? Stok akan diupdate dan pembelian tidak dapat diedit lagi.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Selesaikan',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('finalize-form-{{ $pembelian->id }}').submit();
                    }
                });
            },

            performSearch(event, force = false) {
                const searchInput = document.getElementById('search_sparepart');
                const query = searchInput.value.trim();

                if (!force && query.length < 2) {
                    document.getElementById('search_results').style.display = 'none';
                    return;
                }

                const searchResults = document.getElementById('search_results');
                const searchResultsList = document.getElementById('search_results_list');
                const loadingIndicator = document.getElementById('loading_indicator');

                loadingIndicator.style.display = 'flex';
                searchResults.style.display = 'block';
                searchResultsList.innerHTML = '';

                // Sesuaikan URL search AJAX Anda
                const searchUrl = '{{ route('pembelian.search-variants-ajax') }}';

                fetch(`${searchUrl}?search=${query}`)
                    .then(response => response.json())
                    .then(data => {
                        loadingIndicator.style.display = 'none';
                        if (data.success && data.results && data.results.length > 0) {
                            searchResultsList.innerHTML = '';
                            data.results.forEach(item => {
                                const listItem = document.createElement('a');
                                listItem.href = '#';
                                listItem.className = 'list-group-item list-group-item-action';

                                // Gabungkan nama atribut dan value
                                let variantString = item.attribute_values.map(av =>
                                    `${av.attribute.name}: ${av.value}`).join(', ');

                                listItem.innerHTML = `
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>${item.sparepart.nama_sparepart}</strong>
                                            <small class="text-muted d-block">${variantString}</small>
                                        </div>
                                        <span class="badge badge-info">Stok: ${item.stock}</span>
                                    </div>
                                `;

                                // Event listener untuk memilih item
                                listItem.addEventListener('click', (e) => {
                                    e.preventDefault();
                                    this.selectVariantForRestock(item);
                                });
                                searchResultsList.appendChild(listItem);
                            });
                        } else {
                            searchResultsList.innerHTML =
                                '<div class="alert alert-warning">Tidak ada varian yang ditemukan.</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        loadingIndicator.style.display = 'none';
                        searchResultsList.innerHTML =
                            '<div class="alert alert-danger">Terjadi kesalahan saat mencari.</div>';
                    });
            },

            init() {
                console.log('Pembelian form initialized.');
                this.toggleNewItem(false); // Start in restock mode by default

                // Handle metode pembayaran change
                document.getElementById('metode_pembayaran').addEventListener('change', function() {
                    document.getElementById('jatuh_tempo_div').style.display = (this.value === 'Hutang') ?
                        'block' : 'none';
                });

                // Init DataTable
                $(function() {
                    $('#daftarItemTable').DataTable({
                        "paging": true,
                        "lengthChange": true,
                        "searching": true,
                        "ordering": true,
                        "info": true,
                        "autoWidth": false,
                        "responsive": true,
                    });
                });

                // Form submission logic (for update)
                document.getElementById('formAddItem').addEventListener('submit', function(e) {
                    if (document.getElementById('edit_mode').value === '1') {
                        const detailId = document.getElementById('edit_detail_id').value;
                        this.action = `/admin/pembelian/update-item/${detailId}`; // Sesuaikan URL
                        if (!this.querySelector('input[name="_method"]')) {
                            const methodField = document.createElement('input');
                            methodField.type = 'hidden';
                            methodField.name = '_method';
                            methodField.value = 'PATCH';
                            this.appendChild(methodField);
                        }
                    }
                });
            }
        }
    }
</script>
