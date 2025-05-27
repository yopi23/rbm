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
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="TABLES_1">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>Tipe Kompensasi</th>
                                <th>Gaji Pokok</th>
                                <th>Persentase</th>
                                <th>Target Unit</th>
                                <th>Bonus Target</th>
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
                                            {{ $setting->compensation_type == 'fixed' ? 'Gaji Tetap' : 'Persentase' }}
                                        @else
                                            Belum diatur
                                        @endif
                                    </td>
                                    <td>
                                        @if ($setting && $setting->compensation_type == 'fixed')
                                            Rp {{ number_format($setting->basic_salary, 0, ',', '.') }}
                                        @elseif($setting)
                                            -
                                        @else
                                            Belum diatur
                                        @endif
                                    </td>
                                    <td>
                                        @if ($setting)
                                            {{ $setting->compensation_type == 'fixed'
                                                ? $setting->service_percentage . '% (Service)'
                                                : $setting->percentage_value . '% (Profit)' }}
                                        @else
                                            Belum diatur
                                        @endif
                                    </td>
                                    <td>
                                        @if ($setting && $setting->compensation_type == 'fixed')
                                            {{ $setting->monthly_target }} unit
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($setting && $setting->compensation_type == 'fixed')
                                            Rp {{ number_format($setting->target_bonus, 0, ',', '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-warning btn-sm"
                                            onclick="editSalary(
                                                '{{ $employee->id_user }}',
                                                '{{ $setting->compensation_type ?? 'fixed' }}',
                                                {{ $setting->basic_salary ?? 0 }},
                                                {{ $setting->service_percentage ?? 40 }},
                                                {{ $setting->target_bonus ?? 0 }},
                                                {{ $setting->monthly_target ?? 30 }},
                                                {{ $setting->percentage_value ?? 0 }}
                                            )">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Pengaturan Kompensasi</h4>
            </div>
            <form id="salaryForm" action="{{ route('admin.salary.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="user_id_salary">

                    <div class="form-group">
                        <label>Tipe Kompensasi *</label>
                        <select name="compensation_type" id="compensation_type" class="form-control" required>
                            <option value="fixed"
                                {{ old('compensation_type', $setting->compensation_type ?? 'fixed') == 'fixed' ? 'selected' : '' }}>
                                Gaji Tetap</option>
                            <option value="percentage"
                                {{ old('compensation_type', $setting->compensation_type ?? '') == 'percentage' ? 'selected' : '' }}>
                                Persentase Profit</option>
                        </select>
                    </div>

                    <div id="fixedFields">
                        <div class="form-group">
                            <label>Gaji Pokok *</label>
                            <input type="number" name="basic_salary" id="basic_salary" class="form-control"
                                min="0">
                        </div>
                        <div class="form-group">
                            <label>Persentase Service (%) *</label>
                            <input type="number" name="service_percentage" id="service_percentage" class="form-control"
                                min="0" max="100">
                        </div>
                        <div class="form-group">
                            <label>Target Unit/Bulan *</label>
                            <input type="number" name="monthly_target" id="monthly_target" class="form-control"
                                min="0">
                        </div>
                        <div class="form-group">
                            <label>Bonus Target *</label>
                            <input type="number" name="target_bonus" id="target_bonus" class="form-control"
                                min="0">
                        </div>
                    </div>

                    <div id="percentageFields" style="display:none;">
                        <div class="form-group">
                            <label>Persentase Profit (%) *</label>
                            <input type="number" name="percentage_value" id="percentage_value" class="form-control"
                                min="0" max="100">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- resources/views/admin/page/salary-settings.blade.php -->

<script>
    function editSalary(userId, type, basicSalary, servicePercent, targetBonus, monthlyTarget, percentageValue) {
        $('#user_id_salary').val(userId);
        $('#compensation_type').val(type).trigger('change');

        if (type === 'fixed') {
            $('#fixedFields').show();
            $('#percentageFields').hide();
            $('#basic_salary').val(basicSalary);
            $('#service_percentage').val(servicePercent);
            $('#target_bonus').val(targetBonus);
            $('#monthly_target').val(monthlyTarget);
        } else {
            $('#fixedFields').hide();
            $('#percentageFields').show();
            $('#percentage_value').val(percentageValue);
        }

        $('#modalSalarySettings').modal('show');
    }

    // Pastikan perubahan tipe kompensasi mempengaruhi tampilan field
    $('#compensation_type').change(function() {
        if ($(this).val() === 'fixed') {
            $('#fixedFields').show();
            $('#percentageFields').hide();
            // Set required attribute
            $('#basic_salary, #service_percentage, #target_bonus, #monthly_target').prop('required', true);
            $('#percentage_value').prop('required', false);
        } else {
            $('#fixedFields').hide();
            $('#percentageFields').show();
            // Set required attribute
            $('#basic_salary, #service_percentage, #target_bonus, #monthly_target').prop('required', false);
            $('#percentage_value').prop('required', true);
        }
    }).trigger('change'); // Trigger perubahan saat pertama kali load
</script>
