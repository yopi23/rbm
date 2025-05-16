<form action="{{ route('admin.tg.update', $hp->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="form-group">
        <label for="brand">Brand</label>
        <input type="text" class="form-control" id="brand" name="brand" value="{{ $hp->brand }}" required>
    </div>
    <div class="form-group">
        <label for="type">Type</label>
        <input type="text" class="form-control" id="type" name="type" value="{{ $hp->type }}" required>
    </div>
    <div class="form-group">
        <label for="screen_size">Ukuran Layar</label>
        <input type="text" class="form-control" id="screen_size" name="screen_size" value="{{ $hp->screen_size }}"
            required>
    </div>
    <div class="form-group">
        <label for="camera_position">Posisi Kamera</label>
        <input type="text" class="form-control" id="camera_position" name="camera_position"
            value="{{ $hp->camera_position }}" required>
    </div>
    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    <a href="{{ route('admin.tg.index') }}" class="btn btn-secondary">Batal</a>
</form>
