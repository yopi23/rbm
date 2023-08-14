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
                        <div class="card-tools">
                          {!! isset($link_tambah) ? '<a href="'.$link_tambah.'" class="btn btn-success"><i class="fas fa-plus"></i> Tambah</a>' : '' !!}
                        </div>
                    </div>
                    <div class="card-body">
                      {!! $data; !!}
                  </div>
                </div>
                
            </div>
        </div>
      </div>
    </section>
    <!-- /.content -->
  </div>
@include('admin.component.footer')
