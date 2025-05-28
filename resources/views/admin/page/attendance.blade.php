<!-- resources/views/admin/page/attendance.blade.php -->

@section('attendance', 'active')
@section('main', 'menu-is-opening menu-open')

<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-success">
            <div class="card-header">
                <div class="card-title">
                    Absensi Karyawan - {{ \Carbon\Carbon::today()->format('d F Y') }}
                </div>
                <!-- Tambahkan ini ke dalam attendance.blade.php di bagian card-tools -->

                <div class="card-tools">
                    <div class="btn-group mr-2">
                        <!-- Link ke Riwayat Absensi -->
                        <a href="{{ route('admin.attendance.history') }}" class="btn btn-info btn-sm">
                            <i class="fas fa-history"></i> Riwayat Absensi
                        </a>

                        <!-- Export Hari Ini -->
                        <a href="{{ route('admin.attendance.export', ['view_type' => 'daily', 'date' => date('Y-m-d')]) }}"
                            class="btn btn-success btn-sm">
                            <i class="fas fa-download"></i> Export Hari Ini
                        </a>
                    </div>

                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                        data-target="#modalAbsensi">
                        <i class="fas fa-plus"></i> Absen Manual
                    </button>
                </div>

                <!-- Atau tambahkan statistik quick di atas tabel attendance.blade.php -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-success">
                                <i class="fas fa-user-check"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Hadir Hari Ini</span>
                                <span
                                    class="info-box-number">{{ $attendances->where('status', 'hadir')->count() }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-warning">
                                <i class="fas fa-clock"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Terlambat</span>
                                <span
                                    class="info-box-number">{{ $attendances->where('late_minutes', '>', 0)->count() }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-danger">
                                <i class="fas fa-user-times"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Belum Absen</span>
                                <span class="info-box-number">{{ $employees->count() - $attendances->count() }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-info">
                                <i class="fas fa-history"></i>
                            </span>
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
                                            <span class="badge badge-warning" title="{{ $employee->outside_note }}">
                                                Sedang Keluar
                                            </span>
                                            <button class="btn btn-xs btn-info"
                                                onclick="resetOutsideOffice({{ $employee->id_user }})">
                                                Reset
                                            </button>
                                        @else
                                            <button class="btn btn-xs btn-warning"
                                                onclick="showOutsideOfficeModal({{ $employee->id_user }})">
                                                Set Keluar
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
                        <label>Foto</label>
                        <input type="file" name="photo" class="form-control-file" accept="image/*"
                            capture="camera" required>
                    </div>
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
                <h4 class="modal-title">Set Keluar Kantor</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.attendance.set-outside') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="user_id_outside">
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="note" class="form-control" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Waktu Mulai</label>
                        <input type="datetime-local" name="start_time" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Waktu Selesai (Opsional)</label>
                        <input type="datetime-local" name="end_time" class="form-control">
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-warning">Simpan</button>
                </div>
            </form>
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
        $('#modalOutsideOffice').modal('show');
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
