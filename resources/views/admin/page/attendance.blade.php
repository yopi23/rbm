<!-- resources/views/admin/page/attendance.blade.php -->

@section('attendance', 'active')
@section('main', 'menu-is-opening menu-open')

<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-success">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    Absensi Karyawan - {{ \Carbon\Carbon::today()->format('d F Y') }}
                </h3>

                <div class="d-flex flex-wrap ml-auto">
                    <!-- Tombol Riwayat Absensi -->
                    <a href="{{ route('admin.attendance.history') }}" class="btn btn-info btn-sm mr-1">
                        <i class="fas fa-history"></i> Riwayat Absensi
                    </a>

                    <!-- Tombol Export Hari Ini -->
                    <a href="{{ route('admin.attendance.export', ['view_type' => 'daily', 'date' => date('Y-m-d')]) }}"
                        class="btn btn-success btn-sm mr-1">
                        <i class="fas fa-download"></i> Export Hari Ini
                    </a>

                    <!-- Tombol Absen Manual -->
                    <button type="button" class="btn btn-primary btn-sm mr-1" data-toggle="modal"
                        data-target="#modalAbsensi">
                        <i class="fas fa-plus"></i> Absen Manual
                    </button>

                    <!-- Tombol Riwayat Izin Keluar -->
                    <a href="{{ route('admin.outside-office.history') }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-sign-out-alt"></i> Riwayat Izin Keluar
                    </a>
                </div>
            </div>

            <div class="card-body">
                <!-- Statistik -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-user-check"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Hadir Hari Ini</span>
                                <span
                                    class="info-box-number">{{ $attendances->where('status', 'hadir')->count() }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Terlambat</span>
                                <span
                                    class="info-box-number">{{ $attendances->where('late_minutes', '>', 0)->count() }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-danger"><i class="fas fa-user-times"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Belum Absen</span>
                                <span class="info-box-number">{{ $employees->count() - $attendances->count() }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="info-box bg-info">
                            <span class="info-box-icon"><i class="fas fa-users"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Karyawan</span>
                                <span class="info-box-number">{{ $employees->count() }}</span>
                                <span class="info-box-more">
                                    <a href="{{ route('admin.attendance.history') }}" class="text-white">
                                        Lihat Riwayat <i class="fas fa-arrow-circle-right"></i>
                                    </a>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <!-- Tambahkan button di atas tabel -->
                <button type="button" class="btn btn-warning btn-sm mb-3" data-toggle="modal"
                    data-target="#modalRequestLeave">
                    <i class="fas fa-calendar-minus"></i> Permintaan Izin
                </button>
                <div class="table-responsive">
                    <table class="table table-bordered" id="TABLES_1">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Jabatan</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Status</th>
                                <th>Keterlambatan</th>
                                <th>Lokasi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($employees as $employee)
                                @php
                                    $attendance = $attendances->where('user_id', $employee->id_user)->first();
                                    $schedule = $schedules->where('user_id', $employee->id_user)->first();
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
                                        @if ($attendance && $attendance->check_in)
                                            {{ \Carbon\Carbon::parse($attendance->check_in)->format('H:i') }}
                                            @if ($attendance->photo_in)
                                                <a href="{{ asset('storage/' . $attendance->photo_in) }}"
                                                    target="_blank">
                                                    <i class="fas fa-image"></i>
                                                </a>
                                            @endif
                                        @else
                                            <button class="btn btn-success btn-sm"
                                                onclick="showCheckInModal({{ $employee->id_user }})">
                                                Check In
                                            </button>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($attendance && $attendance->check_out)
                                            {{ \Carbon\Carbon::parse($attendance->check_out)->format('H:i') }}
                                            @if ($attendance->photo_out)
                                                <a href="{{ asset('storage/' . $attendance->photo_out) }}"
                                                    target="_blank">
                                                    <i class="fas fa-image"></i>
                                                </a>
                                            @endif
                                        @elseif($attendance && $attendance->check_in)
                                            <button class="btn btn-danger btn-sm"
                                                onclick="showCheckOutModal({{ $employee->id_user }})">
                                                Check Out
                                            </button>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($attendance)
                                            @switch($attendance->status)
                                                @case('hadir')
                                                    <span class="badge badge-success">Hadir</span>
                                                @break

                                                @case('izin')
                                                    <span class="badge badge-warning">Izin</span>
                                                @break

                                                @case('sakit')
                                                    <span class="badge badge-info">Sakit</span>
                                                @break

                                                @case('alpha')
                                                    <span class="badge badge-danger">Alpha</span>
                                                @break

                                                @case('libur')
                                                    <span class="badge badge-secondary">Libur</span>
                                                @break

                                                @case('cuti')
                                                    <span class="badge badge-primary">Cuti</span>
                                                @break
                                            @endswitch
                                        @else
                                            <span class="badge badge-secondary">Belum Absen</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($attendance && $attendance->late_minutes > 0)
                                            <span class="badge badge-danger">@lateFormat($attendance->late_minutes)
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($attendance && $attendance->location)
                                            <a href="https://maps.google.com/?q={{ $attendance->location }}"
                                                target="_blank">
                                                <i class="fas fa-map-marker-alt"></i> Lihat
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($employee->is_outside_office)
                                            @php
                                                $currentLog = App\Models\OutsideOfficeLog::where(
                                                    'user_id',
                                                    $employee->id_user,
                                                )
                                                    ->where('status', 'active')
                                                    ->first();
                                            @endphp

                                            <div class="mb-1">
                                                <span class="badge badge-warning"
                                                    title="{{ $employee->outside_note }}">
                                                    Sedang Keluar
                                                </span>
                                                @if ($currentLog && $currentLog->is_overdue)
                                                    <span class="badge badge-danger">Terlambat</span>
                                                @endif
                                            </div>

                                            <div class="btn-group-vertical">
                                                <button class="btn btn-xs btn-info"
                                                    onclick="markReturnFromOutside({{ $employee->id_user }})">
                                                    <i class="fas fa-sign-in-alt"></i> Kembali
                                                </button>
                                                <button class="btn btn-xs btn-secondary"
                                                    onclick="showOutsideHistoryModal({{ $employee->id_user }})">
                                                    <i class="fas fa-history"></i> Riwayat
                                                </button>
                                                <button class="btn btn-xs btn-danger"
                                                    onclick="resetOutsideOffice({{ $employee->id_user }})">
                                                    <i class="fas fa-undo"></i> Reset
                                                </button>
                                            </div>
                                        @else
                                            <button class="btn btn-xs btn-warning"
                                                onclick="showOutsideOfficeModal({{ $employee->id_user }})">
                                                <i class="fas fa-sign-out-alt"></i> Set Keluar
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
<div class="card-tools">

    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalAbsensi">
        <i class="fas fa-plus"></i> Absen Manual
    </button>
</div>
<!-- Modal Check In -->
<div class="modal fade" id="modalCheckIn">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Check In</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.attendance.check-in') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="user_id_check_in">

                    <div class="form-group">
                        <label>Lokasi</label>
                        <input type="text" name="location" id="location_check_in" class="form-control" readonly
                            required>
                        <button type="button" class="btn btn-sm btn-info mt-2" onclick="getLocation()">
                            <i class="fas fa-map-marker-alt"></i> Ambil Lokasi
                        </button>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-success">Check In</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Check Out -->
<div class="modal fade" id="modalCheckOut">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Check Out</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.attendance.check-out') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="user_id_check_out">
                    <div class="form-group">
                        <label>Lokasi</label>
                        <input type="text" name="location" id="location_check_out" class="form-control" readonly
                            required>
                        <button type="button" class="btn btn-sm btn-info mt-2" onclick="getLocation('out')">
                            <i class="fas fa-map-marker-alt"></i> Ambil Lokasi
                        </button>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-danger">Check Out</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Set Outside Office -->
<div class="modal fade" id="modalOutsideOffice">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Set Izin Keluar Kantor</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.attendance.set-outside') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="user_id_outside">

                    <div class="form-group">
                        <label>Alasan Keluar <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" required rows="3"
                            placeholder="Contoh: Meeting dengan client, survey lokasi, dll"></textarea>
                        <small class="form-text text-muted">Minimal 5 karakter</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Waktu Mulai <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="start_time" class="form-control" required
                                    min="{{ date('Y-m-d\TH:i') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Waktu Kembali <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="end_time" class="form-control" required>
                                <small class="form-text text-muted">Maksimal 8 jam</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Estimasi Durasi</label>
                        <div class="input-group">
                            <input type="number" name="duration_hours" class="form-control" min="1"
                                max="8" placeholder="Jam">
                            <div class="input-group-append">
                                <span class="input-group-text">Jam</span>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Perhatian:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Keterlambatan kembali > 15 menit akan dikenakan penalty</li>
                            <li>Penalty bertingkat: 2% (15-30 mnt), 5% (30-60 mnt), 10% (1-2 jam), 15% (>2 jam)</li>
                            <li>Maksimal durasi izin keluar adalah 8 jam per hari</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Simpan Izin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="modalOutsideHistory">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Riwayat Izin Keluar - <span id="employee_name_history"></span></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="outside_history_content">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Loading...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Request Leave -->
<div class="modal fade" id="modalRequestLeave">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Permintaan Izin Tidak Masuk</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.attendance.request-leave') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Karyawan</label>
                        <select name="user_id" class="form-control" required>
                            <option value="">Pilih Karyawan</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id_user }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" name="date" class="form-control" required
                            min="{{ date('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label>Jenis Izin</label>
                        <select name="type" class="form-control" required>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                            <option value="cuti">Cuti</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="note" class="form-control" required rows="3"></textarea>
                    </div>
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
    function showCheckInModal(userId) {
        $('#user_id_check_in').val(userId);
        $('#modalCheckIn').modal('show');
    }

    function showCheckOutModal(userId) {
        $('#user_id_check_out').val(userId);
        $('#modalCheckOut').modal('show');
    }

    function showOutsideOfficeModal(userId) {
        $('#user_id_outside').val(userId);

        // Set default times (now + 1 hour)
        const now = new Date();
        const startTime = new Date(now.getTime());
        const endTime = new Date(now.getTime() + (2 * 60 * 60 * 1000)); // +2 hours default

        $('input[name="start_time"]').val(formatDateTimeLocal(startTime));
        $('input[name="end_time"]').val(formatDateTimeLocal(endTime));

        $('#modalOutsideOffice').modal('show');
    }

    function markReturnFromOutside(userId) {
        if (confirm('Konfirmasi kembali dari izin keluar kantor?')) {
            $.ajax({
                url: "{{ route('admin.attendance.outside-office.mark-return') }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    user_id: userId,
                    actual_return_time: new Date().toISOString()
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    alert('Terjadi kesalahan: ' + xhr.responseJSON?.message || 'Unknown error');
                }
            });
        }
    }

    function showOutsideHistoryModal(userId) {
        const employeeName = $(`tr:has(button[onclick="showOutsideHistoryModal(${userId})"])`).find('td:nth-child(2)')
            .text();
        $('#employee_name_history').text(employeeName);

        // Load history via AJAX
        $.ajax({
            url: "{{ route('admin.outside-office.history-ajax') }}",
            method: 'GET',
            data: {
                user_id: userId,
                limit: 10
            },
            success: function(response) {
                $('#outside_history_content').html(response.html);
            },
            error: function() {
                $('#outside_history_content').html(
                    '<div class="alert alert-danger">Gagal memuat riwayat</div>');
            }
        });

        $('#modalOutsideHistory').modal('show');
    }

    function formatDateTimeLocal(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    // Auto calculate end time when duration is changed
    $('input[name="duration_hours"]').on('input', function() {
        const startTime = $('input[name="start_time"]').val();
        const duration = parseInt($(this).val());

        if (startTime && duration) {
            const start = new Date(startTime);
            const end = new Date(start.getTime() + (duration * 60 * 60 * 1000));
            $('input[name="end_time"]').val(formatDateTimeLocal(end));
        }
    });

    // Auto calculate duration when times are changed
    $('input[name="start_time"], input[name="end_time"]').on('change', function() {
        const startTime = $('input[name="start_time"]').val();
        const endTime = $('input[name="end_time"]').val();

        if (startTime && endTime) {
            const start = new Date(startTime);
            const end = new Date(endTime);
            const diffHours = Math.round((end - start) / (1000 * 60 * 60));

            if (diffHours > 0 && diffHours <= 8) {
                $('input[name="duration_hours"]').val(diffHours);
            }
        }
    });

    function resetOutsideOffice(userId) {
        if (confirm('Apakah Anda yakin ingin mereset status keluar kantor? Ini akan menandai sebagai pelanggaran.')) {
            window.location.href = "{{ url('admin/attendance/reset-outside') }}/" + userId;
        }
    }

    function getLocation(type = 'in') {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const lat = position.coords.latitude;
                const long = position.coords.longitude;
                const location = lat + ',' + long;

                if (type === 'in') {
                    $('#location_check_in').val(location);
                } else {
                    $('#location_check_out').val(location);
                }
            });
        } else {
            alert("Geolocation is not supported by this browser.");
        }
    }

    function resetOutsideOffice(userId) {
        if (confirm('Apakah Anda yakin ingin mereset status keluar kantor?')) {
            window.location.href = "{{ url('admin/attendance/reset-outside') }}/" + userId;
        }
    }
</script>
