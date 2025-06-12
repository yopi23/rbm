@section('violations', 'active')
@section('main', 'menu-is-opening menu-open')

<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-danger">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-exclamation-triangle"></i> Pelanggaran Karyawan
                </div>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                        data-target="#modalAddViolation">
                        <i class="fas fa-plus"></i> Tambah Pelanggaran
                    </button>
                    <button type="button" class="btn btn-info btn-sm" onclick="refreshTable()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                    <a type="button" class="btn btn-info btn-sm" href="{{ route('admin.penalty-rules.index') }}">
                        <i class="fas fa-fog"></i> Setting
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="violationsTable">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="10%">Tanggal</th>
                                <th width="15%">Nama</th>
                                <th width="10%">Tipe</th>
                                <th width="20%">Keterangan</th>
                                <th width="12%">Denda</th>
                                <th width="8%">Status</th>
                                <th width="10%">Dicatat Oleh</th>
                                <th width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($violations as $violation)
                                <tr id="violation-row-{{ $violation->id }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ \Carbon\Carbon::parse($violation->violation_date)->format('d M Y') }}</td>
                                    <td>
                                        <strong>{{ $violation->user->name }}</strong>
                                        @if ($violation->user->userDetail)
                                            <br><small class="text-muted">
                                                @switch($violation->user->userDetail->jabatan)
                                                    @case(2)
                                                        Kasir
                                                    @break

                                                    @case(3)
                                                        Teknisi
                                                    @break

                                                    @default
                                                        Unknown
                                                    @break
                                                @endswitch
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($violation->type)
                                            @case('telat')
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-clock"></i> Terlambat
                                                </span>
                                            @break

                                            @case('alpha')
                                                <span class="badge badge-danger">
                                                    <i class="fas fa-user-times"></i> Alpha
                                                </span>
                                            @break

                                            @case('kelalaian')
                                                <span class="badge badge-info">
                                                    <i class="fas fa-exclamation-circle"></i> Kelalaian
                                                </span>
                                            @break

                                            @case('komplain')
                                                <span class="badge badge-secondary">
                                                    <i class="fas fa-comment-slash"></i> Komplain
                                                </span>
                                            @break

                                            @default
                                                <span class="badge badge-dark">
                                                    <i class="fas fa-question"></i> Lainnya
                                                </span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <div title="{{ $violation->description }}">
                                            {{ Str::limit($violation->description, 50) }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-center">
                                            @if ($violation->penalty_amount)
                                                <span class="text-danger font-weight-bold">
                                                    Rp {{ number_format($violation->penalty_amount, 0, ',', '.') }}
                                                </span>
                                            @endif
                                            @if ($violation->penalty_percentage)
                                                <span class="text-warning font-weight-bold">
                                                    {{ $violation->penalty_percentage }}%
                                                </span>
                                            @endif

                                            @if ($violation->applied_penalty_amount && $violation->status === 'processed')
                                                <br><small class="text-success">
                                                    <i class="fas fa-check-circle"></i>
                                                    Diterapkan: Rp
                                                    {{ number_format($violation->applied_penalty_amount, 0, ',', '.') }}
                                                </small>
                                            @endif

                                            @if ($violation->reversed_at)
                                                <br><small class="text-info">
                                                    <i class="fas fa-undo"></i> Dibatalkan
                                                </small>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @switch($violation->status)
                                            @case('pending')
                                                <span class="badge badge-warning badge-lg">
                                                    <i class="fas fa-clock"></i> Pending
                                                </span>
                                            @break

                                            @case('processed')
                                                <span class="badge badge-success badge-lg">
                                                    <i class="fas fa-check"></i> Diproses
                                                </span>
                                                @if ($violation->processed_at)
                                                    <br><small class="text-muted">
                                                        {{ \Carbon\Carbon::parse($violation->processed_at)->format('d/m H:i') }}
                                                    </small>
                                                @endif
                                            @break

                                            @case('forgiven')
                                                <span class="badge badge-info badge-lg">
                                                    <i class="fas fa-heart"></i> Dimaafkan
                                                </span>
                                                @if ($violation->reversed_at)
                                                    <br><small class="text-muted">
                                                        {{ \Carbon\Carbon::parse($violation->reversed_at)->format('d/m H:i') }}
                                                    </small>
                                                @endif
                                            @break
                                        @endswitch
                                    </td>
                                    <td>
                                        <small>
                                            <strong>{{ $violation->createdBy->name }}</strong>
                                            <br>{{ $violation->created_at->format('d/m H:i') }}
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group-vertical btn-block">
                                            @if ($violation->status == 'pending')
                                                <button class="btn btn-success btn-xs mb-1 btn-process-violation"
                                                    data-violation-id="{{ $violation->id }}" data-status="processed"
                                                    title="Proses pelanggaran">
                                                    <i class="fas fa-check"></i> Proses
                                                </button>
                                                <button class="btn btn-info btn-xs mb-1 btn-process-violation"
                                                    data-violation-id="{{ $violation->id }}" data-status="forgiven"
                                                    title="Maafkan pelanggaran">
                                                    <i class="fas fa-heart"></i> Maafkan
                                                </button>
                                            @elseif ($violation->status == 'processed' && $violation->applied_penalty_amount && !$violation->reversed_at)
                                                <button class="btn btn-warning btn-xs mb-1 btn-reverse-penalty"
                                                    data-violation-id="{{ $violation->id }}" title="Batalkan penalty">
                                                    <i class="fas fa-undo"></i> Batalkan
                                                </button>
                                            @endif

                                            <button class="btn btn-secondary btn-xs mb-1 btn-penalty-detail"
                                                data-violation-id="{{ $violation->id }}" title="Detail penalty">
                                                <i class="fas fa-eye"></i> Detail
                                            </button>

                                            <button class="btn btn-outline-primary btn-xs btn-penalty-history"
                                                data-user-id="{{ $violation->user_id }}" title="Riwayat penalty">
                                                <i class="fas fa-history"></i> Riwayat
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Pelanggaran -->
<div class="modal fade" id="modalAddViolation">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h4 class="modal-title">
                    <i class="fas fa-plus"></i> Tambah Pelanggaran
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.violations.store') }}" method="POST" id="formAddViolation">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Karyawan <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-control" required>
                            <option value="">Pilih Karyawan</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id_user }}">
                                    {{ $employee->name }} -
                                    @switch($employee->jabatan)
                                        @case(2)
                                            Kasir
                                        @break

                                        @case(3)
                                            Teknisi
                                        @break

                                        @default
                                            Unknown
                                        @break
                                    @endswitch
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Tanggal Pelanggaran <span class="text-danger">*</span></label>
                        <input type="date" name="violation_date" class="form-control"
                            value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="form-group">
                        <label>Tipe Pelanggaran <span class="text-danger">*</span></label>
                        <select name="type" class="form-control" required id="violation_type">
                            <option value="">Pilih Tipe</option>
                            <option value="telat">Terlambat</option>
                            <option value="alpha">Alpha</option>
                            <option value="kelalaian">Kelalaian</option>
                            <option value="komplain">Komplain</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Keterangan <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="3" required
                            placeholder="Jelaskan detail pelanggaran..."></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Denda (Nominal)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" name="penalty_amount" class="form-control" placeholder="0"
                                        min="0" id="penalty_amount">
                                </div>
                                <small class="form-text text-muted">
                                    Kosongkan jika menggunakan persentase
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Denda (Persentase)</label>
                                <div class="input-group">
                                    <input type="number" name="penalty_percentage" class="form-control"
                                        min="0" max="100" placeholder="0" id="penalty_percentage">
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">
                                    Kosongkan jika menggunakan nominal
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Preview Penalty akan muncul di sini -->
                    <div id="penalty-preview-container"></div>

                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Informasi:</h6>
                        <ul class="mb-0">
                            <li>Penalty akan diterapkan ke salary settings setelah diproses</li>
                            <li>Hanya isi salah satu: nominal ATAU persentase</li>
                            <li>Penalty dapat dibatalkan setelah diproses jika diperlukan</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-save"></i> Simpan Pelanggaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Reverse Penalty -->
<div class="modal fade" id="modalReversePenalty">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h4 class="modal-title">
                    <i class="fas fa-undo"></i> Batalkan Penalty
                </h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="formReversePenalty">
                @csrf
                <input type="hidden" id="reverse_violation_id" name="violation_id">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Peringatan!</strong> Tindakan ini akan membatalkan penalty yang sudah diterapkan dan
                        mengembalikan pengaturan kompensasi ke nilai sebelumnya.
                    </div>

                    <div class="card" id="violation-detail-card">
                        <div class="card-header">
                            <h6 class="mb-0">Detail Pelanggaran</h6>
                        </div>
                        <div class="card-body" id="violation-detail-content">
                            <div class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat detail...</div>
                        </div>
                    </div>

                    <div class="form-group mt-3">
                        <label>Alasan Pembatalan <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required
                            placeholder="Jelaskan alasan mengapa penalty ini dibatalkan (minimal 10 karakter)..."></textarea>
                        <small class="form-text text-muted">
                            Alasan ini akan dicatat dalam log sistem untuk audit trail.
                        </small>
                    </div>

                    <div class="form-check mt-3">
                        <input type="checkbox" class="form-check-input" id="confirmReverse" required>
                        <label class="form-check-label" for="confirmReverse">
                            Saya memahami bahwa penalty akan dibatalkan dan pengaturan kompensasi akan dikembalikan
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-warning" id="btnConfirmReverse" disabled>
                        <i class="fas fa-undo"></i> Batalkan Penalty
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Riwayat Penalty -->
<div class="modal fade" id="modalPenaltyHistory">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h4 class="modal-title">
                    <i class="fas fa-history"></i> Riwayat Penalty - <span id="employee_name_history"></span>
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Bulan</label>
                        <select id="historyMonth" class="form-control">
                            <option value="1">Januari</option>
                            <option value="2">Februari</option>
                            <option value="3">Maret</option>
                            <option value="4">April</option>
                            <option value="5">Mei</option>
                            <option value="6">Juni</option>
                            <option value="7">Juli</option>
                            <option value="8">Agustus</option>
                            <option value="9">September</option>
                            <option value="10">Oktober</option>
                            <option value="11">November</option>
                            <option value="12">Desember</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Tahun</label>
                        <select id="historyYear" class="form-control">
                            <option value="2024">2024</option>
                            <option value="2025" selected>2025</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>&nbsp;</label><br>
                        <button class="btn btn-primary btn-block" onclick="loadPenaltyHistory()">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </div>

                <div class="card card-info mb-3">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-cogs"></i> Info Salary Settings
                        </h6>
                    </div>
                    <div class="card-body" id="employee-balance-info">
                        <div class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="bg-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Tipe</th>
                                <th>Keterangan</th>
                                <th>Penalty</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="penalty-history-table">
                            <tr>
                                <td colspan="5" class="text-center">
                                    <i class="fas fa-spinner fa-spin"></i> Memuat data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <div class="alert alert-warning">
                        <strong><i class="fas fa-calculator"></i> Total Penalty Periode Ini: </strong>
                        <span id="total-penalties" class="font-weight-bold text-danger">Rp 0</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fas fa-times"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Penalty -->
<div class="modal fade" id="modalPenaltyDetail">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h4 class="modal-title">
                    <i class="fas fa-eye"></i> Detail Penalty
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="penalty-detail-content">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin"></i> Memuat detail...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fas fa-times"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .badge-lg {
        font-size: 0.9em;
        padding: 0.5rem 0.75rem;
    }

    .btn-xs {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        line-height: 1.2;
    }

    .btn-group-vertical .btn {
        margin-bottom: 2px;
    }

    .card-outline.card-danger {
        border-top: 3px solid #dc3545;
    }

    #penalty-preview-container .alert {
        margin-top: 15px;
        margin-bottom: 0;
    }

    .table td {
        vertical-align: middle;
    }

    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
</style>

<script>
    $(document).ready(function() {
        // 1. INISIALISASI DATATABLES YANG BENAR (client-side only)
        if ($('#violationsTable').length) {
            $('#violationsTable').DataTable({
                // HAPUS serverSide dan processing
                responsive: true,
                pageLength: 25,
                order: [
                    [1, 'desc']
                ], // Sort by tanggal
                columnDefs: [{
                        orderable: false,
                        targets: [8]
                    }, // Action column tidak bisa sort
                    {
                        searchable: false,
                        targets: [0, 8]
                    } // # dan Action tidak bisa search
                ],
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
                },
                drawCallback: function(settings) {
                    // Re-initialize tooltips setelah table redraw
                    $('[title]').tooltip();
                }
            });

            console.log('DataTables initialized successfully');
        }

        // 2. EVENT HANDLERS UNTUK ACTION BUTTONS
        // Gunakan event delegation karena DataTables bisa destroy/recreate elements
        $(document).on('click', '.btn-process-violation', function(e) {
            e.preventDefault();
            const violationId = $(this).data('violation-id');
            const status = $(this).data('status');
            processViolation(violationId, status);
        });

        $(document).on('click', '.btn-reverse-penalty', function(e) {
            e.preventDefault();
            const violationId = $(this).data('violation-id');
            showReversePenaltyModal(violationId);
        });

        $(document).on('click', '.btn-penalty-detail', function(e) {
            e.preventDefault();
            const violationId = $(this).data('violation-id');
            showPenaltyDetail(violationId);
        });

        $(document).on('click', '.btn-penalty-history', function(e) {
            e.preventDefault();
            const userId = $(this).data('user-id');
            showPenaltyHistory(userId);
        });

        // 3. FORM EVENTS
        // Preview penalty saat input berubah
        $('#penalty_amount, #penalty_percentage, select[name="user_id"]').on('change keyup', function() {
            calculatePenaltyPreview();
        });

        // Prevent both amount and percentage being filled
        $('#penalty_amount').on('input', function() {
            if ($(this).val()) {
                $('#penalty_percentage').val('');
            }
        });

        $('#penalty_percentage').on('input', function() {
            if ($(this).val()) {
                $('#penalty_amount').val('');
            }
        });

        // Reverse penalty form validation
        $('#confirmReverse').on('change', function() {
            $('#btnConfirmReverse').prop('disabled', !this.checked);
        });

        // Form submissions
        $('#formReversePenalty').on('submit', function(e) {
            e.preventDefault();
            handleReversePenalty();
        });

        // 4. MODAL EVENTS
        $('#modalAddViolation').on('hidden.bs.modal', function() {
            $('#formAddViolation')[0].reset();
            $('#penalty-preview-container').empty();
        });

        $('#modalReversePenalty').on('hidden.bs.modal', function() {
            $('#formReversePenalty')[0].reset();
            $('#confirmReverse').prop('checked', false);
            $('#btnConfirmReverse').prop('disabled', true);
        });
    });

    // 5. GLOBAL FUNCTIONS

    let currentEmployeeId = null;

    function refreshTable() {
        // Untuk client-side DataTables, reload halaman untuk data terbaru
        window.location.reload();
    }

    function processViolation(violationId, status) {
        const message = status === 'processed' ?
            'Apakah Anda yakin ingin memproses pelanggaran ini? Penalty akan langsung diterapkan ke salary settings karyawan.' :
            'Apakah Anda yakin ingin memaafkan pelanggaran ini?';

        if (!confirm(message)) {
            return;
        }

        const $buttons = $(`[data-violation-id="${violationId}"]`);
        $buttons.prop('disabled', true);

        $.ajax({
            url: "{{ route('admin.violations.update-status') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                violation_id: violationId,
                status: status
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    refreshTable();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || 'Unknown error'));
            },
            complete: function() {
                $buttons.prop('disabled', false);
            }
        });
    }

    function showReversePenaltyModal(violationId) {
        $('#reverse_violation_id').val(violationId);
        $('#confirmReverse').prop('checked', false);
        $('#btnConfirmReverse').prop('disabled', true);
        $('textarea[name="reason"]').val('');

        // Load violation detail
        $('#violation-detail-content').html(
            '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat detail...</div>');

        $.ajax({
            url: `{{ url('/admin/violations') }}/${violationId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const violation = response.data;
                    const detailHtml = `
                <div class="row">
                    <div class="col-md-6"><strong>Karyawan:</strong></div>
                    <div class="col-md-6">${violation.user.name}</div>
                </div>
                <div class="row">
                    <div class="col-md-6"><strong>Tanggal:</strong></div>
                    <div class="col-md-6">${new Date(violation.violation_date).toLocaleDateString('id-ID')}</div>
                </div>
                <div class="row">
                    <div class="col-md-6"><strong>Tipe:</strong></div>
                    <div class="col-md-6">${violation.type.charAt(0).toUpperCase() + violation.type.slice(1)}</div>
                </div>
                <div class="row">
                    <div class="col-md-6"><strong>Penalty Diterapkan:</strong></div>
                    <div class="col-md-6 text-danger font-weight-bold">
                        Rp ${new Intl.NumberFormat('id-ID').format(violation.applied_penalty_amount)}
                    </div>
                </div>`;

                    $('#violation-detail-content').html(detailHtml);
                } else {
                    $('#violation-detail-content').html(
                        '<div class="text-danger">Gagal memuat detail</div>');
                }
            },
            error: function() {
                $('#violation-detail-content').html('<div class="text-danger">Gagal memuat detail</div>');
            }
        });

        $('#modalReversePenalty').modal('show');
    }

    function handleReversePenalty() {
        const reason = $('#formReversePenalty textarea[name="reason"]').val().trim();

        if (reason.length < 10) {
            alert('Alasan pembatalan harus diisi minimal 10 karakter');
            return;
        }

        if (!confirm('Apakah Anda yakin ingin membatalkan penalty ini?')) {
            return;
        }

        const formData = $('#formReversePenalty').serialize();
        const $submitBtn = $('#btnConfirmReverse');

        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');

        $.ajax({
            url: "{{ route('admin.violations.reverse-penalty') }}",
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    $('#modalReversePenalty').modal('hide');
                    refreshTable();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                alert('Terjadi kesalahan: ' + (xhr.responseJSON?.message || 'Unknown error'));
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html('<i class="fas fa-undo"></i> Batalkan Penalty');
            }
        });
    }

    function showPenaltyDetail(violationId) {
        $('#penalty-detail-content').html(
            '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat detail...</div>');

        $.ajax({
            url: `{{ url('admin/violations') }}/${violationId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const violation = response.data;
                    const detailHtml = `
                <div class="row">
                    <div class="col-md-6"><strong>Karyawan:</strong></div>
                    <div class="col-md-6">${violation.user.name}</div>
                </div>
                <div class="row">
                    <div class="col-md-6"><strong>Tanggal Pelanggaran:</strong></div>
                    <div class="col-md-6">${new Date(violation.violation_date).toLocaleDateString('id-ID')}</div>
                </div>
                <div class="row">
                    <div class="col-md-6"><strong>Tipe:</strong></div>
                    <div class="col-md-6">${violation.type}</div>
                </div>
                <div class="row">
                    <div class="col-md-6"><strong>Keterangan:</strong></div>
                    <div class="col-md-6">${violation.description}</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6"><strong>Penalty Awal:</strong></div>
                    <div class="col-md-6">
                        ${violation.penalty_amount ? 'Rp ' + new Intl.NumberFormat('id-ID').format(violation.penalty_amount) : ''}
                        ${violation.penalty_percentage ? violation.penalty_percentage + '%' : ''}
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6"><strong>Penalty Diterapkan:</strong></div>
                    <div class="col-md-6 text-danger font-weight-bold">
                        ${violation.applied_penalty_amount ? 'Rp ' + new Intl.NumberFormat('id-ID').format(violation.applied_penalty_amount) : 'Belum diterapkan'}
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6"><strong>Diproses Pada:</strong></div>
                    <div class="col-md-6">${violation.processed_at ? new Date(violation.processed_at).toLocaleString('id-ID') : '-'}</div>
                </div>
                <div class="row">
                    <div class="col-md-6"><strong>Diproses Oleh:</strong></div>
                    <div class="col-md-6">${violation.processed_by ? violation.processed_by.name : '-'}</div>
                </div>`;

                    $('#penalty-detail-content').html(detailHtml);
                    $('#modalPenaltyDetail').modal('show');
                }
            },
            error: function() {
                $('#penalty-detail-content').html('<div class="text-danger">Gagal memuat detail</div>');
                $('#modalPenaltyDetail').modal('show');
            }
        });
    }

    function showPenaltyHistory(userId) {
        currentEmployeeId = userId;

        const employeeName = $(`[data-user-id="${userId}"]`).closest('tr').find('td:nth-child(3) strong').text();
        $('#employee_name_history').text(employeeName);

        const now = new Date();
        $('#historyMonth').val(now.getMonth() + 1);
        $('#historyYear').val(now.getFullYear());

        loadEmployeeBalanceInfo(userId);
        loadPenaltyHistory();

        $('#modalPenaltyHistory').modal('show');
    }

    function loadEmployeeBalanceInfo(userId) {
        $('#employee-balance-info').html(
            '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>');

        $.ajax({
            url: `{{ url('admin/employees/balance-info') }}/${userId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    let balanceHtml = `
                <div class="row">
                    <div class="col-md-6">
                        <strong>Saldo Saat Ini:</strong><br>
                        <span class="text-success font-weight-bold">${data.formatted_balance}</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Total Penalty Bulan Ini:</strong><br>
                        <span class="text-danger font-weight-bold">${data.formatted_monthly_penalties}</span>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <strong>Pengaturan Kompensasi Saat Ini:</strong>
                    </div>
                </div>
                <div class="row mt-2">`;

                    if (data.compensation_type === 'fixed') {
                        balanceHtml += `
                    <div class="col-md-4">
                        <small><strong>Tipe:</strong> Gaji Tetap</small>
                    </div>
                    <div class="col-md-4">
                        <small><strong>Gaji Pokok:</strong><br>
                        <span class="text-primary font-weight-bold">${data.formatted_basic_salary}</span></small>
                    </div>
                    <div class="col-md-4">
                        <small><strong>Komisi Service:</strong><br>
                        <span class="text-info">${data.service_percentage}%</span></small>
                    </div>`;
                    } else {
                        balanceHtml += `
                    <div class="col-md-6">
                        <small><strong>Tipe:</strong> Persentase</small>
                    </div>
                    <div class="col-md-6">
                        <small><strong>Persentase Komisi:</strong><br>
                        <span class="text-primary font-weight-bold">${data.percentage_value}%</span></small>
                    </div>`;
                    }

                    balanceHtml += `
                </div>
                <div class="mt-2">
                    <small class="text-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Perhatian:</strong> Penalty akan mengurangi ${data.compensation_type === 'fixed' ? 'gaji pokok' : 'persentase komisi'} secara permanen
                    </small>
                </div>`;

                    $('#employee-balance-info').html(balanceHtml);
                }
            },
            error: function() {
                $('#employee-balance-info').html('<div class="text-danger">Gagal memuat info</div>');
            }
        });
    }

    function loadPenaltyHistory() {
        if (!currentEmployeeId) return;

        const month = $('#historyMonth').val();
        const year = $('#historyYear').val();

        $('#penalty-history-table').html(
            '<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat data...</td></tr>'
        );

        $.ajax({
            url: `{{ url('admin/violations/penalty-history') }}/${currentEmployeeId}`,
            method: 'GET',
            data: {
                month: month,
                year: year
            },
            success: function(response) {
                if (response.success) {
                    const penalties = response.data.penalties;
                    const total = response.data.total_penalties;

                    let tableHtml = '';

                    if (penalties.length === 0) {
                        tableHtml =
                            '<tr><td colspan="5" class="text-center text-muted">Tidak ada penalty pada periode ini</td></tr>';
                    } else {
                        penalties.forEach(function(penalty) {
                            const date = new Date(penalty.violation_date).toLocaleDateString(
                                'id-ID');
                            const typeLabels = {
                                'telat': '<span class="badge badge-warning">Terlambat</span>',
                                'alpha': '<span class="badge badge-danger">Alpha</span>',
                                'kelalaian': '<span class="badge badge-info">Kelalaian</span>',
                                'komplain': '<span class="badge badge-secondary">Komplain</span>',
                                'lainnya': '<span class="badge badge-dark">Lainnya</span>'
                            };

                            const statusLabels = {
                                'processed': '<span class="badge badge-success">Diproses</span>',
                                'forgiven': '<span class="badge badge-info">Dimaafkan</span>'
                            };

                            tableHtml += `
                        <tr>
                            <td>${date}</td>
                            <td>${typeLabels[penalty.type] || penalty.type}</td>
                            <td>${penalty.description}</td>
                            <td>Rp ${new Intl.NumberFormat('id-ID').format(penalty.applied_penalty_amount || 0)}</td>
                            <td>${statusLabels[penalty.status] || penalty.status}</td>
                        </tr>`;
                        });
                    }

                    $('#penalty-history-table').html(tableHtml);
                    $('#total-penalties').text(`Rp ${new Intl.NumberFormat('id-ID').format(total)}`);
                }
            },
            error: function() {
                $('#penalty-history-table').html(
                    '<tr><td colspan="5" class="text-center text-danger">Gagal memuat data</td></tr>');
            }
        });
    }

    function calculatePenaltyPreview() {
        const userId = $('select[name="user_id"]').val();
        const penaltyAmount = $('#penalty_amount').val();
        const penaltyPercentage = $('#penalty_percentage').val();

        if (!userId || (!penaltyAmount && !penaltyPercentage)) {
            $('#penalty-preview-container').empty();
            return;
        }

        $.ajax({
            url: "{{ route('admin.violations.calculate-penalty-preview') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                user_id: userId,
                penalty_amount: penaltyAmount,
                penalty_percentage: penaltyPercentage
            },
            success: function(response) {
                if (response.success) {
                    showPenaltyPreview(response.data);
                }
            },
            error: function(xhr) {
                console.error('Error calculating penalty preview:', xhr.responseJSON);
            }
        });
    }

    function showPenaltyPreview(data) {
        let previewHtml = `
    <div class="alert alert-warning mt-3">
        <h6><i class="fas fa-exclamation-triangle"></i> Preview Dampak Penalty:</h6>
        <div class="row">
            <div class="col-md-6">
                <strong>Jumlah Penalty:</strong><br>
                <span class="text-danger font-weight-bold">${data.formatted_penalty}</span>
            </div>
            <div class="col-md-6">
                <strong>Tipe Perhitungan:</strong><br>
                ${data.calculation_details.type}
            </div>
        </div>`;

        if (data.impact_details) {
            previewHtml += `
        <hr>
        <div class="mt-2">
            <h6><i class="fas fa-cogs text-warning"></i> Dampak pada Salary Settings:</h6>
            <div class="row">
                <div class="col-md-4">
                    <strong>Field Terpengaruh:</strong><br>
                    <span class="badge badge-warning">${data.impact_details.field_affected}</span>
                </div>
                <div class="col-md-4">
                    <strong>Nilai Saat Ini:</strong><br>
                    <span class="text-info">${data.impact_details.current_value}</span>
                </div>
                <div class="col-md-4">
                    <strong>Nilai Setelah Penalty:</strong><br>
                    <span class="text-danger font-weight-bold">${data.impact_details.new_value}</span>
                </div>
            </div>
            <div class="mt-2">
                <strong>Pengurangan:</strong>
                <span class="text-danger">${data.impact_details.reduction}</span>
            </div>
        </div>`;
        }

        previewHtml += `</div>`;
        $('#penalty-preview-container').html(previewHtml);
    }
</script>
