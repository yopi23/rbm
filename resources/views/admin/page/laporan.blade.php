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
    </div>
</div>



{{-- JIKA ADA DATA LAPORAN (SETELAH SUBMIT TANGGAL) --}}
@if (isset($request->tgl_awal))
    {{-- BARIS 1: OMSET --}}
    <div class="row">
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
                <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-cog"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Omset Service</span>
                    <span class="info-box-number">Rp.{{ number_format($omsetService) }},-</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
                <span class="info-box-icon bg-success elevation-1"><i class="fas fa-shopping-cart"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Omset Penjualan</span>
                    <span class="info-box-number">Rp.{{ number_format($omsetPenjualan) }},-</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
                <span class="info-box-icon bg-dark elevation-1"><i class="fas">&#xf155;</i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Omset</span>
                    <span class="info-box-number">Rp.{{ number_format($omsetService + $omsetPenjualan) }},-</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
                <span class="info-box-icon bg-warning elevation-1"><i class="fas"
                        style="color: #fff;">&#xf155;</i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Uang Muka</span>
                    <span class="info-box-number">Rp.{{ number_format($totalUangMuka) }},-</span>
                </div>
            </div>
        </div>
    </div>

    {{-- BARIS 2: LABA & BIAYA --}}
    <div class="row">
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
                <span class="info-box-icon bg-info elevation-1"><i class="fas fa-chart-line"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Laba Kotor</span>
                    <span class="info-box-number">Rp.{{ number_format($labaResult['laba_kotor']) }},-</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
                <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-arrow-circle-down"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Beban</span>
                    <span class="info-box-number">Rp.{{ number_format($labaResult['total_beban']) }},-</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3 bg-teal">
                <span class="info-box-icon"><i class="fas fa-balance-scale-right"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">LABA BERSIH FINAL</span>
                    <span class="info-box-number">Rp.{{ number_format($labaResult['laba_bersih']) }},-</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
                <span class="info-box-icon elevation-1" style="background-color: #00ce90"><i class="fas"
                        style="color: #fff;">&#xf155;</i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Penarikan</span>
                    <span class="info-box-number">Rp.{{ number_format($totalPenarikan) }},-</span>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h3 class="card-title">Rincian Total Beban Periode Ini</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Komponen Beban</th>
                                <th class="text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($labaResult['detail_beban'] as $namaBeban => $jumlahBeban)
                                <tr>
                                    <td>{{ $namaBeban }}</td>
                                    <td class="text-right">Rp. {{ number_format($jumlahBeban, 0, ',', '.') }},-</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="font-weight-bold bg-light">
                            <tr>
                                <td>Total Semua Beban</td>
                                <td class="text-right">Rp. {{ number_format($labaResult['total_beban'], 0, ',', '.') }},-</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Tombol Print --}}
    <form action="{{ route('print_laporan') }}" target="_blank" method="GET" class="mb-3">
        <input type="hidden" value="{{ $request->tgl_awal }}" name="tgl_awal">
        <input type="hidden" value="{{ $request->tgl_akhir }}" name="tgl_akhir">
        <button type="submit" class="btn btn-success"><i class="fas fa-print"></i> Print Semua Laporan</button>
    </form>

    {{-- Laporan Service --}}
    @if (isset($service) && count($service) > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <div class="card-title">Laporan Service Tanggal {{ $request->tgl_awal }} S/d
                            {{ $request->tgl_akhir }}</div>
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
                                            // Kalkulasi ini mungkin perlu disederhanakan di controller, tapi kita biarkan dulu agar tidak error
                                            foreach ($all_part_toko_service as $a) {
                                                if ($item->id == $a->kode_services) {
                                                    $totalPart +=
                                                        ($a->detail_harga_part_service - $a->total_biaya) *
                                                        $a->qty_part;
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
                                <tfoot class="font-weight-bold">
                                    <tr>
                                        <td colspan="7" class="text-right">Total Laba Kotor Service:</td>
                                        <td colspan="3">Rp.{{ number_format($labaKotorService) }},-</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Laporan Sparepart Terpakai --}}
    @if (isset($part_toko_service) && count($part_toko_service) > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <div class="card-title">Laporan Sparepart Terpakai Tanggal {{ $request->tgl_awal }} S/d
                            {{ $request->tgl_akhir }}</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="TABLES_2">
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

    {{-- Laporan Sparepart Luar Toko Terpakai --}}
    @if (isset($part_luar_toko_service) && count($part_luar_toko_service) > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <div class="card-title">Laporan Sparepart Luar Toko Terpakai Tanggal {{ $request->tgl_awal }}
                            S/d {{ $request->tgl_akhir }}</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="TABLES_3">
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

    {{-- Laporan Penjualan --}}
    @if (isset($penjualan) && count($penjualan) > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <div class="card-title">Laporan Penjualan Tanggal {{ $request->tgl_awal }} S/d
                            {{ $request->tgl_akhir }}</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="TABLES_4">
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

    {{-- Laporan Penjualan Sparepart --}}
    @if (isset($penjualan_sparepart) && count($penjualan_sparepart) > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <div class="card-title">Laporan Penjualan Sparepart Tanggal {{ $request->tgl_awal }} S/d
                            {{ $request->tgl_akhir }}</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="TABLES_5">
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
                                            <td>Rp.{{ number_format($profit) }}</td>
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

    {{-- Laporan Penjualan Barang --}}
    @if (isset($penjualan_barang) && count($penjualan_barang) > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <div class="card-title">Laporan Penjualan Handphone/Barang Tanggal {{ $request->tgl_awal }}
                            S/d {{ $request->tgl_akhir }}</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="TABLES_6">
                                <thead>
                                    <th>#</th>
                                    <th>Tanggal</th>
                                    <th>Invoice</th>
                                    <th>Nama</th>
                                    <th>Handphone/Barang</th>
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
                                            <td>Rp.{{ number_format($item->detail_harga_jual * $item->qty_barang) }}
                                            </td>
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

    {{-- Laporan Pesanan --}}
    @if (isset($pesanan) && count($pesanan) > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <div class="card-title">Laporan Pesanan Tanggal {{ $request->tgl_awal }} S/d
                            {{ $request->tgl_akhir }}</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="TABLES_7">
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
                                            <td>{{ $item->kode_pesanan }}</td>
                                            <td>{{ $item->nama_pemesan }}</td>
                                            <td>{{ $item->catatan_pesanan }}</td>
                                            <td>Rp.{{ number_format($item->total_biaya) }}</td>
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

    {{-- Laporan Pesanan Sparepart --}}
    @if (isset($sparepart_pesanan) && count($sparepart_pesanan) > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <div class="card-title">Laporan Pesanan Sparepart Tanggal {{ $request->tgl_awal }} S/d
                            {{ $request->tgl_akhir }}</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="TABLES_8">
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

    {{-- Laporan Pesanan Barang --}}
    @if (isset($barang_pesanan) && count($barang_pesanan) > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <div class="card-title">Laporan Pesanan Handphone/Barang Tanggal {{ $request->tgl_awal }} S/d
                            {{ $request->tgl_akhir }}</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="TABLES_9">
                                <thead>
                                    <th>#</th>
                                    <th>Tanggal</th>
                                    <th>Invoice</th>
                                    <th>Nama</th>
                                    <th>Handphone/Barang</th>
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
                                            <td>Rp.{{ number_format($item->detail_harga_pesan * $item->qty_barang) }}
                                            </td>
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

    {{-- Laporan Pengeluaran Operasional --}}
    @if (isset($pengeluaran_opx) && count($pengeluaran_opx) > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <div class="card-title">Laporan Pengeluaran Operasional Tanggal {{ $request->tgl_awal }} S/d
                            {{ $request->tgl_akhir }}</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="TABLES_10">
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

    {{-- Laporan Pengeluaran Toko --}}
    @if (isset($pengeluaran_toko) && count($pengeluaran_toko) > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <div class="card-title">Laporan Pengeluaran Toko Tanggal {{ $request->tgl_awal }} S/d
                            {{ $request->tgl_akhir }}</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="TABLES_11">
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

    {{-- Laporan Penarikan Saldo Karyawan --}}
    @if (isset($penarikan) && count($penarikan) > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <div class="card-title">Laporan Penarikan Saldo Karyawan Tanggal {{ $request->tgl_awal }} S/d
                            {{ $request->tgl_akhir }}</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="TABLES_12">
                                <thead>
                                    <th>#</th>
                                    <th>Tanggal</th>
                                    <th>Karyawan</th>
                                    <th>Keterangan</th>
                                    <th>Jumlah</th>
                                </thead>
                                <tbody>
                                    @foreach ($penarikan as $item)
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
                </div>
            </div>
        </div>
    @endif

    {{-- Laporan Pemasukkan Lain --}}
    @if (isset($pemasukkan_lain) && count($pemasukkan_lain) > 0)
        <div class="row">
            <div class="col-md-12">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <div class="card-title">Laporan Pemasukkan Lain Tanggal {{ $request->tgl_awal }} S/d
                            {{ $request->tgl_akhir }}</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="TABLES_13">
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
@else
    {{-- Tampilan Hutang jika tidak ada filter tanggal --}}
    <div class="row">
        <div class="col">
            <div class="info-box mb-3">
                <div class="info-box-content">
                    <h4>Hutang</h4>
                    <div class="table-responsive">
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
                                        <strong>Ingat!! Hutang Dibawa Mati</strong>
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

{{-- SCRIPT BAGIAN BAWAH --}}
<script type="text/javascript">
    $(function() {
        const start = moment($('#tgl_awal').val() || moment().startOf('day'));
        const end = moment($('#tgl_akhir').val() || moment());

        function cb(start, end) {
            $('#reportrange span').html(start.format('DD MMMM, YYYY') + ' - ' + end.format('DD MMMM, YYYY'));
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
        $('button.btn-danger').on('click', function() {
            const button = $(this);
            const row = button.closest('tr');
            const id = row.data('id');

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
                    $.ajax({
                        url: `/hutang/${id}`,
                        type: 'DELETE',
                        data: {
                            _token: "{{ csrf_token() }}"
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
