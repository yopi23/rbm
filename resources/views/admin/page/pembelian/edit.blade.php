{{-- File: resources/views/admin/page/pembelian/edit.blade.php --}}
{{-- Pencarian dengan AJAX --}}

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
                                <option value="{{ $item->id }}"
                                    {{ $pembelian->supplier == $item->nama_supplier ? 'selected' : '' }}>
                                    {{ $item->nama_supplier }}
                                </option>
                            @endforeach
                        </select>
                    </dd>

                    <dt class="col-sm-4">Kategori:</dt>
                    <dd class="col-sm-8">
                        <select name="kategori" id="kategori" class="form-control" required
                            onchange="loadSubKategori()">
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

                    <dt class="col-sm-4">Sub Kategori:</dt>
                    <dd class="col-sm-8">
                        <select name="sub_kategori" id="sub_kategori" class="form-control">
                            <option value="">--- Pilih Kategori Dulu ---</option>
                        </select>
                        <div class="invalid-feedback">
                            Pilih sub kategori.
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
                    action="{{ route('pembelian.finalize', $pembelian->id) }}" method="POST" style="display: inline;">
                    @csrf
                    <input type="hidden" id="finalize_supplier" name="supplier" value="">
                    <input type="hidden" id="finalize_metode" name="metode_pembayaran" value="Lunas">
                    <input type="hidden" id="finalize_jatuh_tempo" name="tgl_jatuh_tempo" value="">
                    <input type="hidden" id="finalize_kategori" name="kategori" value="">
                    <button type="button" class="btn btn-success float-right" onclick="finalizeForm()">
                        <i class="fas fa-check"></i> Selesaikan Pembelian
                    </button>
                </form>
            </div>
        </div>

        {{-- Card Pencarian Sparepart --}}
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

                <form action="{{ route('pembelian.add-item', $pembelian->id) }}" method="POST" id="formAddItem">
                    @csrf
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
                                <label for="item_kategori">Kategori</label>
                                <select name="item_kategori" id="item_kategori" class="form-control"
                                    onchange="loadItemSubKategori()">
                                    <option value="">--- Pilih Kategori ---</option>
                                    @foreach ($kategori as $item)
                                        <option value="{{ $item->id }}">{{ $item->nama_kategori }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="item_sub_kategori">Sub Kategori</label>
                                <select name="item_sub_kategori" id="item_sub_kategori" class="form-control">
                                    <option value="">--- Pilih Kategori Dulu ---</option>
                                </select>
                            </div>
                        </div>
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
                                                oninput="updateHargaOtomatis()">
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

                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title">Harga Khusus (Opsional)</h3>
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
                                        <label for="harga_khusus_toko">Harga Toko (Internal)</label>
                                        <input type="number" class="form-control" id="harga_khusus_toko"
                                            name="harga_khusus_toko" min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="harga_khusus_satuan">Harga Satuan (Jual)</label>
                                        <input type="number" class="form-control" id="harga_khusus_satuan"
                                            name="harga_khusus_satuan" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block" id="submitButton">Tambah
                                Item</button>
                            <button type="button" class="btn btn-secondary btn-block mt-2" id="cancelButton"
                                style="display: none;" onclick="cancelEdit()">Batal Edit</button>
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
                            <th>Nama Item</th>
                            <th>Jml</th>
                            <th>Harga Beli</th>
                            <th>Harga Khusus</th>
                            <th>Total</th>
                            <th style="width: 85px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($details as $index => $detail)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    {{ $detail->nama_item }}
                                    @if ($detail->harga_khusus_toko || $detail->harga_khusus_satuan)
                                        <br><small class="badge bg-info">Punya Harga Khusus</small>
                                    @endif
                                </td>
                                <td>{{ $detail->jumlah }}</td>
                                <td>{{ number_format($detail->harga_beli, 0, ',', '.') }}</td>
                                <td>
                                    @if ($detail->harga_khusus_toko)
                                        <small>Toko:
                                            {{ number_format($detail->harga_khusus_toko, 0, ',', '.') }}</small><br>
                                    @endif
                                    @if ($detail->harga_khusus_satuan)
                                        <small>Satuan:
                                            {{ number_format($detail->harga_khusus_satuan, 0, ',', '.') }}</small>
                                    @endif
                                </td>
                                <td>{{ number_format($detail->total, 0, ',', '.') }}</td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm mr-1 my-1"
                                        onclick="editItem({{ json_encode($detail) }})">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('pembelian.remove-item', $detail->id) }}" method="POST"
                                        onsubmit="return confirm('Yakin hapus item ini?')"
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
                                <td colspan="7" class="text-center">Belum ada item</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
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
    document.getElementById('metode_pembayaran').addEventListener('change', function() {
        if (this.value === 'Hutang') {
            document.getElementById('jatuh_tempo_div').style.display = 'block';
        } else {
            document.getElementById('jatuh_tempo_div').style.display = 'none';
        }
    });

    // --- FUNGSI YANG DIKEMBALIKAN ---
    function loadSubKategori() {
        const kategoriId = document.getElementById('kategori').value;
        const subKategoriSelect = document.getElementById('sub_kategori');
        subKategoriSelect.innerHTML = '<option value="">--- Loading... ---</option>';

        if (!kategoriId) {
            subKategoriSelect.innerHTML = '<option value="">--- Pilih Kategori Dulu ---</option>';
            return;
        }

        const url = "{{ url('/admin/pembelian/get-sub-kategori') }}/" + kategoriId;
        fetch(url)
            .then(response => response.json())
            .then(data => {
                subKategoriSelect.innerHTML = '<option value="">--- Pilih Sub Kategori ---</option>';
                if (data.success && data.data) {
                    data.data.forEach(subKategori => {
                        const option = document.createElement('option');
                        option.value = subKategori.id;
                        option.textContent = subKategori.nama_sub_kategori;
                        subKategoriSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error("Error fetching sub kategori:", error);
                subKategoriSelect.innerHTML = '<option value="">--- Gagal Memuat ---</option>';
            });
    }

    // --- FUNGSI YANG DIKEMBALIKAN ---
    function loadItemSubKategori() {
        const kategoriId = document.getElementById('item_kategori').value;
        const subKategoriSelect = document.getElementById('item_sub_kategori');
        subKategoriSelect.innerHTML = '<option value="">--- Loading... ---</option>';

        if (!kategoriId) {
            subKategoriSelect.innerHTML = '<option value="">--- Pilih Kategori Dulu ---</option>';
            return;
        }

        const url = "{{ url('/admin/pembelian/get-sub-kategori') }}/" + kategoriId;
        fetch(url)
            .then(response => response.json())
            .then(data => {
                subKategoriSelect.innerHTML = '<option value="">--- Pilih Sub Kategori ---</option>';
                if (data.success && data.data) {
                    data.data.forEach(subKategori => {
                        const option = document.createElement('option');
                        option.value = subKategori.id;
                        option.textContent = subKategori.nama_sub_kategori;
                        subKategoriSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error("Error fetching item sub kategori:", error);
                subKategoriSelect.innerHTML = '<option value="">--- Gagal Memuat ---</option>';
            });
    }

    function editItem(detail) {
        document.getElementById('formItemTitle').textContent = 'Edit Item';
        document.getElementById('edit_mode').value = '1';
        document.getElementById('edit_detail_id').value = detail.id;
        document.getElementById('sparepart_id').value = detail.sparepart_id;
        document.getElementById('nama_item').value = detail.nama_item;
        document.getElementById('jumlah').value = detail.jumlah;
        document.getElementById('harga_beli').value = detail.harga_beli;
        document.getElementById('harga_jual').value = detail.harga_jual;
        document.getElementById('harga_ecer').value = detail.harga_ecer;
        document.getElementById('harga_pasang').value = detail.harga_pasang;
        document.getElementById('harga_khusus_toko').value = detail.harga_khusus_toko;
        document.getElementById('harga_khusus_satuan').value = detail.harga_khusus_satuan;

        if (detail.item_kategori) {
            document.getElementById('item_kategori').value = detail.item_kategori;
            loadItemSubKategori();
            setTimeout(() => {
                if (detail.item_sub_kategori) {
                    document.getElementById('item_sub_kategori').value = detail.item_sub_kategori;
                }
            }, 500);
        }

        hitungTotal();
        triggerPriceDifferenceUpdate();
        document.getElementById('submitButton').textContent = 'Perbarui Item';
        document.getElementById('cancelButton').style.display = 'block';
        document.getElementById('formAddItem').scrollIntoView({
            behavior: 'smooth'
        });
    }

    function cancelEdit() {
        document.getElementById('formItemTitle').textContent = 'Tambah Item';
        document.getElementById('edit_mode').value = '0';
        document.getElementById('formAddItem').reset();
        document.getElementById('sparepart_id').value = '';
        document.getElementById('item_sub_kategori').innerHTML =
            '<option value="">--- Pilih Kategori Dulu ---</option>';
        document.getElementById('customRadio1').checked = true;
        toggleNewItem(false);
        document.getElementById('submitButton').textContent = 'Tambah Item';
        document.getElementById('cancelButton').style.display = 'none';

        document.getElementById('formAddItem').action = "{{ route('pembelian.add-item', $pembelian->id) }}";
        const methodField = document.querySelector('input[name="_method"]');
        if (methodField) {
            methodField.remove();
        }
    }

    function selectSparepart(item) {
        document.getElementById('sparepart_id').value = item.id;
        document.getElementById('nama_item').value = item.nama_sparepart;
        document.getElementById('harga_beli').value = item.harga_beli;
        document.getElementById('harga_jual').value = item.harga_jual;
        document.getElementById('harga_ecer').value = item.harga_ecer;
        document.getElementById('harga_pasang').value = item.harga_pasang;

        const hargaKhusus = item.harga_khusus && item.harga_khusus.length > 0 ? item.harga_khusus[0] : null;
        document.getElementById('harga_khusus_toko').value = hargaKhusus ? hargaKhusus.harga_toko : '';
        document.getElementById('harga_khusus_satuan').value = hargaKhusus ? hargaKhusus.harga_satuan : '';

        if (item.kode_kategori) {
            document.getElementById('item_kategori').value = item.kode_kategori;
            loadItemSubKategori();
            if (item.kode_sub_kategori) {
                setTimeout(() => {
                    document.getElementById('item_sub_kategori').value = item.kode_sub_kategori;
                }, 500);
            }
        }

        document.getElementById('nama_item').readOnly = true;
        document.getElementById('customRadio1').checked = true;
        toggleNewItem(false);
        hitungTotal();
        triggerPriceDifferenceUpdate();
        document.getElementById('search_results').style.display = 'none';
        document.getElementById('formAddItem').scrollIntoView({
            behavior: 'smooth'
        });
    }

    function hitungTotal() {
        var jumlah = parseFloat(document.getElementById('jumlah').value) || 0;
        var harga = parseFloat(document.getElementById('harga_beli').value) || 0;
        var total = jumlah * harga;
        document.getElementById('total').value = formatRp(total);
    }

    function toggleNewItem(isNew) {
        document.getElementById('nama_item').readOnly = !isNew;
        if (isNew) {
            document.getElementById('formAddItem').reset();
            document.getElementById('customRadio2').checked = true;
            document.getElementById('sparepart_id').value = '';
        }
        triggerPriceDifferenceUpdate();
    }

    function updateHargaOtomatis() {
        const hargaBeli = parseFloat(document.getElementById('harga_beli').value) || 0;
        const isNewItem = document.getElementById('customRadio2').checked;

        if (isNewItem) {
            document.getElementById('harga_jual').value = Math.round(hargaBeli * 1.2);
            document.getElementById('harga_ecer').value = Math.round(hargaBeli * 1.3);
            document.getElementById('harga_pasang').value = hargaBeli;
        }

        hitungTotal();
        triggerPriceDifferenceUpdate();
    }

    function formatRp(angka) {
        return new Intl.NumberFormat('id-ID').format(angka);
    }

    function triggerPriceDifferenceUpdate() {
        setTimeout(calculatePriceDifferences, 50);
    }

    function calculatePriceDifferences() {
        const hargaBeli = parseFloat(document.getElementById('harga_beli').value) || 0;
        const priceFields = ['harga_jual', 'harga_ecer', 'harga_pasang'];

        priceFields.forEach(id => {
            const price = parseFloat(document.getElementById(id).value) || 0;
            const diff = price - hargaBeli;
            const diffElement = document.getElementById(`${id}_diff`);

            if (diff >= 0) {
                diffElement.textContent = `+Rp ${formatRp(diff)}`;
                diffElement.style.color = '#28a745';
            } else {
                diffElement.textContent = `-Rp ${formatRp(Math.abs(diff))}`;
                diffElement.style.color = '#dc3545';
            }
        });
    }

    function finalizeForm() {
        const supplierId = document.getElementById('supplier').value;
        const kategoriId = document.getElementById('kategori').value;
        if (!supplierId || !kategoriId) {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: 'Mohon pilih Supplier dan Kategori.'
            });
            return;
        }
        const metode = document.getElementById('metode_pembayaran').value;
        document.getElementById('finalize_metode').value = metode;
        if (metode === 'Hutang') {
            document.getElementById('finalize_jatuh_tempo').value = document.getElementById('tgl_jatuh_tempo').value;
        }
        document.getElementById('finalize_supplier').value = supplierId;
        document.getElementById('finalize_kategori').value = kategoriId;
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
    }

    document.getElementById('formAddItem').addEventListener('submit', function(e) {
        if (document.getElementById('edit_mode').value === '1') {
            const detailId = document.getElementById('edit_detail_id').value;
            this.action = "{{ url('admin/pembelian/update-item') }}/" + detailId;
            if (!this.querySelector('input[name="_method"]')) {
                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'PATCH';
                this.appendChild(methodField);
            }
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Event listener untuk pencarian (AJAX)
        const searchInput = document.getElementById('search_sparepart');
        const searchButton = document.getElementById('btn_search');
        const searchResults = document.getElementById('search_results');
        const searchResultsList = document.getElementById('search_results_list');
        const loadingIndicator = document.getElementById('loading_indicator');
        let searchTimeout = null;

        function performSearch() {
            const query = searchInput.value.trim();
            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }
            loadingIndicator.style.display = 'flex';
            searchResults.style.display = 'block';
            searchResultsList.innerHTML = '';
            const searchUrl = '{{ route('pembelian.search-spareparts-ajax') }}';
            const formData = new FormData();
            formData.append('search', query);
            formData.append('_token', '{{ csrf_token() }}');

            fetch(searchUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    loadingIndicator.style.display = 'none';
                    if (data.success && data.results && data.results.length > 0) {
                        searchResultsList.innerHTML = '';
                        data.results.forEach(item => {
                            const listItem = document.createElement('a');
                            listItem.href = '#';
                            listItem.className = 'list-group-item list-group-item-action';
                            console.log(item);
                            // MODIFIED: Add subcategory information to the search result
                            const subKategoriHtml = item.nama_sub_kategori ?
                                `<small class="text-muted d-block">${item.nama_sub_kategori}</small>` :
                                '';

                            listItem.innerHTML = `
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        ${item.nama_sparepart}
                                        ${subKategoriHtml}
                                    </div>
                                    <span class="badge badge-info">Stok: ${item.stok_sparepart}</span>
                                </div>
                            `;

                            listItem.addEventListener('click', function(e) {
                                e.preventDefault();
                                selectSparepart(item);
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
                        '<div class="alert alert-danger">Terjadi kesalahan saat mencari.</div>';
                });
        }
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 500);
        });
        searchButton.addEventListener('click', performSearch);

        // Event listener untuk input harga agar memicu kalkulasi selisih
        const priceInputs = ['harga_beli', 'harga_jual', 'harga_ecer', 'harga_pasang'];
        priceInputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            input.addEventListener('input', triggerPriceDifferenceUpdate);
        });
    });
</script>
