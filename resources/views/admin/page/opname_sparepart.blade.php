<div class="row">
    <div class="col-md-12">
        <div class="card card-success card-outline">
            <div class="card-header">
                <div class="card-title">
                    Data Stok Sparepart ( {{ date('Y-m-d H:i:s') }} )
                </div>
                <div class="card-tools">
                    @if ($this_user->jabatan == '0' || $this_user->jabatan == '1')
                        <form action="{{ route('update_stok_sparepart') }}" method="post">
                            @csrf
                            <button type="submit" class="btn btn-sm my-2 btn-success"><i class="fas fa-sync-alt"></i>
                                Update
                                Opname</button>
                        </form>
                    @endif
                    {{-- <a href="{{ route('cetak_opname') }}" target="_blank" class="btn btn-sm btn-success"><i
                            class="fas fa-print"></i> Cetak Opname</a> --}}
                </div>
            </div>
            <div class="card-body">
                <table class="table table-striped table-bordered table-hover responsive" id="table_data">
                    <thead>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Sparepart</th>
                        {{-- <th>Deskripsi</th> --}}
                        <th>Stok</th>
                        <th>Rusak</th>
                        <th>Stok Asli</th>
                        <th>Aksi</th>
                    </thead>
                    @foreach ($sparepart as $item)
                        @php
                            $sparepart_rusak = 0;
                            foreach ($data_sparepart_rusak as $r) {
                                if ($item->id == $r->kode_barang) {
                                    $sparepart_rusak = $r->jumlah_rusak;
                                }
                            }
                        @endphp
                        <tr>
                            <td>{{ $loop->index + 1 }}</td>
                            <td>{{ $item->kode_sparepart }}</td>
                            <td>{{ $item->nama_sparepart }}</td>
                            {{-- <td>{{ $item->desc_sparepart }}</td> --}}
                            <td>{{ $item->stok_sparepart }}</td>
                            <td>{{ $sparepart_rusak }}</td>
                            <td>{{ $item->stock_asli }}</td>
                            <td>
                                <a href="#" class="btn btn-success" data-toggle="modal"
                                    data-target="#modal_ubah_stok_{{ $item->id }}"><i class="fas fa-edit"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>
@foreach ($sparepart as $item)
    @php
        $sparepart_rusak = 0;
        foreach ($data_sparepart_rusak as $r) {
            if ($item->id == $r->kode_barang) {
                $sparepart_rusak = $r->jumlah_rusak;
            }
        }
    @endphp
    <div class="modal fade" id="modal_ubah_stok_{{ $item->id }}">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Ubah Stok {{ $item->kode_sparepart }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('opname_sparepart_ubah_stok', $item->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Kode Sparepart</label>
                            <input type="text" class="form-control" value="{{ $item->kode_sparepart }}" readonly>
                        </div>
                        <div class="form-group">
                            <label>Nama Sparepart</label>
                            <input type="text" class="form-control" value="{{ $item->nama_sparepart }}" readonly>
                        </div>
                        <div class="form-group">
                            <label>Stok Asli</label>
                            <input type="number" name="stock_asli" class="form-control"
                                value="{{ $item->stock_asli }}">
                        </div>
                        <div class="form-group">
                            <label>Rusak</label>
                            <input type="number" name="jumlah_rusak" class="form-control"
                                value="{{ $sparepart_rusak }}">
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-success">Simpan</button>
                    </div>
                </form>

            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
@endforeach
