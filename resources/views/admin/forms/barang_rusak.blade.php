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
                    <form action="{{ isset($data) != null ? route('update_barang_rusak',$data->id) : route('store_barang_rusak') }}" method="POST" enctype="multipart/form-data">
                      @csrf
                      @if (isset($data) != null)
                          @method('PUT')
                      @else
                          @method('POST')
                      @endif
                      <div class="card-body">
                        <div class="form-group">
                            <label>Tanggal Rusak</label>
                            <input type="date" value="{{isset($data) != null ? $data->tgl_rusak_barang : date('Y-m-d')}}" name="tgl_rusak_barang" id="tgl_rusak_barang" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Barang</label>
                            <select name="kode_barang" id="kode_barang" class="form-control select-bootstrap"  {{isset($data) != null ? 'disabled' : ''}}  required>
                                @forelse ($barang as $item)d
                                    <option value="{{$item->id}}" {{isset($data) != null && $data->kode_barang == $item->id ? 'selected' : ''}} >{{$item->kode_barang}} - {{$item->nama_barang}} - (Stok: {{$item->stok_barang}})</option>
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
                        <a href="{{route('stok_produk')}}" class="btn btn-danger">Kembali</a>
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
