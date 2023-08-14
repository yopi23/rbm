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
                    <form action="{{ isset($data) != null ? route('update_pengeluaran_opex',$data->id) : route('store_pengeluaran_opex') }}" method="POST" enctype="multipart/form-data">
                      @csrf
                      @if (isset($data) != null)
                          @method('PUT')
                      @else
                          @method('POST')
                      @endif
                      <div class="card-body">
                          <div class="form-group">
                            <label>Tanggal</label>
                            <input type="date" name="tgl_pengeluaran" value="{{isset($data) != null ? $data->tgl_pengeluaran : date('Y-m-d')}}" id="tgl_pengeluaran" class="form-control" required>
                          </div>
                          <div class="form-group">
                            <label>Nama</label>
                            <input type="text" name="nama_pengeluaran" value="{{isset($data) != null ? $data->nama_pengeluaran : ''}}" id="nama_pengeluaran" class="form-control" required>
                          </div>
                          <div class="form-group">
                            <label>Kategori</label>
                            <select name="kategori" id="kategori" class="form-control" required>
                                <option value="">-- Pilih Pengeluaran --</option>
                                <option value="Penggajian" {{isset($data) != null && $data->kategori == 'Penggajian' ? 'selected' : ''}}>Penggajian</option>
                                <option value="Sewa"{{isset($data) != null && $data->kategori == 'Sewa' ? 'selected' : ''}}>Sewa</option>
                                <option value="Tagihan"{{isset($data) != null && $data->kategori == 'Tagihan' ? 'selected' : ''}}>Tagihan (Wifi, Listrik ,dll)</option>
                                <option value="Lainnya"{{isset($data) != null && $data->kategori == 'Lainnya' ? 'selected' : ''}}>Lainnya</option>
                            </select>
                          </div>
                          <div class="form-group pegawai">
                              <label>Pegawai</label>
                              <select name="kode_pegawai" id="kode_pegawai" class="form-control">
                                  @foreach ($user as $item)
                                      <option value="{{$item->id}}" {{isset($data) != null && $data->kode_pegawai == $item->id ? 'selected' : ''}}>{{$item->name}}</option>
                                  @endforeach
                              </select>
                          </div>
                          <div class="form-group">
                            <label>Jumlah</label>
                            <input type="text" value="{{isset($data) != null ? $data->jml_pengeluaran : '0'}}" name="jml_pengeluaran" id="jml_pengeluaran" class="form-control">
                          </div>
                          <div class="form-group">
                            <label>Catatan</label>
                            <textarea class="form-control" name="desc_pengeluaran" id="desc_pengeluaran" cols="30" rows="10">{{isset($data) != null ? $data->desc_pengeluaran : ''}}</textarea>
                          </div>
                      </div>
                      <div class="card-footer">
                        <button type="submit" class="btn btn-success">Simpan</button>
                        <a href="{{route('pengeluaran_operasional')}}" class="btn btn-danger">Kembali</a>
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
    $(function(){
      var value = $('#kategori').bind(':selected').val();
      if(value == 'Penggajian'){
          $('.pegawai').css("display",'block')
         }else{
            $('.pegawai').css("display",'none')
         }
      $('#kategori').on('change',function(){
         var value = $(this).bind(':selected').val();
         if(value == 'Penggajian'){
          $('.pegawai').css("display",'block')
         }else{
            $('.pegawai').css("display",'none')
         }
      });
    })
    
  </script>
@endsection
@include('admin.component.footer')
