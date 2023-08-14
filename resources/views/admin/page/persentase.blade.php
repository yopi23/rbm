@section('persentase','active')
@section('main','menu-is-opening menu-open')
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-success">
            <div class="card-header">
                <div class="card-title">
                    Persentase Pegawai
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="TABLES_1">
                        <thead>
                            <th>#</th>
                            <th>Nama</th>
                            <th>Jabatan</th>
                            <th>Persentase</th>
                            <th>Aksi</th>
                        </thead>
                        <tbody>
                            @foreach ($data as $item)
                            @php
                                $persetase = 0;
                                foreach ($persentase as $i) {
                                    if($item->kode_user == $i->kode_user){
                                        $persetase = $i->presentase;
                                    }
                                }
                            @endphp
                            <tr>
                                <td>{{$loop->index + 1}}</td>
                                <td>{{$item->name}}</td>
                                <td>
                                    @switch($item->jabatan)
                                    @case(2)
                                    <span class="badge badge-success"> Kasir</span>               
                                        @break
                                    @case(3)
                                    <span class="badge badge-info"> Teknisi</span> 
                                        @break
                                        
                                    @endswitch
                                </td>
                                <td>{{$persetase}} %</td>
                                <td> <a href="#" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#modal_presentase_{{$item->kode_user}}"><i class="fas fa-edit"></i></a> </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@foreach ($data as $item)
@php
                                $persetase = 0;
                                foreach ($persentase as $i) {
                                    if($item->kode_user == $i->kode_user){
                                        $persetase = $i->presentase;
                                    }
                                }
                            @endphp
<div class="modal fade" id="modal_presentase_{{$item->kode_user}}">
    <div class="modal-dialog modal-md">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Persentase</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form action="{{route('edit_persentase')}}" method="POST">
            @csrf
            @method('POST')
            <div class="modal-body">
                <input type="hidden" name="kode_user" id="kode_user" class="form-control" value="{{$item->kode_user}}">
                <div class="form-group">
                    <label>Nama User</label>
                    <input type="text" value="{{$item->name}}" class="form-control" disabled>
                </div>
                <div class="form-group">
                    <label>Persentase ( % )</label>
                    <input type="number" value="{{$persetase}}" name="presentase" id="presentase" class="form-control">
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="submit" class="btn btn-success">Simpan</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
        </form>
      </div>
      <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>    
@endforeach
