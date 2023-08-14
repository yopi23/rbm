@section('page',$page)

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
              <li class="breadcrumb-item"><a href="{{route('dashboard')}}">Home</a></li>
              <li class="breadcrumb-item active">@yield('page')</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-outline card-success">
                            <div class="card-header">
                                <div class="card-title">
                                    Sparepart
                                </div>
                                <div class="card-tools">
                                    <a href="#" class="btn btn-success" data-toggle="modal" data-target="#modal_sparepart" name="tambah_sparepart" id="tambah_sparepart"><i class="fas fa-plus"></i></a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table" id="TABLES_1">
                                        <thead>
                                            <th>#</th>
                                            <th>Sparepart</th>
                                            <th>Harga</th>
                                            <th>Qty</th>
                                            <th>Total</th>
                                            <th>Aksi</th>
                                        </thead>
                                        <tbody>
                                            @php
                                                $total_part_penjualan = 0;
                                            @endphp
                                            @foreach ($sparepart as $item)
                                            @php
                                                $total_part_penjualan += ($item->harga_jual * $item->qty_sparepart);
                                            @endphp
                                                <tr>
                                                    <td>{{$loop->index + 1}}</td>
                                                    <td>{{$item->nama_sparepart}}</td>
                                                    <td>Rp.{{number_format($item->harga_jual)}},-</td>
                                                    <td>{{$item->qty_sparepart}}</td>
                                                    <td>Rp.{{number_format($item->harga_jual * $item->qty_sparepart)}},-</td>
                                                    <td>
                                                        <form action="{{route('delete_detail_sparepart_penjualan',$item->id_detail)}}" onsubmit="return confirm('Apakah Kamu Yakin ?')" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn btn-sm btn-danger" type="submit"><i class="fas fa-trash"></i></button>
                                                        </form>
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
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-success card-outline">
                            <div class="card-header">
                                <div class="card-title">
                                    Handphone,Laptop Dan Barang
                                </div>
                                <div class="card-tools">
                                    <a href="#" class="btn btn-success" data-toggle="modal" data-target="#modal_barang" name="tambah_barang" id="tambah_barang"><i class="fas fa-plus"></i></a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table" id="TABLES_2">
                                        <thead>
                                            <th>#</th>
                                            <th>Barang</th>
                                            <th>Harga</th>
                                            <th>Qty</th>
                                            <th>Total</th>
                                            <th>Aksi</th>
                                        </thead>
                                        <tbody>
                                            @php
                                                $total_barang_penjualan = 0;
                                            @endphp
                                            @foreach ($barang as $item)
                                            @php
                                                $total_barang_penjualan = ($item->harga_jual_barang * $item->qty_barang);
                                            @endphp
                                                <tr>
                                                    <td>{{$loop->index + 1}}</td>
                                                    <td>{{$item->nama_barang}}</td>
                                                    <td>Rp.{{number_format($item->harga_jual_barang)}},-</td>
                                                    <td>{{$item->qty_barang}}</td>
                                                    <td>Rp.{{number_format($item->harga_jual_barang * $item->qty_barang)}},-</td>
                                                    <td>
                                                        <form action="{{route('delete_detail_barang_penjualan',$item->id_detail)}}" onsubmit="return confirm('Apakah Kamu Yakin ?')" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn btn-sm btn-danger" type="submit"><i class="fas fa-trash"></i></button>
                                                        </form>
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
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-success card-outline">
                            <div class="card-header">
                                <div class="card-title">
                                    Garansi
                                </div>
                                <div class="card-tools">
                                    <a href="#" class="btn btn-success" data-toggle="modal" data-target="#modal_garansi" name="tambah_garansi" id="tambah_garansi"><i class="fas fa-plus"></i></a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table" id="TABLES_2">
                                        <thead>
                                            <th>#</th>
                                            <th>Nama</th>
                                            <th>Exp</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </thead>
                                        <tbody>
                                            @foreach ($garansi as $item)
                                                <tr>
                                                    <td>{{$loop->index + 1}}</td>
                                                    <td>{{$item->nama_garansi}}</td>
                                                    <td>{{$item->tgl_exp_garansi}}</td>
                                                    <td>@switch($item->status_garansi)
                                                            @case(0)
                                                                <span class="badge badge-primary">Aktif</span>
                                                                @break
                                                            @case(1)
                                                                <span class="badge badge-danger">Tidak Aktif</span>
                                                                @break
                                                            @case(2)
                                                                <span class="badge badge-success">DiKlaim</span>
                                                                @break
                                                            @default 
                                                        @endswitch
                                                    </td>
                                                    <td>
                                                        <form action="{{route('delete_garansi_penjualan',$item->id)}}" onsubmit="return confirm('Apakah Kamu Yakin ?')" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn btn-sm btn-danger" type="submit"><i class="fas fa-trash"></i></button>
                                                        </form>
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
            </div>
            <div class="col-md-4">
                <form action="{{route('update_penjualan',$data->id)}}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-outline card-success">
                                <div class="card-body">
                                    <label>Grand Total </label>
                                    <h2>Rp.{{number_format($total_part_penjualan + $total_barang_penjualan)}},-</h2>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="card card-outline card-success">
                                <div class="card-body">
                                    <input type="hidden" name="total_penjualan" id="total_penjualan" class="form-control" value="{{$total_part_penjualan + $total_barang_penjualan}}">
                                    <div class="form-group">
                                        <label>Kode Invoice</label>
                                        <input type="text" name="kode_penjualan" id="kode_penjualan" class="form-control" value="{{$data->kode_penjualan}}" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Tanggal</label>
                                        <input type="date" name="tgl_penjualan" id="tgl_penjualan" value="{{$data->tgl_penjualan != null ? $data->tgl_penjualan : date('Y-m-d')}}" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Total Bayar</label>
                                        <input type="number" name="total_bayar" id="total_bayar" class="form-control" value="{{$data->total_bayar}}" placeholder="Total Bayar" required>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" name="simpan" value="bayar" class="form-control btn btn-success"><i class="fas fa-cash-register"></i> Bayar</button>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" name="simpan" value="simpan" class="form-control btn btn-primary"><i class="fas fa-paste"></i> Simpan</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="card card-outline card-success">
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Nama Pelanggan</label>
                                        <input type="text" name="nama_customer" id="nama_customer" class="form-control" placeholder="Nama">
                                    </div>
                                <div class="form-group">
                                    <label>Catatan</label>
                                    <textarea name="catatan_customer" id="catatan_customer" placeholder="Catatan Customer" class="form-control" cols="30" rows="7"></textarea>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>   
        </div>
        <div class="modal fade" id="modal_sparepart">
            <div class="modal-dialog modal-xl">
              <div class="modal-content">
                <div class="modal-header">
                  <h4 class="modal-title">Tambah Sparepart</h4>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                    <div class="modal-body">
                     <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table" id="TABLES_2">
                                    <thead>
                                        <th>No</th>
                                        <th>Sparepart</th>
                                        <th>Harga Jual</th>
                                        <th>Stok</th>
                                        <th>Aksi</th>
                                    </thead>
                                    <tbody id="search-result">
                                        @foreach ($all_sparepart as $item)
                                            <tr>
                                                <td>{{$loop->index + 1}}</td>
                                                <td>{{$item->nama_sparepart}}</td>
                                                <td>Rp.{{number_format($item->harga_jual)}},-</td>
                                                <td>{{$item->stok_sparepart}}</td>
                                                <td>
                                                    @if ($item->stok_sparepart > 0)
                                                        <form action="{{route('create_detail_sparepart_penjualan')}}" method="POST">
                                                            @csrf
                                                            @method('POST')
                                                            <input type="hidden" name="kode_penjualan" id="kode_penjualan" value="{{$data->id}}">
                                                            <input type="hidden" name="kode_sparepart" id="kode_sparepart" value="{{$item->id}}">
                                                            <div class="row">
                                                                <div class="col-md-10">
                                                                    <input type="number" value="1" max="{{$item->stok_sparepart}}" name="qty_sparepart" id="qty_sparepart" class="form-control" required>
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i></button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    @else
                                                        <span class="badge badge-danger">Stok Tidak Tersedia</span>
                                                    @endif
                                                    
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                        </div>
                     </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                    </div>
              </div>
              <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        <div class="modal fade" id="modal_barang">
            <div class="modal-dialog modal-xl">
              <div class="modal-content">
                <div class="modal-header">
                  <h4 class="modal-title">Tambah Barang</h4>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                    <div class="modal-body">
                     <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table" id="TABLES_2">
                                    <thead>
                                        <th>No</th>
                                        <th>Barang</th>
                                        <th>Harga Jual</th>
                                        <th>Stok</th>
                                        <th>Aksi</th>
                                    </thead>
                                    <tbody id="search-result">
                                        @foreach ($all_barang as $item)
                                        <tr>
                                            <td>{{$loop->index + 1}}</td>
                                            <td>{{$item->nama_barang}}</td>
                                            <td>Rp.{{number_format($item->harga_jual_barang)}},-</td>
                                            <td>{{$item->stok_barang}}</td>
                                            <td>
                                                <form action="{{route('create_detail_barang_penjualan')}}" method="POST">
                                                    @csrf
                                                    @method('POST')
                                                    <input type="hidden" name="kode_penjualan" id="kode_penjualan" value="{{$data->id}}">
                                                    <input type="hidden" name="kode_barang" id="kode_barang" value="{{$item->id}}">
                                                    <div class="row">
                                                        <div class="col-md-10">
                                                            <input type="number" value="1" max="{{$item->stok_barang}}" name="qty_barang" id="qty_barang" class="form-control" required>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i></button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                        </div>
                     </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                    </div>
              </div>
              <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        <div class="modal fade" id="modal_garansi">
            <div class="modal-dialog modal-md">
              <div class="modal-content">
                <div class="modal-header">
                  <h4 class="modal-title">Tambah Garansi</h4>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                    <form action="{{route('store_garansi_penjualan')}}" method="POST">
                        @csrf
                        @method('POST')
                    
                    <div class="modal-body">
                        <input type="hidden" name="kode_garansi" id="kode_garansi" value="{{$data->kode_penjualan}}">
                        <div class="form-group">
                            <label>Nama Garansi</label>
                            <input type="text" placeholder="Nama Garansi" name="nama_garansi" id="nama_garansi" class="form-control" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tanggal Mulai Garansi</label>
                                    <input type="date" name="tgl_mulai_garansi" id="tgl_mulai_garansi" value="{{date('Y-m-d')}}" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tanggal Exp Garansi</label>
                                    <input type="date" name="tgl_exp_garansi" id="tgl_exp_garansi" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Catatan Garansi</label>
                            <textarea name="catatan_garansi" placeholder="Catatan Garansi ..." id="catatan_garansi" class="form-control" cols="30" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-success">Simpan</button>
                    </div>
                </form>
              </div>
              <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
      </div>
    </section>
    <!-- /.content -->
  </div>

@include('admin.component.footer')

