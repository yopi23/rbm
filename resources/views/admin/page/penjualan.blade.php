@section('penjualan', 'active')
@section('drop', 'active')
@section('main', 'menu-open')


<div class="row">
    <div class="col-md-8">
        <div class="row">
            <div class="col-md-12">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <div class="card-title">
                            Sparepart
                        </div>
                        <div class="card-tools">
                            <a href="#" class="btn btn-success" data-toggle="modal" data-target="#modal_sparepart"
                                name="tambah_sparepart" id="tambah_sparepart"><i class="fas fa-plus"></i></a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="TABLES_1">
                                <thead>
                                    <th>#</th>
                                    <th>Sparepart</th>
                                    <th>Harga</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                    <th>Aksi</th>
                                </thead>
                                <tbody>
                                    @php
                                        $total_part_penjualan = 0;
                                    @endphp
                                    @foreach ($sparepart as $item)
                                        @php
                                            $total_part_penjualan += $item->harga_ecer * $item->qty_sparepart;
                                        @endphp
                                        <tr>
                                            <td>{{ $loop->index + 1 }}</td>
                                            <td>{{ $item->nama_sparepart }}</td>
                                            <td>Rp.{{ number_format($item->harga_ecer) }},-</td>
                                            <td>{{ $item->qty_sparepart }}</td>
                                            <td>Rp.{{ number_format($item->harga_ecer * $item->qty_sparepart) }},-</td>
                                            <td>
                                                <form
                                                    action="{{ route('delete_detail_sparepart_penjualan', $item->id_detail) }}"
                                                    onsubmit="return confirm('Apakah Kamu Yakin ?')" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-danger" type="submit"><i
                                                            class="fas fa-trash"></i></button>
                                                </form>
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
        <div class="row">
            <div class="col-md-12">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <div class="card-title">
                            Handphone,Laptop Dan Barang
                        </div>
                        <div class="card-tools">
                            <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#modal_barang"
                                name="tambah_barang" id="tambah_barang"><i class="fas fa-plus"></i></a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="TABLES_2">
                                <thead>
                                    <th>#</th>
                                    <th>Barang</th>
                                    <th>Harga</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                    <th>Aksi</th>
                                </thead>
                                <tbody>
                                    @php
                                        $total_barang_penjualan = 0;
                                    @endphp
                                    @foreach ($barang as $item)
                                        @php
                                            $total_barang_penjualan = $item->harga_jual_barang * $item->qty_barang;
                                        @endphp
                                        <tr>
                                            <td>{{ $loop->index + 1 }}</td>
                                            <td>{{ $item->nama_barang }}</td>
                                            <td>Rp.{{ number_format($item->harga_jual_barang) }},-</td>
                                            <td>{{ $item->qty_barang }}</td>
                                            <td>Rp.{{ number_format($item->harga_jual_barang * $item->qty_barang) }},-
                                            </td>
                                            <td>
                                                <form
                                                    action="{{ route('delete_detail_barang_penjualan', $item->id_detail) }}"
                                                    onsubmit="return confirm('Apakah Kamu Yakin ?')" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-danger" type="submit"><i
                                                            class="fas fa-trash"></i></button>
                                                </form>
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
        <div class="row">
            <div class="col-md-12">
                <div class="card card-warning card-outline">
                    <div class="card-header">
                        <div class="card-title">
                            Garansi
                        </div>
                        <div class="card-tools">
                            <a href="#" class="btn btn-warning" data-toggle="modal" data-target="#modal_garansi"
                                name="tambah_garansi" id="tambah_garansi"><i class="fas fa-plus"></i></a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="TABLES_2">
                                <thead>
                                    <th>#</th>
                                    <th>Nama</th>
                                    <th>Exp</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </thead>
                                <tbody>
                                    @foreach ($garansi as $item)
                                        <tr>
                                            <td>{{ $loop->index + 1 }}</td>
                                            <td>{{ $item->nama_garansi }}</td>
                                            <td>{{ $item->tgl_exp_garansi }}</td>
                                            <td>
                                                @switch($item->status_garansi)
                                                    @case(0)
                                                        <span class="badge badge-primary">Aktif</span>
                                                    @break

                                                    @case(1)
                                                        <span class="badge badge-danger">Tidak Aktif</span>
                                                    @break

                                                    @case(2)
                                                        <span class="badge badge-success">DiKlaim</span>
                                                    @break

                                                    @default
                                                @endswitch
                                            </td>
                                            <td>
                                                <form action="{{ route('delete_garansi_penjualan', $item->id) }}"
                                                    onsubmit="return confirm('Apakah Kamu Yakin ?')" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-danger" type="submit"><i
                                                            class="fas fa-trash"></i></button>
                                                </form>
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
    <div class="col-md-4">
        <form action="{{ route('update_penjualan', $data->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-outline card-success">
                        <div class="card-body">
                            <label>Grand Total </label>
                            <h2>Rp.{{ number_format($total_part_penjualan + $total_barang_penjualan) }},-</h2>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="card card-outline card-success">
                        <div class="card-body">
                            <input type="hidden" name="total_penjualan" id="total_penjualan" class="form-control"
                                value="{{ $total_part_penjualan + $total_barang_penjualan }}">
                            <div class="form-group">
                                <label>Kode Invoice</label>
                                <input type="text" name="kode_penjualan" id="kode_penjualan" class="form-control"
                                    value="{{ $data->kode_penjualan }}" disabled>
                            </div>
                            <div class="form-group">
                                <label>Tanggal</label>
                                <input type="date" name="tgl_penjualan" id="tgl_penjualan"
                                    value="{{ $data->tgl_penjualan != null ? $data->tgl_penjualan : date('Y-m-d') }}"
                                    class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Total Bayar</label>
                                <input type="text" name="total_bayar" id="total_bayar" class="form-control"
                                    value="{{ $data->total_bayar }}" placeholder="Total Bayar" hidden>
                                <input type="text" name="in_bayar" id="in_bayar" class="form-control"
                                    placeholder="Total Bayar" required>
                            </div>
                            <div class="form-group">
                                <button type="submit" name="simpan" value="bayar"
                                    class="form-control btn btn-success"><i class="fas fa-cash-register"></i>
                                    Bayar</button>
                            </div>
                            <div class="form-group">
                                <button type="submit" name="simpan" value="simpan"
                                    class="form-control btn btn-primary"><i class="fas fa-paste"></i> Simpan</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="card card-outline card-success">
                        <div class="card-body">
                            <div class="form-group">
                                <label>Nama Pelanggan</label>
                                <input type="text" name="nama_customer" id="nama_customer" class="form-control"
                                    placeholder="Nama">
                            </div>
                            <div class="form-group">
                                <label>Catatan</label>
                                <textarea name="catatan_customer" id="catatan_customer" placeholder="Catatan Customer" class="form-control"
                                    cols="30" rows="7"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="modal_sparepart">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tambah Sparepart</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table" id="TABLES_2">
                                <thead>
                                    <th>No</th>
                                    <th>Sparepart</th>
                                    <th>Harga Jual</th>
                                    <th>Stok</th>
                                    <th>Aksi</th>
                                </thead>
                                <tbody id="search-result">
                                    @foreach ($all_sparepart as $item)
                                        <tr>
                                            <td>{{ $loop->index + 1 }}</td>
                                            <td>{{ $item->nama_sparepart }}</td>
                                            <td>Rp.{{ number_format($item->harga_ecer) }},-</td>
                                            <td>{{ $item->stok_sparepart }}</td>
                                            <td>
                                                @if ($item->stok_sparepart > 0)
                                                    <form action="{{ route('create_detail_sparepart_penjualan') }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('POST')
                                                        <input type="hidden" name="kode_penjualan"
                                                            id="kode_penjualan" value="{{ $data->id }}">
                                                        <input type="hidden" name="kode_sparepart"
                                                            id="kode_sparepart" value="{{ $item->id }}">
                                                        <div class="row">
                                                            <div class="col-md-10">
                                                                <input type="number" value="1"
                                                                    max="{{ $item->stok_sparepart }}"
                                                                    name="qty_sparepart" id="qty_sparepart"
                                                                    class="form-control" required>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <button type="submit" class="btn btn-success"><i
                                                                        class="fas fa-plus"></i></button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                @else
                                                    <span class="badge badge-danger">Stok Tidak Tersedia</span>
                                                @endif

                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

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
<div class="modal fade" id="modal_barang">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tambah Barang</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table" id="TABLES_2">
                                <thead>
                                    <th>No</th>
                                    <th>Barang</th>
                                    <th>Harga Jual</th>
                                    <th>Stok</th>
                                    <th>Aksi</th>
                                </thead>
                                <tbody id="search-result">
                                    @foreach ($all_barang as $item)
                                        <tr>
                                            <td>{{ $loop->index + 1 }}</td>
                                            <td>{{ $item->nama_barang }}</td>
                                            <td>Rp.{{ number_format($item->harga_jual_barang) }},-</td>
                                            <td>{{ $item->stok_barang }}</td>
                                            <td>
                                                <form action="{{ route('create_detail_barang_penjualan') }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('POST')
                                                    <input type="hidden" name="kode_penjualan" id="kode_penjualan"
                                                        value="{{ $data->id }}">
                                                    <input type="hidden" name="kode_barang" id="kode_barang"
                                                        value="{{ $item->id }}">
                                                    <div class="row">
                                                        <div class="col-md-10">
                                                            <input type="number" value="1"
                                                                max="{{ $item->stok_barang }}" name="qty_barang"
                                                                id="qty_barang" class="form-control" required>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <button type="submit" class="btn btn-success"><i
                                                                    class="fas fa-plus"></i></button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

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
<div class="modal fade" id="modal_garansi">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tambah Garansi</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('store_garansi_penjualan') }}" method="POST">
                @csrf
                @method('POST')

                <div class="modal-body">
                    <input type="hidden" name="kode_garansi" id="kode_garansi"
                        value="{{ $data->kode_penjualan }}">
                    <div class="form-group">
                        <label>Nama Garansi</label>
                        <input type="text" placeholder="Nama Garansi" name="nama_garansi" id="nama_garansi"
                            class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tanggal Mulai Garansi</label>
                                <input type="date" name="tgl_mulai_garansi" id="tgl_mulai_garansi"
                                    value="{{ date('Y-m-d') }}" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tanggal Exp Garansi</label>
                                <input type="date" name="tgl_exp_garansi" id="tgl_exp_garansi"
                                    class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Catatan Garansi</label>
                        <textarea name="catatan_garansi" placeholder="Catatan Garansi ..." id="catatan_garansi" class="form-control"
                            cols="30" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-success">
            <div class="card-header">
                <div class="card-title">
                    Data Penjualan Hari ini
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="TABLES_3">
                        <thead>
                            <th>#</th>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Catatan</th>
                            <th>Total Harga</th>
                            <th>Bayar</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </thead>
                        <tbody>
                            @foreach ($view_penjualan as $item)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $item->kode_penjualan }}</td>
                                    <td>{{ $item->nama_customer }}</td>
                                    <td>{{ $item->catatan_customer }}</td>
                                    <td>Rp.{{ number_format($item->total_penjualan) }},-</td>
                                    <td>Rp.{{ number_format($item->total_bayar) }},-</td>
                                    <td>
                                        @switch($item->status_penjualan)
                                            @case(1)
                                                <span class="badge badge-success">Dibayar</span>
                                            @break

                                            @case(2)
                                                <span class="badge badge-info">Disimpan</span>
                                            @break

                                            @default
                                        @endswitch
                                    </td>
                                    <td>
                                        <a href="#" class="btn btn-primary btn-sm" data-toggle="modal"
                                            data-target="#modal_view_penjualan_{{ $item->id }}"><i
                                                class="fas fa-eye"></i></a>
                                        @if ($item->status_penjualan == '2')
                                            <a href="{{ route('edit_penjualan', $item->id) }}"
                                                class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                        @endif
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
@foreach ($view_penjualan as $item)
    <div class="modal fade" id="modal_view_penjualan_{{ $item->id }}">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ $item->kode_penjualan }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-8">
                                    <table>
                                        <tr>
                                            <td>Kode Invoice </td>
                                            <td>: {{ $item->kode_penjualan }}</td>
                                        </tr>
                                        <tr>
                                            <td>Tanggal </td>
                                            <td>: {{ $item->created_at }}</td>
                                        </tr>
                                        <tr>
                                            <td>Nama Pelanggan </td>
                                            <td>: {{ $item->nama_customer }}</td>
                                        </tr>
                                        <tr>
                                            <td>Catatan </td>
                                            <td>: {{ $item->catatan_customer }}</td>
                                        </tr>
                                        <tr>
                                            <td>Status </td>
                                            <td>: @switch($item->status_penjualan)
                                                    @case(1)
                                                        <span class="badge badge-success">Selesai</span>
                                                    @break

                                                    @case(2)
                                                        <span class="badge badge-warning">Belum Selesai</span>
                                                    @break

                                                    @default
                                                @endswitch
                                            </td>
                                        </tr>

                                    </table>
                                </div>
                                <div class="col-md-4">
                                    <table>
                                        <tr>
                                            <td>Total </td>
                                            <td>: Rp.{{ number_format($item->total_penjualan) }},-</td>
                                        </tr>
                                        <tr>
                                            <td>Bayar </td>
                                            <td>: Rp.{{ number_format($item->total_bayar) }},-</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                        </div>
                        <div class="col-md-12">
                            <label>Barang Yang Dipesan</label>
                            <div class="table-responsive">
                                <table class="table" id="TABLES_4">
                                    <thead>
                                        <th>#</th>
                                        <th>Barang</th>
                                        <th>Harga</th>
                                        <th>Qty</th>
                                        <th>Total</th>
                                    </thead>
                                    <tbody>
                                        @php
                                            $no = 1;
                                        @endphp
                                        @foreach ($view_barang as $b)
                                            @if ($b->kode_penjualan == $item->id)
                                                <tr>
                                                    <td>{{ $no++ }}</td>
                                                    <td>{{ $b->nama_barang }}</td>
                                                    <td>Rp.{{ number_format($b->harga_jual_barang) }},-</td>
                                                    <td>{{ number_format($b->qty_barang) }}</td>
                                                    <td>Rp.{{ number_format($b->harga_jual_barang * $b->qty_barang) }},-
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <hr>
                        </div>

                        <div class="col-md-12">
                            <label>Sparepart Yang Dipesan</label>
                            <div class="table-responsive">
                                <table class="table" id="TABLES_4">
                                    <thead>
                                        <th>#</th>
                                        <th>Sparepart</th>
                                        <th>Harga</th>
                                        <th>Qty</th>
                                        <th>Total</th>
                                    </thead>
                                    <tbody>
                                        @php
                                            $no = 1;
                                        @endphp
                                        @foreach ($view_sparepart as $b)
                                            @if ($b->kode_penjualan == $item->id)
                                                <tr>
                                                    <td>{{ $no++ }}</td>
                                                    <td>{{ $b->nama_sparepart }}</td>
                                                    <td>Rp.{{ number_format($b->harga_jual) }},-</td>
                                                    <td>{{ number_format($b->qty_sparepart) }}</td>
                                                    <td>Rp.{{ number_format($b->harga_jual * $b->qty_sparepart) }},-
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
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
@endforeach
<script>
    function formatRupiah(angka, prefix) {
        var number_string = angka.toString().replace(/[^,\d]/g, "");
        var split = number_string.split(",");
        var sisa = split[0].length % 3;
        var rupiah = split[0].substr(0, sisa);
        var ribuan = split[0].substr(sisa).match(/\d{3}/g);

        if (ribuan) {
            separator = sisa ? "." : "";
            rupiah += separator + ribuan.join(".");
        }

        rupiah = split[1] != undefined ? rupiah + ',' + split[1] :
            rupiah; // Tambahkan kondisi untuk menghilangkan angka 0 di depan jika tidak ada koma
        return prefix == undefined ? rupiah : (rupiah ? 'Rp. ' + rupiah : '');
    }

    function getNumericValue(rupiah) {
        var numericValue = rupiah.replace(/[^0-9]/g, "");
        return numericValue;
    }

    var inbayar = document.getElementById("in_bayar");
    var hiddenTBayar = document.getElementById("total_bayar");

    inbayar.addEventListener("input", function(e) {
        var biaya = e.target.value;
        var rupiah = formatRupiah(biaya);
        var numericValue = getNumericValue(biaya);
        e.target.value = rupiah;
        hiddenTBayar.value = numericValue;
    });
</script>
