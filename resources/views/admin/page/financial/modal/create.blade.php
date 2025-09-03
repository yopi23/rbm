<section class="content">
    <div class="container-fluid">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Formulir Transaksi Modal</h3>
            </div>
            <form action="{{ route('modal.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="tanggal">Tanggal Transaksi <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('tanggal') is-invalid @enderror" id="tanggal"
                            name="tanggal" value="{{ old('tanggal', now()->format('Y-m-d')) }}" required>
                        @error('tanggal')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="jenis_transaksi">Jenis Transaksi <span class="text-danger">*</span></label>
                        <select class="form-control @error('jenis_transaksi') is-invalid @enderror" id="jenis_transaksi"
                            name="jenis_transaksi" required>
                            <option value="">-- Pilih Jenis --</option>
                            <option value="setoran_awal"
                                {{ old('jenis_transaksi') == 'setoran_awal' ? 'selected' : '' }}>Setoran Modal Awal
                            </option>
                            <option value="tambahan_modal"
                                {{ old('jenis_transaksi') == 'tambahan_modal' ? 'selected' : '' }}>Tambahan Modal
                            </option>
                            <option value="penarikan_modal"
                                {{ old('jenis_transaksi') == 'penarikan_modal' ? 'selected' : '' }}>Penarikan Modal
                                (Prive)</option>
                        </select>
                        @error('jenis_transaksi')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="jumlah">Jumlah (Rp) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('jumlah') is-invalid @enderror" id="jumlah"
                            name="jumlah" value="{{ old('jumlah') }}" placeholder="Masukkan nominal angka saja"
                            required min="1">
                        @error('jumlah')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="keterangan">Keterangan</label>
                        <textarea class="form-control @error('keterangan') is-invalid @enderror" id="keterangan" name="keterangan"
                            rows="3" placeholder="Contoh: Setoran dari rekening pribadi, dll">{{ old('keterangan') }}</textarea>
                        @error('keterangan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan
                        Transaksi</button>
                    <a href="{{ route('modal.index') }}" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</section>
