<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-success">
            <div class="card-header">
                <div class="card-title">Data Pesanan</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="TABLES_1">
                        <thead>
                            <th>#</th>
                            <th>Tanggal</th>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Alamat</th>
                            <th>No Telp</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </thead>
                        <tbody>
                            @foreach ($data as $item)
                                <tr>
                                    <td>{{$loop->index + 1}}</td>
                                    <td>{{$item->tgl_pesanan}}</td>
                                    <td>{{$item->kode_pesanan}}</td>
                                    <td>{{$item->nama_pemesan}}</td>
                                    <td>{{$item->alamat}}</td>
                                    <td>{{$item->no_telp}}</td>
                                    <td>@switch($item->status_pesanan)
                                        @case('0')
                                            <span class="badge badge-info">Baru</span>
                                            @break
                                        @case('1')
                                        <span class="badge badge-warning">Pending</span>
                                            @break
                                        @case('2')
                                        <span class="badge badge-success">Selesai</span>
                                            @break    
                                        @case('3')
                                        <span class="badge badge-danger">Batal</span>
                                            @break
                                        @default
                                            
                                    @endswitch</td>
                                    <td>
                                        {{-- <a href="#" class="btn btn-info btn-sm"><i class="fas fa-print"></i></a> --}}
                                        <a href="{{route('edit_pesanan',$item->id)}}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>