<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">
            Atur Harga Khusus untuk:
            <span class="badge badge-light">{{ $attributeValue->attribute->name }}: {{ $attributeValue->value }}</span>
        </h3>
    </div>
    <form action="{{ route('price-settings.store') }}" method="POST">
        @csrf
        {{-- Input tersembunyi ini PENTING untuk memberitahu controller aturan mana yang sedang diupdate --}}
        <input type="hidden" name="kategori_sparepart_id" value="{{ $attributeValue->attribute->kategori->id }}">
        <input type="hidden" name="attribute_value_id" value="{{ $attributeValue->id }}">

        <div class="card-body">
            <p class="text-muted">
                Aturan harga yang Anda set di sini akan **menggantikan (override)** aturan umum dari kategori
                "{{ $attributeValue->attribute->kategori->nama_kategori }}". Kosongkan inputan untuk kembali menggunakan
                aturan umum.
            </p>

            <div class="form-group">
                <label>Margin Internal (%)</label>
                <input type="number" step="0.01" class="form-control" name="internal_margin"
                    value="{{ old('internal_margin', $setting->internal_margin ?? '') }}" placeholder="Contoh: 15.5">
            </div>

            <div class="form-group">
                <label>Jasa Default (Rp)</label>
                <input type="number" class="form-control" name="default_service_fee"
                    value="{{ old('default_service_fee', $setting->default_service_fee ?? '') }}"
                    placeholder="Contoh: 50000">
            </div>

            <div class="form-group">
                <label>Garansi (%)</label>
                <input type="number" step="0.01" class="form-control" name="warranty_percentage"
                    value="{{ old('warranty_percentage', $setting->warranty_percentage ?? '') }}"
                    placeholder="Contoh: 10">
            </div>

            <hr>
            <h5>Margin Penjualan Lainnya</h5>

            <div class="form-group">
                <label>Margin Ecer (%)</label>
                <input type="number" step="0.01" class="form-control" name="retail_margin"
                    value="{{ old('retail_margin', $setting->retail_margin ?? '') }}" placeholder="Contoh: 30">
            </div>

            <div class="form-group">
                <label>Margin Grosir (%)</label>
                <input type="number" step="0.01" class="form-control" name="wholesale_margin"
                    value="{{ old('wholesale_margin', $setting->wholesale_margin ?? '') }}" placeholder="Contoh: 20">
            </div>

        </div>
        <div class="card-footer">
            <a href="{{ route('attributes.edit', $attributeValue->attribute_id) }}"
                class="btn btn-secondary">Kembali</a>
            <button type="submit" class="btn btn-primary float-right">Simpan Aturan Khusus</button>
        </div>
    </form>
</div>
