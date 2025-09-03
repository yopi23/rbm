<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-5">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Tambah Beban Tetap Baru</h3>
                    </div>
                    <form action="{{ route('beban.store') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <p>Daftarkan semua biaya rutin bulanan di sini (sewa, internet, gaji, dll).</p>
                            <div class="form-group">
                                <label>Nama Beban</label>
                                <input type="text" name="nama_beban" class="form-control" required
                                    placeholder="Contoh: Sewa Ruko">
                            </div>
                            <div class="form-group">
                                <label>Jumlah per Bulan (Rp)</label>
                                <input type="number" name="jumlah_bulanan" class="form-control" required
                                    placeholder="Contoh: 3000000">
                            </div>
                            <div class="form-group">
                                <label>Keterangan</label>
                                <textarea name="keterangan" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Simpan Beban</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Daftar Beban Tetap Bulanan</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Nama Beban</th>
                                    <th>Jumlah/Bulan (Rp)</th>
                                    <th style="width: 100px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($beban as $item)
                                    <tr>
                                        <td>{{ $item->nama_beban }}</td>
                                        <td class="text-right">
                                            {{ number_format($item->jumlah_bulanan, 0, ',', '.') }}</td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-warning" data-toggle="modal"
                                                    data-target="#editModal-{{ $item->id }}"><i
                                                        class="fas fa-edit"></i></button>
                                                <form action="{{ route('beban.destroy', $item->id) }}" method="POST"
                                                    onsubmit="return confirm('Yakin ingin menghapus beban ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger"><i
                                                            class="fas fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">Belum ada beban tetap yang
                                            didaftarkan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@foreach ($beban as $item)
    <div class="modal fade" id="editModal-{{ $item->id }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Beban Tetap</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form action="{{ route('beban.update', $item->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="form-group"><label>Nama Beban</label><input type="text" name="nama_beban"
                                class="form-control" required value="{{ $item->nama_beban }}"></div>
                        <div class="form-group"><label>Jumlah per Bulan (Rp)</label><input type="number"
                                name="jumlah_bulanan" class="form-control" required value="{{ $item->jumlah_bulanan }}">
                        </div>
                        <div class="form-group"><label>Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="2">{{ $item->keterangan }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
