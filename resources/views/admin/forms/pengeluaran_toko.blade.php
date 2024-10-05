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
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <div class="card-title">@yield('page')</div>
                        </div>
                        <form
                            action="{{ isset($data) != null ? route('update_pengeluaran_toko', $data->id) : route('store_pengeluaran_toko') }}"
                            method="POST" enctype="multipart/form-data">
                            @csrf
                            @if (isset($data) != null)
                                @method('PUT')
                            @else
                                @method('POST')
                            @endif
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Tanggal </label>
                                    <input type="date" name="tanggal_pengeluaran" id="tanggal_pengeluaran"
                                        class="form-control"
                                        value="{{ isset($data) != null ? $data->tanggal_pengeluaran : date('Y-m-d') }}">
                                </div>
                                <div class="form-group">
                                    <label>Nama </label>
                                    <input type="text" name="nama_pengeluaran" id="nama_pengeluaran"
                                        class="form-control"
                                        value="{{ isset($data) != null ? $data->nama_pengeluaran : '' }}" required>
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
                                    <label>Jumlah</label>
                                    <input type="number" name="jumlah_pengeluaran" id="jumlah_pengeluaran"
                                        class="form-control"
                                        value="{{ isset($data) != null ? $data->jumlah_pengeluaran : '' }}" required>
                                </div>
                                <div class="form-group">
                                    <label>Catatan </label>
                                    <textarea name="catatan_pengeluaran" id="catatan_pengeluaran" class="form-control" cols="30" rows="5"
                                        required>{{ isset($data) != null ? $data->catatan_pengeluaran : '' }}</textarea>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-success">Simpan</button>
                                <a href="{{ route('pengeluaran_toko') }}" class="btn btn-danger">Kembali</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /.content -->
</div>
@include('admin.component.footer')
