<form action="{{ route('update_penjualan', $kodetrx->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="formSales d-none">
        <div class="input-group my-2 kategorilaciGrup d-none">
            <label class="input-group-text" for="id_kategorilaci">Penyimpanan</label>
            <select name="id_kategorilaci" class="form-control" required>
                <option value="" disabled selected>--Pilih Kategori Laci--</option>
                @foreach ($listLaci as $kategori)
                    <option value="{{ $kategori->id }}">{{ $kategori->name_laci }}</option>
                @endforeach
            </select>
        </div>
        <div class="input-group my-2">
            <input type="text" id="kodetrx" class="form-control" value="{{ $kodetrx->kode_penjualan }}" readonly />
            <input type="text" id="kodetrxid" class="form-control" value="{{ $kodetrx->id }}" hidden />
            <input type="date" name="tgl_penjualan" id="tgl_penjualan" value="{{ date('Y-m-d') }}"
                class="form-control" readonly>
        </div>
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
            <button class="btn btn-success" data-toggle="modal" data-target="#modal_sp"><i
                    class="fas fa-plus"></i></button>
            <button class="input-group-text btn-primary" data-toggle="modal" data-target="#detail_sp"
                for="Item">Item</button>
            <input type="number" id="item" class="form-control" readonly />
        </div>
        <div class="view-gtotal"
            style="background-color: #e3ff96;border-radius: 5px ;height: 100px;display: flex; align-items: center; justify-content: center;">
            <h2><b>
                    <div id="gtotal-result"></div>
                    <input hidden name="total_penjualan" id="total_penjualan">
                </b>
            </h2>
        </div>
        <div class="input-group my-2">
            <label class="input-group-text" for="nama_customer">pembeli</label>
            <input type="text" name="nama_customer" id="nama_customer" class="form-control" required />
        </div>
        <div class="input-group my-2">
            <span class="input-group-text">Keterangan</span>
            <textarea class="form-control" name="catatan_customer" id="catatan_customer" aria-label="With textarea"></textarea>
        </div>
        <div class="input-group my-2">
            <label class="input-group-text" for="bayar">Bayar</label>
            <input type="number" name="bayar" id="bayar" class="form-control bayar" hidden />
            <input type="text" name="in_bayar" id="in_bayar" class="form-control in_bayar" required />
        </div>
        <span style="display:none;" id="kembalian-value">Rp.
            0,-</span>
        <div class="d-flex align-item-center">
            <button type="submit" name="simpan" value="newbayar" class="btn btn-primary form-control">Simpan</button>
        </div>
    </div>
</form>
{{-- modal pencarian sp --}}
<div class="modal fade" id="modal_sp">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalTitle">Sparepart</h4>

                <div class="input-group my-2" style="max-width: 350px">
                    <label class="input-group-text" for="kat_customer">pelanggan</label>
                    <select name="kat_customer" class="form-control" id="kat_customer" required>
                        <option value="" disabled>--Pilih jenis pelanggan--</option>
                        <option value="ecer"selected>Eceran</option>
                        <option value="konter">Konter</option>
                        <option value="glosir">Glosir (5pcs /type)</option>
                        <option value="jumbo">Glosir jumbo(belanja banyak)</option>
                    </select>
                </div>
                <div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

            </div>
            <div class="modal-body">
                <!-- Formulir untuk menambah/edit data sparepart -->
                <form id="formRestockSparepart">
                    @csrf
                    <div class="form-group">
                        <label>Cari di sini</label>
                        <input type="text" name="caripart" id="caripart" class="form-control"
                            oninput="cariSparepart()" autocomplete="off">

                    </div>
                    <div class="card">
                        <div class="card-body"style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-striped " id="searchResults">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nama Barang</th>
                                        <th>Stok</th>
                                        <th>Harga</th>
                                        <th>QTY</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
{{-- modal end pencarian sp --}}
{{-- modal detail sp --}}
<div class="modal fade" id="detail_sp">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalTitle">Detail Sparepart</h4>
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
                        <table class="table table-striped" id="TABLES_1">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama Barang</th>
                                    <th>Harga</th>
                                    <th>QTY</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="sparepartList">

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
{{-- modal end detail sp --}}
