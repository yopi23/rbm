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
                    <form action="{{ isset($data) != null ? route('supplier.update',$data->id) : route('supplier.store') }}" method="POST"enctype="multipart/form-data">
                      @csrf
                      @if (isset($data) != null)
                          @method('PUT')
                      @else
                          @method('POST')
                      @endif
                      <input type="hidden" name="kode_owner" id="kode_owner" value="{{$this_user->id_upline}}">
                      <div class="card-body">
                          <div class="form-group">
                            <label>Nama Supplier</label>
                            <input type="text" value="{{isset($data) ? $data->nama_supplier : ''}}" name="nama_supplier" id="nama_supplier" placeholder="Nama Supplier" class="form-control">
                          </div>
                          <div class="form-group">
                            <label>No Telp</label>
                            <input type="number"  value="{{isset($data) ? $data->no_telp_supplier : ''}}" name="no_telp_supplier" id="no_telp_supplier" placeholder="No Telp Supplier" class="form-control">
                          </div>
                          <div class="form-group">
                            <label>Alamat Supplier</label>
                            <textarea name="alamat_supplier" id="alamat_supplier" class="form-control" cols="30" rows="5">{{isset($data) ? $data->alamat_supplier : ''}}</textarea>
                          </div>
                      </div>
                      <div class="card-footer">
                        <button type="submit" class="btn btn-success">Simpan</button>
                        <a href="{{route('supplier.index')}}" class="btn btn-danger">Kembali</a>
                      </div>
                    </form>
                </div>
            </div>
        </div>
      </div>
    </section>
    <!-- /.content -->
  </div>
@include('admin.component.footer')
