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
                    <form action="{{ isset($data) != null ? route('cabang.update',$data->id) : route('cabang.store') }}" method="POST">
                      @csrf
                      @if (isset($data) != null)
                          @method('PUT')
                      @else
                          @method('POST')
                      @endif
                      <div class="card-body">
                          <div class="form-group">
                            <label>Nama Cabang</label>
                            <input type="text" value="{{isset($data) ? $data->nama_cabang : ''}}" name="nama_cabang" id="nama_cabang" placeholder="Nama Cabang" class="form-control" required>
                          </div>
                          <div class="form-group">
                            <label>Alamat Cabang</label>
                            <textarea name="alamat_cabang" id="alamat_cabang" placeholder="Alamat Cabang" class="form-control" rows="3">{{isset($data) ? $data->alamat_cabang : ''}}</textarea>
                          </div>
                          <div class="form-group">
                            <label>Status</label>
                            <select name="is_active" id="is_active" class="form-control">
                              <option value="1" {{isset($data) && $data->is_active == '1' ? 'selected' : ''}}>Aktif</option>
                              <option value="0" {{isset($data) && $data->is_active == '0' ? 'selected' : ''}}>Tidak Aktif</option>
                            </select>
                          </div>
                      </div>
                      <div class="card-footer">
                        <button type="submit" class="btn btn-success">Simpan</button>
                        <a href="{{route('cabang.index')}}" class="btn btn-danger">Kembali</a>
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
