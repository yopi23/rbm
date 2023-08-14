

<div class="card card-success card-outline">
    <div class="card-header">
        <ul class="nav nav-pills">
            <li class="nav-item"><a class="nav-link active" href="#stok" data-toggle="tab">Stok</a></li>
            <li class="nav-item"><a class="nav-link" href="#restok" data-toggle="tab">Restok</a></li>
            <li class="nav-item"><a class="nav-link" href="#rusak" data-toggle="tab">Rusak</a></li>
          </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane active" id="stok">
                <div class="row">
                    <div class="col-md-12">
                        <table class="table" id="TABLES_1">
                            <thead>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama Barang</th>
                                <th>Stok</th>
                                <th>Terjual</th>
                            </thead>
                            <tbody>
                                @forelse ($data_barang as $item)
                                    @php
                                        $total_terjual = 0;
                                        foreach($view_barang as $b){
                                            if($b->kode_barang == $item->id){
                                                $total_terjual += $b->qty_barang;
                                            }
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{$loop->index + 1}}</td>
                                        <td>{{$item->kode_barang}}</td>
                                        <td>{{$item->nama_barang}}</td>
                                        <td>{{$item->stok_barang}}</td>
                                        <td>{{$total_terjual}}</td>
                                    </tr>
                                @empty
                                    
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                </div>  
            </div>
            <div class="tab-pane" id="restok">
                <div class="row">
                    <div class="col-md-12">
                        <a href="{{route('create_restok')}}" class="btn btn-success"><i class="fas fa-plus"></i> Tambah</a>
                        <hr>
                        <table class="table" id="TABLES_2">
                            <thead>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Kode</th>
                                <th>Nama Barang</th>
                                <th>Restok</th>
                                <th>Status</th>
                                <th>Catatan</th>
                                <th>Aksi</th>
                            </thead>
                            <tbody>
                                @foreach ($restok_barang as $item)
                                    <tr>
                                        <td>{{$loop->index + 1}}</td>
                                        <td>{{$item->tgl_restok}}</td>
                                        <td>{{$item->kode_barang}}</td>
                                        <td>{{$item->nama_barang}}</td>
                                        <td>{{$item->jumlah_restok}}</td>
                                        <td>@switch($item->status_restok)
                                                @case('Pending')
                                                    <span class="badge badge-warning">Pending</span>      
                                                @break;
                                                @case('Cancel')
                                                    <span class="badge badge-warning">Cancel</span>      
                                                @break;
                                                @case('Success')
                                                    <span class="badge badge-success">Success</span>      
                                                @break;
                                            @endswitch
                                        </td>
                                        
                                        <td>{{$item->catatan_restok}}</td>
                                        <td>
                                            <form action="{{route('delete_restok',$item->id_restok)}}" onsubmit="return confirm('Apakah Anda yakin ?')" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                @if ($item->status_restok != 'Success')
                                                <a href="{{route('edit_restok',$item->id_restok)}}" class="btn btn-warning"><i class="fas fa-edit"></i></a>
                                                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i></button>
                                                @endif
                                                
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                </div>
                    
            </div>
            <div class="tab-pane" id="rusak">
                <div class="row">
                    <div class="col-md-12">
                        <a href="{{route('create_barang_rusak')}}" class="btn btn-success"><i class="fas fa-plus"></i> Tambah</a>
                        <hr>
                        <table class="table" id="TABLES_2">
                            <thead>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama Barang</th>
                                <th>Rusak</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </thead>
                            <tbody>
                                @forelse ($barang_rusak as $item)
                                    <tr>
                                        <td>{{$loop->index + 1}}</td>
                                        <td>{{$item->kode_barang}}</td>
                                        <td>{{$item->nama_barang}}</td>
                                        <td>{{$item->jumlah_rusak}}</td>
                                        <td>{{$item->catatan_rusak}}</td>
                                        <td>
                                            <form action="{{route('delete_barang_rusak',$item->id_rusak)}}" onsubmit="return confirm('Apakah Anda yakin ?')" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <a href="{{route('edit_barang_rusak',$item->id_rusak)}}" class="btn btn-warning"><i class="fas fa-edit"></i></a>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer">

    </div>
</div>