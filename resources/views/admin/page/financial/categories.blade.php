@extends('admin.main')

@section('title', $page)

@section('content')
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>{{ $page }}</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('financial.index') }}">Manajemen Keuangan</a></li>
                            <li class="breadcrumb-item active">{{ $page }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <!-- Form Tambah Kategori -->
                    <div class="col-md-4">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Tambah Kategori Baru</h3>
                            </div>
                            <form action="{{ route('financial.categories.store') }}" method="POST">
                                @csrf
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="tipe_kategori">Tipe Kategori <span class="text-danger">*</span></label>
                                        <select class="form-control @error('tipe_kategori') is-invalid @enderror"
                                            id="tipe_kategori" name="tipe_kategori" required>
                                            <option value="">-- Pilih Tipe Kategori --</option>
                                            <option value="Pemasukan"
                                                {{ old('tipe_kategori') == 'Pemasukan' ? 'selected' : '' }}>Pemasukan
                                            </option>
                                            <option value="Pengeluaran"
                                                {{ old('tipe_kategori') == 'Pengeluaran' ? 'selected' : '' }}>Pengeluaran
                                            </option>
                                        </select>
                                        @error('tipe_kategori')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="nama_kategori">Nama Kategori <span class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control @error('nama_kategori') is-invalid @enderror"
                                            id="nama_kategori" name="nama_kategori" value="{{ old('nama_kategori') }}"
                                            placeholder="Contoh: Gaji, Operasional, dll" required>
                                        @error('nama_kategori')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>
                                        Simpan</button>
                                </div>
                            </form>
                        </div>

                        <!-- Action buttons -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Navigasi</h3>
                            </div>
                            <div class="card-body">
                                <a href="{{ route('financial.index') }}" class="btn btn-block btn-primary mb-2">
                                    <i class="fas fa-tachometer-alt mr-1"></i> Dashboard Keuangan
                                </a>
                                <a href="{{ route('financial.transactions') }}" class="btn btn-block btn-info mb-2">
                                    <i class="fas fa-list mr-1"></i> Daftar Transaksi
                                </a>
                                <a href="{{ route('financial.reports') }}" class="btn btn-block btn-warning">
                                    <i class="fas fa-file-invoice mr-1"></i> Laporan Keuangan
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Daftar Kategori -->
                    <div class="col-md-8">
                        <!-- Kategori Pemasukan -->
                        <div class="card card-success">
                            <div class="card-header">
                                <h3 class="card-title">Kategori Pemasukan</h3>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th width="5%">No</th>
                                            <th>Nama Kategori</th>
                                            <th>Dibuat Oleh</th>
                                            <th>Tanggal Dibuat</th>
                                            <th width="15%">Status</th>
                                            <th width="15%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $no = 1; @endphp
                                        @forelse($categories->where('tipe_kategori', 'Pemasukan') as $category)
                                            <tr>
                                                <td>{{ $no++ }}</td>
                                                <td>{{ $category->nama_kategori }}</td>
                                                <td>{{ $category->createdBy->name }}</td>
                                                <td>{{ date('d/m/Y', strtotime($category->created_at)) }}</td>
                                                <td>
                                                    <span
                                                        class="badge {{ $category->is_active ? 'badge-success' : 'badge-danger' }}">
                                                        {{ $category->is_active ? 'Aktif' : 'Non-Aktif' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <form
                                                        action="{{ route('financial.categories.update', $category->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="is_active"
                                                            value="{{ $category->is_active ? 0 : 1 }}">
                                                        <button type="submit"
                                                            class="btn btn-sm {{ $category->is_active ? 'btn-danger' : 'btn-success' }}">
                                                            <i
                                                                class="fas {{ $category->is_active ? 'fa-times' : 'fa-check' }}"></i>
                                                            {{ $category->is_active ? 'Non-Aktifkan' : 'Aktifkan' }}
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center">Tidak ada kategori pemasukan</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Kategori Pengeluaran -->
                        <div class="card card-danger">
                            <div class="card-header">
                                <h3 class="card-title">Kategori Pengeluaran</h3>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th width="5%">No</th>
                                            <th>Nama Kategori</th>
                                            <th>Dibuat Oleh</th>
                                            <th>Tanggal Dibuat</th>
                                            <th width="15%">Status</th>
                                            <th width="15%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $no = 1; @endphp
                                        @forelse($categories->where('tipe_kategori', 'Pengeluaran') as $category)
                                            <tr>
                                                <td>{{ $no++ }}</td>
                                                <td>{{ $category->nama_kategori }}</td>
                                                <td>{{ $category->createdBy->name }}</td>
                                                <td>{{ date('d/m/Y', strtotime($category->created_at)) }}</td>
                                                <td>
                                                    <span
                                                        class="badge {{ $category->is_active ? 'badge-success' : 'badge-danger' }}">
                                                        {{ $category->is_active ? 'Aktif' : 'Non-Aktif' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <form
                                                        action="{{ route('financial.categories.update', $category->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="is_active"
                                                            value="{{ $category->is_active ? 0 : 1 }}">
                                                        <button type="submit"
                                                            class="btn btn-sm {{ $category->is_active ? 'btn-danger' : 'btn-success' }}">
                                                            <i
                                                                class="fas {{ $category->is_active ? 'fa-times' : 'fa-check' }}"></i>
                                                            {{ $category->is_active ? 'Non-Aktifkan' : 'Aktifkan' }}
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center">Tidak ada kategori pengeluaran</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
