<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $page }}</h3>
    </div>
    <div class="card-body">

        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Gunakan fitur ini untuk melihat dan menyesuaikan harga jual produk secara
            cepat tanpa perlu membuka form edit satu per satu.
        </div>

        <!-- Filter Section -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Filter Kategori</label>
                    <select class="form-control select" id="filter-kategori">
                        <option value="">-- Semua Kategori --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nama_kategori }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-5">
                <div class="form-group">
                    <label>Cari Produk</label>
                    <div class="input-group">
                        <input type="text" id="filter-search" class="form-control"
                            placeholder="Ketik nama / sku produk...">
                        <div class="input-group-append">
                            <button class="btn btn-primary" id="btn-search"><i class="fas fa-search"></i> Cari</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Markup Tool -->
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-magic"></i> Alat Markup Massal</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i
                            class="fas fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body bg-light">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tipe Markup</label>
                            <select id="markup-type" class="form-control">
                                <option value="nominal">Nominal (+ Rp)</option>
                                <option value="persen">Persentase (+ %)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Markup Harga Umum (Retail)</label>
                            <input type="number" id="markup-retail" class="form-control" placeholder="Contoh: 5000">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Markup Harga Ecer (Wholesale)</label>
                            <input type="number" id="markup-wholesale" class="form-control" placeholder="Contoh: 2000">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button id="btn-apply-markup" class="btn btn-success btn-block">
                                <i class="fas fa-check-double"></i> Terapkan ke Tabel
                            </button>
                        </div>
                    </div>
                </div>
                <small class="text-muted">* Terapkan ke tabel hanya akan mengubah angka di layar Anda. Jangan lupa klik
                    <b>Simpan Perubahan</b> di bawah tabel untuk menyimpan ke database.</small>
            </div>
        </div>

        <!-- Table Data -->
        <div class="table-responsive mt-3">
            <table class="table table-bordered table-hover table-striped text-nowrap" id="table-markup">
                <thead class="bg-dark text-white">
                    <tr>
                        <th width="50" class="text-center">
                            <input type="checkbox" id="check-all" checked>
                        </th>
                        <th>Kategori</th>
                        <th>Kode/SKU</th>
                        <th>Nama Produk</th>
                        <th>Harga Beli</th>
                        <th width="150">Harga Umum (Retail)</th>
                        <th width="150">Harga Ecer (Grosir)</th>
                        <th width="150">Harga Internal</th>
                    </tr>
                </thead>
                <tbody id="tbody-markup">
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            Pilih kategori atau klik cari untuk menampilkan data.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
    <div class="card-footer bg-white text-right">
        <button id="btn-save-all" class="btn btn-primary btn-lg" disabled>
            <i class="fas fa-save"></i> Simpan Perubahan Harga
        </button>
    </div>
</div>

<script>
    $(document).ready(function () {
        let currentData = [];

        // Initialize Select2 if available
        if ($.fn.select2) {
            $('.select2').select2();
        }

        // Format number to Rupiah format
        const formatRp = (num) => {
            if (num === null || num === undefined) return '0';
            return num.toString().replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        };

        const parseRp = (str) => {
            if (!str) return 0;
            return parseFloat(str.toString().replace(/\D/g, '')) || 0;
        };

        const updateMargins = (row) => {
            let index = row.data('index');
            let item = currentData[index];
            let basePrice = item.harga_beli || 0;

            let retail = parseRp(row.find('.input-retail').val());
            let wholesale = parseRp(row.find('.input-wholesale').val());
            let internal = parseRp(row.find('.input-internal').val());

            const calcMargin = (price) => {
                let margin = price - basePrice;
                let percent = basePrice > 0 ? (margin / basePrice * 100).toFixed(1) : (price > 0 ? 100 : 0);
                return { margin, percent };
            };

            let rm = calcMargin(retail);
            row.find('.margin-retail').html(`Margin: Rp ${formatRp(rm.margin)} (${rm.percent}%)`);

            let wm = calcMargin(wholesale);
            row.find('.margin-wholesale').html(`Margin: Rp ${formatRp(wm.margin)} (${wm.percent}%)`);

            let im = calcMargin(internal);
            row.find('.margin-internal').html(`Margin: Rp ${formatRp(im.margin)} (${im.percent}%)`);
        };

        // Format input on typing
        $(document).on('keyup', '.input-currency', function (e) {
            if (e.which >= 37 && e.which <= 40) return; // arrows
            $(this).val(function (index, value) {
                return value.replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            });
            updateMargins($(this).closest('tr'));
        });

        const loadData = () => {
            let kategori_id = $('#filter-kategori').val();
            let search = $('#filter-search').val();

            $('#tbody-markup').html('<tr><td colspan="8" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Memuat data...</td></tr>');
            $('#btn-save-all').prop('disabled', true);

            $.ajax({
                url: "{{ route('markup.data') }}",
                type: "GET",
                data: {
                    kategori_id: kategori_id,
                    search: search
                },
                success: function (res) {
                    if (res.success) {
                        currentData = res.data;
                        renderTable();
                    } else {
                        $('#tbody-markup').html('<tr><td colspan="8" class="text-center py-4 text-danger">Gagal memuat data.</td></tr>');
                    }
                },
                error: function () {
                    $('#tbody-markup').html('<tr><td colspan="8" class="text-center py-4 text-danger">Terjadi kesalahan koneksi.</td></tr>');
                }
            });
        };

        const renderTable = () => {
            let html = '';
            if (currentData.length === 0) {
                html = '<tr><td colspan="8" class="text-center py-4">Data tidak ditemukan.</td></tr>';
                $('#btn-save-all').prop('disabled', true);
            } else {
                currentData.forEach((item, index) => {
                    html += `
                    <tr data-index="${index}" class="row-item">
                        <td class="text-center align-middle">
                            <input type="checkbox" class="check-item" value="${index}" checked>
                        </td>
                        <td class="align-middle">${item.kategori}</td>
                        <td class="align-middle">${item.sku}</td>
                        <td class="align-middle" style="white-space: normal; min-width: 200px;">
                            ${item.nama_produk}
                            ${item.tipe === 'variant' ? '<span class="badge badge-info ml-1">Varian</span>' : ''}
                        </td>
                        <td class="align-middle text-right">Rp ${formatRp(item.harga_beli)}</td>
                        <td>
                            <input type="text" class="form-control input-retail input-currency" value="${formatRp(item.harga_jual)}">
                            <small class="text-success mt-1 d-block margin-retail font-weight-bold"></small>
                        </td>
                        <td>
                            <input type="text" class="form-control input-wholesale input-currency" value="${formatRp(item.harga_ecer)}">
                            <small class="text-success mt-1 d-block margin-wholesale font-weight-bold"></small>
                        </td>
                        <td>
                            <input type="text" class="form-control input-internal input-currency" value="${formatRp(item.harga_internal)}">
                            <small class="text-success mt-1 d-block margin-internal font-weight-bold"></small>
                        </td>
                    </tr>
                `;
                });
                $('#btn-save-all').prop('disabled', false);
            }
            $('#tbody-markup').html(html);
            $('#check-all').prop('checked', true);

            // Initial calculate margin
            $('.row-item').each(function () {
                updateMargins($(this));
            });
        };

        // Load data on search button click
        $('#btn-search').click(function () {
            loadData();
        });

        // Optional: Load data on category change
        $('#filter-kategori').change(function () {
            loadData();
        });

        // Enter to search
        $('#filter-search').keypress(function (e) {
            if (e.which == 13) {
                loadData();
            }
        });

        // Load initial data
        loadData();

        // Check all functionality
        $('#check-all').change(function () {
            $('.check-item').prop('checked', $(this).prop('checked'));
        });

        // Apply Bulk Markup
        $('#btn-apply-markup').click(function () {
            let type = $('#markup-type').val();
            let retailMarkup = parseFloat($('#markup-retail').val()) || 0;
            let wholesaleMarkup = parseFloat($('#markup-wholesale').val()) || 0;

            if (retailMarkup === 0 && wholesaleMarkup === 0) {
                alert('Silakan masukkan nilai markup terlebih dahulu.');
                return;
            }

            let appliedCount = 0;

            $('.row-item').each(function () {
                let checkbox = $(this).find('.check-item');
                if (checkbox.prop('checked')) {
                    let index = checkbox.val();
                    let item = currentData[index];

                    let inputRetail = $(this).find('.input-retail');
                    let inputWholesale = $(this).find('.input-wholesale');

                    // Get current values from input
                    let currentRetail = parseRp(inputRetail.val());
                    let currentWholesale = parseRp(inputWholesale.val());
                    let basePrice = item.harga_beli;

                    let newRetail = currentRetail;
                    let newWholesale = currentWholesale;

                    if (type === 'nominal') {
                        if (retailMarkup !== 0) newRetail = basePrice + retailMarkup;
                        if (wholesaleMarkup !== 0) newWholesale = basePrice + wholesaleMarkup;
                    } else if (type === 'persen') {
                        if (retailMarkup !== 0) newRetail = basePrice + (basePrice * retailMarkup / 100);
                        if (wholesaleMarkup !== 0) newWholesale = basePrice + (basePrice * wholesaleMarkup / 100);
                    }

                    // Update inputs
                    inputRetail.val(formatRp(Math.ceil(newRetail)));
                    inputWholesale.val(formatRp(Math.ceil(newWholesale)));

                    // Update margin text
                    updateMargins($(this));

                    // Add highlight effect
                    $(this).addClass('table-warning');
                    setTimeout(() => { $(this).removeClass('table-warning'); }, 1000);

                    appliedCount++;
                }
            });

            alert(`Markup berhasil diterapkan ke ${appliedCount} produk terpilih di tabel.`);
        });

        // Save All Data
        $('#btn-save-all').click(function () {
            let itemsToSave = [];

            $('.row-item').each(function () {
                let checkbox = $(this).find('.check-item');
                if (checkbox.prop('checked')) {
                    let index = checkbox.val();
                    let item = currentData[index];

                    let valRetail = parseRp($(this).find('.input-retail').val());
                    let valWholesale = parseRp($(this).find('.input-wholesale').val());
                    let valInternal = parseRp($(this).find('.input-internal').val());

                    itemsToSave.push({
                        tipe: item.tipe,
                        db_id: item.db_id,
                        parent_id: item.parent_id,
                        harga_jual: valRetail,
                        harga_ecer: valWholesale,
                        harga_internal: valInternal
                    });
                }
            });

            if (itemsToSave.length === 0) {
                alert('Tidak ada data yang dipilih untuk disimpan.');
                return;
            }

            if (!confirm('Anda yakin ingin menyimpan perubahan harga untuk ' + itemsToSave.length + ' produk?')) return;

            let btn = $(this);
            let originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

            $.ajax({
                url: "{{ route('markup.update') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    items: itemsToSave
                },
                success: function (res) {
                    if (res.success) {
                        alert(res.message);
                        loadData(); // Reload table
                    } else {
                        alert(res.message);
                        btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function (err) {
                    alert('Terjadi kesalahan koneksi.');
                    console.error(err);
                    btn.prop('disabled', false).html(originalText);
                }
            });
        });
    });
</script>