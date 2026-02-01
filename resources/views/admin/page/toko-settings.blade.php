@section('page', $page)
@include('admin.component.header')
@include('admin.component.navbar')
@include('admin.component.sidebar')

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-store"></i> Pengaturan Toko</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Pengaturan Toko</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <form action="{{ route('toko-settings.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row">
                    <!-- Left Column - Basic Info -->
                    <div class="col-md-8">
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-info-circle"></i> Informasi Toko</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="nama_toko">Nama Toko <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nama_toko" name="nama_toko"
                                        value="{{ old('nama_toko', $settings->nama_toko ?? '') }}"
                                        placeholder="Contoh: Yoyo Cell">
                                </div>

                                <div class="form-group">
                                    <label for="alamat_toko">Alamat Toko</label>
                                    <textarea class="form-control" id="alamat_toko" name="alamat_toko" rows="3"
                                        placeholder="Alamat lengkap toko Anda">{{ old('alamat_toko', $settings->alamat_toko ?? '') }}</textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nomor_cs">Nomor WhatsApp CS</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fab fa-whatsapp"></i></span>
                                                </div>
                                                <input type="text" class="form-control" id="nomor_cs" name="nomor_cs"
                                                    value="{{ old('nomor_cs', $settings->nomor_cs ?? '') }}"
                                                    placeholder="6281234567890">
                                            </div>
                                            <small class="text-muted">Format: 6281234567890 (tanpa + atau spasi)</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nomor_info_bot">Nomor Info Bot</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-robot"></i></span>
                                                </div>
                                                <input type="text" class="form-control" id="nomor_info_bot" name="nomor_info_bot"
                                                    value="{{ old('nomor_info_bot', $settings->nomor_info_bot ?? '') }}"
                                                    placeholder="6281234567890">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nota_footer_line1">Nota Footer Baris 1</label>
                                            <input type="text" class="form-control" id="nota_footer_line1" name="nota_footer_line1"
                                                value="{{ old('nota_footer_line1', $settings->nota_footer_line1 ?? '') }}"
                                                placeholder="Contoh: Barang yang tidak diambil dalam 3 bulan...">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="nota_footer_line2">Nota Footer Baris 2</label>
                                            <input type="text" class="form-control" id="nota_footer_line2" name="nota_footer_line2"
                                                value="{{ old('nota_footer_line2', $settings->nota_footer_line2 ?? '') }}"
                                                placeholder="Contoh: Terima kasih atas kunjungan Anda!">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Public Page Settings -->
                        <div class="card card-success card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-globe"></i> Halaman Publik Cek Status</h3>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    Halaman publik memungkinkan pelanggan mengecek status service dan garansi secara mandiri.
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="public_page_enabled"
                                            name="public_page_enabled" value="1"
                                            {{ old('public_page_enabled', $settings->public_page_enabled ?? true) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="public_page_enabled">
                                            <strong>Aktifkan Halaman Publik</strong>
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="slug">Slug URL <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ url('/cek/') }}/</span>
                                        </div>
                                        <input type="text" class="form-control" id="slug" name="slug"
                                            value="{{ old('slug', $settings->slug ?? '') }}"
                                            placeholder="nama-toko-anda" pattern="[a-z0-9\-]+">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary" id="btn-generate-slug">
                                                <i class="fas fa-magic"></i> Generate
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted">Hanya huruf kecil, angka, dan tanda hubung (-). Contoh: yoyo-cell</small>
                                </div>

                                @if($publicPageUrl)
                                    <div class="form-group">
                                        <label>Link Halaman Publik</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="public-url" value="{{ $publicPageUrl }}" readonly>
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-primary" onclick="copyToClipboard()">
                                                    <i class="fas fa-copy"></i> Salin
                                                </button>
                                                <a href="{{ $publicPageUrl }}" target="_blank" class="btn btn-primary">
                                                    <i class="fas fa-external-link-alt"></i> Buka
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Logo & Theme -->
                    <div class="col-md-4">
                        <div class="card card-warning card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-image"></i> Logo Toko</h3>
                            </div>
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    @if(isset($settings) && $settings->logo_url)
                                        <img src="{{ Storage::url($settings->logo_url) }}" alt="Logo Toko"
                                            class="img-fluid img-thumbnail" id="logo-preview"
                                            style="max-height: 150px; max-width: 150px; object-fit: contain;">
                                    @else
                                        <div class="bg-light border rounded d-flex align-items-center justify-content-center"
                                            id="logo-placeholder" style="height: 150px; width: 150px; margin: 0 auto;">
                                            <i class="fas fa-store fa-3x text-muted"></i>
                                        </div>
                                        <img src="" alt="Logo Preview" class="img-fluid img-thumbnail d-none"
                                            id="logo-preview" style="max-height: 150px; max-width: 150px; object-fit: contain;">
                                    @endif
                                </div>
                                <div class="form-group">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="logo" name="logo"
                                            accept="image/png,image/jpeg,image/jpg">
                                        <label class="custom-file-label" for="logo">Pilih file logo...</label>
                                    </div>
                                    <small class="form-text text-muted mt-2">
                                        Format: JPG/PNG, Max: 1MB. Disarankan rasio 1:1 atau landscape.
                                    </small>
                                </div>
                                <div class="form-group text-left">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="print_logo_on_receipt" name="print_logo_on_receipt" {{ isset($settings) && $settings->print_logo_on_receipt ? 'checked' : (isset($settings) ? '' : 'checked') }}>
                                        <label class="custom-control-label" for="print_logo_on_receipt">Cetak Logo di Nota</label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Jika dimatikan, logo tidak akan muncul pada hasil cetak printer thermal.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="card card-info card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-palette"></i> Warna Branding</h3>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small">Warna akan digunakan di halaman publik cek status.</p>

                                <div class="form-group">
                                    <label for="primary_color">Warna Utama</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="primary_color_picker"
                                            value="{{ old('primary_color', $settings->primary_color ?? '#10B981') }}"
                                            style="width: 50px; padding: 2px;">
                                        <input type="text" class="form-control" id="primary_color" name="primary_color"
                                            value="{{ old('primary_color', $settings->primary_color ?? '#10B981') }}"
                                            pattern="^#[0-9A-Fa-f]{6}$" maxlength="7">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="secondary_color">Warna Sekunder</label>
                                    <div class="input-group">
                                        <input type="color" class="form-control form-control-color" id="secondary_color_picker"
                                            value="{{ old('secondary_color', $settings->secondary_color ?? '#059669') }}"
                                            style="width: 50px; padding: 2px;">
                                        <input type="text" class="form-control" id="secondary_color" name="secondary_color"
                                            value="{{ old('secondary_color', $settings->secondary_color ?? '#059669') }}"
                                            pattern="^#[0-9A-Fa-f]{6}$" maxlength="7">
                                    </div>
                                </div>

                                <!-- Color Preview -->
                                <div class="card mt-3">
                                    <div class="card-header p-2" id="color-preview-header"
                                        style="background-color: {{ $settings->primary_color ?? '#10B981' }};">
                                        <span class="text-white font-weight-bold">Preview Header</span>
                                    </div>
                                    <div class="card-body p-2">
                                        <button type="button" class="btn btn-sm" id="color-preview-btn"
                                            style="background-color: {{ $settings->primary_color ?? '#10B981' }}; color: white;">
                                            Tombol Contoh
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <div class="card">
                            <div class="card-body">
                                <button type="submit" class="btn btn-success btn-block btn-lg">
                                    <i class="fas fa-save"></i> Simpan Pengaturan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>

@include('admin.component.footer')

<script src="https://cdn.jsdelivr.net/npm/bs-custom-file-input/dist/bs-custom-file-input.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize Bootstrap Custom File Input
        bsCustomFileInput.init();

        // Logo preview logic
        $('#logo').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Update Label (Manual fallback if bs-custom-file-input fails)
                $(this).next('.custom-file-label').html(file.name);

                // Update Preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#logo-preview')
                        .attr('src', e.target.result)
                        .removeClass('d-none');
                    $('#logo-placeholder').addClass('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                // Reset if cancelled
                $(this).next('.custom-file-label').html('Pilih file...');
                // Optional: Reset preview to original if needed, or keep current
            }
        });

        // Color picker sync
        $('#primary_color_picker').on('input', function() {
            const color = $(this).val();
            $('#primary_color').val(color);
            updateColorPreview();
        });

        $('#primary_color').on('input', function() {
            const color = $(this).val();
            if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
                $('#primary_color_picker').val(color);
                updateColorPreview();
            }
        });

        $('#secondary_color_picker').on('input', function() {
            const color = $(this).val();
            $('#secondary_color').val(color);
        });

        $('#secondary_color').on('input', function() {
            const color = $(this).val();
            if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
                $('#secondary_color_picker').val(color);
            }
        });

        function updateColorPreview() {
            const color = $('#primary_color').val();
            $('#color-preview-header').css('background-color', color);
            $('#color-preview-btn').css('background-color', color);
        }

        // Generate slug
        $('#btn-generate-slug').on('click', function() {
            const nama = $('#nama_toko').val();
            if (!nama) {
                alert('Silakan isi nama toko terlebih dahulu');
                return;
            }

            $.ajax({
                url: '{{ route("toko-settings.generate-slug") }}',
                method: 'POST',
                data: {
                    nama: nama,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#slug').val(response.slug);
                },
                error: function() {
                    // Fallback: generate locally
                    const slug = nama.toLowerCase()
                        .replace(/[^a-z0-9\s-]/g, '')
                        .replace(/\s+/g, '-')
                        .replace(/-+/g, '-')
                        .trim();
                    $('#slug').val(slug);
                }
            });
        });

        // Slug validation
        $('#slug').on('input', function() {
            let value = $(this).val().toLowerCase();
            value = value.replace(/[^a-z0-9-]/g, '');
            $(this).val(value);
        });
    });

    function copyToClipboard() {
        const copyText = document.getElementById("public-url");
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        document.execCommand("copy");
        
        // Show tooltip or toast
        // Using AdminLTE toastr if available, else alert
        if(typeof toastr !== 'undefined') {
            toastr.success('Link berhasil disalin!');
        } else {
            alert('Link berhasil disalin: ' + copyText.value);
        }
    }
</script>
