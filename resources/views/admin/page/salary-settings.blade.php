@section('salary_settings', 'active')
@section('main', 'menu-is-opening menu-open')

<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-success">
            <div class="card-header">
                <div class="card-title">
                    Pengaturan Kompensasi Karyawan
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
                                <th class="text-nowrap">Target 1</th>
                                <th class="text-nowrap">Target 2</th>
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
                                        @if ($employee->jabatan == 2)
                                            <span class="badge badge-success">Kasir</span>
                                        @elseif ($employee->jabatan == 3)
                                            <span class="badge badge-info">Teknisi</span>
                                        @else
                                            <span class="badge badge-secondary">Lainnya</span>
                                        @endif
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
                                        @if ($setting && $setting->compensation_type == 'fixed')
                                            Rp {{ number_format($setting->basic_salary, 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($setting && $setting->compensation_type == 'percentage')
                                            {{ $setting->percentage_value }}% (Profit)
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($setting)
                                            @if ($employee->jabatan == 3)
                                                {{ $setting->monthly_target ?? 0 }} unit
                                            @elseif($employee->jabatan == 2)
                                                {{ $setting->target_transaction_count ?? 0 }} trx
                                            @else
                                                -
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($setting)
                                            @if ($employee->jabatan == 3)
                                                Rp {{ number_format($setting->target_shop_profit ?? 0, 0, ',', '.') }}
                                            @elseif($employee->jabatan == 2)
                                                Rp {{ number_format($setting->target_sales_revenue ?? 0, 0, ',', '.') }}
                                            @else
                                                -
                                            @endif
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
                                        <button class="btn btn-warning btn-sm my-1"
                                            onclick="editSalary({{ json_encode([
                                                'user_id' => $employee->id_user,
                                                'name' => $employee->name,
                                                'jabatan' => $employee->jabatan,
                                                'compensation_type' => $setting->compensation_type ?? 'fixed',
                                                'basic_salary' => $setting->basic_salary ?? 0,
                                                'percentage_value' => $setting->percentage_value ?? 0,
                                                'monthly_target' => $setting->monthly_target ?? 0,
                                                'target_shop_profit' => $setting->target_shop_profit ?? 0,
                                                'target_transaction_count' => $setting->target_transaction_count ?? 0,
                                                'target_sales_revenue' => $setting->target_sales_revenue ?? 0,
                                                'target_bonus' => $setting->target_bonus ?? 0,
                                            ]) }})">
                                            <i class="fas fa-edit"></i>
                                            {{ $setting ? 'Edit' : 'Atur' }}
                                        </button>

                                        @if ($setting)
                                            <button class="btn btn-info btn-sm my-1"
                                                onclick="viewSalaryDetail({{ json_encode($setting) }}, {{ $employee->jabatan }})">
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
                            <h6 class="mb-0">Pengaturan Target & Bonus</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted"><small>Bonus akan diberikan jika kedua target tercapai dalam satu
                                    bulan.</small></p>

                            <div class="row">
                                <div class="col-md-8" id="technicianTargetFields">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Target Unit/Bulan *</label>
                                                <input type="number" name="monthly_target" id="monthly_target"
                                                    class="form-control" min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Target Min. Profit Toko (Rp) *</label>
                                                <input type="number" name="target_shop_profit"
                                                    id="target_shop_profit" class="form-control" min="0"
                                                    step="1000">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-8" id="cashierTargetFields">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Target Jumlah Transaksi *</label>
                                                <input type="number" name="target_transaction_count"
                                                    id="target_transaction_count" class="form-control"
                                                    min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Target Omzet Penjualan (Rp) *</label>
                                                <input type="number" name="target_sales_revenue"
                                                    id="target_sales_revenue" class="form-control" min="0"
                                                    step="1000">
                                            </div>
                                        </div>
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
                            <li>Kehadiran dan pelanggaran akan mempengaruhi perhitungan gaji akhir.</li>
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
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    function editSalary(data) {
        $('#salaryForm')[0].reset();
        $('#modalTitle').text('Edit Pengaturan Kompensasi untuk ' + data.name);

        $('#user_id_salary').val(data.user_id);
        $('#employee_name_display').text(data.name);

        if (data.compensation_type === 'fixed') {
            $('#type_fixed').prop('checked', true);
            $('#basic_salary').val(data.basic_salary);
        } else {
            $('#type_percentage').prop('checked', true);
            $('#percentage_value').val(data.percentage_value);
        }
        toggleCompensationType();

        // Logika untuk menampilkan/menyembunyikan field target berdasarkan jabatan
        if (data.jabatan == 3) { // Teknisi
            $('#technicianTargetFields').show();
            $('#cashierTargetFields').hide();
            $('#monthly_target').val(data.monthly_target);
            $('#target_shop_profit').val(data.target_shop_profit);
        } else if (data.jabatan == 2) { // Kasir
            $('#technicianTargetFields').hide();
            $('#cashierTargetFields').show();
            $('#target_transaction_count').val(data.target_transaction_count);
            $('#target_sales_revenue').val(data.target_sales_revenue);
        } else { // Jabatan lain (jika ada) disembunyikan semua
            $('#technicianTargetFields').hide();
            $('#cashierTargetFields').hide();
        }

        $('#target_bonus').val(data.target_bonus);

        $('#modalSalarySettings').modal('show');
    }

    function viewSalaryDetail(setting, jabatan) {
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
                </tr>`;

        if (setting.compensation_type === 'fixed') {
            content += `
                <tr>
                    <td><strong>Gaji Pokok</strong></td>
                    <td>: Rp ${new Intl.NumberFormat('id-ID').format(setting.basic_salary)}</td>
                </tr>`;
        } else {
            content += `
                <tr>
                    <td><strong>Persentase Profit</strong></td>
                    <td>: ${setting.percentage_value}%</td>
                </tr>`;
        }

        content += `
            <tr><td colspan="2"><hr class="my-2"></td></tr>
            <tr>
                <td colspan="2"><strong>Pengaturan Target & Bonus</strong></td>
            </tr>`;

        if (jabatan == 3) { // Tampilkan target untuk Teknisi
            content += `
                <tr>
                    <td><strong>Target Unit/Bulan</strong></td>
                    <td>: ${setting.monthly_target} unit</td>
                </tr>
                <tr>
                    <td><strong>Target Profit Toko</strong></td>
                    <td>: Rp ${new Intl.NumberFormat('id-ID').format(setting.target_shop_profit)}</td>
                </tr>`;
        } else if (jabatan == 2) { // Tampilkan target untuk Kasir
            content += `
                <tr>
                    <td><strong>Target Transaksi</strong></td>
                    <td>: ${setting.target_transaction_count} transaksi</td>
                </tr>
                <tr>
                    <td><strong>Target Omzet</strong></td>
                    <td>: Rp ${new Intl.NumberFormat('id-ID').format(setting.target_sales_revenue)}</td>
                </tr>`;
        }

        // Tampilkan Bonus untuk semua
        content += `
            <tr>
                <td><strong>Bonus Jika Tercapai</strong></td>
                <td>: Rp ${new Intl.NumberFormat('id-ID').format(setting.target_bonus)}</td>
            </tr>
        </table>`;

        $('#salaryDetailContent').html(content);
        $('#modalSalaryDetail').modal('show');
    }

    function toggleCompensationType() {
        if ($('#type_fixed').is(':checked')) {
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
    }

    $(document).ready(function() {
        // Sembunyikan kedua div target pada awalnya
        $('#technicianTargetFields').hide();
        $('#cashierTargetFields').hide();

        $('input[name="compensation_type"]').change(toggleCompensationType);
        toggleCompensationType();

        $('#salaryForm').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...').prop('disabled',
            true);

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        alert('Pengaturan kompensasi berhasil disimpan!');
                        $('#modalSalarySettings').modal('hide');
                        location.reload();
                    } else {
                        alert(response.message || 'Gagal menyimpan data.');
                    }
                },
                error: function(xhr) {
                    let errorMessage =
                        'Terjadi kesalahan. Silakan periksa kembali data Anda.';
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
