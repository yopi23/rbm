@section('pengambilan', 'active')
@section('drop', 'active')
@section('main', 'menu-open')
<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-success">
            <div class="card-header">
                <div class="card-title">
                    Data Service
                </div>
                <div class="card-tools">
                    <a href="#" class="btn btn-success" data-toggle="modal" data-target="#modal_service"
                        name="tambah_service" id="tambah_service"><i class="fas fa-plus"></i></a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
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
                        <tbody>
                            @php
                                $total_harga = 0;
                            @endphp
                            @foreach ($service as $item)
                                @php
                                    $total_harga += $item->total_biaya - $item->dp;
                                @endphp
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $item->nama_pelanggan }}</td>
                                    <td>{{ $item->type_unit }}</td>
                                    <td>{{ $item->keterangan }}</td>
                                    <td>Rp.{{ number_format($item->total_biaya) }},-</td>
                                    <td>Rp.{{ number_format($item->dp) }},-</td>
                                    <td>Rp.{{ number_format($item->total_biaya - $item->dp) }},-</td>
                                    <td>
                                        <form action="{{ route('destroy_detail_pengembalian', $data->id) }}"
                                            onsubmit="return confirm('Apakah Kamu Yakin ?')" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="id_service" id="id_service"
                                                value="{{ $item->id }}">
                                            <button type="submit" name="delete" id="delete"
                                                class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
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
    <div class="col-md-4">
        <form action="{{ route('update_pengembalian', $data->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card card-outline card-success">
                <div class="card-header">
                    <div class="card-title">
                        Data Pengembalian
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Total</label>
                        <h4>Rp.{{ number_format($total_harga) }},-</h4>
                        <input type="text" value="{{ $total_harga }}" name="totalharga" class="form-control"
                            hidden>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Pengambilan</label>
                        <input type="date" value="{{ date('Y-m-d') }}" name="tgl_pengambilan" id="tgl_pengambilan"
                            class="form-control">
                    </div>
                    <div class="form-group">
                        <select name="id_kategorilaci" class="form-control" required>
                            <option value="">Pilih Kategori Laci</option>
                            @foreach ($listLaci as $kategori)
                                <option value="{{ $kategori->id }}">
                                    {{ $kategori->name_laci }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nama Pengambil</label>
                        <input type="text" placeholder="Nama Pengambil" name="nama_pengambilan" id="nama_pengambilan"
                            class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Total Bayar</label>
                        <input type="number" min="{{ $total_harga }}" value="0" name="total_bayar"
                            id="total_bayar" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success form-control">Bayar</button>
                    <a href=""></a>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="modal_service">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Pengambilan Service</h4>
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
                                                <form action="{{ route('store_detail_pengembalian', $data->id) }}"
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
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
