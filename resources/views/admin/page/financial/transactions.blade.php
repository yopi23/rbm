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
                <!-- Filter Card -->
                <div class="card collapsed-card">
                    <div class="card-header">
                        <h3 class="card-title">Filter Data</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('financial.transactions') }}" method="GET">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="year">Tahun</label>
                                        <select name="year" id="year" class="form-control">
                                            @foreach ($years as $y)
                                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                                    {{ $y }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="month">Bulan</label>
                                        <select name="month" id="month" class="form-control">
                                            <option value="">Semua Bulan</option>
                                            @foreach (['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'] as $value => $label)
                                                <option value="{{ $value }}"
                                                    {{ $month == $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="type">Tipe Transaksi</label>
                                        <select name="type" id="type" class="form-control">
                                            <option value="">Semua Tipe</option>
                                            <option value="Pemasukan" {{ $type == 'Pemasukan' ? 'selected' : '' }}>
                                                Pemasukan</option>
                                            <option value="Pengeluaran" {{ $type == 'Pengeluaran' ? 'selected' : '' }}>
                                                Pengeluaran</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="category">Kategori</label>
                                        <select name="category" id="category" class="form-control">
                                            <option value="">Semua Kategori</option>
                                            @foreach ($categories as $c)
                                                <option value="{{ $c }}"
                                                    {{ $category == $c ? 'selected' : '' }}>{{ $c }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="source">Sumber Data</label>
                                        <select name="source" id="source" class="form-control">
                                            <option value="">Semua Sumber</option>
                                            <option value="service" {{ $source == 'service' ? 'selected' : '' }}>Service
                                            </option>
                                            <option value="sales" {{ $source == 'sales' ? 'selected' : '' }}>Penjualan
                                            </option>
                                            <option value="operational" {{ $source == 'operational' ? 'selected' : '' }}>
                                                Operasional</option>
                                            <option value="store" {{ $source == 'store' ? 'selected' : '' }}>Pengeluaran
                                                Toko</option>
                                            <option value="laci" {{ $source == 'laci' ? 'selected' : '' }}>Laci</option>
                                            <option value="manual" {{ $source == 'manual' ? 'selected' : '' }}>Manual
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-filter mr-1"></i>
                                            Filter</button>
                                        <a href="{{ route('financial.transactions') }}" class="btn btn-default"><i
                                                class="fas fa-sync-alt mr-1"></i> Reset</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mb-3">
                    <a href="{{ route('financial.create') }}" class="btn btn-success"><i
                            class="fas fa-plus-circle mr-1"></i> Tambah Transaksi</a>
                    <a href="{{ route('financial.index') }}" class="btn btn-primary"><i
                            class="fas fa-tachometer-alt mr-1"></i> Dashboard Keuangan</a>
                    <a href="{{ route('financial.reports') }}" class="btn btn-warning"><i
                            class="fas fa-file-invoice mr-1"></i> Laporan Keuangan</a>
                </div>

                <!-- Main card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Data Transaksi Keuangan</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="transactions-table" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th width="5%">No</th>
                                        <th>Tanggal</th>
                                        <th>Kode</th>
                                        <th>Kategori</th>
                                        <th>Deskripsi</th>
                                        <th>Tipe</th>
                                        <th>Jumlah</th>
                                        <th>Metode</th>
                                        <th>Sumber</th>
                                        <th width="10%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $no = ($transactions->currentPage() - 1) * $transactions->perPage() + 1; @endphp
                                    @foreach ($transactions as $transaction)
                                        <tr>
                                            <td>{{ $no++ }}</td>
                                            <td>{{ date('d/m/Y', strtotime($transaction->tanggal)) }}</td>
                                            <td>{{ $transaction->kode_transaksi }}</td>
                                            <td>{{ $transaction->kategori }}</td>
                                            <td>{{ $transaction->deskripsi }}</td>
                                            <td>
                                                @if ($transaction->tipe_transaksi == 'Pemasukan')
                                                    <span class="badge badge-success">Pemasukan</span>
                                                @else
                                                    <span class="badge badge-danger">Pengeluaran</span>
                                                @endif
                                            </td>
                                            <td>Rp {{ number_format($transaction->jumlah, 0, ',', '.') }}</td>
                                            <td>{{ $transaction->metode_pembayaran }}</td>
                                            <td>
                                                @if ($transaction->kode_referensi)
                                                    @if (strpos($transaction->kategori, 'DP Service') === 0 || strpos($transaction->kategori, 'Pengambilan Service') === 0)
                                                        <span class="badge badge-primary">Service</span>
                                                    @elseif(strpos($transaction->kategori, 'Penjualan') === 0)
                                                        <span class="badge badge-info">Penjualan</span>
                                                    @elseif(strpos($transaction->kategori, 'Operasional:') === 0)
                                                        <span class="badge badge-danger">Operasional</span>
                                                    @elseif(strpos($transaction->kategori, 'Pengeluaran Toko') === 0)
                                                        <span class="badge badge-warning">Toko</span>
                                                    @elseif(strpos($transaction->kategori, 'Laci:') === 0)
                                                        <span class="badge badge-secondary">Laci</span>
                                                    @else
                                                        <span class="badge badge-secondary">Sistem</span>
                                                    @endif
                                                @else
                                                    <span class="badge badge-dark">Manual</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('financial.edit', $transaction->id) }}"
                                                        class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    @if (!$transaction->kode_referensi)
                                                        <button type="button" class="btn btn-sm btn-danger"
                                                            data-toggle="modal" data-target="#delete-modal"
                                                            data-transaction-id="{{ $transaction->id }}" title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    @else
                                                        <button type="button" class="btn btn-sm btn-secondary"
                                                            title="Tidak dapat dihapus" disabled>
                                                            <i class="fas fa-lock"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        {{ $transactions->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="delete-modal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
