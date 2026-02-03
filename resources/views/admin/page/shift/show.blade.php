@extends('admin.layout.app')

@section('content-app')
<div class="content-wrapper">
    <!-- Content Header -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-cash-register"></i> {{ $page ?? 'Detail Shift' }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item"><a href="#">Shift</a></li>
                        <li class="breadcrumb-item active">Detail</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <!-- Info Header Card -->
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-receipt"></i> Laporan Shift #{{ $shift->id }}
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-{{ $shift->status == 'open' ? 'success' : 'secondary' }} badge-lg">
                            <i class="fas fa-{{ $shift->status == 'open' ? 'lock-open' : 'lock' }}"></i> 
                            {{ strtoupper($shift->status) }}
                        </span>
                        <span class="badge badge-info badge-lg ml-2">
                            <i class="far fa-calendar-alt"></i> 
                            {{ $shift->created_at->format('d/m/Y') }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Kasir Info -->
                        <div class="col-lg-4 col-md-6">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-primary elevation-1">
                                    <i class="fas fa-user"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Kasir</span>
                                    <span class="info-box-number">{{ $shift->user->name }}</span>
                                    <small>
                                        <i class="far fa-clock"></i> Start: {{ $shift->start_time->format('d/m/Y H:i') }}<br>
                                        <i class="far fa-clock"></i> End: {{ $shift->end_time ? $shift->end_time->format('d/m/Y H:i') : '-' }}
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Awal -->
                        <div class="col-lg-4 col-md-6">
                            <div class="info-box bg-gradient-success">
                                <span class="info-box-icon">
                                    <i class="fas fa-wallet"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Modal Awal</span>
                                    <span class="info-box-number">Rp {{ number_format($shift->modal_awal, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Saldo Expected -->
                        <div class="col-lg-4 col-md-6">
                            <div class="info-box bg-gradient-info">
                                <span class="info-box-icon">
                                    <i class="fas fa-calculator"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Saldo Akhir (System)</span>
                                    <span class="info-box-number">Rp {{ number_format($expectedCash, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Cash In -->
                        <div class="col-lg-3 col-md-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h4>Rp {{ number_format($cashIn, 0, ',', '.') }}</h4>
                                    <p>Kas Masuk</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-arrow-down"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Cash Out -->
                        <div class="col-lg-3 col-md-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h4>Rp {{ number_format($cashOut, 0, ',', '.') }}</h4>
                                    <p>Kas Keluar</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-arrow-up"></i>
                                </div>
                            </div>
                        </div>

                        @if($shift->status == 'closed')
                        <!-- Saldo Aktual -->
                        <div class="col-lg-3 col-md-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h4>Rp {{ number_format($shift->saldo_akhir_aktual, 0, ',', '.') }}</h4>
                                    <p>Saldo Aktual</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Selisih -->
                        <div class="col-lg-3 col-md-6">
                            <div class="small-box bg-{{ $shift->selisih < 0 ? 'danger' : 'success' }}">
                                <div class="inner">
                                    <h4>Rp {{ number_format($shift->selisih, 0, ',', '.') }}</h4>
                                    <p>Selisih</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-{{ $shift->selisih < 0 ? 'minus' : 'plus' }}-circle"></i>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="row">
                        <div class="col-12">
                            @if($shift->status == 'open')
                            <a href="{{ route('shift.close', $shift->id) }}" class="btn btn-danger btn-lg">
                                <i class="fas fa-lock"></i> Tutup Shift
                            </a>
                            @else
                            <button class="btn btn-primary btn-lg" onclick="window.print()">
                                <i class="fas fa-print"></i> Print Laporan
                            </button>
                            <a href="#" class="btn btn-secondary btn-lg">
                                <i class="fas fa-file-pdf"></i> Export PDF
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Penggunaan Sparepart -->
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tools"></i> Penggunaan Sparepart
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover m-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Sparepart</th>
                                    <th class="text-center">Stock Awal (Est)</th>
                                    <th class="text-center">Masuk</th>
                                    <th class="text-center">Digunakan</th>
                                    <th class="text-center">Sisa (Saat Ini)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sparepartReport as $item)
                                <tr>
                                    <td><strong>{{ $item['nama'] }}</strong></td>
                                    <td class="text-center"><span class="badge badge-secondary">{{ $item['initial_stock_est'] }}</span></td>
                                    <td class="text-center"><span class="badge badge-success">{{ $item['stock_in'] ?? 0 }}</span></td>
                                    <td class="text-center"><span class="badge badge-warning">{{ $item['used'] }}</span></td>
                                    <td class="text-center"><span class="badge badge-info">{{ $item['sisa'] }}</span></td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        <i class="fas fa-info-circle"></i> Tidak ada penggunaan sparepart
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Penjualan -->
                <div class="col-lg-6">
                    <div class="card card-success card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-shopping-cart"></i> Penjualan
                            </h3>
                            <div class="card-tools">
                                <span class="badge badge-success">{{ count($penjualans) }} Transaksi</span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 400px;">
                                <table class="table table-sm m-0">
                                    <thead class="bg-light sticky-top">
                                        <tr>
                                            <th>Kode</th>
                                            <th>Detail Item</th>
                                            <th class="text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($penjualans as $p)
                                        <tr>
                                            <td><span class="badge badge-primary">{{ $p->kode_penjualan }}</span></td>
                                            <td>
                                                <ul class="list-unstyled mb-0" style="font-size: 0.875rem;">
                                                    @foreach($p->detailSparepart as $detail)
                                                        <li><i class="fas fa-caret-right text-muted"></i> {{ $detail->sparepart->nama_sparepart ?? 'N/A' }} <span class="badge badge-light">{{ $detail->jumlah }}</span></li>
                                                    @endforeach
                                                </ul>
                                            </td>
                                            <td class="text-right"><strong>Rp {{ number_format($p->total_bayar, 0, ',', '.') }}</strong></td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">
                                                <i class="fas fa-info-circle"></i> Tidak ada penjualan
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Service -->
                <div class="col-lg-6">
                    <div class="card card-info card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-wrench"></i> Service (Diambil/Lunas)
                            </h3>
                            <div class="card-tools">
                                <span class="badge badge-info">{{ count($services) }} Service</span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 400px;">
                                <table class="table table-sm m-0">
                                    <thead class="bg-light sticky-top">
                                        <tr>
                                            <th>Kode</th>
                                            <th>Pelanggan</th>
                                            <th>Sparepart</th>
                                            <th class="text-right">Biaya</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($services as $s)
                                        <tr>
                                            <td><span class="badge badge-info">{{ $s->kode_service }}</span></td>
                                            <td><strong>{{ $s->nama_pelanggan }}</strong></td>
                                            <td>
                                                <ul class="list-unstyled mb-0" style="font-size: 0.875rem;">
                                                    @foreach($s->partToko as $part)
                                                        <li><i class="fas fa-caret-right text-muted"></i> {{ $part->sparepart->nama_sparepart ?? 'N/A' }} <span class="badge badge-light">{{ $part->jumlah }}</span></li>
                                                    @endforeach
                                                    @foreach($s->partLuar as $part)
                                                        <li><i class="fas fa-caret-right text-warning"></i> {{ $part->nama_barang }} <span class="badge badge-light">{{ $part->jumlah }}</span></li>
                                                    @endforeach
                                                </ul>
                                            </td>
                                            <td class="text-right"><strong>Rp {{ number_format($s->total_biaya, 0, ',', '.') }}</strong></td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">
                                                <i class="fas fa-info-circle"></i> Tidak ada service selesai
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Pengeluaran Toko -->
                <div class="col-lg-6">
                    <div class="card card-warning card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-money-bill-wave"></i> Pengeluaran Toko
                            </h3>
                            <div class="card-tools">
                                <span class="badge badge-warning">{{ count($pengeluaranTokos) }} Item</span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover m-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Keterangan</th>
                                            <th class="text-right">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($pengeluaranTokos as $pt)
                                        <tr>
                                            <td><i class="fas fa-circle text-warning" style="font-size: 0.5rem;"></i> {{ $pt->nama_pengeluaran }}</td>
                                            <td class="text-right"><strong class="text-danger">Rp {{ number_format($pt->jumlah_pengeluaran, 0, ',', '.') }}</strong></td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="2" class="text-center text-muted">
                                                <i class="fas fa-info-circle"></i> Tidak ada pengeluaran toko
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pengeluaran Operasional -->
                <div class="col-lg-6">
                    <div class="card card-danger card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-file-invoice-dollar"></i> Pengeluaran Operasional
                            </h3>
                            <div class="card-tools">
                                <span class="badge badge-danger">{{ count($pengeluaranOperasionals) }} Item</span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover m-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Kategori</th>
                                            <th>Keterangan</th>
                                            <th class="text-right">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($pengeluaranOperasionals as $po)
                                        <tr>
                                            <td><span class="badge badge-secondary">{{ $po->kategori }}</span></td>
                                            <td>{{ $po->keterangan }}</td>
                                            <td class="text-right"><strong class="text-danger">Rp {{ number_format($po->jumlah, 0, ',', '.') }}</strong></td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">
                                                <i class="fas fa-info-circle"></i> Tidak ada pengeluaran operasional
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mutasi Kas Lengkap (Ledger) -->
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-exchange-alt"></i> Rincian Mutasi Kas (Semua Transaksi)
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover m-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Waktu</th>
                                    <th>Deskripsi</th>
                                    <th>Tipe</th>
                                    <th class="text-right text-success">Masuk (Debit)</th>
                                    <th class="text-right text-danger">Keluar (Kredit)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($shift->kasPerusahaan as $kas)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($kas->tanggal)->format('H:i') }}</td>
                                    <td>{{ $kas->deskripsi }}</td>
                                    <td>
                                        <small class="badge badge-light">
                                            {{ class_basename($kas->sourceable_type) }}
                                        </small>
                                    </td>
                                    <td class="text-right text-success">
                                        {{ $kas->debit > 0 ? 'Rp '.number_format($kas->debit, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-right text-danger">
                                        {{ $kas->kredit > 0 ? 'Rp '.number_format($kas->kredit, 0, ',', '.') : '-' }}
                                    </td>
                                </tr>
                                @endforeach
                                <tr class="bg-light font-weight-bold">
                                    <td colspan="3" class="text-right">Total</td>
                                    <td class="text-right text-success">Rp {{ number_format($shift->kasPerusahaan->sum('debit'), 0, ',', '.') }}</td>
                                    <td class="text-right text-danger">Rp {{ number_format($shift->kasPerusahaan->sum('kredit'), 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    <small><i class="fas fa-info-circle"></i> Tabel ini menampilkan semua pergerakan uang yang tercatat di sistem selama shift ini berlangsung.</small>
                </div>
            </div>
        </div>
    </section>
</div>

@push('styles')
<style>
    .info-box-number {
        font-size: 1.5rem;
    }
    
    .small-box h4 {
        font-size: 1.8rem;
        font-weight: bold;
    }
    
    .badge-lg {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }
    
    .table-responsive {
        overflow-y: auto;
    }
    
    .table thead.sticky-top {
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .card-header {
        background-color: #f4f6f9;
    }
    
    @media print {
        .content-wrapper,
        .content {
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .card {
            border: 1px solid #dee2e6 !important;
            page-break-inside: avoid;
        }
        
        .btn, .breadcrumb, .card-tools button {
            display: none !important;
        }
    }
</style>
@endpush
@endsection