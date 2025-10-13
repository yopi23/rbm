@php
    $isEdit = isset($attribute);
@endphp

<div class="row">
    <div class="col-md-{{ $isEdit ? '6' : '12' }}">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">{{ $isEdit ? 'Edit Atribut: ' . $attribute->name : 'Tambah Atribut Baru' }}</h3>
            </div>
            <form action="{{ $isEdit ? route('attributes.update', $attribute->id) : route('attributes.store') }}"
                method="POST">
                @csrf
                @if ($isEdit)
                    @method('PATCH')
                @endif
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Nama Atribut</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                            name="name" placeholder="Contoh: Kualitas, Jenis, Bahan"
                            value="{{ old('name', $attribute->name ?? '') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="kategori_sparepart_id">Kaitkan ke Kategori</label>
                        <select class="form-control @error('kategori_sparepart_id') is-invalid @enderror"
                            id="kategori_sparepart_id" name="kategori_sparepart_id" required>
                            <option value="">--- Pilih Kategori ---</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ old('kategori_sparepart_id', $attribute->kategori_sparepart_id ?? '') == $category->id ? 'selected' : '' }}>
                                    {{ $category->nama_kategori }}
                                </option>
                            @endforeach
                        </select>
                        @error('kategori_sparepart_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('attributes.index') }}" class="btn btn-secondary">Kembali</a>
                    <button type="submit"
                        class="btn btn-primary float-right">{{ $isEdit ? 'Perbarui Atribut' : 'Simpan Atribut' }}</button>
                </div>
            </form>
        </div>
    </div>

    @if ($isEdit)
        <div class="col-md-6">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Manajemen Nilai & Aturan Harga Khusus</h3>
                </div>
                <div class="card-body">
                    {{-- ====================================================== --}}
                    {{-- BAGIAN FORM YANG DITAMBAHKAN ADA DI SINI --}}
                    {{-- ====================================================== --}}
                    <form action="{{ route('attribute-values.store', $attribute->id) }}" method="POST" class="mb-4">
                        @csrf
                        <label for="value">Tambah Nilai Baru</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="value"
                                placeholder="Contoh: Original, Meetoo" required>
                            <div class="input-group-append">
                                <button class="btn btn-success" type="submit">Tambah</button>
                            </div>
                        </div>
                    </form>

                    <hr>

                    <label>Daftar Nilai Saat Ini</label>
                    @if ($attribute->values->isEmpty())
                        <p class="text-muted">Belum ada nilai untuk atribut ini.</p>
                    @else
                        <ul class="list-group">
                            @foreach ($attribute->values()->with('priceSetting')->get() as $value)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        {{ $value->value }}
                                        @if ($value->priceSetting)
                                            <small class="d-block text-success">
                                                <i class="fas fa-star"></i> Punya aturan harga khusus
                                            </small>
                                        @else
                                            <small class="d-block text-muted">
                                                Menggunakan aturan umum kategori
                                            </small>
                                        @endif
                                    </div>
                                    <div class="btn-group">
                                        <a href="{{ route('price-settings.form', ['attribute_value_id' => $value->id]) }}"
                                            class="btn btn-sm btn-outline-primary" title="Atur Harga Khusus">
                                            <i class="fas fa-tags"></i>
                                        </a>
                                        <form action="{{ route('attribute-values.destroy', $value->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus nilai ini?');"
                                            style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger" title="Hapus Nilai">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
