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
    <div class="btn-group" role="group" aria-label="Basic radio toggle button group">
        <input type="radio" class="btn-check" name="btn-shortcut" id="btn-shortcut" autocomplete="off" checked
            hidden>
        <label class="btn btn-outline-primary" for="btn-shortcut">Shortcut</label>

        <input type="radio" class="btn-check" name="btn-dashboard" id="btn-dashboard" autocomplete="off" hidden>
        <label class="btn btn-outline-primary" for="btn-dashboard">Dashboard</label>
    </div>
    <div class="my-2" id="main">
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
                            <li class="nav-item"><a class="nav-link active" href="#servis"
                                    data-toggle="tab">Servis</a>
                            </li>
                            <li class="nav-item"><a class="nav-link" href="#catatan"
                                    data-toggle="tab">Catatan</a>
                            </li>
                            <li class="nav-item"><a class="nav-link" href="#order" data-toggle="tab">List
                                    Order</a>
                            </li>
                            <li class="nav-item"><a class="nav-link" href="#stok_kosong" data-toggle="tab">Stok
                                    Kosong</a></li>
                            <li class="nav-item"><a class="nav-link"
                                    href="{{ route('penjualan') }}">Penjualan</a>
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
                                                            name="tgl_service" id="tgl_service"
                                                            class="form-control">
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
                                                                <input type="text"
                                                                    name="harga_kode_sparepart[0]"
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
                                                        <input type="text" class="form-control biaya-input"
                                                            name="in_biaya_servis" id="in_biaya_servis">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Dp</label>
                                                        <input type="text" class="form-control" name="dp"
                                                            id="dp" value="0" hidden>
                                                        <input type="text" class="form-control dp-input"
                                                            name="in_dp" id="in_dp">
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
                                                <input type="date" name="tgl_catatan"
                                                    value="{{ date('Y-m-d') }}" id="tgl_catatan"
                                                    class="form-control">
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
                                                            <form
                                                                action="{{ route('delete_catatan', $item->id) }}"
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
                                                <input type="date" name="tgl_pemasukan"
                                                    id="tgl_pemasukan_lain" class="form-control"
                                                    value="{{ date('Y-m-d') }}">
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
                                                    id="jumlah_pemasukan_lain" class="form-control"
                                                    value="0">
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
                                                            <form
                                                                action="{{ route('delete_list_order', $item->id) }}"
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
                                            <button type="submit"
                                                class="btn btn-sm btn-primary mt-2">Proses</button>
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
                                <input type="date" value="{{ date('Y-m-d') }}" name="tgl_order"
                                    id="tgl_order" class="form-control">
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
                                <input type="number" class="form-control" id="real" name="real"
                                    required>
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
    </div>

    {{-- jalan pintas --}}
    <div class="my-2" id="shortcut">
        <div class="container-center">
            <center class="mb-4">
                <h5>Tambah Data</h5>
            </center>
            <div class="input-group my-2">
                <label class="input-group-text" for="id_kategorilaci">Jenis</label>
                <select name="id_kategorilaci" class="form-control" id="transactionType" required>
                    <option value="" disabled selected>--Pilih jenis transaksi--</option>
                    <option value="pemasukan">Pemasukan</option>
                    <option value="pengeluaran">Pengeluaran</option>
                    <option value="penjualan">Penjualan</option>
                    <option value="service">Service</option>
                </select>
            </div>

            <div class="listservice table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover" id="dataTable">
                    <thead>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Unit</th>
                        <th>Keterangan</th>
                        <th>Aksi</th>
                    </thead>
                    <tbody>
                        @forelse ($service as $item)
                            @if ($item->status_services == 'Antri')
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td><b>{{ $item->nama_pelanggan }}</b><br>{{ $item->kode_service }}<br>{{ $item->no_telp }}
                                    </td>
                                    <td>{{ $item->type_unit }}</td>
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
                                            <button type="submit"
                                                class="btn btn-sm btn-primary mt-2">Proses</button>
                                        </form>
                                    </td>
                                </tr>
                            @endif
                        @empty
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{-- form service --}}
            <form action="{{ route('create_service_in_dashboard') }}" method="POST">
                @csrf
                @method('POST')
                <div class="formservice d-none">

                    <input type="text" value="{{ $kode_service }}" name="kode_service" id="kode_service"
                        class="form-control" hidden>
                    <input type="date" value="{{ date('Y-m-d') }}" name="tgl_service" id="tgl_service"
                        class="form-control" hidden>
                    <input type="text" value="{{ auth()->user()->name }}" class="form-control" hidden>

                    <div class="input-group my-2">
                        <span class="input-group-text">Nama</span>
                        <input type="text" name="nama_pelanggan" id="nama_pelanggan" class="form-control"
                            autofocus>
                    </div>
                    <div class="input-group my-2">
                        <span class="input-group-text">No Tlp.</span>
                        <input type="text" name="no_telp" id="no_telp" class="form-control"
                            autocomplete="off">
                    </div>
                    <div class="input-group my-2" id="typeGrup">
                        <span class="input-group-text">Type</span>
                        <input type="text" name="type_unit" id="type_unit" class="form-control">
                    </div>

                    <div class="input-group my-2" id="keteranganGrup">
                        <span class="input-group-text">Keterangan</span>
                        <textarea class="form-control" name="ket" id="ket" aria-label="With textarea"></textarea>
                    </div>
                    <div class="table-responsive border border-primary rounded p-3">
                        <label>saran harga</label>
                        <select name="kode_part[0]" id="kode_part[0]"
                            class="form-control select-bootstrap kode_part">
                            <option value="">-- Pilih Sparepart --
                            </option>
                            @forelse ($sparepart as $item)
                                <option value="{{ $item->id }}" data-stok="{{ $item->stok_sparepart }}"
                                    data-harga="{{ $item->harga_jual + $item->harga_pasang }}"
                                    {{ $item->stok_sparepart <= 0 ? 'disabled' : '' }}>
                                    {{ $item->nama_sparepart . ' ' . '(Rp.' . number_format($item->harga_jual + $item->harga_pasang) . ')' }}
                                    {{ $item->stok_sparepart <= 0 ? '( Stok Kosong )' : '' }}
                                </option>
                            @empty
                            @endforelse
                        </select>
                        <div class="input-group">
                            <input type="text" name="harga_kode_part[0]" id="harga_kode_part[0]"
                                class="form-control harga_spart" readonly>

                            <input type="number" value="1" name="qty_kode_part[0]" id="qty_kode_part[0]"
                                class="form-control qty_spart">
                        </div>

                    </div>
                    <div class="input-group my-2">
                        <span class="input-group-text">Biaya</span>
                        <input type="text" value="0" class="form-control" name="biaya_servis"
                            id="biaya_servis" hidden>
                        <input type="text" class="form-control biaya-input" name="in_biaya_servis"
                            id="in_biaya_servis">

                        <span class="input-group-text">DP</span>
                        <input type="text" class="form-control" name="dp" id="dp" value="0"
                            hidden>
                        <input type="text" class="form-control dp-input" name="in_dp" id="in_dp">
                    </div>
                    <div class="d-flex align-item-center">
                        <button type="submit" class="btn btn-primary form-control">Simpan</button>
                    </div>
                </div>
            </form>
            {{-- end form service --}}
            {{-- form penjualan --}}
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
                    <input type="text" id="kodetrx" class="form-control"
                        value="{{ $kodetrx->kode_penjualan }}" readonly />
                    <input type="text" id="kodetrxid" class="form-control" value="{{ $kodetrx->id }}"
                        hidden />
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
                        <label class="input-group-text" for="customer">pembeli</label>
                        <input type="text" name="customer" id="customer" class="form-control" required />
                    </div>
                    <div class="input-group my-2">
                        <span class="input-group-text">Keterangan</span>
                        <textarea class="form-control" name="ket" id="ket" aria-label="With textarea"></textarea>
                    </div>
                    <div class="input-group my-2">
                        <label class="input-group-text" for="bayar">Bayar</label>
                        <input type="number" name="bayar" id="bayar" class="form-control" required />
                    </div>
                    <div class="d-flex align-item-center">
                        <button type="submit" name="simpan" value="newbayar"
                            class="btn btn-primary form-control">Simpan</button>
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
            {{-- end penjualan --}}
        </div>
    </div>
    {{-- jalan pintas --}}

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
    $(document).on('change', '.kode_part', function() {
        var harga = $(this).find(':selected').data('harga');
        var stok = $(this).find(':selected').data('stok');

        // Format harga menjadi format uang
        var formattedHarga = formatCurrency(harga);

        // Update input harga dengan format uang
        $(this).closest('.table-responsive').find('.harga_spart').val(formattedHarga);
    })
    $(document).on('keyup change click', '.qty_part', function() {
        var qty = $(this).val();
        var harga = $(this).parents('tr').find('.kode_sparepart :selected').data('harga');
        var total_harga = harga * qty;
    })

    function formatCurrency(amount) {
        return 'Rp. ' + amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g,
            "."); // Menggunakan titik sebagai pemisah ribuan
    }
</script>

{{-- transisi --}}
<script>
    // Fungsi untuk menampilkan konten Shortcut dan menyembunyikan Dashboard
    function showShortcut() {
        document.getElementById('shortcut').style.display = 'block';
        document.getElementById('main').style.display = 'none';
        setActiveButton('btn-shortcut');
    }

    // Fungsi untuk menampilkan konten Dashboard dan menyembunyikan Shortcut
    function showDashboard() {
        document.getElementById('main').style.display = 'block';
        document.getElementById('shortcut').style.display = 'none';
        setActiveButton('btn-dashboard');
    }
    // Fungsi untuk mengatur kelas aktif pada tombol
    function setActiveButton(activeId) {
        const labels = document.querySelectorAll('.btn-group label');
        labels.forEach(label => {
            if (label.htmlFor === activeId) {
                label.classList.remove('btn-outline-primary');
                label.classList.add('btn-primary');
            } else {
                label.classList.remove('btn-primary');
                label.classList.add('btn-outline-primary');
            }
        });
    }
    // Menampilkan konten Shortcut saat halaman diakses
    window.onload = showShortcut;

    // Menambahkan event listener pada tombol
    document.getElementById('btn-shortcut').addEventListener('click', showShortcut);
    document.getElementById('btn-dashboard').addEventListener('click', showDashboard);
</script>

{{-- jenis transaksi --}}
<script>
    document.getElementById('transactionType').addEventListener('change', function() {
        const formServices = document.querySelectorAll('.formservice');
        const formSales = document.querySelectorAll('.formSales');
        const id_kategorilaciGrup = document.querySelectorAll('.kategorilaciGrup');
        const listservice = document.querySelectorAll('.listservice');

        // Menampilkan atau menyembunyikan field tambahan berdasarkan jenis transaksi
        if (this.value === 'service') {
            formServices.forEach(service => service.classList.remove('d-none'));
            formSales.forEach(jualpart => jualpart.classList.add('d-none'));
            listservice.forEach(listservice => listservice.classList.add('d-none'));

        } else if (this.value === 'penjualan') {
            formServices.forEach(service => service.classList.add('d-none'));
            id_kategorilaciGrup.forEach(laci => laci.classList.remove('d-none'));
            formSales.forEach(jualpart => jualpart.classList.remove('d-none'));
            listservice.forEach(listservice => listservice.classList.add('d-none'));
        } else {
            listservice.forEach(listservice => listservice.classList.add('d-none'));
        }
    });
</script>
{{-- pencarian part --}}
<script>
    $(document).ready(function() {
        $('#modal_sp').on('shown.bs.modal', function() {
            $('#caripart').focus();
        });
        // Panggil fungsi updateGrandTotal saat halaman dimuat
        updateGrandTotal();
    });
    $(document).ready(function() {
        // Event input untuk mencari sparepart
        $("#caripart").on("input", function() {
            cariSparepart();
        });
    });

    function sanitizeInput(input) {
        return $('<div>').text(input).html(); // Menyandikan input untuk menghindari XSS
    }

    function cariSparepart() {
        const cariPart = sanitizeInput($("#caripart").val().toLowerCase());

        // Cek apakah input kosong
        if (cariPart === '') {
            tampilkanDataTabelSP([]); // Kosongkan tabel jika tidak ada input
            return; // Keluar dari fungsi
        }
        const sparepartData = <?php echo json_encode($sparepart); ?>;
        // console.info(sparepartData);

        const hasilPencarian = sparepartData.filter(sparepart => {
            return sparepart.nama_sparepart.toLowerCase().includes(cariPart);
        });
        console.info(hasilPencarian);
        tampilkanDataTabelSP(hasilPencarian);
    }

    function sanitizeOutput(output) {
        return $('<div>').text(output).html(); // Menyandikan output untuk menghindari XSS
    }

    function tampilkanDataTabelSP(data) {
        $("#searchResults tbody").empty(); // Kosongkan tabel sebelum menampilkan hasil pencarian

        const selectedCustomer = $('#kat_customer').val(); // Ambil jenis pelanggan yang dipilih

        data.forEach((item, index) => {
            let hargaFinal = parseFloat(item.harga_ecer); // Ambil harga asli

            // Logika penyesuaian harga berdasarkan jenis pelanggan
            if (selectedCustomer === 'ecer') {
                if (hargaFinal < 15000) {
                    hargaFinal += parseFloat(item.harga_beli); // Tambah harga modal
                } else if (hargaFinal >= 15000 && hargaFinal <= 200000) {
                    hargaFinal += 10000; // Tambah 10.000
                } else if (hargaFinal > 200000) {
                    hargaFinal += 20000; // Tambah 20.000
                }
            } else if (selectedCustomer === 'glosir') {
                if (hargaFinal < 15000 && hargaFinal >= 5000) {
                    hargaFinal += -1000; // kurangi
                } else if (hargaFinal >= 50000 && hargaFinal < 200000) {
                    hargaFinal += -5000; // Tambah 10.000
                }
            } else if (selectedCustomer === 'jumbo') {
                if (hargaFinal < 15000 && hargaFinal >= 5000) {
                    hargaFinal += -2000; // kurangi
                } else if (hargaFinal >= 50000 && hargaFinal < 200000) {
                    hargaFinal += -10000; // Tambah 10.000
                }
            }
            const stock = item.stok_sparepart;
            const stockDisplay = stock > 0 ? sanitizeOutput(stock) :
                '<span style="color: red;">Kosong</span>';
            const buttonDisabled = stock > 0 ? '' : 'disabled'; // Menonaktifkan tombol jika stok kosong
            const buttonClass = stock > 0 ? 'btn-success' : 'btn-secondary'; // Mengubah kelas tombol

            const newRow = `<tr>
                <td>${i + 1}</td>
                <td style="max-width:200px;">${sanitizeOutput(item.nama_sparepart)}</td>
                <td style="max-width:100px; ">
                ${stockDisplay}
                </td>
                <td style="max-width:150px;">
                    ${sanitizeOutput(formatCurrency(hargaFinal))}
                </td>
                <td style="max-width:50px;">
                    <input class="form-control" id="qty${index}" autocomplete="off" placeholder="Jumlah" oninput="validateQty(this, ${stock})">
                </td>
                <td>
                    <button class="btn ${buttonClass} mb-2"
                    data-id="${sanitizeOutput(item.id)}"
                    data-nama="${sanitizeOutput(item.nama_sparepart)}"
                    data-harga="${sanitizeOutput(hargaFinal)}"
                    data-qty="${sanitizeOutput(item.qty)}"
                    ${buttonDisabled}
                    onclick="jualSparepart(event, this)">
                    <i class="fa fa-plus"></i>
                </button>

                </td>
            </tr>`;

            $("#searchResults tbody").append(newRow);
        });
    };

    // Fungsi untuk validasi input jumlah
    function validateQty(input, stock) {
        const qty = parseInt(input.value);
        if (stock <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Stok Kosong',
                text: 'Tidak dapat melakukan pembelian, stok tidak tersedia.',
                confirmButtonText: 'Ok'
            });
            input.value = ''; // Reset input
            return; // Keluar dari fungsi
        }
        if (qty > stock) {
            Swal.fire({
                icon: 'warning',
                title: 'Jumlah tidak valid',
                text: `Jumlah tidak boleh melebihi stok (${stock})`,
                confirmButtonText: 'Ok'
            });
            input.value = stock; // Reset ke stok maksimum
        }
    }
</script>
{{-- pencarian --}}
{{-- simpan ke lokal --}}
<script>
    function jualSparepart(event, button) {
        event.preventDefault(); // Mencegah perilaku default tombol

        // Ambil data dari tombol
        const id = $(button).data('id');
        const nama = $(button).data('nama');
        const harga = $(button).data('harga');
        const kodetrxid = $('#kodetrxid').val();
        // Mengambil nilai qty dari input yang relevan
        const qtyInputId = $(button).closest('tr').find('input[id^="qty"]').attr(
            'id'); // Mencari input qty di baris yang sama
        const qty = $("#" + qtyInputId).val(); // Mengambil nilai dari input qty

        // Validasi qty
        if (!qty || qty <= 0) {
            alert('Jumlah tidak valid!');
            return;
        }
        // Kirim data ke server menggunakan AJAX
        $.ajax({
            url: '{{ route('create_detail_sparepart_penjualan') }}', // Sesuaikan dengan route Anda
            type: 'POST',
            data: {
                kode_penjualan: kodetrxid, // Ambil kode transaksi
                kode_sparepart: id,
                qty_sparepart: qty,
                custom_harga: harga,
                _token: '{{ csrf_token() }}' // Kirim token CSRF
            },
            success: function(response) {
                // Tangani respon dari server (misalnya, update tampilan)
                Swal.fire({
                    icon: 'success',
                    title: 'Data ditambahkan',
                    text: `${nama} (${qty})`,
                    showConfirmButton: false,
                    timer: 2500
                });
                // Muat ulang data atau tampilkan pesan sesuai kebutuhan

            },
            error: function(xhr) {
                // Tangani error
                Swal.fire({
                    icon: 'danger',
                    title: 'Gagal ditambahkan',
                    text: "Terjadi kesalahan: " + xhr.responseText,
                    showConfirmButton: false,
                    timer: 2500
                });

            }
        });
    }

    // detail data
    $(document).ready(function() {
        // Panggil fungsi untuk mengisi data saat modal dibuka
        $('#detail_sp').on('shown.bs.modal', function() {
            updateDetails()
        });

        function updateDetails() {
            // Fungsi untuk memuat data dari localStorage
            const kodePenjualan = $('#kodetrxid').val(); // Ambil kode penjualan dari PHP
            const sparepartList = $('#sparepartList');
            sparepartList.empty(); // Kosongkan tabel sebelum mengisi

            $.ajax({
                url: `/penjualan/detail/${kodePenjualan}`, // Sesuaikan dengan endpoint di server
                method: 'GET',
                success: function(data) {
                    data.detailsparepart.forEach((item, index) => {
                        const newRow = `<tr>
                    <td>${index + 1}</td>
                    <td>${sanitizeOutput(item.nama_sparepart)}</td>
                    <td>${formatCurrency(item.detail_harga_jual)}</td>
                    <td>${sanitizeOutput(item.qty_sparepart)}</td>
                    <td>
                        <button class="btn btn-danger" data-nama="${item.nama_sparepart}" data-qty="${item.qty_sparepart}" onclick="removeItem(this,${item.id_detail})">Hapus</button>
                    </td>
                </tr>`;
                        sparepartList.append(newRow);
                    });
                },

            });
        }
    });

    // Fungsi untuk menghapus item dari
    function removeItem(button, id) {
        const namaSparepart = button.getAttribute('data-nama');
        const qty = button.getAttribute('data-qty');
        Swal.fire({
            title: 'Apakah Kamu Yakin?',
            text: `Menghapus ${namaSparepart} (qty: ${qty})`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Tidak, batalkan'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `dashboard/${id}/delete_detail_sparepart`, // Sesuaikan dengan endpoint di server
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}', // Tambahkan token CSRF
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Detail sparepart berhasil dihapus!',
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: 'Terjadi kesalahan saat menghapus detail sparepart.',
                        });
                    }
                });
            }
        });

    };
</script>
{{-- untuk update otomatis --}}
<script>
    $(document).ready(function() {
        // Fungsi untuk mengambil dan memperbarui data
        function updateTotals() {
            const kodePenjualan = $('#kodetrxid').val(); // Ambil ID penjualan dari PHP
            $.ajax({
                url: `/penjualan/detail/${kodePenjualan}`,
                method: 'GET',
                success: function(data) {
                    $('#item').val(data.totalitem); // Update jumlah item
                    $('#gtotal-result').text('Rp. ' + new Intl.NumberFormat().format(data
                        .total_part_penjualan)); // Update grand total
                    $('#total_penjualan').val(data.total_part_penjualan); // Update input hidden
                }
            });
        }

        // Panggil fungsi updateTotals saat halaman dimuat
        updateTotals();

        // Misalnya, jika ada event tertentu, panggil updateTotals
        $('#modal_sp').on('hidden.bs.modal', function() {
            updateTotals();
        });
        $('#detail_sp').on('hidden.bs.modal', function() {
            updateTotals();
        });
    });
</script>
//
@endsection
@endif
