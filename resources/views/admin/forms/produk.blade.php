@section('page',$page)
{{-- @section('produk','active')
@section('main','menu-is-opening menu-open') --}}

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
                  <form action="{{ isset($data) != null ? route('update_produk',$data->id) : route('store_produk') }}" method="POST"  enctype="multipart/form-data">
                    @csrf
                    @if (isset($data) != null)
                        @method('PUT')
                    @else
                        @method('POST')
                    @endif  
                    <div class="row">
                        <div class="col-md-4">
                          <div class="card card-outline card-success">
                            <div class="card-header">
                                <div class="card-title">Image</div>
                            </div>
                            <div class="card-body text-center">
                              @if (isset($data) && $data->foto_barang != '-')
                                  <img src="{{asset('public/uploads/'.$data->foto_barang)}}" width="100%" height="100%" class="img" id="view-img">
                              @else
                                  <img src="{{asset('public/img/no_image.png')}}" width="100%" height="100%" class="img" id="view-img">
                              @endif
                              <hr>
                              <div class="form-group">
                                <input type="file" accept="image/png, image/jpeg" class="form-control form-input" name="foto_barang" id="foto_barang">
                              </div>
                            </div>
                        </div>
                        </div>
                        <div class="col-md-8">
                          <div class="card card-outline card-success">
                            <div class="card-header">
                                <div class="card-title">
                                  Data Barang
                                </div>
                            </div>
                            <div class="card-body">
                              <div class="form-group">
                                <label>Nama Barang</label>
                                <input type="text" value="{{isset($data) != null ? $data->nama_barang : ''}}" placeholder="Nama Barang" name="nama_barang" id="nama_barang" class="form-control">
                              </div>
                              <div class="form-group">
                                <label>Merk Barang</label>
                                <input type="text"value="{{isset($data) != null ? $data->merk_barang : ''}}" name="merk_barang" placeholder="Merk Barang" id="merk_barang" class="form-control">
                              </div>
                              <div class="form-group">
                                <label>Kategori Barang</label>
                                <select name="kode_kategori" id="kode_kategori" class="form-control">
                                    @forelse ($kategori as $item)
                                        <option value="{{$item->id}}" {{isset($data) != null && $data->kategori_barang == $item->id ? 'selected' : ''}}>{{$item->nama_kategori}}</option>
                                    @empty

                                    @endforelse
                                </select>
                              </div>
                              <div class="form-group">
                                <label>Deskripsi Barang</label>
                                <textarea name="desc_barang" placeholder="Deskripsi Barang" id="desc_barang" cols="30" rows="10" class="form-control">{{isset($data) != null ? $data->desc_barang : ''}}</textarea>
                              </div>
                              <div class="form-group">
                                <label>Status Barang</label>
                                <select name="status_barang" id="status_barang" class="form-control">
                                    <option value="0" {{isset($data) != null && $data->status_barang == '0' ? 'selected' : ''}}>Aktif</option>
                                    <option value="1" {{isset($data) != null && $data->status_barang == '1' ? 'selected' : ''}}>Tidak Aktif</option>
                                </select>
                              </div>
                              <div class="form-group">
                                <label>Kondisi Barang</label>
                                <select name="kondisi_barang" id="kondisi_barang" class="form-control">
                                    <option value="Bekas" {{isset($data) != null && $data->kondisi_barang == 'Bekas' ? 'selected' : ''}}>Bekas</option>
                                    <option value="Baru" {{isset($data) != null && $data->kondisi_barang == 'Baru' ? 'selected' : ''}}>Baru</option>
                                </select>
                              </div>
                              <div class="form-group">
                                <label>Stok Barang</label>
                                <input type="text" name="stok_barang" value="{{isset($data) != null ? $data->stok_barang : '0'}}" placeholder="Stok Barang" id="stok_barang" class="form-control" {{isset($data) != null ? 'readonly' : ''}}>
                              </div>
                              <div class="row">
                                <div class="col-md-6">
                                  <div class="form-group">
                                    <label>Harga Beli</label>
                                    <input type="text" name="harga_beli_barang" value="{{isset($data) != null ? $data->harga_beli_barang : '0'}}" placeholder="Harga Beli" id="harga_beli" class="form-control">
                                  </div>
                                </div>
                                <div class="col-md-6">
                                  <div class="form-group">
                                    <label>Harga Jual</label>
                                    <input type="text" name="harga_jual_barang" value="{{isset($data) != null ? $data->harga_jual_barang : '0'}}" placeholder="Harga Jual" id="harga_jual" class="form-control">
                                  </div>
                                </div>
                              </div>
                              
                              
                            </div>
                            <div class="card-footer">
                              <button type="submit" class="btn btn-success">Simpan</button>
                              <a href="{{route('produk')}}" class="btn btn-danger">Kembali</a>
                            </div>
                        </div>
                        </div>
                      </div>
                      
                  </form>
               
                
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

