<style>
    .create-hp-card {
        background: #ffffff;
        border-radius: 16px;
        border: none;
        box-shadow: 0 10px 30px rgba(92, 75, 153, 0.08);
        overflow: hidden;
        max-width: 960px;
        margin: 0 auto;
    }
    .create-hp-header {
        background: linear-gradient(135deg, #5C4B99 0%, #7D6EC4 100%);
        color: #ffffff;
        padding: 24px 30px;
    }
    .create-hp-header h3 {
        font-weight: 700;
        margin: 0;
        font-size: 22px;
    }
    .create-hp-header p {
        margin: 4px 0 0 0;
        opacity: 0.85;
        font-size: 13px;
    }
    .form-section-title {
        font-size: 14px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #5C4B99;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .form-section-title::after {
        content: "";
        flex: 1;
        height: 1px;
        background: #E2E8F0;
    }
    .info-group-box {
        background: #F8FAFC;
        border: 1px dashed #CBD5E1;
        border-radius: 12px;
        padding: 16px;
        transition: all 0.3s ease;
    }
    .info-group-box.active {
        background: #F0FDF4;
        border: 1px solid #86EFAC;
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
        transition: all 0.2s ease;
    }
    .form-control:focus, .form-select:focus {
        border-color: #7D6EC4;
        box-shadow: 0 0 0 3px rgba(125, 110, 196, 0.15);
    }
    .btn-gradient-submit {
        background: linear-gradient(135deg, #5C4B99 0%, #7D6EC4 100%);
        color: #ffffff;
        border: none;
        border-radius: 10px;
        padding: 12px 28px;
        font-weight: 700;
        font-size: 15px;
        box-shadow: 0 4px 12px rgba(92, 75, 153, 0.25);
        transition: all 0.2s ease;
    }
    .btn-gradient-submit:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(92, 75, 153, 0.35);
        color: #ffffff;
    }
</style>

<div class="container-fluid py-3">
    <div class="card create-hp-card">
        <!-- Header Banner -->
        <div class="create-hp-header d-flex justify-content-between align-items-center">
            <div>
                <h3><i class="bi bi-phone-plus me-2"></i>Tambah Data HP & Kelompok Antigores</h3>
                <p>Pilih Kode TG yang sudah ada atau daftarkan HP baru ke dalam kelompok persamaannya.</p>
            </div>
            <a href="{{ route('admin.tg.index') }}" class="btn btn-outline-light btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
        </div>

        <!-- Form Body -->
        <div class="card-body p-4 p-md-5">
            <form action="{{ route('admin.tg.store') }}" method="POST">
                @csrf

                <!-- SECTION 1: KODE ANTIGORES / GRUP -->
                <div class="form-section-title">
                    <span>1. Kelompok Kode Antigores (TG)</span>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label class="custom-input-label text-indigo fw-bold">Cari Tipe HP Acuan / Kelompok</label>
                        <input type="text" id="code_tg_search" class="form-control form-control-lg mb-2 border-indigo" placeholder="🔍 Ketik tipe HP acuan (misal: A16, A5s, Smart 8)..." oninput="filterCodeTgSelect(this.value)">

                        <label class="custom-input-label">Pilih Kelompok (Kode TG)</label>
                        <select name="code_tg" id="code_tg_select" class="form-select form-select-lg" onchange="onCodeTgSelect(this)">
                            <option value="">-- Pilih Kode TG (misal: Oppo A16 / E47) --</option>
                            @foreach ($existingCodes as $c)
                                <option value="{{ $c->code_tg }}" 
                                        data-brand="{{ $c->brand_id }}" 
                                        data-size="{{ $c->screen_size_id }}" 
                                        data-sizename="{{ $c->screenSize->size ?? '-' }}"
                                        data-cam="{{ $c->camera_position_id }}"
                                        data-camname="{{ $c->cameraPosition->position ?? '-' }}"
                                        data-models="{{ $c->models_str ?? '' }}">
                                    📱 {{ $c->display_label }} — (Layar: {{ $c->screenSize->size ?? '-' }} | Kamera: {{ $c->cameraPosition->position ?? '-' }})
                                </option>
                            @endforeach
                            <option value="NEW">➕ Buat Kode Antigores Baru (Grup Baru)...</option>
                        </select>

                        <div id="new_code_tg_wrapper" class="mt-2" style="display:none;">
                            <input type="text" name="new_code_tg" id="new_code_tg" class="form-control" placeholder="Ketik Kode TG Baru (Contoh: E81, E82)">
                        </div>
                    </div>

                    <!-- Live Preview Badge Card -->
                    <div class="col-md-4">
                        <div class="info-group-box h-100 d-flex flex-column justify-content-center" id="preview_box">
                            <span class="text-muted small fw-bold">STATUS GRUP SELECTED:</span>
                            <div id="preview_content" class="mt-1">
                                <span class="badge bg-secondary">Belum Memilih Kode TG</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: TIPE HP & SPESIFIKASI -->
                <div class="form-section-title">
                    <span>2. Detail Tipe HP & Spesifikasi Layar</span>
                </div>

                <!-- Tipe HP Input -->
                <div class="mb-4">
                    <label class="custom-input-label">Tipe HP Baru <span class="text-danger">*</span></label>
                    <input type="text" name="tipe_hp" class="form-control form-control-lg" placeholder="Contoh: iPhone 16 Pro, Spark 20 Pro, Redmi Note 13" required>
                    <div class="form-text text-muted mt-1">
                        <i class="bi bi-info-circle me-1"></i>Anda dapat memasukkan beberapa tipe HP sekaligus dengan memisahkan tanda koma <code>,</code>
                    </div>
                </div>

                <!-- 2-Column Specs Grid -->
                <div class="row g-3 mb-4">
                    <!-- Brand -->
                    <div class="col-md-4">
                        <label class="custom-input-label">Brand / Merk HP</label>
                        <select name="brand_id" id="brand_id" class="form-select" onchange="toggleNewField('brand_id', 'new_brand_wrapper')">
                            <option value="">Pilih Brand</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                            <option value="NEW">+ Tambah Brand Baru...</option>
                        </select>
                        <div id="new_brand_wrapper" class="mt-2" style="display:none;">
                            <input type="text" name="new_brand" class="form-control" placeholder="Nama Brand (misal: Tecno, Infinix)">
                        </div>
                    </div>

                    <!-- Screen Size -->
                    <div class="col-md-4">
                        <label class="custom-input-label">Ukuran Layar</label>
                        <select name="screen_size_id" id="screen_size_id" class="form-select" onchange="toggleNewField('screen_size_id', 'new_size_wrapper')">
                            <option value="">Pilih Ukuran Layar</option>
                            @foreach ($screenSizes as $size)
                                <option value="{{ $size->id }}">{{ $size->size }}</option>
                            @endforeach
                            <option value="NEW">+ Tambah Ukuran Layar Baru...</option>
                        </select>
                        <div id="new_size_wrapper" class="mt-2" style="display:none;">
                            <input type="text" name="new_screen_size" class="form-control" placeholder="Ukuran Layar (misal: 6.78 inch)">
                        </div>
                    </div>

                    <!-- Camera Position -->
                    <div class="col-md-4">
                        <label class="custom-input-label">Posisi Kamera Depan</label>
                        <select name="camera_position_id" id="camera_position_id" class="form-select" onchange="toggleNewField('camera_position_id', 'new_cam_wrapper')">
                            <option value="">Pilih Posisi Kamera</option>
                            @foreach ($cameraPositions as $position)
                                <option value="{{ $position->id }}">{{ $position->position }} (Group {{ $position->group }})</option>
                            @endforeach
                            <option value="NEW">+ Tambah Posisi Kamera Baru...</option>
                        </select>
                        <div id="new_cam_wrapper" class="mt-2" style="display:none;">
                            <input type="text" name="new_camera_position" class="form-control mb-2" placeholder="Nama Posisi Kamera (misal: Punch Hole Kiri)">
                            <input type="text" name="new_camera_group" class="form-control" placeholder="Group (misal: A, B)" value="A">
                        </div>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="d-flex align-items-center gap-3 pt-3 border-top">
                    <button type="submit" class="btn btn-gradient-submit">
                        <i class="bi bi-check-lg me-1"></i> Simpan Data HP
                    </button>
                    <a href="{{ route('admin.tg.index') }}" class="btn btn-light px-4 rounded-3 border">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function filterCodeTgSelect(query) {
    var select = document.getElementById('code_tg_select');
    var q = query.trim().toLowerCase();
    var firstMatchIndex = -1;

    for (var i = 0; i < select.options.length; i++) {
        var opt = select.options[i];
        if (!opt.value || opt.value === 'NEW') {
            opt.style.display = '';
            continue;
        }
        var text = opt.text.toLowerCase();
        var models = (opt.getAttribute('data-models') || '').toLowerCase();
        var code = opt.value.toLowerCase();

        if (!q || text.includes(q) || models.includes(q) || code.includes(q)) {
            opt.style.display = '';
            if (firstMatchIndex === -1 && q) {
                firstMatchIndex = i;
            }
        } else {
            opt.style.display = 'none';
        }
    }

    if (q && firstMatchIndex !== -1) {
        select.selectedIndex = firstMatchIndex;
        onCodeTgSelect(select);
    } else if (!q) {
        select.selectedIndex = 0;
        onCodeTgSelect(select);
    }
}

function onCodeTgSelect(selectElem) {
    var val = selectElem.value;
    var newCodeWrapper = document.getElementById('new_code_tg_wrapper');
    var previewBox = document.getElementById('preview_box');
    var previewContent = document.getElementById('preview_content');

    if (val === 'NEW') {
        newCodeWrapper.style.display = 'block';
        previewBox.classList.remove('active');
        previewContent.innerHTML = '<span class="badge bg-warning text-dark">Membuat Grup TG Baru</span>';
    } else if (val) {
        newCodeWrapper.style.display = 'none';
        previewBox.classList.add('active');

        var selectedOption = selectElem.options[selectElem.selectedIndex];
        var brandId = selectedOption.getAttribute('data-brand');
        var sizeId = selectedOption.getAttribute('data-size');
        var sizeName = selectedOption.getAttribute('data-sizename');
        var camId = selectedOption.getAttribute('data-cam');
        var camName = selectedOption.getAttribute('data-camname');
        var modelsStr = selectedOption.getAttribute('data-models') || '';

        var modelsHtml = modelsStr ? '<div class="mt-1"><small class="text-primary fw-bold">📱 HP di Grup Ini:</small><br><span class="badge bg-light text-dark border me-1 mb-1">' + modelsStr.split(', ').join('</span><span class="badge bg-light text-dark border me-1 mb-1">') + '</span></div>' : '';

        previewContent.innerHTML = 
            '<div class="d-flex flex-column gap-1">' +
                '<span class="fw-bold text-success"><i class="bi bi-check-circle-fill me-1"></i>Grup ' + val + ' Terpilih</span>' +
                '<span class="small text-muted">Layar: <b>' + sizeName + '</b> | Kamera: <b>' + camName + '</b></span>' +
                modelsHtml +
            '</div>';

        if (brandId) {
            document.getElementById('brand_id').value = brandId;
            toggleNewField('brand_id', 'new_brand_wrapper');
        }
        if (sizeId) {
            document.getElementById('screen_size_id').value = sizeId;
            toggleNewField('screen_size_id', 'new_size_wrapper');
        }
        if (camId) {
            document.getElementById('camera_position_id').value = camId;
            toggleNewField('camera_position_id', 'new_cam_wrapper');
        }
    } else {
        newCodeWrapper.style.display = 'none';
        previewBox.classList.remove('active');
        previewContent.innerHTML = '<span class="badge bg-secondary">Belum Memilih Kode TG</span>';
    }
}

function toggleNewField(selectId, wrapperId) {
    var select = document.getElementById(selectId);
    var wrapper = document.getElementById(wrapperId);
    if (select.value === 'NEW') {
        wrapper.style.display = 'block';
    } else {
        wrapper.style.display = 'none';
    }
}
</script>
