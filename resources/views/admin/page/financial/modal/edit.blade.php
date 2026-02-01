<div class="card card-warning">
    <div class="card-header">
        <h3 class="card-title">Edit Transaksi Modal</h3>
    </div>
    <form action="{{ route('modal.update', $transaksi->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="alert alert-info">
                <strong>Perhatian!</strong> Anda hanya dapat mengubah tanggal dan keterangan. Untuk
                membatalkan, gunakan tombol "Hapus" di halaman daftar modal.
            </div>

            <div class="form-group">
                <label>Jenis Transaksi</label>
                <input type="text" class="form-control"
                    value="{{ Str::title(str_replace('_', ' ', $transaksi->jenis_transaksi)) }}" disabled>
            </div>
            <div class="form-group">
                <label>Jumlah</label>
                <input type="text" class="form-control"
                    value="Rp {{ number_format($transaksi->jumlah, 0, ',', '.') }}" disabled>
            </div>

            <div class="form-group">
                <label for="tanggal">Tanggal Transaksi <span class="text-danger">*</span></label>
                <input type="date" class="form-control @error('tanggal') is-invalid @enderror"
                    id="tanggal" name="tanggal"
                    value="{{ old('tanggal', \Carbon\Carbon::parse($transaksi->tanggal)->format('Y-m-d')) }}"
                    required>
                @error('tanggal')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="keterangan">Keterangan</label>
                <textarea class="form-control @error('keterangan') is-invalid @enderror" id="keterangan" name="keterangan"
                    rows="3">{{ old('keterangan', $transaksi->keterangan) }}</textarea>
                @error('keterangan')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> Update
                Transaksi</button>
            <a href="{{ route('modal.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
