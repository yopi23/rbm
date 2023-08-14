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
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <div class="card-title">@yield('page')</div>
                    </div>
                    <form action="{{ isset($data) != null ? route('UpdateKategoriSparepart',$data->id) : route('StoreKategoriSparepart') }}" method="POST" enctype="multipart/form-data">
                      @csrf
                      @if (isset($data) != null)
                          @method('PUT')
                      @else
                          @method('POST')
                      @endif
                      <div class="card-body">
                        <div class="form-group">
                          @if (isset($data) && $data->foto_kategori != '-')
                              <img src="{{asset('public/uploads/'.$data->foto_kategori)}}" width="25%" height="25%" class="img" id="view-img">
                          @else
                              <img src="{{asset('public/img/no_image.png')}}" width="25%" height="25%" class="img" id="view-img">
                          @endif
                          <br>
                          <label>Foto Kategori</label>
                          <input type="file" accept="image/jpeg, image/png" class="form-control" name="foto_kategori" id="foto_kategori">
                        </div>
                        <div class="form-group">
                          <label>Nama Kategori</label>
                          <input type="text" name="nama_kategori" id="nama_kategori" placeholder="Nama Kategori" class="form-control" value="{{isset($data) != null ? $data->nama_kategori : ''}}">
                        </div>
                      </div>
                      <div class="card-footer">
                        <button type="submit" class="btn btn-success">Simpan</button>
                        <a href="{{route('kategori_sparepart')}}" class="btn btn-danger">Kembali</a>
                      </div>
                    </form>
                    
                </div>
                
            </div>
        </div>
      </div>
    </section>
    <!-- /.content -->
  </div>
@section('content-script')
  <script>
      $('#foto_kategori').change( function(event) {
          $("#view-img").fadeIn("fast").attr('src',URL.createObjectURL(event.target.files[0]));
      });
  </script>
@endsection
@include('admin.component.footer')
