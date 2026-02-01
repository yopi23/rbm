@extends('admin.layout.template')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Detail Shift #{{ $shift->id }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('shift.index') }}">Shift</a></li>
                        <li class="breadcrumb-item active">Detail</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Summary Cards -->
            <div class="row">
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-cash-register"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Saldo Awal</span>
                            <span class="info-box-number">Rp {{ number_format($shift->modal_awal, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-arrow-down"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Uang Masuk (Sistem)</span>
                            <span class="info-box-number">Rp {{ number_format($cashIn, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-danger"><i class="fas fa-arrow-up"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Uang Keluar (Sistem)</span>
                            <span class="info-box-number">Rp {{ number_format($cashOut, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-wallet"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Saldo Akhir (Sistem)</span>
                            <span class="info-box-number">Rp {{ number_format($expectedCash, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($shift->status == 'closed')
            <div class="alert alert-secondary">
                <h5><i class="icon fas fa-lock"></i> Shift Ditutup</h5>
                Waktu Tutup: {{ $shift->end_time }} <br>
                Saldo Aktual: Rp {{ number_format($shift->saldo_akhir_aktual, 0, ',', '.') }} <br>
                Selisih: Rp {{ number_format($shift->selisih, 0, ',', '.') }} <br>
                Catatan: {{ $shift->note }}
            </div>
            @endif

            <!-- Action Buttons -->
            @if($shift->status == 'open')
            <div class="row mb-3">
                <div class="col-12">
                    <a href="{{ route('shift.edit', $shift->id) }}" class="btn btn-danger btn-lg btn-block">
                        <i class="fas fa-power-off"></i> Tutup Shift
                    </a>
                </div>
            </div>
            @endif

            <div class="row">
                <!-- Sparepart Analysis -->
                <div class="col-md-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Analisa Sparepart (Pergerakan Stok Shift Ini)</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Nama Sparepart</th>
                                        <th>Stok Awal (Est)</th>
                                        <th>Masuk (Beli)</th>
                                        <th>Keluar (Jual/Service)</th>
                                        <th>Sisa (Akhir)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sparepartReport as $id => $item)
                                    <tr>
                                        <td>{{ $item['nama'] }}</td>
                                        <td>{{ $item['initial_stock_est'] }}</td>
                                        <td>{{ $item['stock_in'] }}</td>
                                        <td>{{ $item['used'] }}</td>
                                        <td>{{ $item['sisa'] }}</td>
                                    </tr>
                                    @endforeach
                                    @if(empty($sparepartReport))
                                    <tr>
                                        <td colspan="5" class="text-center">Tidak ada pergerakan sparepart pada shift ini.</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Transactions Tabs -->
                <div class="col-12 col-sm-12">
                    <div class="card card-primary card-outline card-outline-tabs">
                        <div class="card-header p-0 border-bottom-0">
                            <ul class="nav nav-tabs" id="custom-tabs-four-tab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="custom-tabs-four-penjualan-tab" data-toggle="pill" href="#custom-tabs-four-penjualan" role="tab" aria-controls="custom-tabs-four-penjualan" aria-selected="true">Penjualan</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="custom-tabs-four-service-tab" data-toggle="pill" href="#custom-tabs-four-service" role="tab" aria-controls="custom-tabs-four-service" aria-selected="false">Service</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="custom-tabs-four-pengeluaran-tab" data-toggle="pill" href="#custom-tabs-four-pengeluaran" role="tab" aria-controls="custom-tabs-four-pengeluaran" aria-selected="false">Pengeluaran Toko</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="custom-tabs-four-tabContent">
                                <!-- Penjualan Tab -->
                                <div class="tab-pane fade show active" id="custom-tabs-four-penjualan" role="tabpanel" aria-labelledby="custom-tabs-four-penjualan-tab">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Kode</th>
                                                <th>Tanggal</th>
                                                <th>Total</th>
                                                <th>Item</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($penjualans as $p)
                                            <tr>
                                                <td>{{ $p->kode_penjualan }}</td>
                                                <td>{{ $p->tgl_penjualan }}</td>
                                                <td>Rp {{ number_format($p->total_tagihan, 0, ',', '.') }}</td>
                                                <td>
                                                    <ul>
                                                    @foreach($p->detailSparepart as $d)
                                                        <li>{{ $d->sparepart->nama_sparepart ?? 'Unknown' }} ({{ $d->qty_sparepart }}x)</li>
                                                    @endforeach
                                                    </ul>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Service Tab -->
                                <div class="tab-pane fade" id="custom-tabs-four-service" role="tabpanel" aria-labelledby="custom-tabs-four-service-tab">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Kode</th>
                                                <th>Teknisi</th>
                                                <th>Status</th>
                                                <th>Total Biaya</th>
                                                <th>Sparepart Digunakan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($services as $s)
                                            <tr>
                                                <td>{{ $s->kode_service }}</td>
                                                <td>{{ $s->teknisi_name ?? '-' }}</td>
                                                <td>{{ $s->status_services }}</td>
                                                <td>Rp {{ number_format($s->total_biaya, 0, ',', '.') }}</td>
                                                <td>
                                                    <ul>
                                                    @foreach($s->partToko as $pt)
                                                        <li>{{ $pt->sparepart->nama_sparepart ?? 'Unknown' }} ({{ $pt->qty_part }}x)</li>
                                                    @endforeach
                                                    </ul>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pengeluaran Tab -->
                                <div class="tab-pane fade" id="custom-tabs-four-pengeluaran" role="tabpanel" aria-labelledby="custom-tabs-four-pengeluaran-tab">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Keterangan</th>
                                                <th>Jumlah</th>
                                                <th>Tanggal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($pengeluaranTokos as $pt)
                                            <tr>
                                                <td>{{ $pt->keterangan }}</td>
                                                <td>Rp {{ number_format($pt->jumlah, 0, ',', '.') }}</td>
                                                <td>{{ $pt->tgl_pengeluaran }}</td>
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
        </div>
    </section>
</div>
@endsection
