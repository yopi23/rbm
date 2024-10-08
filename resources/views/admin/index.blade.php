@extends('admin.layout.app')
@section('content-app')
@section('dashboard', 'active')
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">{{ $page }}</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">{{ $page }}</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
    @if ($this_user->jabatan == '0')
        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Small boxes (Stat box) -->
                <div class="row">
                    <div class="col-12 col-sm-6 col-md-12">
                        <div class="info-box mb-3">
                            <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-users"></i></span>

                            <div class="info-box-content">
                                <span class="info-box-text">Owner</span>
                                <span class="info-box-number">{{ number_format($data->count()) }}</span>
                            </div>
                            <!-- /.info-box-content -->
                        </div>
                        <!-- /.info-box -->
                    </div>
                </div>
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
                <!-- /.row -->
            </div><!-- /.container-fluid -->
        </section>
        <!-- /.content -->
</div>
@endsection
@else
<!-- Main content -->
<section class="content">
<div class="container-fluid">
    <!-- Small boxes (Stat box) -->
    @if ($this_user->jabatan == '1' || $this_user->jabatan == '2')
        <div class="row">
            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box mb-3">
                    <a class="info-box-icon bg-primary elevation-1" href="{{ route('list_all_service') }}">
                        <i class="fas fa-cog"></i>
                    </a>
                    <div class="info-box-content">
                        <span class="info-box-text">Service</span>
                        <span class="info-box-number">Rp.{{ number_format($total_service) }},-</span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box mb-3">

                    <a class="info-box-icon bg-success elevation-1" href="{{ route('penjualan') }}"><i
                            class="fas fa-shopping-cart"></i></a>

                    <div class="info-box-content">
                        <span class="info-box-text">Penjualan</span>
                        <span class="info-box-number">Rp.{{ number_format($total_penjualan) }},-</span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box mb-3">
                    <span class="info-box-icon bg-dark elevation-1"><i class="fas">&#xf155;</i></span>

                    <div class="info-box-content">
                        <span class="info-box-text">Hari Ini</span>
                        <span
                            class="info-box-number">Rp.{{ number_format($total_penjualan + $total_service + $total_pemasukkan_lain - $total_pengeluaran) }},-</span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box mb-3">
                    {{-- @if ($this_user->jabatan == '1') --}}
                    <a class="info-box-icon bg-info elevation-1" href="{{ route('laci.form') }}">
                        <i class="fas fa-cog"></i>
                    </a>
                    {{-- @else
                        <a class="info-box-icon bg-info elevation-1" href="#">
                            <i class="fas
                            fa-cog"></i>
                        </a>
                    @endif --}}
                    <div class="info-box-content">
                        <span class="info-box-text">Laci</span>
                        <span class="info-box-number">Rp.{{ number_format($totalReceh) }},-</span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box mb-3">

                    <a class="info-box-icon bg-warning elevation-1" href="{{ route('penjualan') }}"><i
                            class="fas fa-cogs text-white"></i></a>

                    <div class="info-box-content">
                        <span class="info-box-text">Pengeluaran</span>
                        <span class="info-box-number">Rp.{{ number_format($debit) }},-</span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
            <div class="col-12 col-sm-6 col-md-4">
                <div class="info-box mb-3">
                    @if ($sumreal >= $totalReceh)
                        <a class="info-box-icon bg-success elevation-1" href="#" data-toggle="modal"
                            data-target="#reallaci">
                            <i class="fas">&#xf155;</i></a>

                        <div class="info-box-content">
                            <span class="info-box-text">Uang sebenarnya</span>
                            <span class="info-box-number">
                                Rp.{{ number_format($sumreal) }},-
                            </span>
                        </div>
                    @else
                        <a class="info-box-icon bg-danger elevation-1" href="#" data-toggle="modal"
                            data-target="#reallaci"><i class="fas">&#xf155;</i></a>

                        <div class="info-box-content">
                            <span class="info-box-text">Uang sebenarnya <strong class="bg-danger"
                                    style="padding: 5px;border-radius: 20px;">Kurang</strong></span>
                            <span class="info-box-number">
                                Rp.{{ number_format($sumreal) }},-
                            </span>
                        </div>
                    @endif
                    <!-- /.info-box-content -->
                </div>
                <!-- /.info-box -->
            </div>
        </div>
    @endif
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
    @if ($this_user->jabatan == '1' || $this_user->jabatan == '2')

        @if ($penarikan->isNotEmpty())
            @foreach ($penarikan as $data)
                <span class="alert alert-danger" style="display: block; margin-bottom:5px">
                    Nama: {{ $data->name }} - Mengajukan: Rp. {{ number_format($data->jumlah_penarikan) }}
                    <br>
                </span>
            @endforeach
            {{-- @else
                No recent withdrawals available. --}}
        @endif

    @endif
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary card-outline mt-3">
                <div class="card-header">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a class="nav-link active" href="#servis" data-toggle="tab">Servis</a>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="#catatan" data-toggle="tab">Catatan</a>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="#order" data-toggle="tab">List Order</a>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="#stok_kosong" data-toggle="tab">Stok
                                Kosong</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('penjualan') }}">Penjualan</a>
                        </li>
                        <li class="nav-item"><a class="nav-link"
                                href="{{ route('pengembalian') }}">Pengembalian</a></li>
                        <li class="nav-item"><a class="nav-link" href="#pemasukan_lain"
                                data-toggle="tab">Pemasukan
                                Lain</a></li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="servis">
                            <div class="row">
                                <div class="col-md-12">
                                    <form action="{{ route('create_service_in_dashboard') }}" method="POST">
                                        @csrf
                                        @method('POST')
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Kode Service</label>
                                                    <input type="text" value="{{ $kode_service }}"
                                                        name="kode_service" id="kode_service"
                                                        class="form-control" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Tanggal</label>
                                                    <input type="date" value="{{ date('Y-m-d') }}"
                                                        name="tgl_service" id="tgl_service" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Penginput</label>
                                                    <input type="text" value="{{ auth()->user()->name }}"
                                                        class="form-control" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Nama Pelanggan</label>
                                                    <input type="text" name="nama_pelanggan"
                                                        id="nama_pelanggan" class="form-control" autofocus>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>No Telp</label>
                                                    <input type="text" name="no_telp" id="nama_pelanggan"
                                                        class="form-control" autocomplete="off">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Type Unit</label>
                                            <input type="text" name="type_unit" id="type_unit"
                                                class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label>Keterangan</label>
                                            <textarea name="ket" id="ket" class="form-control summernote" cols="30" rows="5"></textarea>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table" id="dataTable ">
                                                <thead class="thead-light">
                                                    <th>Sparepart</th>
                                                    <th>Harga + jasa</th>
                                                    <th>Qty</th>
                                                    <th><button type="button" class="btn btn-success"
                                                            id="add-dynamic-input"><i
                                                                class="fas fa-plus"></i></button></th>
                                                </thead>
                                                <tbody class="dynamic-input">
                                                    <tr>
                                                        <td>
                                                            <select name="kode_sparepart[0]"
                                                                id="kode_sparepart[0]"
                                                                class="form-control select-bootstrap kode_sparepart">
                                                                <option value="">-- Pilih Sparepart --
                                                                </option>
                                                                @forelse ($sparepart as $item)
                                                                    <option value="{{ $item->id }}"
                                                                        data-stok="{{ $item->stok_sparepart }}"
                                                                        data-harga="{{ $item->harga_jual + $item->harga_pasang }}"
                                                                        {{ $item->stok_sparepart <= 0 ? 'disabled' : '' }}>
                                                                        {{ $item->nama_sparepart }}
                                                                        {{ $item->stok_sparepart <= 0 ? '( Stok Kosong )' : '' }}
                                                                    </option>
                                                                @empty
                                                                @endforelse
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="text" name="harga_kode_sparepart[0]"
                                                                id="harga_kode_sparepart[0]"
                                                                class="form-control harga_part" readonly>
                                                        </td>

                                                        <td>
                                                            <input type="number" value="1"
                                                                name="qty_kode_sparepart[0]"
                                                                id="qty_kode_sparepart[0]"
                                                                class="form-control qty_part">
                                                        </td>
                                                        <td><button type="button"
                                                                class="btn btn-danger remove_dynamic"
                                                                name="remove_dynamic" id="remove_dynamic"><i
                                                                    class="fas fa-trash"></i></button></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Biaya</label>
                                                    <input type="text" value="0" class="form-control"
                                                        name="biaya_servis" id="biaya_servis" hidden>
                                                    <input type="text" class="form-control"
                                                        name="in_biaya_servis" id="in_biaya_servis">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Dp</label>
                                                    <input type="text" class="form-control" name="dp"
                                                        id="dp" value="0" hidden>
                                                    <input type="text" class="form-control" name="in_dp"
                                                        id="in_dp">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-item-center">
                                            <button type="submit"
                                                class="btn btn-primary form-control">Simpan</button>
                                        </div>
                                    </form>
                                </div>

                            </div>

                        </div>
                        <div class="tab-pane" id="catatan">
                            <div class="row">
                                <div class="col-md-4">
                                    <form action="{{ route('create_catatan') }}" method="POST"
                                        id="form_catatan">
                                        @csrf
                                        @method('POST')
                                        <div class="form-group">
                                            <label for="">Tanggal</label>
                                            <input type="date" name="tgl_catatan" value="{{ date('Y-m-d') }}"
                                                id="tgl_catatan" class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label>Judul Catatan</label>
                                            <input type="text" name="judul_catatan" id="judul_catatan"
                                                class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Catatan</label>
                                            <textarea name="catatan" id="catatan" placeholder="Catatan" class="form-control" cols="30" rows="10"
                                                required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-success">Simpan</button>
                                    </form>
                                </div>
                                <div class="col-md-8" style="overflow: scroll; height:500px;">
                                    <table border="0" id="table_catatan">
                                        @foreach ($catatan as $item)
                                            <tr>
                                                <div class="card card-success card-outline">
                                                    <div class="card-header">
                                                        {{ $item->judul_catatan }}
                                                    </div>
                                                    <div class="card-body">
                                                        {{ $item->catatan }}
                                                    </div>
                                                    <div class="card-footer">
                                                        <form action="{{ route('delete_catatan', $item->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger"><i
                                                                    class="fas fa-trash"></i></button>
                                                        </form>

                                                    </div>
                                                </div>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane" id="pemasukan_lain">
                            <div class="row">
                                <div class="col-md-4">
                                    <form action="{{ route('create_pemasukkan_lain') }}" method="POST">
                                        @csrf
                                        @method('POST')
                                        <div class="form-group">
                                            <label>Tanggal</label>
                                            <input type="date" name="tgl_pemasukan" id="tgl_pemasukan_lain"
                                                class="form-control" value="{{ date('Y-m-d') }}">
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
                                            <label>Judul</label>
                                            <input type="text" name="judul_pemasukan"
                                                id="judul_pemasukan_lain" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Catatan</label>
                                            <textarea name="catatan_pemasukan" class="form-control" id="catatan_pemasukan_lain" cols="30" rows="5"
                                                required></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Jumlah</label>
                                            <input type="number" name="jumlah_pemasukan"
                                                id="jumlah_pemasukan_lain" class="form-control" value="0">
                                        </div>
                                        <button type="submit"
                                            class="btn btn-success form-control">Simpan</button>
                                    </form>
                                </div>
                                <div class="col-md-8">
                                    <table class="table" id="TABLES_3">
                                        <thead>
                                            <th>No</th>
                                            <th>Tanggal</th>
                                            <th>Judul</th>
                                            <th>Catatan</th>
                                            <th>Jumlah</th>
                                            <th>Aksi</th>
                                        </thead>
                                        <tbody>
                                            @forelse ($pemasukkan_lain as $item)
                                                <tr>
                                                    <td>{{ $loop->index + 1 }}</td>
                                                    <td>{{ $item->tgl_pemasukkan }}</td>
                                                    <td>{{ $item->judul_pemasukan }}</td>
                                                    <td>{{ $item->catatan_pemasukkan }}</td>
                                                    <td>Rp.{{ number_format($item->jumlah_pemasukkan) }},-</td>
                                                    <td>
                                                        <form
                                                            action="{{ route('delete_pemasukkan_lain', $item->id) }}"
                                                            onsubmit="confirm('Apakah Kamu Yakin ?')"
                                                            method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="btn btn-sm btn-danger"><i
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
                        <div class="tab-pane" id="order">
                            <div class="row">
                                <div class="col-md-12">
                                    <a href="#" data-toggle="modal" data-target="#modal_list_order"
                                        class="btn btn-success">Tambah</a>
                                    <hr>
                                    <table class="table" id="TABLES_4">
                                        <thead>
                                            <th>No</th>
                                            <th>Tanggal</th>
                                            <th>Nama</th>
                                            <th>Catatan</th>
                                            <th>Aksi</th>
                                        </thead>
                                        <tbody>
                                            @foreach ($list_order as $item)
                                                <tr>
                                                    <td>{{ $loop->index + 1 }}</td>
                                                    <td>{{ $item->tgl_order }}</td>
                                                    <td>{{ $item->nama_order }}</td>
                                                    <td>{{ $item->catatan_order }}</td>
                                                    <td>
                                                        <form action="{{ route('delete_list_order', $item->id) }}"
                                                            onsubmit="return confirm('Apa Kamu Yakin ?')"
                                                            method="post">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="btn btn-sm btn-danger"><i
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
                        <div class="tab-pane" id="stok_kosong">
                            <div class="row">
                                <div class="col-md-12">
                                    <table class="table" id="TABLES_4">
                                        <thead>
                                            <th>No</th>
                                            <th>Kode Sparepart</th>
                                            <th>Nama Sparepart</th>
                                            <th>Stok</th>
                                        </thead>
                                        <tbody>
                                            @foreach ($sparepart as $item)
                                                @if ($item->stok_sparepart <= 0)
                                                    <tr>
                                                        <td>{{ $loop->index + 1 }}</td>
                                                        <td>{{ $item->kode_sparepart }}</td>
                                                        <td>{{ $item->nama_sparepart }}</td>
                                                        <td>{{ $item->stok_sparepart }}</td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer"></div>
            </div>
        </div>
    </div>
    <br>
    <h4>Data Services</h4>
    <hr>
    <div class="card card-success card-outline">
        <div class="card-body">
            <table class="table" id="dataTable">
                <thead>
                    <th>No</th>
                    <th>Kode</th>
                    <th>Nama Pelanggan</th>
                    <th>Type unit</th>
                    <th>No Hp</th>
                    <th>Keterangan</th>
                    <th>Aksi</th>
                </thead>
                <tbody>
                    @forelse ($service as $item)
                        @if ($item->status_services == 'Antri')
                            <tr>
                                <td>{{ $loop->index + 1 }}</td>
                                <td>{{ $item->kode_service }}</td>
                                <td>{{ $item->nama_pelanggan }}</td>
                                <td>{{ $item->type_unit }}</td>
                                <td>{{ $item->no_telp }}</td>
                                <td>{{ $item->keterangan }}</td>
                                <td>
                                    <a href="{{ route('nota_service', $item->id) }}" target="_blank"
                                        class="btn btn-sm btn-success mt-2"><i class="fas fa-print"></i></a>
                                    <a href="{{ route('nota_tempel', $item->id) }}" target="_blank"
                                        class="btn btn-sm btn-warning mt-2"><i class="fas fa-print"></i></a>
                                    <form action="{{ route('proses_service', $item->id) }}"
                                        onsubmit="return confirm('Apakah Kamu yakin ingin memproses Service ini ?')"
                                        method="POST">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status_services" id="status_services"
                                            value="Diproses">
                                        <button type="submit" class="btn btn-sm btn-primary mt-2">Proses</button>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @empty
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <!-- /.row -->
    {{-- Modal --}}
    <div class="modal fade" id="modal_list_order">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah List Order</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('create_list_order') }}" method="POST">
                    @csrf
                    @method('POST')
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Tanggal</label>
                            <input type="date" value="{{ date('Y-m-d') }}" name="tgl_order" id="tgl_order"
                                class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Nama</label>
                            <input type="text" name="nama_order" id="nama_order" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea name="catatan_order" id="catatan_order" class="form-control" cols="30" rows="10"></textarea>
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
    {{-- uang sebenarnya --}}
    <div class="modal fade" id="reallaci" tabindex="-1" role="dialog" aria-labelledby="recehModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="recehModalLabel">Input uang real</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('laci.real') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <!-- Form input receh -->
                        <div class="form-group">
                            <label for="amount">Jumlah uang</label>
                            <input type="number" class="form-control" id="real" name="real" required>
                        </div>
                        <!-- Tambahkan field lainnya jika diperlukan -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary">Kirim</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- uang sebenarnya --}}

</div><!-- /.container-fluid -->
</section>
<!-- /.content -->
</div>

@if ($isModalRequired)
<!-- Modal untuk input receh -->
<div class="modal fade" id="recehModal" tabindex="-1" role="dialog" aria-labelledby="recehModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="recehModalLabel">Input Receh</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('laci.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <!-- Form input receh -->
                    <div class="form-group">
                        <label for="amount">Jumlah Receh</label>
                        <input type="number" class="form-control" id="receh" name="receh" required>
                    </div>
                    <!-- Tambahkan field lainnya jika diperlukan -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Kirim</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#recehModal').modal('show');
    });
</script>
@endif
@endsection



@section('content-script')
<script>
    var i = 0;
    var perkiraan_harga = 0;
    $('#add-dynamic-input').on('click', function() {
        ++i;
        $('.dynamic-input').append('<tr><td><select name="kode_sparepart[' + i + ']" id="kode_sparepart[' + i +
            ']"  class="form-control select-bootstrap kode_sparepart"><option value="">-- Pilih Sparepart --</option>'
            @forelse ($sparepart as $item)
                +'<option value="' + {{ $item->id }} + '" data-harga="' +
                    {{ $item->harga_jual + $item->harga_pasang }} + '" data-stok="' +
                    {{ $item->stok_sparepart }} + '">{{ $item->nama_sparepart }}</option>'
            @empty @endforelse +
            '</select></td><td><input type="text" name="harga_kode_sparepart[' + i +
            ']" id="harga_kode_sparepart[' + i + ']" class="form-control harga_part" readonly></td><td>' +
            '<input type="text" name="stok_kode_sparepart[' + i + ']" id="stok_kode_sparepart[' + i +
            ']" class="form-control stok_part" readonly></td><td><input type="number" value="1" name="qty_kode_sparepart[' +
            i + ']" id="qty_kode_sparepart[' + i +
            ']" class="form-control qty_part"></td><td><button type="button" class="btn btn-danger remove_dynamic" name="remove_dynamic" id="remove_dynamic"><i class="fas fa-trash"></i></button></td></tr>'
        );
        $('.select-bootstrap').select2({
            theme: 'bootstrap4'
        });
    });
    $(document).on('click', '.remove_dynamic', function() {
        $(this).parents('tr').remove()
    });
    $(document).on('change', '.kode_sparepart', function() {
        var harga = $(this).find(':selected').data('harga');
        var stok = $(this).find(':selected').data('stok');
        var qty = $(this).parents('tr').find('.qty_part').val();
        $(this).parents('tr').find('.harga_part').val(harga);
        $(this).parents('tr').find('.stok_part').val(stok);
    })
    $(document).on('keyup change click', '.qty_part', function() {
        var qty = $(this).val();
        var harga = $(this).parents('tr').find('.kode_sparepart :selected').data('harga');
        var total_harga = harga * qty;
    })
</script>
@endsection
@endif
