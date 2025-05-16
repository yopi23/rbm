<form action="{{ route('admin.tg.store') }}" method="POST">
    @csrf
    <div class="form-group">
        <label>Brand</label>
        <select name="brand_id" class="form-control" required>
            <option value="">Pilih Brand</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label>Tipe HP</label>
        <input type="text" name="tipe_hp" class="form-control" placeholder="Contoh: a3s, a5s, a7s">
    </div>

    <div class="form-group">
        <label>Ukuran Layar</label>
        <select name="screen_size_id" class="form-control" required>
            <option value="">Pilih Ukuran Layar</option>
            @foreach ($screenSizes as $size)
                <option value="{{ $size->id }}">{{ $size->size }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label>Posisi Kamera</label>
        <select name="camera_position_id" class="form-control" required>
            <option value="">Pilih Posisi Kamera</option>
            @foreach ($cameraPositions as $position)
                <option value="{{ $position->id }}">{{ $position->position }}</option>
            @endforeach
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('admin.tg.index') }}" class="btn btn-secondary">Batal</a>
</form>
