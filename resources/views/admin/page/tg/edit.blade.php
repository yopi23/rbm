<style>
    .create-hp-card {
        background: #ffffff;
        border-radius: 16px;
        border: none;
        box-shadow: 0 10px 30px rgba(92, 75, 153, 0.08);
        overflow: hidden;
        max-width: 800px;
        margin: 0 auto;
    }
    .create-hp-header {
        background: linear-gradient(135deg, #5C4B99 0%, #7D6EC4 100%);
        color: #ffffff;
        padding: 24px 30px;
    }
    .custom-input-label {
        font-weight: 600;
        color: #1E293B;
        font-size: 13px;
        margin-bottom: 6px;
    }
    .form-control, .form-select {
        border-radius: 10px;
        border: 1px solid #CBD5E1;
        padding: 10px 14px;
        font-size: 14px;
    }
    .btn-gradient-submit {
        background: linear-gradient(135deg, #5C4B99 0%, #7D6EC4 100%);
        color: #ffffff;
        border: none;
        border-radius: 10px;
        padding: 12px 28px;
        font-weight: 700;
        font-size: 15px;
    }
</style>

<div class="container-fluid py-4">
    <div class="card create-hp-card">
        <div class="create-hp-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold m-0 fs-4"><i class="bi bi-pencil-square me-2"></i>Edit Data HP & Kode TG</h3>
                <p class="m-0 small opacity-75">Perbarui spesifikasi atau kelompok cetakan antigores untuk model ini.</p>
            </div>
            <a href="{{ route('admin.tg.index') }}" class="btn btn-outline-light btn-sm rounded-pill px-3">
                Batal
            </a>
        </div>

        <div class="card-body p-4 p-md-5">
            <form action="{{ route('admin.tg.update', $hp->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="mb-3">
                    <label for="code_tg" class="custom-input-label">Kode Antigores (Kode Cetakan TG)</label>
                    <input type="text" class="form-control form-control-lg fw-bold text-primary" id="code_tg" name="code_tg" value="{{ $hp->code_tg }}" placeholder="Contoh: E52, E47, E01">
                </div>

                <div class="mb-3">
                    <label for="type" class="custom-input-label">Tipe HP</label>
                    <input type="text" class="form-control form-control-lg" id="type" name="type" value="{{ $hp->type }}" required>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label for="brand_id" class="custom-input-label">Brand / Merk</label>
                        <select name="brand_id" class="form-select" required>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" {{ $hp->brand_id == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="screen_size_id" class="custom-input-label">Ukuran Layar</label>
                        <select name="screen_size_id" class="form-select" required>
                            @foreach ($screenSizes as $size)
                                <option value="{{ $size->id }}" {{ $hp->screen_size_id == $size->id ? 'selected' : '' }}>{{ $size->size }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="camera_position_id" class="custom-input-label">Posisi Kamera Depan</label>
                        <select name="camera_position_id" class="form-select" required>
                            @foreach ($cameraPositions as $position)
                                <option value="{{ $position->id }}" {{ $hp->camera_position_id == $position->id ? 'selected' : '' }}>{{ $position->position }} (Group {{ $position->group }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 pt-3 border-top">
                    <button type="submit" class="btn btn-gradient-submit">
                        <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                    </button>
                    <a href="{{ route('admin.tg.index') }}" class="btn btn-light px-4 rounded-3 border">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
