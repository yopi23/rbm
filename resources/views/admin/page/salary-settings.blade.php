<!-- resources/views/admin/page/salary-settings.blade.php -->

@section('salary_settings', 'active')
@section('main', 'menu-is-opening menu-open')

<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-success">
            <div class="card-header">
                <div class="card-title">
                    Pengaturan Kompensasi Karyawan
                </div>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" onclick="addNewSalary()">
                        <i class="fas fa-plus"></i> Tambah Pengaturan
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="TABLES_1">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th class="text-nowrap">Tipe Kompensasi</th>
                                <th class="text-nowrap">Gaji Pokok</th>
                                <th>Persentase</th>
                                <th class="text-nowrap">Target Unit</th>
                                <th class="text-nowrap">Target Profit Toko</th>
                                <th class="text-nowrap">Bonus Target</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($employees as $employee)
                                @php
                                    $setting = $salarySettings->where('user_id', $employee->id_user)->first();
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $employee->name }}</td>
                                    <td>
                                        @switch($employee->jabatan)
                                            @case(2)
                                                <span class="badge badge-success">Kasir</span>
                                            @break

                                            @case(3)
                                                <span class="badge badge-info">Teknisi</span>
                                            @break
                                        @endswitch
                                    </td>
                                    <td>
                                        @if ($setting)
                                            @if ($setting->compensation_type == 'fixed')
                                                <span class="badge badge-primary">Gaji Tetap</span>
                                            @else
                                                <span class="badge badge-success">Persentase</span>
                                            @endif
                                        @else
                                            <span class="badge badge-secondary">Belum diatur</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($setting)
                                            Rp {{ number_format($setting->basic_salary, 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($setting)
                                            @if ($setting->compensation_type == 'fixed')
                                                {{ $setting->service_percentage }}% (Service)
                                            @else
                                                {{ $setting->percentage_value }}% (Profit)
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($setting)
                                            {{ $setting->monthly_target }} unit
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($setting)
                                            Rp {{ number_format($setting->target_shop_profit, 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($setting)
                                            Rp {{ number_format($setting->target_bonus, 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($setting)
                                            <span class="badge badge-success">Aktif</span>
                                        @else
                                            <span class="badge badge-warning">Belum Diatur</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-warning btn-sm my-2"
                                            onclick="editSalary({{ json_encode([
                                                'user_id' => $employee->id_user,
                                                'name' => $employee->name,
                                                'compensation_type' => $setting->compensation_type ?? 'fixed',
                                                'basic_salary' => $setting->basic_salary ?? 0,
                                                'service_percentage' => $setting->service_percentage ?? 0,
                                                'target_bonus' => $setting->target_bonus ?? 0,
                                                'monthly_target' => $setting->monthly_target ?? 0,
                                                'percentage_value' => $setting->percentage_value ?? 0,
                                                'target_shop_profit' => $setting->target_shop_profit ?? 0,
                                            ]) }})">
                                            <i class="fas fa-edit"></i>
                                            {{ $setting ? 'Edit' : 'Atur' }}
                                        </button>

                                        @if ($setting)
                                            <button class="btn btn-info btn-sm"
                                                onclick="viewSalaryDetail({{ json_encode($setting) }})">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        @endif
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

<!-- Modal Pengaturan Gaji -->
<div class="modal fade" id="modalSalarySettings">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalTitle">Pengaturan Kompensasi</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="salaryForm" action="{{ route('admin.salary-settings.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="user_id_salary">

                    <div class="form-group">
                        <label><strong>Karyawan:</strong></label>
                        <p id="employee_name_display" class="form-control-static font-weight-bold"></p>
                    </div>

                    <div class="form-group">
                        <label>Tipe Kompensasi *</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="compensation_type" id="type_fixed"
                                value="fixed" checked>
                            <label class="form-check-label" for="type_fixed">
                                <strong>Gaji Tetap</strong>
                                <small class="text-muted d-block">Menerima gaji pokok bulanan.</small>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="compensation_type" id="type_percentage"
                                value="percentage">
                            <label class="form-check-label" for="type_percentage">
                                <strong>Persentase Profit</strong>
                                <small class="text-muted d-block">Hanya menerima komisi dari profit service.</small>
                            </label>
                        </div>
                    </div>

                    <div id="fixedFields">
                        <div class="form-group">
                            <label>Gaji Pokok (Rp) *</label>
                            <input type="number" name="basic_salary" id="basic_salary" class="form-control"
                                min="0" step="1000">
                        </div>
                    </div>

                    <div id="percentageFields">
                        <div class="form-group">
                            <label>Persentase dari Profit (%) *</label>
                            <input type="number" name="percentage_value" id="percentage_value" class="form-control"
                                min="0" max="100" step="0.1">
                        </div>
                    </div>

                    <div class="card border-primary mt-3">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">Pengaturan Target & Bonus (Berlaku untuk Semua Tipe)</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted"><small>Bonus akan diberikan jika kedua target (unit dan profit)
                                    tercapai dalam satu bulan.</small></p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Target Unit/Bulan *</label>
                                        <input type="number" name="monthly_target" id="monthly_target"
                                            class="form-control" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Target Min. Profit Toko (Rp) *</label>
                                        <input type="number" name="target_shop_profit" id="target_shop_profit"
                                            class="form-control" min="0" step="1000">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Bonus Jika Target Tercapai (Rp) *</label>
                                        <input type="number" name="target_bonus" id="target_bonus"
                                            class="form-control" min="0" step="1000">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3">
                        <h6><i class="fas fa-lightbulb"></i> Catatan Penting:</h6>
                        <ul class="mb-0">
                            <li>Kehadiran akan mempengaruhi perhitungan gaji pokok (untuk tipe Gaji Tetap) atau total
                                komisi (untuk tipe Persentase).</li>
                            <li>Pelanggaran akan mengurangi total gaji sesuai aturan yang berlaku.</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Salary -->
<div class="modal fade" id="modalSalaryDetail">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Detail Pengaturan Kompensasi</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="salaryDetailContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    function addNewSalary() {
        // Fungsi ini tidak perlu diubah, sudah benar.
        $('#salaryForm')[0].reset();
        $('#modalTitle').text('Tambah Pengaturan Kompensasi');
        $('#employee_name_display').text('Pilih Karyawan dari Tabel'); // Beri instruksi
        $('#user_id_salary').val('');
        $('#type_fixed').prop('checked', true);
        toggleCompensationType();
        $('#modalSalarySettings').modal('show');
    }

    // ==========================================================
    // FUNGSI EDIT SALARY YANG DIPERBARUI
    // ==========================================================
    function editSalary(data) {
        $('#salaryForm')[0].reset();

        $('#modalTitle').text('Edit Pengaturan Kompensasi');
        $('#employee_name_display').text(data.name);
        $('#user_id_salary').val(data.user_id);

        // Set tipe kompensasi
        if (data.compensation_type === 'fixed') {
            $('#type_fixed').prop('checked', true);
            $('#basic_salary').val(data.basic_salary);
        } else {
            $('#type_percentage').prop('checked', true);
            $('#percentage_value').val(data.percentage_value);
        }

        // PERBAIKAN: Isi data target & bonus yang sekarang berlaku untuk semua tipe
        $('#monthly_target').val(data.monthly_target);
        $('#target_shop_profit').val(data.target_shop_profit);
        $('#target_bonus').val(data.target_bonus);

        toggleCompensationType();
        $('#modalSalarySettings').modal('show');
    }

    // ==========================================================
    // FUNGSI VIEW SALARY DETAIL YANG DIPERBARUI
    // ==========================================================
    function viewSalaryDetail(setting) {
        // Tambahkan pengecekan jika setting tidak ada
        if (!setting) {
            $('#salaryDetailContent').html('<p class="text-center">Pengaturan belum dibuat untuk karyawan ini.</p>');
            $('#modalSalaryDetail').modal('show');
            return;
        }

        let content = `
            <table class="table table-borderless table-sm">
                <tr>
                    <td width="40%"><strong>Tipe Kompensasi</strong></td>
                    <td>: ${setting.compensation_type === 'fixed' ? '<span class="badge badge-primary">Gaji Tetap</span>' : '<span class="badge badge-success">Persentase</span>'}</td>
                </tr>
        `;

        if (setting.compensation_type === 'fixed') {
            content += `
                <tr>
                    <td><strong>Gaji Pokok</strong></td>
                    <td>: Rp ${new Intl.NumberFormat('id-ID').format(setting.basic_salary)}</td>
                </tr>
            `;
        } else { // percentage
            content += `
                <tr>
                    <td><strong>Persentase Profit</strong></td>
                    <td>: ${setting.percentage_value}%</td>
                </tr>
            `;
        }

        // PERBAIKAN: Tampilkan data target & bonus untuk semua tipe kompensasi
        content += `
            <tr><td colspan="2"><hr class="my-2"></td></tr>
            <tr>
                <td colspan="2"><strong>Pengaturan Target & Bonus</strong></td>
            </tr>
            <tr>
                <td><strong>Target Unit/Bulan</strong></td>
                <td>: ${setting.monthly_target} unit</td>
            </tr>
            <tr>
                <td><strong>Target Profit Toko</strong></td>
                <td>: Rp ${new Intl.NumberFormat('id-ID').format(setting.target_shop_profit)}</td>
            </tr>
            <tr>
                <td><strong>Bonus Jika Tercapai</strong></td>
                <td>: Rp ${new Intl.NumberFormat('id-ID').format(setting.target_bonus)}</td>
            </tr>
        `;

        content += `</table>`;

        $('#salaryDetailContent').html(content);
        $('#modalSalaryDetail').modal('show');
    }

    // ==========================================================
    // FUNGSI TOGGLE YANG DIPERBARUI
    // ==========================================================
    function toggleCompensationType() {
        const isFixed = $('#type_fixed').is(':checked');

        // Hanya toggle field yang spesifik per tipe
        if (isFixed) {
            $('#fixedFields').show();
            $('#percentageFields').hide();
            $('#basic_salary').prop('required', true);
            $('#percentage_value').prop('required', false);
        } else {
            $('#fixedFields').hide();
            $('#percentageFields').show();
            $('#basic_salary').prop('required', false);
            $('#percentage_value').prop('required', true);
        }
        // Bagian target dan bonus (yang ada di dalam modal form) tidak lagi di-toggle,
        // karena sudah dipisahkan dan akan selalu terlihat.
    }

    // Event listeners dan AJAX (tidak perlu diubah, sudah benar)
    $(document).ready(function() {
        $('input[name="compensation_type"]').change(function() {
            toggleCompensationType();
        });

        // Initial setup
        toggleCompensationType();

        $('#salaryForm').on('submit', function(e) {
            e.preventDefault();
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...').prop('disabled',
                true);

            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        alert('Pengaturan kompensasi berhasil disimpan!');
                        $('#modalSalarySettings').modal('hide');
                        location.reload();
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Terjadi kesalahan. Silakan coba lagi.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    alert(errorMessage);
                },
                complete: function() {
                    submitBtn.html(originalText).prop('disabled', false);
                }
            });
        });
    });
</script>
