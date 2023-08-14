<div class="row">
    <div class="col-md-12">
        <div class="card card-success card-outline">
            <div class="card-body">
                <form action="{{route('laporan_owner')}}" method="GET">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Dari</label>
                            <input type="date" value="{{isset($request->tgl_awal) != null ? $request->tgl_awal : ''}}" name="tgl_awal" id="tgl_awal" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Sampai</label>
                            <input type="date" value="{{isset($request->tgl_akhir) != null ? $request->tgl_akhir : ''}}" name="tgl_akhir" id="tgl_akhir" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Owner</label>
                            <select name="kode_owner" id="kode_owner" class="form-control select-bootstrap">
                                @foreach ($owner as $item)
                                    <option value="{{$item->id_user}}" {{isset($request->kode_owner) != null && $request->kode_owner == $item->id_user ? 'selected' : ''}}>{{$item->fullname}}</option>
                                @endforeach
                            </select>
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
</div>

{{-- Services --}}
@if (isset($service))
    <form action="{{route('print_laporan_owner')}}" target="_blank" method="GET">
        <input type="hidden" value="{{isset($request->tgl_awal) != null ? $request->tgl_awal : ''}}" name="tgl_awal" id="tgl_awal">
        <input type="hidden" value="{{isset($request->tgl_akhir) != null ? $request->tgl_akhir : ''}}" name="tgl_akhir" id="tgl_akhir" >
        <input type="hidden" value="{{isset($request->kode_owner) != null ? $request->kode_owner : ''}}" name="kode_owner" id="kode_owner" >
        <button type="submit" class="btn btn-success"><i class="fas fa-print"></i> Print</button>
    </form>
    <br>
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <div class="card-title">
                        Laporan Service Tanggal {{$request->tgl_awal }} S/d {{$request->tgl_akhir}}
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
                                        foreach($all_part_toko_service as $a){
                                            if($item->id == $a->kode_services){
                                                $total_part += (($a->detail_harga_part_service - $a->harga_pasang) * $a->qty_part);
                                            }
                                        }
                                    @endphp
                                @endif
                                @if (isset($part_luar_toko_service))
                                    @php
                                        foreach($all_part_luar_toko_service as $b){
                                            if($item->id == $b->kode_services){
                                                $total_part += $b->harga_part * $b->qty_part;
                                            }
                                        }
                                    @endphp
                                @endif
                                    <tr>
                                        <td>{{$loop->index + 1}}</td>
                                        <td>{{$item->created_at}}</td>
                                        <td>{{$item->kode_service}}</td>
                                        <td>{{$item->nama_pelanggan}}</td>
                                        <td>{{$item->no_telp}}</td>
                                        <td>{{$item->type_unit}}</td>
                                        <td>Rp.{{number_format($item->total_biaya)}},-</td>
                                        <td>Rp.{{number_format($total_part)}},-</td>
                                        <td>Rp.{{number_format($item->total_biaya - $total_part)}},-</td>
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
@if (isset($part_toko_service))
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <div class="card-title">
                        Laporan Sparepart Terpakai Tanggal {{$request->tgl_awal }} S/d {{$request->tgl_akhir}}
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
                                        <td>{{$loop->index + 1}}</td>
                                        <td>{{$item->tgl_keluar}}</td>
                                        <td>{{$item->kode_service}}</td>
                                        <td>{{$item->nama_sparepart}}</td>
                                        <td>Rp.{{number_format($item->detail_harga_part_service)}},-</td>
                                        <td>{{$item->qty_part}}</td>
                                        <td>Rp.{{number_format(($item->detail_harga_part_service) * $item->qty_part)}},-</td>
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
                        Laporan Sparepart Luar Toko Terpakai Tanggal {{$request->tgl_awal }} S/d {{$request->tgl_akhir}}
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
                                        <td>{{$loop->index + 1}}</td>
                                        <td>{{$item->tgl_keluar}}</td>
                                        <td>{{$item->kode_service}}</td>
                                        <td>{{$item->nama_part}}</td>
                                        <td>Rp.{{number_format($item->harga_part)}},-</td>
                                        <td>{{number_format($item->qty_part)}}</td>
                                        <td>Rp.{{number_format($item->harga_part * $item->qty_part)}},-</td>
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
                        Laporan Penjualan Tanggal {{$request->tgl_awal }} S/d {{$request->tgl_akhir}}
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
                                        <td>{{$loop->index + 1}}</td>
                                       <td>{{$item->created_at}}</td>
                                       <td>{{$item->kode_penjualan}}</td>
                                       <td>{{$item->nama_customer}}</td>
                                       <td>{{$item->catatan_customer}}</td>
                                       <td>Rp.{{number_format($item->total_penjualan)}},-</td>
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
                        Laporan Penjualan Sparepart Tanggal {{$request->tgl_awal }} S/d {{$request->tgl_akhir}}
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
                                @foreach ($penjualan_sparepart as $item)
                                   <tr>
                                    <td>{{$loop->index + 1}}</td>
                                    <td>{{$item->tgl_keluar}}</td>
                                    <td>{{$item->kode_penjualan}}</td>
                                    <td>{{$item->nama_customer}}</td>
                                    <td>{{$item->nama_sparepart}}</td>
                                    <td>{{$item->qty_sparepart}}</td>
                                    <td>Rp.{{number_format($item->harga_beli)}}</td>
                                    <td>Rp.{{number_format($item->detail_harga_jual * $item->qty_sparepart)}}</td>
                                    <td>Rp.{{number_format(($item->detail_harga_jual * $item->qty_sparepart) - $item->harga_beli)}}</td>
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
@if (isset($penjualan_barang))
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <div class="card-title">
                        Laporan Penjualan Hanphones/ Barang Tanggal {{$request->tgl_awal }} S/d {{$request->tgl_akhir}}
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
                                    <td>{{$loop->index + 1}}</td>
                                    <td>{{$item->tgl_keluar}}</td>
                                    <td>{{$item->kode_penjualan}}</td>
                                    <td>{{$item->nama_customer}}</td>
                                    <td>{{$item->nama_barang}}</td>
                                    <td>{{$item->qty_barang}}</td>
                                    <td>Rp.{{number_format($item->harga_beli_barang)}}</td>
                                    <td>Rp.{{number_format($item->detail_harga_jual * $item->qty_barang)}}</td>
                                    <td>Rp.{{number_format(($item->detail_harga_jual * $item->qty_barang) - $item->harga_beli_barang)}}</td>
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
                        Laporan Pesanan Tanggal {{$request->tgl_awal }} S/d {{$request->tgl_akhir}}
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
                                        <td>{{$loop->index + 1}}</td>
                                        <td>{{$item->created_at}}</td>
                                        <td>{{$item->nama_pemesan}}</td>
                                        <td>{{$item->catatan_pesanan}}</td>
                                        <td>{{$item->total_biaya}}</td>
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