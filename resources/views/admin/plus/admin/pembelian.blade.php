@include('admin.plus.template.head')
@include('admin.plus.template.nav')
<div class="container-fluid">
    <div class="row">
        @include('admin.plus.template.sidebar')
        <!-- Laporan  -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main active">
            <div class="card-tr" style="margin-top: 55px; position:relative;">
                <div class="row">
                    <div class="col-2">
                        <img src="#" title="images">
                    </div>
                    <div class="col">
                        <span><b>
                                <h3>Pembelian</h3>
                            </b></span>
                    </div>

                </div>
                <div class="bg-success"
                    style="position: absolute; top: 0; right: 0; z-index: 1; color:#ffffff; padding: 10px; border-top-right-radius: 8px; border-bottom-left-radius: 8px;">
                    <span class="m-0">Tanggal: 27 Oktober 2023</span>
                </div>
            </div>
            <div class="card-tr" style="margin-top: 5px;">
                <div class="row">
                    <div class="col-md-8 ms-sm-auto col-lg-8 px-md-2">
                        {{-- sparepart --}}
                        <div class="card border-success">
                            <div class="card-header"
                                style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 18px"><strong>Sparepart</strong></span>
                                <button class="btn btn-success content-justify-end">Tambah</button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="example">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nama Barang</th>
                                                <th>Harga</th>
                                                <th>Qty</th>
                                                <th>Total</th>
                                                <th>#</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>1</td>
                                                <td>Lcd samsung A51</td>
                                                <td>1.200.000</td>
                                                <td>2</td>
                                                <td>2.400.000</td>
                                                <td>
                                                    <button class="btn btn-danger">Hapus</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>2</td>
                                                <td>Lcd samsung A52</td>
                                                <td>1.200.000</td>
                                                <td>2</td>
                                                <td>2.400.000</td>
                                                <td>
                                                    <button class="btn btn-danger">Hapus</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        {{-- end sparepart --}}
                        {{-- Barang --}}
                        <div class="card border-primary my-4">
                            <div class="card-header"
                                style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 18px"><strong>Barang</strong></span>
                                <button class="btn btn-primary content-justify-end">Tambah</button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="table">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Nama Barang</th>
                                                <th>Harga</th>
                                                <th>Qty</th>
                                                <th>Total</th>
                                                <th>#</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>1</td>
                                                <td>Lcd samsung A51</td>
                                                <td>1.200.000</td>
                                                <td>2</td>
                                                <td>2.400.000</td>
                                                <td>
                                                    <button class="btn btn-danger">Hapus</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>2</td>
                                                <td>Lcd samsung A52</td>
                                                <td>1.200.000</td>
                                                <td>2</td>
                                                <td>2.400.000</td>
                                                <td>
                                                    <button class="btn btn-danger">Hapus</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        {{-- end barang --}}
                        {{-- Garansi --}}
                        <div class="card border-warning">
                            <div class="card-header"
                                style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 18px"><strong>Garansi</strong></span>
                                <button class="btn btn-warning content-justify-end">Tambah</button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Keterangan</th>
                                                <th>Exp</th>
                                                <th>...</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>1</td>
                                                <td>Lcd samsung A51</td>
                                                <td>1.200.000</td>
                                                <td>
                                                    <button class="btn btn-danger">Hapus</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>2</td>
                                                <td>Lcd samsung A52</td>
                                                <td>1.200.000</td>
                                                <td>
                                                    <button class="btn btn-danger">Hapus</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        {{-- end garansi --}}
                    </div>
                    <div class="col-md-4 ms-sm-auto col-lg-4 px-md-2">
                        <div class="card card-outline card-success mb-3">
                            <div class="card-body">
                                <label><strong>Grand Total</strong> </label>
                                <h2>Rp.0,-</h2>
                                <div style="position: absolute; top: 0; right: 0; z-index: 1; padding: 10px;">
                                    <span class="m-0">Tr3435567</span>
                                </div>
                            </div>
                        </div>
                        <div class="card card-outline card-success my-3">
                            <div class="card-body">
                                <label><strong>Total bayar</strong></label>
                                <input id="totalbayar" type="text" class="form-control" placeholder="Total bayar"
                                    aria-label="Total bayar">

                                <label class="mt-2"><strong>Nama SPL</strong></label>
                                <input id="customer" type="text" class="form-control mb-2" placeholder="Nama"
                                    aria-label="customer">

                                <textarea name="catatan_customer" id="catatan_customer" placeholder="Catatan Customer" class="form-control my-2"
                                    cols="30" rows="7"></textarea>

                                <button class="btn btn-success col-12 mt-4">Bayar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- kolom -->
        </main>
        <!-- end laporan -->
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="sparepartModal" tabindex="-1" aria-labelledby="sparepartModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sparepartModalLabel">Pilih Sparepart</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Isi modal dengan daftar sparepart yang bisa dipilih -->
                <select id="selectSparepart" class="form-control">
                    <option value="1">Sparepart 1</option>
                    <option value="2">Sparepart 2</option>
                    <option value="3">Sparepart 3</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="setSparepart()">Pilih Sparepart</button>
            </div>
        </div>
    </div>
</div>
<script>
    const spareparts = [{
            id: 1,
            nama: 'part1',
            harga: '1000'
        },
        {
            id: 2,
            nama: 'part2',
            harga: '2000'
        },
        {
            id: 3,
            nama: 'part3',
            harga: '3000'
        }
    ];
</script>

<script>
    function setSparepart() {
        // Mendapatkan nilai yang dipilih dari modal
        const selectedSparepartId = document.getElementById('selectSparepart').value;

        // Cari objek sparepart yang sesuai berdasarkan ID yang dipilih
        const selectedSparepart = spareparts.find(sparepart => sparepart.id.toString() === selectedSparepartId);

        if (selectedSparepart) {
            // Menyimpan nama dan harga ke input "Sparepart" dan "Harga Pasang"
            document.getElementById('sparepartInput').value = selectedSparepart.nama;
            document.getElementById('hargaPasangInput').value = selectedSparepart.harga;
        }

        // Menutup modal
        const modal = new bootstrap.Modal(document.getElementById('sparepartModal'));
        modal.hide();
    }
</script>

@include('admin.plus.template.foot')
