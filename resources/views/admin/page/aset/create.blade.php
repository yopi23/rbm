<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Formulir Aset Baru</h3>
            </div>
            <form action="{{ route('asets.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="jenis_perolehan">Jenis Perolehan <span class="text-danger">*</span></label>
                        <select name="jenis_perolehan" class="form-control" required>
                            <option value="pembelian_baru">Pembelian Baru (Kurangi Kas Perusahaan)</option>
                            <option value="aset_awal">Aset Awal / Hibah (Tanpa Pengaruh Kas)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="nama_aset">Nama Aset <span class="text-danger">*</span></label>
                        <input type="text" name="nama_aset" class="form-control" value="{{ old('nama_aset') }}"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="kategori_aset">Kategori</label>
                        <input type="text" name="kategori_aset" class="form-control"
                            value="{{ old('kategori_aset') }}" placeholder="Contoh: Elektronik, Furnitur, Peralatan">
                    </div>
                    <div class="form-group">
                        <label for="tanggal_perolehan">Tanggal Perolehan <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_perolehan" class="form-control"
                            value="{{ old('tanggal_perolehan', now()->format('Y-m-d')) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="nilai_perolehan">Nilai Perolehan (Harga Beli) <span
                                class="text-danger">*</span></label>
                        <input type="number" name="nilai_perolehan" class="form-control"
                            value="{{ old('nilai_perolehan') }}" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="masa_manfaat_bulan">Masa Manfaat (Bulan)</label>
                                <input type="number" name="masa_manfaat_bulan" class="form-control"
                                    value="{{ old('masa_manfaat_bulan', 48) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nilai_residu">Nilai Residu (Rp)</label>
                                <input type="number" name="nilai_residu" class="form-control"
                                    value="{{ old('nilai_residu', 0) }}">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="keterangan">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3">{{ old('keterangan') }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Simpan Aset</button>
                    <a href="{{ route('asets.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</section>
