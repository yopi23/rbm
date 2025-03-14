@section('laporan', 'active')


<div class="row">
    <div class="col">
        <div class="info-box mb-3">
            <div class="info-box-content">
                <form action="{{ route('laporan') }}" method="GET">
                    <div class="row">
                        <div class="col-md-4 col-sm-12 my-2">
                            <input type="date"
                                value="{{ isset($request->tgl_awal) ? $request->tgl_awal : '' }}"name="tgl_awal"
                                id="tgl_awal" class="form-control" hidden>
                            <input type="date"
                                value="{{ isset($request->tgl_akhir) ? $request->tgl_akhir : '' }}"name="tgl_akhir"
                                id="tgl_akhir" class="form-control" hidden>

                            <div
                                id="reportrange"style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                                <i class="fa fa-calendar"></i>&nbsp;
                                <span></span> <i class="fa fa-caret-down"></i>
                            </div>
                        </div>
                        <div class="col-sm-4 my-2">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                        <div class="col text-right">
                            <strong
                                style="font-family: 'Courier New', Courier, monospace;"class="pt-3 pb-3">@php echo date('l,d-M-Y') @endphp</strong>
                        </div>
                    </div>
                </form>
            </div>

        </div>
        <!-- /.info-box-content -->
    </div>
    <!-- /.info-box -->
</div>



<div class="row">
    <div class="col-12 col-sm-6 col-md-4">
        <div class="info-box mb-3">
            <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-cog"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">Omset Service</span>
                @if (isset($totalPendapatanService))
                    <span class="info-box-number">Rp.{{ number_format($totalPendapatanService) }},-</span>
                @endif
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <div class="col-12 col-sm-6 col-md-4">
        <div class="info-box mb-3">
            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-shopping-cart"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">Omset Penjualan</span>
                @if (isset($penjualan_sparepart))
                    <span class="info-box-number">Rp.{{ number_format($totalPenjualan) }},-</span>
                @endif
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <div class="col-12 col-sm-6 col-md-4">
        <div class="info-box mb-3">
            <span class="info-box-icon bg-dark elevation-1"><i class="fas">&#xf155;</i></span>

            <div class="info-box-content">
                <span class="info-box-text">Total omset</span>
                @if (isset($totalPenjualan))
                    <span
                        class="info-box-number">Rp.{{ number_format($totalPenjualan + $totalPendapatanService) }},-</span>
                @endif
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <div class="col-12 col-sm-6 col-md-4">
        <div class="info-box mb-3">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas"
                    style="color: #fff;">&#xf155;</i></span>

            <div class="info-box-content">
                <span class="info-box-text">Total Uang Muka</span>
                @if (isset($DpService))
                    <span class="info-box-number">Rp.{{ number_format($DpService) }},-</span>
                @endif
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    {{-- pemasukan lainnya --}}
    <div class="col-12 col-sm-6 col-md-4">
        <div class="info-box mb-3">
            <span class="info-box-icon elevation-1" style="background-color: #00ce90"><i class="fas"
                    style="color: #fff;">&#xf155;</i></span>

            <div class="info-box-content">
                <span class="info-box-text">Total pemasukan lainnya</span>
                @if (isset($totalPemasukkanLain))
                    <span class="info-box-number">Rp.{{ number_format($totalPemasukkanLain) }},-</span>
                @endif
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    {{-- pemasukan lainnya --}}
    {{-- penarikan --}}
    <div class="col-12 col-sm-6 col-md-4">
        <div class="info-box mb-3">
            <span class="info-box-icon elevation-1" style="background-color: #00ce90"><i class="fas"
                    style="color: #fff;">&#xf155;</i></span>

            <div class="info-box-content">
                <span class="info-box-text">Total penarikan</span>
                @php
                    $totalPenarikan = 0;
                @endphp
                @if (isset($penarikan))
                    @foreach ($penarikan as $item)
                        @php
                            $totalPenarikan += $item->jumlah_penarikan;
                        @endphp
                    @endforeach
                @endif
                <span class="info-box-number">Rp.{{ number_format($totalPenarikan) }},-</span>

            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    {{-- penarikan --}}
</div>

{{-- laba --}}
<div class="row">
    <div class="col-12 col-sm-6 col-md-4">
        <div class="info-box mb-3">
            <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-cog"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">Laba Service</span>
                @if (isset($totalPendapatanService))
                    <span
                        class="info-box-number">Rp.{{ number_format($totalPendapatanService - $total_part_service) }},-</span>
                @endif
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <div class="col-12 col-sm-6 col-md-4">
        <div class="info-box mb-3">
            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-shopping-cart"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">Laba Penjualan</span>
                @if (isset($totalModalJual))
                    <span class="info-box-number">Rp.{{ number_format($totalPenjualan - $totalModalJual) }},-</span>
                @endif
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
    <div class="col-12 col-sm-6 col-md-4">
        <div class="info-box mb-3">
            <span class="info-box-icon bg-dark elevation-1"><i class="fas">&#xf155;</i></span>

            <div class="info-box-content">
                <span class="info-box-text">Total Laba</span>
                @if (isset($totalPendapatanService))
                    <span
                        class="info-box-number">Rp.{{ number_format($totalPendapatanService - $total_part_service + ($totalPenjualan - $totalModalJual)) }},-</span>
                @endif
            </div>
            <!-- /.info-box-content -->
        </div>
        <!-- /.info-box -->
    </div>
</div>
{{-- end laba --}}

{{-- hutang --}}
@if (@isset($hutang))
    <div class="row">
        <div class="col">
            <div class="info-box mb-3">
                <div class="info-box-content">
                    <h4>Hutang</h4>
                    <div class="table responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Tanggal</th>
                                    <th>Nota</th>
                                    <th>Toko</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                    <th>Opsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($hutang as $item)
                                    <tr data-id="{{ $item->id }}">
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item->created_at }}</td>
                                        <td>{{ $item->kode_nota }}</td>
                                        <td>{{ $item->nama_supplier }}</td>
                                        <td>Rp.{{ number_format($item->total_hutang) }},-</td>
                                        <td>
                                            @if ($item->status == 1)
                                                <strong>
                                                    <span class="text-danger">Belum Lunas</span>
                                                </strong>
                                            @endif
                                        </td>
                                        <td><button class="btn btn-danger">Hapus</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-right"><strong>Total:</strong></td>
                                    <td>Rp.{{ number_format($totalJumlah) }},-</td>
                                    <td colspan="2" class="bg-warning text-dark font-weight-bold text-center">
                                        <strong>Ingat!!
                                            Hutang Dibawa Mati</strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
{{-- hutang --}}

{{-- <div class="row">
    <div class="col-md-12">
        <div class="card card-success card-outline">
            <div class="card-body">
                <form action="{{route('laporan')}}" method="GET">
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Dari</label>
                            <input type="date" value="{{isset($request->tgl_awal) != null ? $request->tgl_awal : ''}}" name="tgl_awal" id="tgl_awal" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Sampai</label>
                            <input type="date" value="{{isset($request->tgl_akhir) != null ? $request->tgl_akhir : ''}}" name="tgl_akhir" id="tgl_akhir" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Search</label>
                            <button type="submit" class="form-control btn btn-success">Search</button>
                        </div>
                    </div>
                </div>
            </form>
            </div>
        </div>
    </div>
</div> --}}

{{-- Services --}}
{{-- @if (isset($service))
    <form action="{{ route('print_laporan') }}" target="_blank" method="GET">
        <input type="hidden" value="{{ isset($request->tgl_awal) != null ? $request->tgl_awal : '' }}" name="tgl_awal"
            id="tgl_awal">
        <input type="hidden" value="{{ isset($request->tgl_akhir) != null ? $request->tgl_akhir : '' }}"
            name="tgl_akhir" id="tgl_akhir">
        <button type="submit" class="btn btn-success"><i class="fas fa-print"></i> Print</button>
    </form>
    <br>
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <div class="card-title">
                        Laporan Service Tanggal {{ $request->tgl_awal }} S/d {{ $request->tgl_akhir }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="TABLES_1">
                            <thead>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Invoice</th>
                                <th>Nama</th>
                                <th>No Telp</th>
                                <th>Tipe Device</th>
                                <th>Total</th>
                                <th>Sparepart</th>
                                <th>Profit</th>
                            </thead>
                            <tbody>
                                @foreach ($service as $item)
                                    @if (isset($part_toko_service))
                                        @php
                                            $total_part = 0;
                                            foreach ($all_part_toko_service as $a) {
                                                if ($item->id == $a->kode_services) {
                                                    $total_part +=
                                                        ($a->detail_harga_part_service - $a->total_biaya) *
                                                        $a->qty_part;
                                                }
                                            }
                                        @endphp
                                    @endif
                                    @if (isset($part_luar_toko_service))
                                        @php
                                            foreach ($all_part_luar_toko_service as $b) {
                                                if ($item->id == $b->kode_services) {
                                                    $total_part += $b->harga_part * $b->qty_part;
                                                }
                                            }
                                        @endphp
                                    @endif
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item->created_at }}</td>
                                        <td>{{ $item->kode_service }}</td>
                                        <td>{{ $item->nama_pelanggan }}</td>
                                        <td>{{ $item->no_telp }}</td>
                                        <td>{{ $item->type_unit }}</td>
                                        <td>Rp.{{ number_format($item->total_biaya) }},-</td>
                                        <td>Rp.{{ number_format($total_part) }},-</td>
                                        <td>Rp.{{ number_format($item->total_biaya - $total_part) }},-</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="text-right">
                                <strong>Total Service</strong>
                                <span>Rp.{{ number_format($totalPendapatanService) }},-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif --}}
@if (isset($service))
    <form action="{{ route('print_laporan') }}" target="_blank" method="GET">
        <input type="hidden" value="{{ isset($request->tgl_awal) != null ? $request->tgl_awal : '' }}"
            name="tgl_awal" id="tgl_awal">
        <input type="hidden" value="{{ isset($request->tgl_akhir) != null ? $request->tgl_akhir : '' }}"
            name="tgl_akhir" id="tgl_akhir">
        <button type="submit" class="btn btn-success"><i class="fas fa-print"></i> Print</button>
    </form>
    <br>
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <div class="card-title">
                        Laporan Service Tanggal {{ $request->tgl_awal }} S/d {{ $request->tgl_akhir }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="TABLES_1">
                            <thead>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Invoice</th>
                                <th>Nama</th>
                                <th>Tipe Device</th>
                                <th>Total</th>
                                <th>Sparepart</th>
                                <th>Profit</th>
                                <th>Teknisi</th>
                                <th>Komisi</th>
                            </thead>
                            <tbody>
                                @php
                                    $totalProfit = 0;
                                @endphp
                                @foreach ($service as $item)
                                    @php
                                        $totalPart = 0;
                                        foreach ($all_part_toko_service as $a) {
                                            if ($item->id == $a->kode_services) {
                                                $totalPart +=
                                                    ($a->detail_harga_part_service - $a->total_biaya) * $a->qty_part;
                                            }
                                        }
                                        foreach ($all_part_luar_toko_service as $b) {
                                            if ($item->id == $b->kode_services) {
                                                $totalPart += $b->harga_part * $b->qty_part;
                                            }
                                        }
                                        $profit = $item->total_biaya - $totalPart;
                                        $totalProfit += $profit;
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item->created_at }}</td>
                                        <td>{{ $item->kode_service }}</td>
                                        <td>{{ $item->nama_pelanggan }}</td>
                                        <td>{{ $item->type_unit }}</td>
                                        <td>Rp.{{ number_format($item->total_biaya) }},-</td>
                                        <td>Rp.{{ number_format($totalPart) }},-</td>
                                        <td>Rp.{{ number_format($profit) }},-</td>
                                        <td>{{ $item->name }}</td>
                                        <td>Rp.{{ number_format($item->profit) }},-</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="text-right">
                                <strong>Total Profit service</strong>
                                <span>Rp.{{ number_format($totalProfit) }},-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@if (isset($part_toko_service))
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <div class="card-title">
                        Laporan Sparepart Terpakai Tanggal {{ $request->tgl_awal }} S/d {{ $request->tgl_akhir }}
                    </div>

                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="TABLES_1">
                            <thead>
                                <th>#</th>
                                <th>Tanggal Ambil</th>
                                <th>Invoice</th>
                                <th>Nama</th>
                                <th>Harga</th>
                                <th>Qty</th>
                                <th>Total</th>
                            </thead>
                            <tbody>
                                @foreach ($part_toko_service as $item)
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item->tgl_keluar }}</td>
                                        <td>{{ $item->kode_service }}</td>
                                        <td>{{ $item->nama_sparepart }}</td>
                                        <td>Rp.{{ number_format($item->detail_harga_part_service) }},-</td>
                                        <td>{{ $item->qty_part }}</td>
                                        <td>Rp.{{ number_format($item->detail_harga_part_service * $item->qty_part) }},-
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
@if (isset($part_luar_toko_service))
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <div class="card-title">
                        Laporan Sparepart Luar Toko Terpakai Tanggal {{ $request->tgl_awal }} S/d
                        {{ $request->tgl_akhir }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="TABLES_1">
                            <thead>
                                <th>#</th>
                                <th>Tanggal Keluar</th>
                                <th>Invoice</th>
                                <th>Nama</th>
                                <th>Harga</th>
                                <th>Qty</th>
                                <th>Total</th>
                            </thead>
                            <tbody>
                                @foreach ($part_luar_toko_service as $item)
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item->tgl_keluar }}</td>
                                        <td>{{ $item->kode_service }}</td>
                                        <td>{{ $item->nama_part }}</td>
                                        <td>Rp.{{ number_format($item->harga_part) }},-</td>
                                        <td>{{ number_format($item->qty_part) }}</td>
                                        <td>Rp.{{ number_format($item->harga_part * $item->qty_part) }},-</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
{{-- Penjualan --}}
@if (isset($penjualan))
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <div class="card-title">
                        Laporan Penjualan Tanggal {{ $request->tgl_awal }} S/d {{ $request->tgl_akhir }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="TABLES_1">
                            <thead>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Invoice</th>
                                <th>Nama</th>
                                <th>Keterangan</th>
                                <th>Total</th>
                            </thead>
                            <tbody>
                                @foreach ($penjualan as $item)
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item->created_at }}</td>
                                        <td>{{ $item->kode_penjualan }}</td>
                                        <td>{{ $item->nama_customer }}</td>
                                        <td>{{ $item->catatan_customer }}</td>
                                        <td>Rp.{{ number_format($item->total_penjualan) }},-</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
@if (isset($penjualan_sparepart))
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <div class="card-title">
                        Laporan Penjualan Sparepart Tanggal {{ $request->tgl_awal }} S/d {{ $request->tgl_akhir }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="TABLES_1">
                            <thead>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Invoice</th>
                                <th>Nama</th>
                                <th>Sparepart</th>
                                <th>Qty</th>
                                <th>Harga Beli (Modal)</th>
                                <th>Harga Jual</th>
                                <th>Profit</th>
                            </thead>
                            <tbody>
                                @php
                                    $totalProfitPenjualan = 0;
                                @endphp

                                @foreach ($penjualan_sparepart as $item)
                                    @php
                                        $profit =
                                            $item->detail_harga_jual * $item->qty_sparepart -
                                            $item->detail_harga_modal * $item->qty_sparepart;
                                        $totalProfitPenjualan += $profit;
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item->tgl_keluar }}</td>
                                        <td>{{ $item->kode_penjualan }}</td>
                                        <td>{{ $item->nama_customer }}</td>
                                        <td>{{ $item->nama_sparepart }}</td>
                                        <td>{{ $item->qty_sparepart }}</td>
                                        <td>Rp.{{ number_format($item->detail_harga_modal) }}</td>
                                        <td>Rp.{{ number_format($item->detail_harga_jual) }}</td>
                                        <td>Rp.{{ number_format($item->detail_harga_jual * $item->qty_sparepart - $item->detail_harga_modal * $item->qty_sparepart) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="text-right">
                                <strong>Total profit penjualan</strong>
                                <span>Rp.{{ number_format($totalProfitPenjualan) }},-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@if (isset($penjualan_barang))
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <div class="card-title">
                        Laporan Penjualan Hanphones/ Barang Tanggal {{ $request->tgl_awal }} S/d
                        {{ $request->tgl_akhir }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="TABLES_1">
                            <thead>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Invoice</th>
                                <th>Nama</th>
                                <th>Handhpones / Barang</th>
                                <th>Qty</th>
                                <th>Harga Beli (Modal)</th>
                                <th>Harga Jual</th>
                                <th>Profit</th>
                            </thead>
                            <tbody>
                                @foreach ($penjualan_barang as $item)
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item->tgl_keluar }}</td>
                                        <td>{{ $item->kode_penjualan }}</td>
                                        <td>{{ $item->nama_customer }}</td>
                                        <td>{{ $item->nama_barang }}</td>
                                        <td>{{ $item->qty_barang }}</td>
                                        <td>Rp.{{ number_format($item->harga_beli_barang) }}</td>
                                        <td>Rp.{{ number_format($item->detail_harga_jual * $item->qty_barang) }}</td>
                                        <td>Rp.{{ number_format($item->detail_harga_jual * $item->qty_barang - $item->harga_beli_barang) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
{{-- Pesanan --}}
@if (isset($pesanan))
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <div class="card-title">
                        Laporan Pesanan Tanggal {{ $request->tgl_awal }} S/d {{ $request->tgl_akhir }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="TABLES_1">
                            <thead>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Invoice</th>
                                <th>Nama</th>
                                <th>Keterangan</th>
                                <th>Total</th>
                            </thead>
                            <tbody>
                                @foreach ($pesanan as $item)
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item->created_at }}</td>
                                        <td>{{ $item->nama_pemesan }}</td>
                                        <td>{{ $item->catatan_pesanan }}</td>
                                        <td>{{ $item->total_biaya }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@if (isset($sparepart_pesanan))
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <div class="card-title">
                        Laporan Pesanan Sparepart Tanggal {{ $request->tgl_awal }} S/d {{ $request->tgl_akhir }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="TABLES_1">
                            <thead>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Invoice</th>
                                <th>Nama</th>
                                <th>Sparepart</th>
                                <th>Qty</th>
                                <th>Harga Beli (Modal)</th>
                                <th>Harga Jual</th>
                                <th>Profit</th>
                            </thead>
                            <tbody>
                                @foreach ($sparepart_pesanan as $item)
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item->tgl_keluar }}</td>
                                        <td>{{ $item->kode_pesanan }}</td>
                                        <td>{{ $item->nama_pemesan }}</td>
                                        <td>{{ $item->nama_sparepart }}</td>
                                        <td>{{ $item->qty_sparepart }}</td>
                                        <td>Rp.{{ number_format($item->harga_beli) }}</td>
                                        <td>Rp.{{ number_format($item->detail_harga_pesan * $item->qty_sparepart) }}
                                        </td>
                                        <td>Rp.{{ number_format($item->detail_harga_pesan * $item->qty_sparepart - $item->harga_beli) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
@if (isset($barang_pesanan))
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <div class="card-title">
                        Laporan Pesanan Hanphones/ Barang Tanggal {{ $request->tgl_awal }} S/d
                        {{ $request->tgl_akhir }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="TABLES_1">
                            <thead>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Invoice</th>
                                <th>Nama</th>
                                <th>Handhpones / Barang</th>
                                <th>Qty</th>
                                <th>Harga Beli (Modal)</th>
                                <th>Harga Jual</th>
                                <th>Profit</th>
                            </thead>
                            <tbody>
                                @foreach ($barang_pesanan as $item)
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item->tgl_keluar }}</td>
                                        <td>{{ $item->kode_pesanan }}</td>
                                        <td>{{ $item->nama_pemesan }}</td>
                                        <td>{{ $item->nama_barang }}</td>
                                        <td>{{ $item->qty_barang }}</td>
                                        <td>Rp.{{ number_format($item->harga_beli_barang) }}</td>
                                        <td>Rp.{{ number_format($item->detail_harga_pesan * $item->qty_barang) }}</td>
                                        <td>Rp.{{ number_format($item->detail_harga_pesan * $item->qty_barang - $item->harga_beli_barang) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
{{-- Pengeluaran --}}
@if (isset($pengeluaran_opx))
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <div class="card-title">
                        Laporan Pengeluaran Operasional Tanggal {{ $request->tgl_awal }} S/d
                        {{ $request->tgl_akhir }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="TABLES_1">
                            <thead>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Nama</th>
                                <th>Kategori</th>
                                <th>Keterangan</th>
                                <th>Jumlah</th>

                            </thead>
                            <tbody>
                                @foreach ($pengeluaran_opx as $item)
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item->created_at }}</td>
                                        <td>{{ $item->nama_pengeluaran }}</td>
                                        <td>{{ $item->kategori }}</td>
                                        <td>{{ $item->desc_pengeluaran }}</td>
                                        <td>Rp.{{ number_format($item->jml_pengeluaran) }},-</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
@if (isset($pengeluaran_toko))
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <div class="card-title">
                        Laporan Pengeluaran Toko Tanggal {{ $request->tgl_awal }} S/d {{ $request->tgl_akhir }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="TABLES_1">
                            <thead>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Nama</th>
                                <th>Keterangan</th>
                                <th>Jumlah</th>

                            </thead>
                            <tbody>
                                @foreach ($pengeluaran_toko as $item)
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item->created_at }}</td>
                                        <td>{{ $item->nama_pengeluaran }}</td>
                                        <td>{{ $item->catatan_pengeluaran }}</td>
                                        <td>Rp.{{ number_format($item->jumlah_pengeluaran) }},-</td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@if (isset($penarikan))
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <div class="card-title">
                        Laporan Penarikan Saldo Karyawan Tanggal {{ $request->tgl_awal }} S/d
                        {{ $request->tgl_akhir }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="TABLES_1">
                            <thead>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Karyawan</th>
                                <th>Keterangan</th>
                                <th>Jumlah</th>
                            </thead>
                            <tbody>
                                @php
                                    $totalPenarikan = 0;
                                @endphp
                                @foreach ($penarikan as $item)
                                    @php
                                        $totalPenarikan += $item->jumlah_penarikan;
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item->created_at }}</td>
                                        <td>{{ $item->fullname }}</td>
                                        <td>{{ $item->catatan_penarikan }}</td>
                                        <td>Rp.{{ number_format($item->jumlah_penarikan) }},-</td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="text-right">
                                <strong>Total Penarikan</strong>
                                <span>Rp.{{ number_format($totalPenarikan) }},-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
{{-- Pemasukkan Lain --}}
@if (isset($pemasukkan_lain))
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <div class="card-title">
                        Laporan Pemasukkan Lain Tanggal {{ $request->tgl_awal }} S/d {{ $request->tgl_akhir }}
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="TABLES_1">
                            <thead>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Nama</th>
                                <th>Keterangan</th>
                                <th>Jumlah</th>

                            </thead>
                            <tbody>
                                @foreach ($pemasukkan_lain as $item)
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item->created_at }}</td>
                                        <td>{{ $item->judul_pemasukan }}</td>
                                        <td>{{ $item->catatan_pemasukkan }}</td>
                                        <td>Rp.{{ number_format($item->jumlah_pemasukkan) }},-</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif



<script type="text/javascript">
    $(function() {
        // const start = moment().subtract(29, 'days');
        // const end = moment();
        const start = moment($('#tgl_awal').val() || moment().startOf(
            'day')); // Mengambil nilai tanggal awal yang sudah di-submit
        const end = moment($('#tgl_akhir').val() ||
            moment()); // Mengambil nilai tanggal akhir yang sudah di-submit

        function cb(start, end) {
            $('#reportrange span').html(start.format('DD MMMM, YYYY') + ' - ' + end.format('DD MMMM, YYYY'));

            // Kirim nilai tanggal awal dan tanggal akhir ke rute 'laporan'
            const startDate = start.format('YYYY-MM-DD');
            const endDate = end.format('YYYY-MM-DD');

            $('#tgl_awal').val(startDate);
            $('#tgl_akhir').val(endDate);

        }

        $('#reportrange').daterangepicker({
            startDate: start,
            endDate: end,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                    'month').endOf('month')]
            }
        }, cb);

        cb(start, end);
    });
</script>
<script>
    $(document).ready(function() {
        // Event handler untuk tombol hapus
        $('button.btn-danger').on('click', function() {
            const button = $(this);
            const row = button.closest('tr');
            const id = row.data('id'); // Ambil ID dari data-id

            // Konfirmasi penghapusan
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data ini akan dihapus.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!',
            }).then((result) => {
                if (result.isConfirmed) {
                    // Kirim permintaan AJAX ke server
                    $.ajax({
                        url: `/hutang/${id}`,
                        type: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}" // Sertakan CSRF token
                        },
                        success: function(response) {
                            Swal.fire({
                                position: 'center',
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 2000,
                            });

                            // Hapus baris dari tabel
                            row.remove();
                        },
                        error: function(error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Terjadi kesalahan saat menghapus data.',
                            });
                        }
                    });
                }
            });
        });
    });
</script>
