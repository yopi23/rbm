@extends('admin.layout.app')

@section('content-app')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">{{ $page ?? 'Detail Shift' }}</h1>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="invoice p-3 mb-3">
            <div class="row">
                <div class="col-12">
                    <h4>
                        <i class="fas fa-globe"></i> Laporan Shift #{{ $shift->id }}
                        <small class="float-right">Date: {{ $shift->created_at->format('d/m/Y') }}</small>
                    </h4>
                </div>
            </div>
            <div class="row invoice-info">
                <div class="col-sm-4 invoice-col">
                    Kasir
                    <address>
                        <strong>{{ $shift->user->name }}</strong><br>
                        Start: {{ $shift->start_time->format('d/m/Y H:i') }}<br>
                        End: {{ $shift->end_time ? $shift->end_time->format('d/m/Y H:i') : '-' }}<br>
                        Status: <span class="badge badge-{{ $shift->status == 'open' ? 'success' : 'secondary' }}">{{ strtoupper($shift->status) }}</span>
                    </address>
                </div>
                <div class="col-sm-4 invoice-col">
                    Ringkasan Kas
                    <address>
                        <strong>Modal Awal:</strong> Rp {{ number_format($shift->modal_awal, 0, ',', '.') }}<br>
                        <strong>Masuk (System):</strong> Rp {{ number_format($cashIn, 0, ',', '.') }}<br>
                        <strong>Keluar (System):</strong> Rp {{ number_format($cashOut, 0, ',', '.') }}<br>
                        <strong>Saldo Akhir (System):</strong> Rp {{ number_format($expectedCash, 0, ',', '.') }}<br>
                        @if($shift->status == 'closed')
                        <strong>Saldo Aktual:</strong> Rp {{ number_format($shift->saldo_akhir_aktual, 0, ',', '.') }}<br>
                        <strong>Selisih:</strong> <span class="{{ $shift->selisih < 0 ? 'text-danger' : 'text-success' }}">Rp {{ number_format($shift->selisih, 0, ',', '.') }}</span>
                        @endif
                    </address>
                </div>
                <div class="col-sm-4 invoice-col">
                    @if($shift->status == 'open')
                    <a href="{{ route('shift.close', $shift->id) }}" class="btn btn-danger btn-block"><i class="fas fa-lock"></i> Tutup Shift</a>
                    @else
                    <button class="btn btn-default btn-block" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                    @endif
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <h5><i class="fas fa-tools"></i> Penggunaan Sparepart</h5>
                    <table class="table table-striped table-sm">
                        <thead>
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
                                <td>{{ $item['nama'] }}</td>
                                <td class="text-center">{{ $item['initial_stock_est'] }}</td>
                                <td class="text-center">{{ $item['stock_in'] ?? 0 }}</td>
                                <td class="text-center">{{ $item['used'] }}</td>
                                <td class="text-center">{{ $item['sisa'] }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center">Tidak ada penggunaan sparepart.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-6">
                    <h5><i class="fas fa-shopping-cart"></i> Penjualan</h5>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Detail Item</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($penjualans as $p)
                            <tr>
                                <td>{{ $p->kode_penjualan }}</td>
                                <td>
                                    <ul class="pl-3 mb-0">
                                        @foreach($p->detailSparepart as $detail)
                                            <li>{{ $detail->sparepart->nama_sparepart ?? 'N/A' }} ({{ $detail->jumlah }})</li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td>Rp {{ number_format($p->total_bayar, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3">Tidak ada penjualan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="col-6">
                    <h5><i class="fas fa-wrench"></i> Service (Diambil/Lunas)</h5>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Pelanggan</th>
                                <th>Sparepart Digunakan</th>
                                <th>Biaya</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($services as $s)
                            <tr>
                                <td>{{ $s->kode_service }}</td>
                                <td>{{ $s->nama_pelanggan }}</td>
                                <td>
                                    <ul class="pl-3 mb-0">
                                        @foreach($s->partToko as $part)
                                            <li>{{ $part->sparepart->nama_sparepart ?? 'N/A' }} ({{ $part->jumlah }})</li>
                                        @endforeach
                                        @foreach($s->partLuar as $part)
                                            <li>{{ $part->nama_barang }} ({{ $part->jumlah }})</li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td>Rp {{ number_format($s->biaya_pelunasan + $s->dp, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4">Tidak ada service selesai.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row mt-4">
                 <div class="col-6">
                    <h5><i class="fas fa-money-bill-wave"></i> Pengeluaran Toko</h5>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Keterangan</th>
                                <th>Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pengeluaranTokos as $pt)
                            <tr>
                                <td>{{ $pt->nama_pengeluaran }}</td>
                                <td>Rp {{ number_format($pt->jumlah_pengeluaran, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2">Tidak ada pengeluaran toko.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                 </div>
                 <div class="col-6">
                    <h5><i class="fas fa-file-invoice-dollar"></i> Pengeluaran Operasional</h5>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th>Keterangan</th>
                                <th>Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pengeluaranOperasionals as $po)
                            <tr>
                                <td>{{ $po->kategori }}</td>
                                <td>{{ $po->keterangan }}</td>
                                <td>Rp {{ number_format($po->jumlah, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3">Tidak ada pengeluaran operasional.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                 </div>
            </div>
        </div>
    </div>
</div>
        </div>
    </section>
</div>
@endsection
