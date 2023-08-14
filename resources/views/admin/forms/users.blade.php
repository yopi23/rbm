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
                    <form action="{{ isset($data) != null ? route('users.update',$data->id_user) : route('users.store') }}" method="POST"enctype="multipart/form-data">
                      @csrf
                      @if (isset($data) != null)
                          @method('PUT')
                      @else
                          @method('POST')
                      @endif
                      <div class="card-body">
                          <div class="form-group">
                            <label>Nama</label>
                            <input type="text" value="{{isset($data) ? $data->name : ''}}" name="name" id="name" placeholder="Nama" class="form-control">
                          </div>
                          <div class="form-group">
                            <label>Email</label>
                            <input type="email"  value="{{isset($data) ? $data->email : ''}}" name="email" id="email" placeholder="Email" class="form-control">
                          </div>
                          <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" id="password" placeholder="Password" class="form-control">
                          </div>
                          <div class="form-group">
                            <label>Role</label>
                            <select name="jabatan" id="jabatan" class="form-control">
                              <option value="2"{{isset($data) && $data->jabatan == '2' ? 'selected' : ''}}> Kasir </option>
                              <option value="3" {{isset($data) && $data->jabatan == '3' ? 'selected' : ''}}> Teknisi </option>
                            </select>
                          </div>
                          <div class="form-group">
                            <label>Status</label>
                            <select name="status_user" id="status_user" class="form-control">
                              <option value="0" {{isset($data) && $data->status_user == '0' ? 'selected' : ''}}>Tidak Aktif</option>
                              <option value="1" {{isset($data) && $data->status_user == '1' ? 'selected' : ''}}>Aktif</option>
                            </select>
                          </div>
                      </div>
                      <div class="card-footer">
                        <button type="submit" class="btn btn-success">Simpan</button>
                        <a href="{{route('users.index')}}" class="btn btn-danger">Kembali</a>
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
