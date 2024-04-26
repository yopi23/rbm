@section('page', $page)
@include('admin.component.header')
@include('admin.component.navbar')
@include('admin.component.sidebar')

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">@yield('page')</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">@yield('page')</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    @if (session('success'))
                        <div class="alert alert-primary">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-4">
                            <form action="{{ route('update_service', $data->id) }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <div class="card card-success card-outline">
                                    <div class="card-header">
                                        <div class="card-title">
                                            @yield('page')
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Kode Service</label>
                                            <input type="text" name="kode_service" id="kode_service"
                                                class="form-control" value="{{ $data->kode_service }}" disabled>
                                        </div>
                                        <div class="form-group">
                                            <label>Tanggal Service</label>
                                            <input type="date" name="tgl_service" id="tgl_service"
                                                class="form-control" value="{{ $data->tgl_service }}" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Nama Pelanggan</label>
                                            <input type="text" name="nama_pelanggan" id="nama_pelanggan"
                                                class="form-control" value="{{ $data->nama_pelanggan }}" required>
                                        </div>
                                        <div class="form-group">
                                            <label>No Telp</label>
                                            <input type="text" name="no_telp" id="no_telp" class="form-control"
                                                value="{{ $data->no_telp }}" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Type Unit</label>
                                            <input type="text" name="type_unit" id="type_unit" class="form-control"
                                                value="{{ $data->type_unit }}" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Total Biaya</label>
                                            <input type="number" name="total_biaya" id="total_biaya"
                                                class="form-control" value="{{ $data->total_biaya }}" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Keterangan</label>
                                            <textarea name="keterangan" id="keterangan" class="form-control" cols="30" rows="5" required>{{ $data->keterangan }}</textarea>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-success">Simpan</button>
                                        <a href="{{ route('all_service') }}" class="btn btn-danger">Cancel</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card card-outline card-success">
                                        <div class="card-header">
                                            <div class="card-title">
                                                Data Sparepart
                                            </div>
                                            <div class="card-tools">
                                                <a href="#" class="btn btn-success" data-toggle="modal"
                                                    data-target="#modal_sparepart" name="tambah_part"
                                                    id="tambah_part"><i class="fas fa-plus"></i> Tambah</a>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table" id="TABLES_1">
                                                    <thead>
                                                        <th>#</th>
                                                        <th>Sparepart</th>
                                                        <th>Harga Jual</th>
                                                        <th>Total</th>
                                                        <th>Aksi</th>
                                                    </thead>
                                                    <tbody>
                                                        @php
                                                            $total_part_toko = 0;
                                                        @endphp
                                                        @forelse ($detail as $item)
                                                            @php
                                                                $total_part_toko +=
                                                                    $item->detail_harga_part_service * $item->qty_part;
                                                            @endphp
                                                            <tr>
                                                                <td>{{ $loop->index + 1 }}</td>
                                                                <td>{{ $item->nama_sparepart }}</td>
                                                                <td>Rp.{{ number_format($item->detail_harga_part_service) }}
                                                                    X {{ $item->qty_part }}</td>
                                                                <td>Rp.{{ number_format($item->detail_harga_part_service * $item->qty_part) }}
                                                                </td>
                                                                <td>
                                                                    <form
                                                                        action="{{ route('delete_sparepart_toko', $item->id_detail_part) }}"
                                                                        onsubmit="return confirm('Apakah Anda yakin ?')"
                                                                        method="POST">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="btn btn-danger"><i
                                                                                class="fas fa-trash"></i></button>
                                                                    </form>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="card card-outline card-success">
                                        <div class="card-header">
                                            <div class="card-title">
                                                Data Sparepart Lain (Luar Sparepart Toko)
                                            </div>
                                            <div class="card-tools">
                                                <a href="#" data-toggle="modal"
                                                    data-target="#modal_sparepart_luar" class="btn btn-success"
                                                    name="tambah_part" id="tambah_part"><i class="fas fa-plus"></i>
                                                    Tambah</a>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table" id="TABLES_1">
                                                    <thead>
                                                        <th>#</th>
                                                        <th>Sparepart</th>
                                                        <th>Harga Jual + Pasang</th>
                                                        <th>Total</th>
                                                        <th>Aksi</th>
                                                    </thead>
                                                    <tbody>
                                                        @php
                                                            $total_part_luar = 0;
                                                        @endphp
                                                        @forelse ($detail_luar as $item)
                                                            @php
                                                                $total_part_luar += $item->harga_part * $item->qty_part;
                                                            @endphp
                                                            <tr>
                                                                <td>{{ $loop->index + 1 }}</td>
                                                                <td>{{ $item->nama_part }}</td>
                                                                <td>Rp.{{ number_format($item->harga_part) }},- X
                                                                    {{ $item->qty_part }}</td>
                                                                <td>Rp.{{ number_format($item->harga_part * $item->qty_part) }},-
                                                                </td>
                                                                <td>
                                                                    <form
                                                                        action="{{ route('delete_sparepart_luar', $item->id) }}"
                                                                        onsubmit="return confirm('Apakah Anda yakin ?')"
                                                                        method="POST">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <a href="#" data-toggle="modal"
                                                                            data-target="#modal_edit_sparepart_luar_{{ $item->id }}"
                                                                            data-teknisi-id="{{ $teknisi->id }}"
                                                                            data-service-id="{{ $service->id }}"
                                                                            class="btn btn-warning"><i
                                                                                class="fas fa-edit"></i></a>

                                                                        <button type="submit"
                                                                            class="btn btn-danger"><i
                                                                                class="fas fa-trash"></i></button>
                                                                    </form>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="card card-outline card-success">
                                        <div class="card-header">
                                            <h3>Total Perkiraan Harga</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table">
                                                    <thead>
                                                        <th>#</th>
                                                        <th>Part Service</th>
                                                        <th>Total Harga</th>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>1</td>
                                                            <td>Sparepart Dari Toko</td>
                                                            <td>Rp.{{ number_format($total_part_toko) }},00</td>
                                                        </tr>
                                                        <tr>
                                                            <td>2</td>
                                                            <td>Sparepart Dari Luar Toko</td>
                                                            <td>Rp.{{ number_format($total_part_luar) }},00</td>
                                                        </tr>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <td colspan="2">Perkiraan Keseluruhan Harga (Rp)</td>
                                                            <td>
                                                                <h3>{{ number_format($total_part_toko + $total_part_luar) }},00
                                                                </h3>
                                                            </td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
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
                                <div class="form-group">
                                    <label>Search</label>
                                    <input type="text" name="keyword" id="keyword" class="form-control">
                                </div>
                                <div class="table-responsive">
                                    <table class="table" id="TABLES_2">
                                        <thead>
                                            <th>No</th>
                                            <th>Sparepart</th>
                                            <th>Harga Jual + Pasang</th>
                                            <th>Stok</th>
                                            <th>Aksi</th>
                                        </thead>
                                        <tbody id="search-result">

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
        @foreach ($detail as $item)
            <div class="modal fade" id="modal_edit_sparepart_toko_{{ $item->id_detail_part }}">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Edit Sparepart Toko</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form action="{{ route('update_sparepart_toko', $item->id_detail_part) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-body">
                                <input type="hidden" name="kode_services" id="kode_services"
                                    value="{{ $data->id }}">
                                <div class="form-group">
                                    <label>Nama SparePart</label>
                                    <input type="text" value="{{ $item->nama_sparepart }}" name="nama_part"
                                        id="nama_part" class="form-control" disabled required>
                                </div>
                                <div class="form-group">
                                    <label>Harga SparePart + Harga Pasang</label>
                                    <input type="number" value="{{ $item->harga_jual + $item->harga_pasang }}"
                                        name="harga_part" id="harga_part" class="form-control" disabled required>
                                </div>
                                <div class="form-group">
                                    <label>Qty</label>
                                    <input type="number" value="{{ $item->qty_part }}" name="qty_part"
                                        id="qty_part" class="form-control" required>
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
        @endforeach
        <div class="modal fade" id="modal_sparepart_luar">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Tambah Sparepart Luar</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('store_sparepart_luar') }}" method="POST">
                        @csrf
                        @method('POST')
                        <div class="modal-body">
                            <input type="hidden" name="kode_services" id="kode_services"
                                value="{{ $data->id }}">
                            <div class="form-group">
                                <label>Nama SparePart</label>
                                <input type="text" name="nama_part" id="nama_part" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Harga SparePart / Biji</label>
                                <input type="number" value="0" name="harga_part" id="harga_part"
                                    class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Qty</label>
                                <input type="number" value="1" name="qty_part" id="qty_part"
                                    class="form-control" required>
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
        @foreach ($detail_luar as $item)
            <div class="modal fade" id="modal_edit_sparepart_luar_{{ $item->id }}">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Edit Sparepart Luar</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form action="{{ route('update_sparepart_luar', $item->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-body">
                                <input type="hidden" name="kode_services" id="kode_services"
                                    value="{{ $data->id }}">
                                <div class="form-group">
                                    <label>Nama SparePart</label>
                                    <input type="text" value="{{ $item->nama_part }}" name="nama_part"
                                        id="nama_part" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Harga SparePart / Biji</label>
                                    <input type="number" value="{{ $item->harga_part }}" name="harga_part"
                                        id="harga_part" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Qty</label>
                                    <input type="number" value="{{ $item->qty_part }}" name="qty_part"
                                        id="qty_part" class="form-control" required>
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
        @endforeach
        <!-- /.modal -->
    </section>
    <!-- /.content -->
</div>
@section('content-script')
    <script>
        $('#keyword').on('keyup', function() {
            val = $(this).val();
            $.ajax({
                type: 'GET',
                url: '{{ route('search_sparepart') }}',
                data: {
                    'search': val,
                    'kode_service': '{{ $data->id }}'
                },
                success: function(data) {
                    $('#search-result').html(data)
                }
            });
        });
    </script>
@endsection
@include('admin.component.footer')
