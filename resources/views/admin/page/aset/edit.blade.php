<section class="content">
    <div class="container-fluid">
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title">Edit Data Aset</h3>
            </div>
            <form action="{{ route('asets.update', $aset->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        <label for="nama_aset">Nama Aset <span class="text-danger">*</span></label>
                        <input type="text" name="nama_aset" class="form-control"
                            value="{{ old('nama_aset', $aset->nama_aset) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="kategori_aset">Kategori</label>
                        <input type="text" name="kategori_aset" class="form-control"
                            value="{{ old('kategori_aset', $aset->kategori_aset) }}">
                    </div>
                    <div class="form-group">
                        <label for="tanggal_perolehan">Tanggal Perolehan <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_perolehan" class="form-control"
                            value="{{ old('tanggal_perolehan', $aset->tanggal_perolehan) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="nilai_perolehan">Nilai Perolehan (Harga Beli)</label>
                        <input type="number" class="form-control" value="{{ $aset->nilai_perolehan }}" disabled>
                        <small class="text-muted">Nilai perolehan tidak dapat diubah untuk menjaga integritas data
                            keuangan.</small>
                    </div>
                    <div class="form-group">
                        <label for="keterangan">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3">{{ old('keterangan', $aset->keterangan) }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning">Update Aset</button>
                    <a href="{{ route('asets.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</section>
