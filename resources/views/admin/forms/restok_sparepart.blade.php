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
                    <form action="{{ isset($data) != null ? route('update_sparepart_restok',$data->id) : route('store_sparepart_restok') }}" method="POST" enctype="multipart/form-data">
                      @csrf
                      @if (isset($data) != null)
                          @method('PUT')
                      @else
                          @method('POST')
                      @endif
                      <input type="hidden" name="kode_owner" id="kode_owner" value="{{$this_user->id_upline}}">
                      <div class="card-body">
                        <div class="form-group">
                            <label>Tanggal Restok</label>
                            <input type="date" value="{{isset($data) != null ? $data->tgl_restok : date('Y-m-d')}}" name="tgl_restok" id="tgl_restok" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Sparepart</label>
                            <select name="kode_barang" id="kode_barang" class="form-control select-bootstrap" {{isset($data) != null ? 'disabled' : ''}}   required>
                                @forelse ($sparepart as $item)
                                    <option value="{{$item->id}}" {{isset($data) != null && $data->kode_barang == $item->id ? 'selected' : ''}} data-beli="{{$item->harga_beli}}" data-jual="{{$item->harga_jual}}" data-pasang="{{$item->harga_pasang}}">{{$item->kode_sparepart}} - {{$item->nama_sparepart}} - (Stok: {{$item->stok_sparepart}})</option>
                                @empty
                                     
                                @endforelse
                            </select>
                        </div>
                        <div id="harga-sparepart">
                          @if ($this_user->jabatan == '1')
                          <div class="row">
                            <div class="col-md-4">
                              <div class="form-group">
                                <label>Harga Beli</label>
                                <input type="number" value="0" name="harga_beli" id="harga_beli" class="form-control">
                              </div>
                            </div>
                            <div class="col-md-4">
                              <div class="form-group">
                                <label>Harga Jual</label>
                                <input type="number" value="0" name="harga_jual" id="harga_jual" class="form-control">
                              </div>
                            </div>
                            <div class="col-md-4">
                              <div class="form-group">
                                <label>Harga Pasang</label>
                                <input type="number" value="0" name="harga_pasang" id="harga_pasang" class="form-control">
                              </div>
                            </div>
                          </div>
                            @else
                            <div class="row">
                              <div class="col-md-6">
                                <div class="form-group">
                                  <label>Harga Jual</label>
                                  <input type="hidden" value="0" name="harga_beli" id="harga_beli" class="form-control">
                                  <input type="number" value="0" name="harga_jual" id="harga_jual" class="form-control">
                                </div>
                              </div>
                              <div class="col-md-6">
                                <div class="form-group">
                                  <label>Harga Pasang</label>
                                  <input type="number" value="0" name="harga_pasang" id="harga_pasang" class="form-control">
                                </div>
                              </div>
                            </div>
                            @endif
                        </div>
                        <div class="form-group">
                            <label>Supplier</label>
                            <select name="kode_supplier" id="kode_supplier" class="form-control select-bootstrap" {{isset($data) != null ? 'disabled' : ''}}   required>
                                @forelse ($supplier as $item)
                                    <option value="{{$item->id}}" {{isset($data) != null && $data->kode_supplier == $item->id ? 'selected' : ''}} >{{$item->nama_supplier}}</option>
                                @empty
                                    
                                @endforelse
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Jumlah Restok</label>
                            <input type="number" min="1" value="{{isset($data) != null ? $data->jumlah_restok : '1'}}" name="jumlah_restok" id="jumlah_restok" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Catatan Restok</label>
                            <textarea name="catatan_restok" id="catatan_restok" class="form-control" cols="30" rows="10">{{isset($data) != null ? $data->catatan_restok : ''}}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Status Restok</label>
                            <select name="status_restok" id="status_restok" class="form-control select-bootstrap">
                                <option value="Pending" {{isset($data) != null && $data->status_restok == 'Pending' ? 'selected' : ''}}>Pending</option>
                                <option value="Success" {{isset($data) != null && $data->status_restok == 'Success' ? 'selected' : ''}}>Success</option>
                                <option value="Cancel"{{isset($data) != null && $data->status_restok == 'Cancel' ? 'selected' : ''}}>Cancel</option>
                            </select>
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
  </div>
  @section('content-script')
  <script>
    $(function(){
      var value = $('#kode_barang').bind(':selected').val();
      if(value != null){
         var beli = $(this).find(':selected').data('beli');
         var jual = $(this).find(':selected').data('jual');
         var pasang = $(this).find(':selected').data('pasang');
         $('#harga_beli').val(beli);
         $('#harga_jual').val(jual);
         $('#harga_pasang').val(pasang);
          $('#harga-sparepart').css("display",'block')
         }else{
            $('#harga-sparepart').css("display",'none')
         }
      $('#kode_barang').on('change',function(){
         var value = $(this).bind(':selected').val();
         var beli = $(this).find(':selected').data('beli');
         var jual = $(this).find(':selected').data('jual');
         var pasang = $(this).find(':selected').data('pasang');
         $('#harga_beli').val(beli);
         $('#harga_jual').val(jual);
         $('#harga_pasang').val(pasang);
      });
    })
    
  </script>
@endsection
@include('admin.component.footer')
