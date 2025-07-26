<!-- resources/views/admin/page/penalty-rules.blade.php -->

@section('penalty_rules', 'active')
@section('main', 'menu-is-opening menu-open')

<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-warning">
            <div class="card-header">
                <div class="card-title">
                    Pengaturan Aturan Penalty
                    <small class="text-muted" id="owner-info"></small>
                </div>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalAddRule">
                        <i class="fas fa-plus"></i> Tambah Aturan
                    </button>
                    <button type="button" class="btn btn-success btn-sm" onclick="seedDefaultRules()">
                        <i class="fas fa-seedling"></i> Load Default Rules
                    </button>
                    <a href="{{ route('admin.penalty-rules.export') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-download"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Tabs untuk berbagai jenis aturan -->
            <ul class="nav nav-tabs" id="ruleTypeTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="attendance-tab" data-toggle="tab" href="#attendance" role="tab">
                        <i class="fas fa-clock"></i> Keterlambatan Absensi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="outside-tab" data-toggle="tab" href="#outside" role="tab">
                        <i class="fas fa-sign-out-alt"></i> Izin Keluar Kantor
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="absence-tab" data-toggle="tab" href="#absence" role="tab">
                        <i class="fas fa-user-times"></i> Alpha/Tidak Masuk
                    </a>
                </li>
            </ul>

            <div class="tab-content mt-3" id="ruleTypeTabsContent">
                <!-- Tab Attendance Late Rules -->
                <div class="tab-pane fade show active" id="attendance" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-info">Aturan untuk Gaji Tetap</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Rentang Waktu</th>
                                            <th>Penalty</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="fixed-rules-tbody">
                                        <!-- Will be populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h5 class="text-success">Aturan untuk Sistem Persentase</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Rentang Waktu</th>
                                            <th>Penalty</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="percentage-rules-tbody">
                                        <!-- Will be populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Outside Office Rules -->
                <div class="tab-pane fade" id="outside" role="tabpanel">
                    <h5 class="text-warning">Aturan Terlambat Kembali dari Izin Keluar</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="bg-light">
                                <tr>
                                    <th>Rentang Waktu</th>
                                    <th>Penalty (%)</th>
                                    <th>Berlaku Untuk</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="outside-rules-tbody">
                                <!-- Will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab Absence/Alpha Rules -->
                <div class="tab-pane fade" id="absence" role="tabpanel">
                    <h5 class="text-danger">Aturan Alpha/Tidak Masuk Kerja</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-info">Aturan untuk Gaji Tetap</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Penalty</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="absence-fixed-rules-tbody">
                                        <!-- Will be populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6 class="text-success">Aturan untuk Sistem Persentase</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Penalty</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="absence-percentage-rules-tbody">
                                        <!-- Will be populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3">
                        <h6><i class="fas fa-info-circle"></i> Catatan Aturan Alpha:</h6>
                        <ul class="mb-0 small">
                            <li><strong>Gaji Tetap:</strong> Penalty biasanya berupa potong gaji harian (100% dari gaji
                                harian)</li>
                            <li><strong>Sistem Persentase:</strong> Penalty berupa persentase dari komisi bulanan</li>
                            <li>Aturan alpha akan diterapkan otomatis oleh sistem cron job setiap hari</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add/Edit Rule -->
<div class="modal fade" id="modalAddRule">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modal-title">Tambah Aturan Penalty</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="ruleForm">
                <div class="modal-body">
                    <input type="hidden" id="rule_id" name="rule_id">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Jenis Aturan <span class="text-danger">*</span></label>
                                <select name="rule_type" id="rule_type" class="form-control" required>
                                    <option value="attendance_late">Keterlambatan Absensi</option>
                                    <option value="outside_office_late">Terlambat Kembali Izin Keluar</option>
                                    <option value="absence">Alpha/Tidak Masuk Kerja</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Berlaku Untuk <span class="text-danger">*</span></label>
                                <select name="compensation_type" id="compensation_type" class="form-control"
                                    required>
                                    <option value="fixed">Gaji Tetap Saja</option>
                                    <option value="percentage">Sistem Persentase Saja</option>
                                    <option value="both">Keduanya</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row time-range-row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Waktu Minimum (menit) <span class="text-danger"
                                        id="min-minutes-required">*</span></label>
                                <input type="number" name="min_minutes" id="min_minutes" class="form-control"
                                    required min="0">
                                <small class="text-muted" id="min-minutes-help">Contoh: 6 (untuk 6 menit ke
                                    atas)</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Waktu Maksimum (menit)</label>
                                <input type="number" name="max_minutes" id="max_minutes" class="form-control"
                                    min="1">
                                <small class="text-muted" id="max-minutes-help">Kosongkan jika tidak ada batas
                                    maksimal</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Penalty Nominal (Rupiah)</label>
                                <input type="number" name="penalty_amount" id="penalty_amount" class="form-control"
                                    min="0" step="1000">
                                <small class="text-muted">Untuk gaji tetap. Contoh: 25000</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Penalty Persentase (%)</label>
                                <input type="number" name="penalty_percentage" id="penalty_percentage"
                                    class="form-control" min="0" max="100">
                                <small class="text-muted">Untuk sistem persentase. Contoh: 5</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Deskripsi <span class="text-danger">*</span></label>
                        <textarea name="description" id="description" class="form-control" required rows="2"
                            placeholder="Contoh: Keterlambatan sedang (16-30 menit) - Denda Rp 25.000"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Prioritas</label>
                                <input type="number" name="priority" id="priority" class="form-control"
                                    min="1" value="1">
                                <small class="text-muted">Urutan aturan (1 = prioritas tertinggi)</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="is_active" id="is_active" class="form-control">
                                    <option value="1">Aktif</option>
                                    <option value="0">Tidak Aktif</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Petunjuk:</h6>
                        <ul class="mb-0 small">
                            <li>Untuk gaji tetap: isi <strong>Penalty Nominal</strong>, kosongkan Penalty Persentase
                            </li>
                            <li>Untuk sistem persentase: isi <strong>Penalty Persentase</strong>, kosongkan Penalty
                                Nominal</li>
                            <li>Untuk aturan alpha: biasanya 100% gaji harian (gaji tetap) atau 15% komisi (persentase)
                            </li>
                            <li>Pastikan tidak ada overlap rentang waktu untuk jenis aturan yang sama</li>
                            <li>Prioritas menentukan urutan evaluasi aturan</li>
                        </ul>
                    </div>
                </div>

                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Pastikan CSRF token tersedia
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        if (!csrfToken) {
            console.warn('CSRF token not found in meta tag');
        }

        // Setup AJAX defaults
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        });

        loadRules();

        // Form submission
        $('#ruleForm').on('submit', function(e) {
            e.preventDefault();
            saveRule();
        });

        // Auto-generate description
        $('#rule_type, #compensation_type, #min_minutes, #max_minutes, #penalty_amount, #penalty_percentage')
            .on('change input', function() {
                generateDescription();
            });

        // Handle rule type change for UI adjustments
        $('#rule_type').on('change', function() {
            const ruleType = $(this).val();
            if (ruleType === 'absence') {
                // Hide time fields for absence rules
                $('#min_minutes, #max_minutes').prop('required', false);
                $('#min_minutes').val('0');
                $('#max_minutes').val('');
                $('#min-minutes-required').hide();
                $('#min-minutes-help').text('Tidak berlaku untuk aturan alpha');
                $('#max-minutes-help').text('Tidak berlaku untuk aturan alpha');
                $('.time-range-row').addClass('d-none');
            } else {
                // Show time fields for time-based rules
                $('#min_minutes').prop('required', true);
                $('#min-minutes-required').show();
                $('#min-minutes-help').text('Contoh: 6 (untuk 6 menit ke atas)');
                $('#max-minutes-help').text('Kosongkan jika tidak ada batas maksimal');
                $('.time-range-row').removeClass('d-none');
            }
            generateDescription();
        });

        // Reset form when modal closes
        $('#modalAddRule').on('hidden.bs.modal', function() {
            resetForm();
        });
    });

    function loadRules() {
        $.get('{{ route('admin.penalty-rules.list') }}', function(data) {
            if (data.success) {
                populateRulesTable(data.rules);
                // Show owner info
                if (data.owner_code) {
                    $('#owner-info').text('(Owner: ' + data.owner_code + ')');
                }
            } else {
                alert('Error: ' + data.message);
            }
        }).fail(function(xhr) {
            if (xhr.status === 403) {
                alert('Akses ditolak: ' + xhr.responseJSON?.message);
            } else {
                alert('Terjadi kesalahan saat memuat data');
            }
        });
    }

    function populateRulesTable(rules) {
        // Clear existing data
        $('#fixed-rules-tbody, #percentage-rules-tbody, #outside-rules-tbody, #absence-fixed-rules-tbody, #absence-percentage-rules-tbody')
            .empty();

        rules.forEach(function(rule) {
            if (rule.rule_type === 'attendance_late') {
                const row = createRuleRow(rule);
                if (rule.compensation_type === 'fixed') {
                    $('#fixed-rules-tbody').append(row);
                } else if (rule.compensation_type === 'percentage') {
                    $('#percentage-rules-tbody').append(row);
                }
            } else if (rule.rule_type === 'outside_office_late') {
                const row = createRuleRow(rule);
                $('#outside-rules-tbody').append(row);
            } else if (rule.rule_type === 'absence') {
                const row = createAbsenceRuleRow(rule);
                if (rule.compensation_type === 'fixed') {
                    $('#absence-fixed-rules-tbody').append(row);
                } else if (rule.compensation_type === 'percentage') {
                    $('#absence-percentage-rules-tbody').append(row);
                }
            }
        });
    }

    function createRuleRow(rule) {
        const statusBadge = rule.is_active ?
            '<span class="badge badge-success">Aktif</span>' :
            '<span class="badge badge-secondary">Tidak Aktif</span>';

        const penalty = rule.penalty_amount > 0 ?
            'Rp ' + new Intl.NumberFormat('id-ID').format(rule.penalty_amount) :
            rule.penalty_percentage + '%';

        const range = rule.max_minutes ?
            rule.min_minutes + '-' + rule.max_minutes + ' mnt' :
            '>' + rule.min_minutes + ' mnt';

        const compensationType = rule.rule_type === 'outside_office_late' ?
            '<span class="badge badge-info">Semua</span>' :
            '';

        return `
        <tr>
            <td>${range}</td>
            <td><strong>${penalty}</strong></td>
            ${rule.rule_type === 'outside_office_late' ? '<td>' + compensationType + '</td>' : ''}
            <td>${statusBadge}</td>
            <td>
                <button class="btn btn-xs btn-warning" onclick="editRule(${rule.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-xs btn-danger" onclick="deleteRule(${rule.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
    }

    function createAbsenceRuleRow(rule) {
        const statusBadge = rule.is_active ?
            '<span class="badge badge-success">Aktif</span>' :
            '<span class="badge badge-secondary">Tidak Aktif</span>';

        const penalty = rule.penalty_amount > 0 ?
            'Rp ' + new Intl.NumberFormat('id-ID').format(rule.penalty_amount) :
            rule.penalty_percentage + '%';

        return `
        <tr>
            <td><strong>${penalty}</strong><br><small class="text-muted">${rule.description}</small></td>
            <td>${statusBadge}</td>
            <td>
                <button class="btn btn-xs btn-warning" onclick="editRule(${rule.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-xs btn-danger" onclick="deleteRule(${rule.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
    }

    function resetForm() {
        $('#ruleForm')[0].reset();
        $('#rule_id').val('');
        $('#modal-title').text('Tambah Aturan Penalty');

        // Show time range fields by default
        $('.time-range-row').removeClass('d-none');
        $('#min_minutes').prop('required', true);
        $('#min-minutes-required').show();
        $('#min-minutes-help').text('Contoh: 6 (untuk 6 menit ke atas)');
        $('#max-minutes-help').text('Kosongkan jika tidak ada batas maksimal');

        // Reset validation state
        $('#ruleForm').removeClass('was-validated');
        $('#ruleForm .is-invalid').removeClass('is-invalid');
        $('#ruleForm .invalid-feedback').remove();
    }

    function editRule(id) {
        $.get('{{ route('admin.penalty-rules.show', ':id') }}'.replace(':id', id), function(data) {
            if (data.success) {
                const rule = data.rule;

                $('#modal-title').text('Edit Aturan Penalty');
                $('#rule_id').val(rule.id);
                $('#rule_type').val(rule.rule_type);
                $('#compensation_type').val(rule.compensation_type);
                $('#min_minutes').val(rule.min_minutes);
                $('#max_minutes').val(rule.max_minutes || '');
                $('#penalty_amount').val(rule.penalty_amount > 0 ? rule.penalty_amount : '');
                $('#penalty_percentage').val(rule.penalty_percentage > 0 ? rule.penalty_percentage : '');
                $('#description').val(rule.description);
                $('#priority').val(rule.priority);
                $('#is_active').val(rule.is_active ? '1' : '0');

                // Trigger rule type change to adjust UI
                $('#rule_type').trigger('change');

                $('#modalAddRule').modal('show');
            } else {
                alert('Error: ' + data.message);
            }
        }).fail(function(xhr) {
            alert('Gagal memuat data aturan');
        });
    }

    function deleteRule(id) {
        if (confirm('Apakah Anda yakin ingin menghapus aturan ini?')) {
            $.ajax({
                url: '{{ route('admin.penalty-rules.destroy', ':id') }}'.replace(':id', id),
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(data) {
                    if (data.success) {
                        alert('Aturan berhasil dihapus');
                        loadRules();
                    } else {
                        alert('Error: ' + data.message);
                    }
                },
                error: function(xhr) {
                    alert('Gagal menghapus aturan: ' + (xhr.responseJSON?.message || 'Unknown error'));
                }
            });
        }
    }

    function saveRule() {
        // Validasi form
        if (!$('#ruleForm')[0].checkValidity()) {
            $('#ruleForm')[0].reportValidity();
            return;
        }
        if (!validateFormData()) {
            return;
        }

        const isEdit = $('#rule_id').val() !== '';
        const ruleId = $('#rule_id').val();

        // Gunakan FormData untuk handling yang lebih baik
        const form = $('#ruleForm')[0];
        const formData = new FormData(form);

        // Set null values untuk field kosong
        if (!$('#max_minutes').val()) {
            formData.delete('max_minutes');
        }
        if (!$('#penalty_amount').val()) {
            formData.delete('penalty_amount');
        }
        if (!$('#penalty_percentage').val()) {
            formData.delete('penalty_percentage');
        }

        // Tambahkan CSRF token
        formData.set('_token', $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}');

        // Untuk PUT request, tambahkan method spoofing
        if (isEdit) {
            formData.set('_method', 'PUT');
        }

        // Tentukan URL
        const url = isEdit ?
            '{{ route('admin.penalty-rules.update', ':id') }}'.replace(':id', ruleId) :
            '{{ route('admin.penalty-rules.store') }}';

        // Disable tombol submit
        const submitBtn = $('#ruleForm button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(response) {
                if (response.success) {
                    // Tampilkan notifikasi sukses
                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.message || 'Aturan berhasil disimpan');
                    } else {
                        alert(response.message || 'Aturan berhasil disimpan');
                    }

                    // Tutup modal dan reset form
                    $('#modalAddRule').modal('hide');
                    resetForm();
                    loadRules();
                } else {
                    // Tampilkan pesan error dari server
                    const message = response.message || 'Terjadi kesalahan saat menyimpan';
                    if (typeof toastr !== 'undefined') {
                        toastr.error(message);
                    } else {
                        alert('Error: ' + message);
                    }
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Terjadi kesalahan saat menyimpan';

                try {
                    const response = JSON.parse(xhr.responseText);

                    if (xhr.status === 422) {
                        // Validation errors
                        if (response.errors) {
                            errorMessage = 'Validation errors:\n';
                            Object.keys(response.errors).forEach(key => {
                                errorMessage += '- ' + response.errors[key][0] + '\n';
                            });
                        } else if (response.message) {
                            errorMessage = response.message;
                        }
                    } else if (xhr.status === 403) {
                        errorMessage = 'Akses ditolak: ' + (response.message || 'Unauthorized');
                    } else if (xhr.status === 404) {
                        errorMessage = 'Endpoint tidak ditemukan';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Internal server error';
                        if (response.message) {
                            errorMessage += ': ' + response.message;
                        }
                    } else if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    // Response bukan JSON
                    if (xhr.status === 419) {
                        errorMessage = 'CSRF Token expired. Silakan refresh halaman.';
                    } else if (xhr.status === 405) {
                        errorMessage = 'Method not allowed. Periksa routing.';
                    } else {
                        errorMessage = 'Error: ' + xhr.status + ' - ' + xhr.statusText;
                    }
                }

                if (typeof toastr !== 'undefined') {
                    toastr.error(errorMessage);
                } else {
                    alert(errorMessage);
                }
            },
            complete: function() {
                // Re-enable tombol submit
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    }

    function seedDefaultRules() {
        if (confirm('Ini akan menambahkan aturan default (termasuk aturan alpha). Lanjutkan?')) {
            const btn = $('button[onclick="seedDefaultRules()"]');
            const originalText = btn.html();
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');

            $.post('{{ route('admin.penalty-rules.seed') }}', {
                _token: '{{ csrf_token() }}'
            }, function(data) {
                if (data.success) {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(data.message);
                    } else {
                        alert(data.message);
                    }
                    loadRules();
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Error: ' + data.message);
                    } else {
                        alert('Error: ' + data.message);
                    }
                }
            }).fail(function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Gagal memuat default rules';
                if (typeof toastr !== 'undefined') {
                    toastr.error(errorMsg);
                } else {
                    alert('Error: ' + errorMsg);
                }
            }).always(function() {
                btn.prop('disabled', false).html(originalText);
            });
        }
    }

    function generateDescription() {
        const ruleType = $('#rule_type').val();
        const compensationType = $('#compensation_type').val();
        const minMinutes = $('#min_minutes').val();
        const maxMinutes = $('#max_minutes').val();
        const penaltyAmount = $('#penalty_amount').val();
        const penaltyPercentage = $('#penalty_percentage').val();

        let range = '';
        let penaltyText = '';
        let typeText = '';

        // Handle absence rules differently (no time ranges)
        if (ruleType === 'absence') {
            typeText = 'Alpha/Tidak masuk kerja';

            if (penaltyAmount && penaltyAmount > 0) {
                penaltyText = `Denda Rp ${new Intl.NumberFormat('id-ID').format(penaltyAmount)}`;
            } else if (penaltyPercentage && penaltyPercentage > 0) {
                if (compensationType === 'fixed' && penaltyPercentage == 100) {
                    penaltyText = `Potong gaji harian`;
                } else {
                    penaltyText = `Penalty ${penaltyPercentage}%`;
                }
            }
        } else {
            // Handle time-based rules
            if (!minMinutes && minMinutes !== '0') return;

            if (maxMinutes) {
                range = `${minMinutes}-${maxMinutes} menit`;
            } else {
                range = `>${minMinutes} menit`;
            }

            if (penaltyAmount && penaltyAmount > 0) {
                penaltyText = `Denda Rp ${new Intl.NumberFormat('id-ID').format(penaltyAmount)}`;
            } else if (penaltyPercentage && penaltyPercentage > 0) {
                penaltyText = `Penalty ${penaltyPercentage}%`;
            }

            if (ruleType === 'attendance_late') {
                typeText = 'Keterlambatan absensi';
            } else if (ruleType === 'outside_office_late') {
                typeText = 'Terlambat kembali dari izin keluar';
            }
        }

        if (typeText && penaltyText) {
            const description = ruleType === 'absence' ?
                `${typeText} - ${penaltyText}` :
                `${typeText} (${range}) - ${penaltyText}`;
            $('#description').val(description);
        }
    }

    function validateFormData() {
        const ruleType = $('#rule_type').val();
        const penaltyAmount = $('#penalty_amount').val();
        const penaltyPercentage = $('#penalty_percentage').val();

        // Pastikan minimal satu penalty diisi
        if (!penaltyAmount && !penaltyPercentage) {
            alert('Harus mengisi minimal satu jenis penalty (nominal atau persentase)');
            return false;
        }

        // Validasi rentang waktu (kecuali untuk absence rules)
        if (ruleType !== 'absence') {
            const minMinutes = parseInt($('#min_minutes').val());
            const maxMinutes = $('#max_minutes').val() ? parseInt($('#max_minutes').val()) : null;

            if (!minMinutes && minMinutes !== 0) {
                alert('Waktu minimum harus diisi untuk aturan berbasis waktu');
                return false;
            }

            if (maxMinutes && maxMinutes <= minMinutes) {
                alert('Waktu maksimum harus lebih besar dari waktu minimum');
                return false;
            }
        }

        return true;
    }

    function debugFormData() {
        console.log('=== DEBUG FORM DATA ===');
        console.log('rule_type:', $('#rule_type').val());
        console.log('compensation_type:', $('#compensation_type').val());
        console.log('min_minutes:', $('#min_minutes').val());
        console.log('max_minutes:', $('#max_minutes').val());
        console.log('penalty_amount:', $('#penalty_amount').val());
        console.log('penalty_percentage:', $('#penalty_percentage').val());
        console.log('description:', $('#description').val());
        console.log('priority:', $('#priority').val());
        console.log('is_active:', $('#is_active').val());
        console.log('CSRF Token:', $('meta[name="csrf-token"]').attr('content'));
        console.log('=======================');
    }
</script>

<style>
    /* Custom styles for penalty rules page */
    .card-outline.card-warning {
        border-top: 3px solid #ffc107;
    }

    .nav-tabs .nav-link {
        border: 1px solid transparent;
        border-top-left-radius: 0.25rem;
        border-top-right-radius: 0.25rem;
    }

    .nav-tabs .nav-link:hover {
        border-color: #e9ecef #e9ecef #dee2e6;
    }

    .nav-tabs .nav-link.active {
        color: #495057;
        background-color: #fff;
        border-color: #dee2e6 #dee2e6 #fff;
    }

    .table-responsive {
        border-radius: 0.25rem;
    }

    .table thead th {
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
    }

    .badge {
        font-size: 0.75em;
    }

    .btn-xs {
        padding: 0.125rem 0.25rem;
        font-size: 0.75rem;
        border-radius: 0.15rem;
    }

    .modal-lg {
        max-width: 900px;
    }

    .alert-info {
        background-color: #d1ecf1;
        border-color: #bee5eb;
        color: #0c5460;
    }

    .text-info {
        color: #17a2b8 !important;
    }

    .text-success {
        color: #28a745 !important;
    }

    .text-warning {
        color: #ffc107 !important;
    }

    .text-danger {
        color: #dc3545 !important;
    }

    /* Loading state styles */
    .btn:disabled {
        opacity: 0.65;
    }

    .fa-spinner {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Form validation styles */
    .is-invalid {
        border-color: #dc3545;
    }

    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #dc3545;
    }

    /* Tab content spacing */
    .tab-content {
        padding-top: 1rem;
    }

    /* Table hover effects */
    .table tbody tr:hover {
        background-color: rgba(0, 0, 0, .05);
    }

    /* Modal form spacing */
    .modal-body .form-group {
        margin-bottom: 1rem;
    }

    .modal-body .row {
        margin-left: -5px;
        margin-right: -5px;
    }

    .modal-body .row>[class*="col-"] {
        padding-left: 5px;
        padding-right: 5px;
    }

    /* Alert styling */
    .alert ul {
        margin-bottom: 0;
        padding-left: 1.5rem;
    }

    .alert li {
        margin-bottom: 0.25rem;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .card-tools .btn {
            margin-bottom: 0.25rem;
        }

        .table-responsive {
            font-size: 0.875rem;
        }

        .btn-xs {
            padding: 0.1rem 0.2rem;
            font-size: 0.7rem;
        }

        .modal-lg {
            max-width: 95%;
            margin: 1rem auto;
        }
    }

    /* Hide elements with d-none class */
    .d-none {
        display: none !important;
    }

    /* Success/Error message styling */
    .toastr-success {
        background-color: #28a745;
    }

    .toastr-error {
        background-color: #dc3545;
    }
</style>
