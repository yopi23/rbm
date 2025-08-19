<div class="container-fluid">
    <div class="card card-warning">
        <div class="card-header">
            <h3 class="card-title">Form Edit Paket</h3>
        </div>
        <form action="{{ route('administrator.tokens.plans.update', $plan->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="form-group">
                    <label for="name">Nama Paket</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                        name="name" value="{{ old('name', $plan->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="price">Harga (Rupiah)</label>
                    <input type="number" class="form-control @error('price') is-invalid @enderror" id="price"
                        name="price" value="{{ old('price', $plan->price) }}" required>
                    @error('price')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="duration_in_months">Durasi (dalam Bulan)</label>
                    <input type="number" class="form-control @error('duration_in_months') is-invalid @enderror"
                        id="duration_in_months" name="duration_in_months"
                        value="{{ old('duration_in_months', $plan->duration_in_months) }}" required>
                    @error('duration_in_months')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="{{ route('administrator.tokens.plans.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
