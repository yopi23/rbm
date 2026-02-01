<!-- resources/views/admin/page/work-schedule.blade.php -->

@section('schedule', 'active')
@section('main', 'menu-is-opening menu-open')

<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <div class="card-title">
                    Jadwal Kerja Karyawan
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
                                <th>Senin</th>
                                <th>Selasa</th>
                                <th>Rabu</th>
                                <th>Kamis</th>
                                <th>Jumat</th>
                                <th>Sabtu</th>
                                <th>Minggu</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($employees as $employee)
                                @php
                                    $employeeSchedules = $schedules->where('user_id', $employee->id_user);
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
                                    @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                        @php
                                            $schedule = $employeeSchedules->where('day_of_week', $day)->first();
                                        @endphp
                                        <td>
                                            @if ($schedule && $schedule->is_working_day)
                                                {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} -
                                                {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                            @else
                                                <span class="badge badge-secondary">Libur</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td>
                                        <button class="btn btn-warning btn-sm"
                                            onclick="editSchedule({{ $employee->id_user }})">
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

<!-- Modal Edit Schedule -->
<div class="modal fade" id="modalEditSchedule">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Jadwal Kerja</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.work-schedule.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="user_id_schedule">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Hari</th>
                                <th>Jam Masuk</th>
                                <th>Jam Pulang</th>
                                <th>Hari Kerja</th>
                                <th>Penanggung Jawab</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $days = [
                                    'Monday' => 'Senin',
                                    'Tuesday' => 'Selasa',
                                    'Wednesday' => 'Rabu',
                                    'Thursday' => 'Kamis',
                                    'Friday' => 'Jumat',
                                    'Saturday' => 'Sabtu',
                                    'Sunday' => 'Minggu',
                                ];
                            @endphp
                            @foreach ($days as $dayEng => $dayInd)
                                <tr>
                                    <td>{{ $dayInd }}</td>
                                    <td>
                                        <input type="hidden" name="schedules[{{ $loop->index }}][day_of_week]"
                                            value="{{ $dayEng }}">
                                        <input type="time" name="schedules[{{ $loop->index }}][start_time]"
                                            class="form-control start_time_{{ $dayEng }}" value="10:00">
                                    </td>
                                    <td>
                                        <input type="time" name="schedules[{{ $loop->index }}][end_time]"
                                            class="form-control end_time_{{ $dayEng }}" value="18:00">
                                    </td>
                                    <td>
                                        <select name="schedules[{{ $loop->index }}][is_working_day]"
                                            class="form-control working_day_{{ $dayEng }}">
                                            <option value="1"
                                                {{ in_array($dayEng, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']) ? 'selected' : '' }}>
                                                Ya</option>
                                            <option value="0" {{ $dayEng == 'Sunday' ? 'selected' : '' }}>Tidak
                                            </option>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input is_pic_{{ $dayEng }}" 
                                                id="is_pic_{{ $dayEng }}" name="schedules[{{ $loop->index }}][is_pic]" value="1">
                                            <label class="custom-control-label" for="is_pic_{{ $dayEng }}"></label>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editSchedule(userId) {
        $('#user_id_schedule').val(userId);

        // Get existing schedule using AJAX
        var url = "{{ route('admin.work-schedule.user', ':id') }}";
        url = url.replace(':id', userId);

        $.ajax({
            url: url,
            method: 'GET',
            success: function(response) {
                if (response.schedules) {
                    // Reset form to default values first
                    $('.start_time_Monday, .start_time_Tuesday, .start_time_Wednesday, .start_time_Thursday, .start_time_Friday, .start_time_Saturday')
                        .val('08:00');
                    $('.end_time_Monday, .end_time_Tuesday, .end_time_Wednesday, .end_time_Thursday, .end_time_Friday, .end_time_Saturday')
                        .val('16:30');
                    $('.working_day_Monday, .working_day_Tuesday, .working_day_Wednesday, .working_day_Thursday, .working_day_Friday, .working_day_Saturday')
                        .val('1');
                    $('.start_time_Sunday').val('08:00');
                    $('.end_time_Sunday').val('16:30');
                    $('.working_day_Sunday').val('0');
                    
                    // Reset Checkboxes
                    $('.is_pic_Monday, .is_pic_Tuesday, .is_pic_Wednesday, .is_pic_Thursday, .is_pic_Friday, .is_pic_Saturday, .is_pic_Sunday')
                        .prop('checked', false);

                    // Then update with actual values
                    response.schedules.forEach(function(schedule) {
                        $('.start_time_' + schedule.day_of_week).val(schedule.start_time.substring(
                            0, 5));
                        $('.end_time_' + schedule.day_of_week).val(schedule.end_time.substring(0,
                            5));
                        $('.working_day_' + schedule.day_of_week).val(schedule.is_working_day ?
                            '1' : '0');
                        
                        if (schedule.is_pic) {
                            $('.is_pic_' + schedule.day_of_week).prop('checked', true);
                        }
                    });
                }
                $('#modalEditSchedule').modal('show');
            },
            error: function(xhr) {
                console.log(xhr);
                // If error, just show modal with default values
                $('#modalEditSchedule').modal('show');
            }
        });
    }
</script>
