<div class="container-fluid">
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">Form Tambah Paket Baru</h3>
        </div>
        <form action="{{ route('administrator.tokens.plans.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label for="name">Nama Paket</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                        name="name" placeholder="Contoh: Paket Premium 1 Bulan" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="price">Harga (Rupiah)</label>
                    <input type="number" class="form-control @error('price') is-invalid @enderror" id="price"
                        name="price" placeholder="Contoh: 50000" value="{{ old('price') }}" required>
                    @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="duration_in_months">Durasi (dalam Bulan)</label>
                    <input type="number" class="form-control @error('duration_in_months') is-invalid @enderror"
                        id="duration_in_months" name="duration_in_months" placeholder="Contoh: 1"
                        value="{{ old('duration_in_months') }}" required>
                    @error('duration_in_months')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('administrator.tokens.plans.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
