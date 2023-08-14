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
                    <form action="{{ isset($data) != null ? route('update_sparepart_rusak',$data->id) : route('store_sparepart_rusak') }}" method="POST" enctype="multipart/form-data">
                      @csrf
                      @if (isset($data) != null)
                          @method('PUT')
                      @else
                          @method('POST')
                      @endif
                      <input type="hidden" name="kode_owner" id="kode_owner" value="{{$this_user->id_upline}}">
                      <div class="card-body">
                        <div class="form-group">
                            <label>Tanggal Rusak</label>
                            <input type="date" value="{{isset($data) != null ? $data->tgl_rusak_barang : date('Y-m-d')}}" name="tgl_rusak_barang" id="tgl_rusak_barang" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Sparepart</label>
                            <select name="kode_barang" id="kode_barang" class="form-control select-bootstrap"  {{isset($data) != null ? 'disabled' : ''}}  required>
                                @forelse ($sparepart as $item)d
                                    <option value="{{$item->id}}" {{isset($data) != null && $data->kode_barang == $item->id ? 'selected' : ''}} >{{$item->kode_sparepart}} - {{$item->nama_sparepart}} - (Stok: {{$item->stok_sparepart}})</option>
                                @empty
                                    
                                @endforelse
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Jumlah Rusak</label>
                            <input type="number" min="{{isset($data) != null ? '0' : '1'}}" value="{{isset($data) != null ? $data->jumlah_rusak : '1'}}" name="jumlah_rusak" id="jumlah_rusak" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Catatan Rusak</label>
                            <textarea name="catatan_rusak" id="catatan_rusak" class="form-control" cols="30" rows="10">{{isset($data) != null ? $data->catatan_rusak : ''}}</textarea>
                        </div>
                      </div>
                      <div class="card-footer">
                        <button type="submit" class="btn btn-success">Simpan</button>
                        <a href="{{route('stok_sparepart')}}" class="btn btn-danger">Kembali</a>
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
