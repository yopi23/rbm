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
            <div class="col-md-12">
                  @if(session('error'))
                  <div class="alert alert-danger">
                      {{ session('error') }}
                  </div>
                  @endif
                  @if(session('success'))
                  <div class="alert alert-primary">
                      {{ session('success') }}
                  </div>
                  @endif
                    <div class="row">
                        <div class="col-md-8">
                            <div class="row">
                                @php
                                    $total_barang = 0;
                                    $total_sparepart = 0;
                                @endphp
                                @if ($barang->count() > 0)
                                <div class="col-md-12">
                                    <div class="card card-outline card-success">
                                        <div class="card-header">
                                          <div class="card-title">
                                            Barang Yang Dipesan
                                          </div>  
                                        </div>
                                        <div class="card-body">
                                            <table class="table" id="TABLES_1">
                                                <thead>
                                                    <th>#</th>
                                                    <th>Nama Barang</th>
                                                    <th>Harga</th>
                                                    <th>Total</th>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        $total_barang = 0;
                                                    @endphp
                                                    @foreach ($barang as $item)
                                                    @php
                                                        $total_barang += ($item->detail_harga_pesan * $item->qty_barang);
                                                    @endphp
                                                        <tr>
                                                            <td>{{$loop->index + 1}}</td>
                                                            <td>{{$item->nama_barang}}</td>
                                                            <td>Rp.{{number_format($item->detail_harga_pesan)}},- X {{$item->qty_barang}}</td>
                                                            <td>Rp.{{number_format($item->detail_harga_pesan * $item->qty_barang)}}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if ($sparepart->count() >= 0)
                                    <div class="col-md-12">
                                        <div class="card card-outline card-success">
                                            <div class="card-header">
                                            <div class="card-title">
                                                Sparepart Yang Dipesan
                                            </div>
                                            </div>
                                            <div class="card-body">
                                                <table class="table" id="TABLES_1">
                                                    <thead>
                                                        <th>#</th>
                                                        <th>Nama Sparepart</th>
                                                        <th>Harga</th>
                                                        <th>Total</th>
                                                    </thead>
                                                    <tbody>
                                                        @php
                                                            $total_sparepart = 0;
                                                        @endphp
                                                        @foreach ($sparepart as $item)
                                                        @php
                                                            $total_sparepart += ($item->detail_harga_pesan * $item->qty_sparepart);
                                                        @endphp
                                                            <tr>
                                                                <td>{{$loop->index + 1}}</td>
                                                                <td>{{$item->nama_sparepart}}</td>
                                                                <td>Rp.{{number_format($item->detail_harga_pesan)}},- X {{$item->qty_sparepart}}</td>
                                                                <td>Rp.{{number_format($item->detail_harga_pesan * $item->qty_sparepart)}}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                
                            </div>
                        </div>
                        <div class="col-md-4">
                            <form action="{{route('update_pesanan',$data->id)}}" onsubmit="return confirm('Apakah Kamu Yakin ?')" method="POST"  enctype="multipart/form-data">
                                @csrf
                                @method('PUT') 
                                <div class="card card-outline card-success">
                                    <div class="card-header">
                                        <div class="card-title">Detail Pesanan</div>
                                    </div>
                                    <div class="card-body">
                                        <table>
                                            <tr>
                                                <td>Kode Pesanan</td>
                                                <td>: {{$data->kode_pesanan}}</td>
                                            </tr>
                                            <tr>
                                                <td>Tanggal</td>
                                                <td>: {{$data->tgl_pesanan}}</td>
                                            </tr>
                                            
                                            <tr>
                                                <td>Nama</td>
                                                <td>: {{$data->nama_pemesan}}</td>
                                            </tr>
                                            <tr>
                                                <td>Alamat</td>
                                                <td>: {{$data->alamat}}</td>
                                            </tr>  
                                            <tr>
                                                <td>No Telepon</td>
                                                <td>: {{$data->no_telp}}</td>
                                            </tr> 
                                        </table>
                                        <div class="form-group">
                                            <label>Total</label>
                                               <h4> Rp.{{number_format($total_barang + $total_sparepart)}},-</h4>
                                        </div>
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select name="status_pesanan" id="status_pesanan" class="form-control select-bootstrap" @if ($data->status_pesanan == '3' || $data->status_pesanan == '2')
                                                {{'disabled'}}
                                            @endif>
                                                <option value="0" {{$data->status_pesanan == '0' ? 'selected' : ''}} >Baru</option>
                                                <option value="1" {{$data->status_pesanan == '1' ? 'selected' : ''}} >Pending</option>
                                                <option value="2" {{$data->status_pesanan == '2' ? 'selected' : ''}} >Selesai</option>
                                                <option value="3" {{$data->status_pesanan == '3' ? 'selected' : ''}} >Batal</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Total Bayar</label>
                                            <input type="number" @if ($data->status_pesanan == '3' || $data->status_pesanan == '2')
                                                {{'disabled'}}
                                            @endif name="total_bayar" id="total_pesanan" class="form-control" value="{{$data->total_bayar}}">
                                        </div>
                                        @if ($data->status_pesanan == '0' || $data->status_pesanan == '1')
                                            <div class="form-group">
                                                <button type="submit" class="form-control btn btn-success" name="simpan" id="simpan">Simpan</button>
                                            </div>
                                        @endif
                                       
                                        <div class="form-group">
                                            <a href="{{route('pesanan')}}" class="btn btn-danger form-control">Kembali</a>
                                        </div>
                                      
                                    </div>
                                </div>
                            </form>
                        </div>
                      </div>
                      
                
            </div>
        </div>
      </div>
    </section>
    <!-- /.content -->
  </div>
@section('content-script')
  <script>
      $('#foto_barang').change( function(event) {
          $("#view-img").fadeIn("fast").attr('src',URL.createObjectURL(event.target.files[0]));
      });
  </script>
@endsection
@include('admin.component.footer')

