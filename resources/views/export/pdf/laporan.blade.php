<!DOCTYPE html>
<html>
<head>
	<title>Laporan ALL IN ONE</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>
<body>
	<style type="text/css">
		table tr td,
		table tr th{
			font-size: 9pt;
		}
	</style>
	<center>
		<h5>Laporan ALL IN ONE</h4>
	</center>
    <table style="width: 100%; border:0;">
        <tbody>
            <tr>
                <td>Tanggal : {{date('Y-m-d')}} </td>
                <td style="text-align: right;"></td>
            </tr>
            <tr>
                <td>Owner : {{$this_user->fullname}} ( {{$this_user->kode_invite}} ) </td>
                <td style="text-align: right;">Periode : {{$request->tgl_awal}} - {{$request->tgl_akhir}}</td>
            </tr>
            </tbody>
    </table>
    <center><h6>Service</h6></center>
	<table class='table table-bordered' style="width: 100%;">
		<thead>
			<tr>
				<th>No</th>
				<th>Tanggal</th>
				<th>Invoice</th>
				<th>Nama</th>
				<th>Type</th>
				<th>Total</th>
                <th>Sparepart</th>
				<th>Profit</th>
			</tr>
		</thead>
        <tbody>
            @php
                $final_total_service = 0;
                $final_sparepart_service = 0;
                $final_profit_service = 0;
            @endphp
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
                    <td>{{$item->type_unit}}</td>
                    <td>Rp.{{number_format($item->total_biaya)}},-</td>
                    <td>Rp.{{number_format($total_part)}},-</td>
                    <td>Rp.{{number_format($item->total_biaya - $total_part)}},-</td>
                </tr>
                @php
                    $final_total_service += $item->total_biaya;
                    $final_sparepart_service += $total_part;
                    $final_profit_service += ($item->total_biaya - $total_part);
                @endphp
            @endforeach
        </tbody>
        <tfoot class="font-weight-bold">
            <tr>
                <td colspan="5" class="text-center text-uppercase">Total</td>
                <td>Rp.{{number_format($final_total_service)}},-</td>
                <td>Rp.{{number_format($final_sparepart_service)}},-</td>
                <td>Rp.{{number_format($final_profit_service)}},-</td>
            </tr>
        </tfoot>
	</table>
    <center><h6>Laba Sparepart (Terpakai Untuk Service)</h6></center>
    <table class="table table-bordered" style="width: 100%">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Invoice</th>
                <th>Sparepart</th>
                <th>Qty</th>
                <th>Harga Beli (Modal)</th>
                <th>Harga Jual + Pasang</th>
                <th>Laba</th>
            </tr>
        </thead>
        <tbody>
            @php
                $total_modal_part_service = 0;
                $total_harga_part_service = 0;
                $total_profit_part_service = 0;
            @endphp
            @foreach ($part_toko_service as $item)
            @php
                $harga_part_service = ($a->detail_harga_part_service - $a->harga_pasang) * $item->qty_part;
                $profit_part_service = $harga_part_service - ($item->harga_beli * $item->qty_part);

                $total_modal_part_service += ($item->harga_beli * $item->qty_part);
                $total_harga_part_service += $harga_part_service;
                $total_profit_part_service += $profit_part_service;
            @endphp
                <tr>
                    <td>{{$loop->index + 1}}</td>
                    <td>{{$item->tgl_keluar}}</td>
                    <td>{{$item->kode_service}}</td>
                    <td>{{$item->nama_sparepart}}</td>
                    <td>{{$item->qty_part}}</td>
                    <td>Rp.{{number_format($item->harga_beli * $item->qty_part)}},-</td>
                    <td>Rp.{{number_format($harga_part_service)}},-</td>
                    <td>Rp.{{number_format($profit_part_service)}},-</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="font-weight-bold">
            <tr>
                <td colspan="5" class="text-center text-uppercase">Total</td>
                <td>Rp.{{number_format($total_modal_part_service)}},-</td>
                <td>Rp.{{number_format($total_harga_part_service)}},-</td>
                <td>Rp.{{number_format($total_profit_part_service)}},-</td>
            </tr>
        </tfoot>
    </table>
    <center><h6>Sparepart Luar Toko(Terpakai Untuk Service)</h6></center>
    <table class="table table-bordered" style="width: 100%">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Invoice</th>
                <th>Sparepart</th>
                <th>Qty</th>
                <th>Harga</th>
            </tr>
        </thead>
        <tbody>
            @php
                $total_harga_part_luar_service = 0;
            @endphp
            @foreach ($part_luar_toko_service as $item)
                @php
                $total_harga_part_luar_service += ($item->harga_part * $item->qty_part);
                @endphp
                <tr>
                    <td>{{$loop->index + 1}}</td>
                    <td>{{$item->tgl_keluar}}</td>
                    <td>{{$item->kode_service}}</td>
                    <td>{{$item->nama_part}}</td>
                    <td>{{number_format($item->qty_part)}}</td>
                    <td>Rp.{{number_format($item->harga_part * $item->qty_part)}},-</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="font-weight-bold">
            <tr>
                <td colspan="5" class="text-center text-uppercase">Total</td>
                <td>Rp.{{number_format($total_harga_part_luar_service)}},-</td>
            </tr>
        </tfoot>
    </table>
    <center><h6>Laba Penjualan Sparepart</h6></center>
    <table class='table table-bordered' style="width: 100%;">
		<thead>
			<tr>
				<th>No</th>
				<th>Tanggal</th>
				<th>Invoice</th>
				<th>Nama</th>
                <th>Sparepart</th>
				<th>Qty</th>
                <th>Harga Beli (Modal)</th>
                <th>Harga Jual</th>
				<th>Profit</th>
			</tr>
		</thead>
        <tbody>
            @php
                $final_total_part_penjualan = 0;
                $final_modal_part_penjualan = 0;
                $final_profit_part_penjualan = 0;
            @endphp
            @foreach ($penjualan_sparepart as $item)
                @php
                    $harga_part_penjualan = ($item->detail_harga_jual  * $item->qty_sparepart);
                    $profit_part_penjualan = $harga_part_penjualan - ($item->harga_beli * $item->qty_sparepart);

                    $final_total_part_penjualan += ($item->harga_beli * $item->qty_sparepart);
                    $final_modal_part_penjualan += $harga_part_penjualan;
                    $final_profit_part_penjualan += $profit_part_penjualan;
                @endphp
                <tr>
                    <td>{{$loop->index + 1}}</td>
                <td>{{$item->created_at}}</td>
                <td>{{$item->kode_penjualan}}</td>
                <td>{{$item->nama_customer}}</td>
                <td>{{$item->nama_sparepart}}</td>
                <td>{{number_format($item->qty_sparepart)}}</td>
                <td>Rp.{{number_format($item->harga_beli * $item->qty_sparepart)}},-</td>
                <td>Rp.{{number_format($harga_part_penjualan)}},-</td>
                <td>Rp.{{number_format($profit_part_penjualan)}},-</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="font-weight-bold">
            <tr>
                <td colspan="6" class="text-center text-uppercase">Total</td>
                <td>Rp.{{number_format($final_total_part_penjualan)}},-</td>
                <td>Rp.{{number_format($final_modal_part_penjualan)}},-</td>
                <td>Rp.{{number_format($final_profit_part_penjualan)}},-</td>
            </tr>
        </tfoot>
	</table>
    <center><h6>Laba Penjualan Handphone / Barang</h6></center>
    <table class='table table-bordered' style="width: 100%;">
		<thead>
			<tr>
				<th>No</th>
				<th>Tanggal</th>
				<th>Invoice</th>
				<th>Nama</th>
				<th>Qty</th>
                <th>Harga Beli (Modal)</th>
                <th>Harga Jual</th>
				<th>Profit</th>
			</tr>
		</thead>
        <tbody>
            @php
                $final_total_barang_penjualan = 0;
                $final_modal_barang_penjualan = 0;
                $final_profit_barang_penjualan = 0;
            @endphp
            @foreach ($penjualan_barang as $item)
                @php
                    $harga_barang_penjualan = ($item->detail_harga_jual  * $item->qty_barang);
                    $profit_barang_penjualan = $harga_barang_penjualan - ($item->harga_beli_barang * $item->qty_barang);

                    $final_total_barang_penjualan += ($item->harga_beli_barang * $item->qty_barang);
                    $final_modal_barang_penjualan += $harga_barang_penjualan;
                    $final_profit_barang_penjualan += $profit_barang_penjualan;
                @endphp
                <tr>
                    <td>{{$loop->index + 1}}</td>
                <td>{{$item->created_at}}</td>
                <td>{{$item->kode_penjualan}}</td>
                <td>{{$item->nama_customer}}</td>
                <td>{{number_format($item->qty_barang)}}</td>
                <td>Rp.{{number_format($item->harga_beli_barang * $item->qty_barang)}},-</td>
                <td>Rp.{{number_format($harga_barang_penjualan)}},-</td>
                <td>Rp.{{number_format($profit_barang_penjualan)}},-</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="font-weight-bold">
            <tr>
                <td colspan="5" class="text-center text-uppercase">Total</td>
                <td>Rp.{{number_format($final_total_barang_penjualan)}},-</td>
                <td>Rp.{{number_format($final_modal_barang_penjualan)}},-</td>
                <td>Rp.{{number_format($final_profit_barang_penjualan)}},-</td>
            </tr>
        </tfoot>
	</table>
    <center><h6>Laba Pesanan Sparepart</h6></center>
    <table class='table table-bordered' style="width: 100%;">
		<thead>
			<tr>
				<th>No</th>
				<th>Tanggal</th>
				<th>Invoice</th>
				<th>Nama</th>
                <th>Sparepart</th>
				<th>Qty</th>
                <th>Harga Beli (Modal)</th>
                <th>Harga Jual</th>
				<th>Profit</th>
			</tr>
		</thead>
        <tbody>
            @php
                $final_total_part_pesanan = 0;
                $final_modal_part_pesanan = 0;
                $final_profit_part_pesanan = 0;
            @endphp
            @foreach ($sparepart_pesanan as $item)
                @php
                    $harga_part_pesanan = ($item->detail_harga_pesan  * $item->qty_sparepart);
                    $profit_part_pesanan = $harga_part_pesanan - ($item->harga_beli * $item->qty_sparepart);

                    $final_total_part_pesanan += ($item->harga_beli * $item->qty_sparepart);
                    $final_modal_part_pesanan += $harga_part_pesanan;
                    $final_profit_part_pesanan += $profit_part_pesanan;
                @endphp
                <tr>
                    <td>{{$loop->index + 1}}</td>
                <td>{{$item->created_at}}</td>
                <td>{{$item->kode_pesanan}}</td>
                <td>{{$item->nama_pemesan}}</td>
                <td>{{$item->nama_sparepart}}</td>
                <td>{{number_format($item->qty_sparepart)}}</td>
                <td>Rp.{{number_format($item->harga_beli * $item->qty_sparepart)}},-</td>
                <td>Rp.{{number_format($harga_part_pesanan)}},-</td>
                <td>Rp.{{number_format($profit_part_pesanan)}},-</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="font-weight-bold">
            <tr>
                <td colspan="6" class="text-center text-uppercase">Total</td>
                <td>Rp.{{number_format($final_total_part_pesanan)}},-</td>
                <td>Rp.{{number_format($final_modal_part_pesanan)}},-</td>
                <td>Rp.{{number_format($final_profit_part_pesanan)}},-</td>
            </tr>
        </tfoot>
	</table>
    <center><h6>Laba Pesanan Handphone / Barang</h6></center>
    <table class='table table-bordered' style="width: 100%;">
		<thead>
			<tr>
				<th>No</th>
				<th>Tanggal</th>
				<th>Invoice</th>
				<th>Nama</th>
				<th>Qty</th>
                <th>Harga Beli (Modal)</th>
                <th>Harga Jual</th>
				<th>Profit</th>
			</tr>
		</thead>
        <tbody>
            @php
                $final_total_barang_pesanan = 0;
                $final_modal_barang_pesanan = 0;
                $final_profit_barang_pesanan = 0;
            @endphp
            @foreach ($barang_pesanan as $item)
                @php
                    $harga_barang_pesanan = ($item->detail_harga_pesan  * $item->qty_barang);
                    $profit_barang_pesanan = $harga_barang_pesanan - ($item->harga_beli_barang * $item->qty_barang);

                    $final_total_barang_pesanan += ($item->harga_beli_barang * $item->qty_barang);
                    $final_modal_barang_pesanan += $harga_barang_pesanan;
                    $final_profit_barang_pesanan += $profit_barang_pesanan;
                @endphp
                <tr>
                    <td>{{$loop->index + 1}}</td>
                <td>{{$item->created_at}}</td>
                <td>{{$item->kode_pesanan}}</td>
                <td>{{$item->nama_pemesan}}</td>
                <td>{{number_format($item->qty_barang)}}</td>
                <td>Rp.{{number_format($item->harga_beli_barang * $item->qty_barang)}},-</td>
                <td>Rp.{{number_format($harga_barang_pesanan)}},-</td>
                <td>Rp.{{number_format($profit_barang_pesanan)}},-</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="font-weight-bold">
            <tr>
                <td colspan="5" class="text-center text-uppercase">Total</td>
                <td>Rp.{{number_format($final_total_barang_pesanan)}},-</td>
                <td>Rp.{{number_format($final_modal_barang_pesanan)}},-</td>
                <td>Rp.{{number_format($final_profit_barang_pesanan)}},-</td>
            </tr>
        </tfoot>
	</table>
    <center><h6>Pengeluaran Toko</h6></center>
    <table class='table table-bordered' style="width: 100%;">
		<thead>
			<tr>
				<th>No</th>
				<th>Tanggal</th>
				<th>Nama</th>
				<th>Keterangan</th>
                <th>Jumlah</th>
			</tr>
		</thead>
        <tbody>
            @php
                $final_pengeluaran_toko = 0;
            @endphp
            @foreach ($pengeluaran_toko as $item)
                @php
                    $final_pengeluaran_toko += $item->jumlah_pengeluaran;
                @endphp
                <tr>
                    <td>{{$loop->index + 1}}</td>
                    <td>{{$item->created_at}}</td>
                    <td>{{$item->nama_pengeluaran}}</td>
                    <td>{{$item->catatan_pengeluaran}}</td>
                    <td>Rp.{{number_format($item->jumlah_pengeluaran)}},-</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="font-weight-bold">
            <tr>
                <td colspan="4" class="text-center text-uppercase">Total</td>
                <td>Rp.{{number_format($final_pengeluaran_toko)}},-</td>
            </tr>
        </tfoot>
	</table>
    <center><h6>Pengeluaran Operasional (OPEX)</h6></center>
    <table class='table table-bordered' style="width: 100%;">
		<thead>
			<tr>
				<th>No</th>
				<th>Tanggal</th>
				<th>Nama</th>
                <th>Kategori</th>
				<th>Keterangan</th>
                <th>Jumlah</th>
			</tr>
		</thead>
        <tbody>
            @php
                $final_pengeluaran_opx = 0;
            @endphp
            @foreach ($pengeluaran_opx as $item)
                @php
                    $final_pengeluaran_opx += $item->jml_pengeluaran;
                @endphp
                   @foreach ($pengeluaran_opx as $item)
                   <tr>
                       <td>{{$loop->index + 1}}</td>
                       <td>{{$item->created_at}}</td>
                       <td>{{$item->nama_pengeluaran}}</td>
                       <td>{{$item->kategori}}</td>
                       <td>{{$item->desc_pengeluaran}}</td>
                       <td>Rp.{{number_format($item->jml_pengeluaran)}},-</td>
                   </tr>
               @endforeach
            @endforeach
        </tbody>
        <tfoot class="font-weight-bold">
            <tr>
                <td colspan="5" class="text-center text-uppercase">Total</td>
                <td>Rp.{{number_format($final_pengeluaran_opx)}},-</td>
            </tr>
        </tfoot>
	</table>
    <center><h6>Penarikan Saldo Karyawan</h6></center>
    <table class='table table-bordered' style="width: 100%;">
		<thead>
			<tr>
                <th>#</th>
                <th>Tanggal</th>
                <th>Karyawan</th>
                <th>Keterangan</th>
                <th>Jumlah</th>
			</tr>
		</thead>
        <tbody>
            @php
                $final_penarikan = 0;
            @endphp
             @foreach ($penarikan as $item)
                @php
                    $final_penarikan += $item->jumlah_penarikan
                @endphp
                <tr>
                    <td>{{$loop->index + 1}}</td>
                    <td>{{$item->created_at}}</td>
                    <td>{{$item->fullname}}</td>
                    <td>{{$item->catatan_penarikan}}</td>
                    <td>Rp.{{number_format($item->jumlah_penarikan)}},-</td>
                    
                </tr>
            @endforeach
        </tbody>
        <tfoot class="font-weight-bold">
            <tr>
                <td colspan="4" class="text-center text-uppercase">Total</td>
                <td>Rp.{{number_format($final_penarikan)}},-</td>
            </tr>
        </tfoot>
	</table>
    <center><h6>Pemasukkan Lain</h6></center>
    <table class='table table-bordered' style="width: 100%;">
		<thead>
			<tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Nama</th>
                <th>Keterangan</th>
                <th>Jumlah</th>
			</tr>
		</thead>
        <tbody>
            @php
                $final_pemasukkan_lain = 0;
            @endphp
            @foreach ($pemasukkan_lain as $item)
                @php
                    $final_pemasukkan_lain += $item->jumlah_pemasukkan;
                @endphp
                    <tr>
                        <td>{{$loop->index + 1}}</td>
                        <td>{{$item->created_at}}</td>
                        <td>{{$item->judul_pemasukan}}</td>
                        <td>{{$item->catatan_pemasukkan}}</td>
                        <td>Rp.{{number_format($item->jumlah_pemasukkan)}},-</td>
                    </tr>
            @endforeach
        </tbody>
        <tfoot class="font-weight-bold">
            <tr>
                <td colspan="4" class="text-center text-uppercase">Total</td>
                <td>Rp.{{number_format($final_pemasukkan_lain)}},-</td>
            </tr>
        </tfoot>
	</table>
</body>
</html>