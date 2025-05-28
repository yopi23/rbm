<!-- resources/views/admin/page/attendance-detail.blade.php -->

@section('attendance_detail', 'active')
@section('main', 'menu-is-opening menu-open')

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Detail Absensi - {{ $attendance->user->name }}</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.attendance.history') }}">Riwayat Absensi</a>
                    </li>
                    <li class="breadcrumb-item active">Detail</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Navigation Buttons -->
    <div class="col-md-12 mb-3">
        <div class="btn-toolbar justify-content-between">
            <div class="btn-group">
                @if ($previousAttendance)
                    <a href="{{ route('admin.attendance.detail', $previousAttendance->id) }}" class="btn btn-secondary"
                        title="Record sebelumnya">
                        <i class="fas fa-chevron-left"></i> Sebelumnya
                        <small
                            class="d-block">{{ \Carbon\Carbon::parse($previousAttendance->attendance_date)->format('d/m/Y') }}</small>
                    </a>
                @endif
                @if ($nextAttendance)
                    <a href="{{ route('admin.attendance.detail', $nextAttendance->id) }}" class="btn btn-secondary ml-2"
                        title="Record selanjutnya">
                        Selanjutnya <i class="fas fa-chevron-right"></i>
                        <small
                            class="d-block">{{ \Carbon\Carbon::parse($nextAttendance->attendance_date)->format('d/m/Y') }}</small>
                    </a>
                @endif
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#modalEditAttendance">
                    <i class="fas fa-edit"></i> Edit Record
                </button>
                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#modalDeleteAttendance">
                    <i class="fas fa-trash"></i> Hapus Record
                </button>
                <a href="{{ route('admin.attendance.history') }}" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Main Info Card -->
    <div class="col-md-8">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle"></i> Informasi Absensi
                </h3>
                <div class="card-tools">
                    <span class="badge badge-info">ID: {{ $attendance->id }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td width="40%"><strong>Nama Karyawan:</strong></td>
                                <td>
                                    {{ $attendance->user->name }}
                                    @if ($attendance->user->userDetail && $attendance->user->userDetail->is_outside_office)
                                        <br><small class="badge badge-warning">Sedang Keluar Kantor</small>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td>{{ $attendance->user->email ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Jabatan:</strong></td>
                                <td>
                                    @if ($attendance->user->userDetail)
                                        @switch($attendance->user->userDetail->jabatan)
                                            @case(0)
                                                <span class="badge badge-dark">Super Admin</span>
                                            @break

                                            @case(1)
                                                <span class="badge badge-primary">Admin</span>
                                            @break

                                            @case(2)
                                                <span class="badge badge-success">Kasir</span>
                                            @break

                                            @case(3)
                                                <span class="badge badge-info">Teknisi</span>
                                            @break

                                            @default
                                                <span class="badge badge-secondary">Unknown</span>
                                        @endswitch
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Tanggal:</strong></td>
                                <td>
                                    <strong>{{ \Carbon\Carbon::parse($attendance->attendance_date)->format('d F Y') }}</strong>
                                    <br><small
                                        class="text-muted">{{ \Carbon\Carbon::parse($attendance->attendance_date)->format('l') }}</small>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    @switch($attendance->status)
                                        @case('hadir')
                                            <span class="badge badge-success badge-lg">
                                                <i class="fas fa-check"></i> Hadir
                                            </span>
                                        @break

                                        @case('izin')
                                            <span class="badge badge-warning badge-lg">
                                                <i class="fas fa-exclamation"></i> Izin
                                            </span>
                                        @break

                                        @case('sakit')
                                            <span class="badge badge-info badge-lg">
                                                <i class="fas fa-thermometer"></i> Sakit
                                            </span>
                                        @break

                                        @case('alpha')
                                            <span class="badge badge-danger badge-lg">
                                                <i class="fas fa-times"></i> Alpha
                                            </span>
                                        @break

                                        @case('libur')
                                            <span class="badge badge-secondary badge-lg">
                                                <i class="fas fa-calendar"></i> Libur
                                            </span>
                                        @break

                                        @case('cuti')
                                            <span class="badge badge-primary badge-lg">
                                                <i class="fas fa-plane"></i> Cuti
                                            </span>
                                        @break
                                    @endswitch
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td width="40%"><strong>Check In:</strong></td>
                                <td>
                                    @if ($attendance->check_in)
                                        <span class="text-success">
                                            <i class="fas fa-sign-in-alt"></i>
                                            <strong>{{ \Carbon\Carbon::parse($attendance->check_in)->format('H:i:s') }}</strong>
                                        </span>
                                        <br><small
                                            class="text-muted">{{ \Carbon\Carbon::parse($attendance->check_in)->format('d/m/Y H:i:s') }}</small>
                                    @else
                                        <span class="text-muted">Belum check in</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Check Out:</strong></td>
                                <td>
                                    @if ($attendance->check_out)
                                        <span class="text-danger">
                                            <i class="fas fa-sign-out-alt"></i>
                                            <strong>{{ \Carbon\Carbon::parse($attendance->check_out)->format('H:i:s') }}</strong>
                                        </span>
                                        <br><small
                                            class="text-muted">{{ \Carbon\Carbon::parse($attendance->check_out)->format('d/m/Y H:i:s') }}</small>
                                    @else
                                        <span class="text-muted">Belum check out</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Durasi Kerja:</strong></td>
                                <td>
                                    @if ($workDuration)
                                        <span class="text-primary">
                                            <i class="fas fa-clock"></i>
                                            <strong>{{ $workDuration->format('%H jam %I menit') }}</strong>
                                        </span>
                                        @if ($schedule)
                                            @php
                                                $scheduledHours = \Carbon\Carbon::parse(
                                                    $schedule->end_time,
                                                )->diffInHours(\Carbon\Carbon::parse($schedule->start_time));
                                                $actualHours = $workDuration->h + $workDuration->i / 60;
                                            @endphp
                                            <br><small class="text-muted">
                                                Target: {{ $scheduledHours }} jam
                                                @if ($actualHours >= $scheduledHours)
                                                    <span class="text-success">(✓ Memenuhi)</span>
                                                @else
                                                    <span class="text-warning">(⚠ Kurang
                                                        {{ round($scheduledHours - $actualHours, 1) }} jam)</span>
                                                @endif
                                            </small>
                                        @endif
                                    @else
                                        <span class="text-muted">Belum selesai / Tidak tersedia</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Keterlambatan:</strong></td>
                                <td>
                                    @if ($attendance->late_minutes > 0)
                                        <span class="badge badge-danger badge-lg">
                                            <i class="fas fa-clock"></i>
                                            @lateFormat{{ $attendance->late_minutes }} menit
                                        </span>
                                        @if ($attendance->late_minutes > 30)
                                            <br><small class="text-danger">⚠ Melebihi batas toleransi (30 menit)</small>
                                        @endif
                                    @else
                                        <span class="text-success">
                                            <i class="fas fa-check"></i> <strong>Tepat waktu</strong>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Metode Absen:</strong></td>
                                <td>
                                    @if ($attendance->created_by)
                                        <span class="badge badge-info">
                                            <i class="fas fa-user-cog"></i> Manual Admin
                                        </span>
                                    @elseif($attendance->location && strpos($attendance->location, 'Scanned by Admin') !== false)
                                        <span class="badge badge-warning">
                                            <i class="fas fa-qrcode"></i> QR Code Scan
                                        </span>
                                    @else
                                        <span class="badge badge-primary">
                                            <i class="fas fa-mobile-alt"></i> Mobile App
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if ($attendance->note)
                    <div class="alert alert-info mt-3">
                        <h6><i class="fas fa-sticky-note"></i> Catatan:</h6>
                        <p class="mb-0">{{ $attendance->note }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Photos Card -->
        @if ($attendance->photo_in || $attendance->photo_out)
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-camera"></i> Foto Absensi
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if ($attendance->photo_in)
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-sign-in-alt"></i> Foto Check In
                                            @if ($attendance->check_in)
                                                <small
                                                    class="float-right">{{ \Carbon\Carbon::parse($attendance->check_in)->format('H:i') }}</small>
                                            @endif
                                        </h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <a href="{{ asset('storage/' . $attendance->photo_in) }}" target="_blank"
                                            class="photo-link">
                                            <img src="{{ asset('storage/' . $attendance->photo_in) }}"
                                                class="img-fluid img-thumbnail photo-preview"
                                                style="max-height: 250px; cursor: pointer;" alt="Foto Check In">
                                        </a>
                                        <div class="mt-2">
                                            <a href="{{ asset('storage/' . $attendance->photo_in) }}" target="_blank"
                                                class="btn btn-sm btn-primary">
                                                <i class="fas fa-external-link-alt"></i> Buka Full Size
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if ($attendance->photo_out)
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-danger text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-sign-out-alt"></i> Foto Check Out
                                            @if ($attendance->check_out)
                                                <small
                                                    class="float-right">{{ \Carbon\Carbon::parse($attendance->check_out)->format('H:i') }}</small>
                                            @endif
                                        </h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <a href="{{ asset('storage/' . $attendance->photo_out) }}" target="_blank"
                                            class="photo-link">
                                            <img src="{{ asset('storage/' . $attendance->photo_out) }}"
                                                class="img-fluid img-thumbnail photo-preview"
                                                style="max-height: 250px; cursor: pointer;" alt="Foto Check Out">
                                        </a>
                                        <div class="mt-2">
                                            <a href="{{ asset('storage/' . $attendance->photo_out) }}"
                                                target="_blank" class="btn btn-sm btn-primary">
                                                <i class="fas fa-external-link-alt"></i> Buka Full Size
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Timeline Card -->
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history"></i> Timeline Absensi
                </h3>
            </div>
            <div class="card-body">
                <div class="timeline">
                    @if ($attendance->check_in)
                        <div class="time-label">
                            <span
                                class="bg-success">{{ \Carbon\Carbon::parse($attendance->check_in)->format('H:i') }}</span>
                        </div>
                        <div>
                            <i class="fas fa-sign-in-alt bg-success"></i>
                            <div class="timeline-item">
                                <span class="time">
                                    <i class="fas fa-clock"></i>
                                    {{ \Carbon\Carbon::parse($attendance->check_in)->format('H:i:s') }}
                                </span>
                                <h3 class="timeline-header">Check In</h3>
                                <div class="timeline-body">
                                    Karyawan melakukan check in
                                    @if ($attendance->late_minutes > 0)
                                        dengan keterlambatan @lateFormat($attendance->late_minutes)
                                    @else
                                        tepat waktu
                                    @endif
                                    @if ($attendance->location)
                                        <br><small class="text-muted">Lokasi: {{ $attendance->location }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($attendance->check_out)
                        <div class="time-label">
                            <span
                                class="bg-danger">{{ \Carbon\Carbon::parse($attendance->check_out)->format('H:i') }}</span>
                        </div>
                        <div>
                            <i class="fas fa-sign-out-alt bg-danger"></i>
                            <div class="timeline-item">
                                <span class="time">
                                    <i class="fas fa-clock"></i>
                                    {{ \Carbon\Carbon::parse($attendance->check_out)->format('H:i:s') }}
                                </span>
                                <h3 class="timeline-header">Check Out</h3>
                                <div class="timeline-body">
                                    Karyawan melakukan check out
                                    @if ($workDuration)
                                        setelah bekerja selama {{ $workDuration->format('%H jam %I menit') }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <div>
                        <i class="fas fa-clock bg-gray"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Side Info Cards -->
    <div class="col-md-4">
        <!-- Schedule Info -->
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-calendar-alt"></i> Jadwal Kerja
                </h3>
            </div>
            <div class="card-body">
                @if ($schedule)
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td width="50%">Hari:</td>
                            <td><strong>{{ \Carbon\Carbon::parse($attendance->attendance_date)->format('l') }}</strong>
                            </td>
                        </tr>
                        <tr>
                            <td>Jam Masuk:</td>
                            <td><strong>{{ $schedule->start_time }}</strong></td>
                        </tr>
                        <tr>
                            <td>Jam Pulang:</td>
                            <td><strong>{{ $schedule->end_time }}</strong></td>
                        </tr>
                        <tr>
                            <td>Status:</td>
                            <td>
                                @if ($schedule->is_working_day)
                                    <span class="badge badge-success">Hari Kerja</span>
                                @else
                                    <span class="badge badge-secondary">Libur</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Durasi Target:</td>
                            <td>
                                @php
                                    $scheduledHours = \Carbon\Carbon::parse($schedule->end_time)->diffInHours(
                                        \Carbon\Carbon::parse($schedule->start_time),
                                    );
                                @endphp
                                <strong>{{ $scheduledHours }} jam</strong>
                            </td>
                        </tr>
                    </table>
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Tidak ada jadwal kerja untuk hari ini
                    </div>
                @endif
            </div>
        </div>

        <!-- Location Info -->
        @if ($attendance->location)
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-map-marker-alt"></i> Informasi Lokasi
                    </h3>
                </div>
                <div class="card-body">
                    @if (strpos($attendance->location, 'Scanned by Admin') !== false)
                        <div class="alert alert-info mb-2">
                            <i class="fas fa-qrcode"></i> <strong>QR Code Scan oleh Admin</strong>
                        </div>
                        <p class="text-muted mb-0">{{ $attendance->location }}</p>
                    @elseif(strpos($attendance->location, ',') !== false)
                        @php
                            $coords = explode(',', $attendance->location);
                            $lat = trim($coords[0]);
                            $lng = trim($coords[1]);
                        @endphp
                        <div class="text-center mb-3">
                            <a href="https://maps.google.com/?q={{ $attendance->location }}" target="_blank"
                                class="btn btn-primary btn-block">
                                <i class="fas fa-map-marker-alt"></i> Buka di Google Maps
                            </a>
                        </div>
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td width="40%">Latitude:</td>
                                <td><code>{{ $lat }}</code></td>
                            </tr>
                            <tr>
                                <td>Longitude:</td>
                                <td><code>{{ $lng }}</code></td>
                            </tr>
                        </table>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                Klik tombol di atas untuk melihat lokasi di peta
                            </small>
                        </div>
                    @else
                        <p class="mb-0">{{ $attendance->location }}</p>
                    @endif
                </div>
            </div>
        @endif

        <!-- Violations Info -->
        @if ($violations->count() > 0)
            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle"></i> Pelanggaran Terkait
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-danger">{{ $violations->count() }}</span>
                    </div>
                </div>
                <div class="card-body">
                    @foreach ($violations as $violation)
                        <div class="alert alert-danger">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">
                                        @switch($violation->type)
                                            @case('telat')
                                                <i class="fas fa-clock"></i> Terlambat
                                            @break

                                            @case('alpha')
                                                <i class="fas fa-user-times"></i> Alpha
                                            @break

                                            @case('kelalaian')
                                                <i class="fas fa-exclamation-circle"></i> Kelalaian
                                            @break

                                            @case('komplain')
                                                <i class="fas fa-comment-slash"></i> Komplain
                                            @break

                                            @default
                                                <i class="fas fa-exclamation-triangle"></i> Lainnya
                                        @endswitch
                                    </h6>
                                    <p class="mb-1">{{ $violation->description }}</p>
                                    @if ($violation->penalty_amount || $violation->penalty_percentage)
                                        <small class="text-muted">
                                            <i class="fas fa-money-bill-wave"></i> Denda:
                                            @if ($violation->penalty_amount)
                                                Rp {{ number_format($violation->penalty_amount, 0, ',', '.') }}
                                            @endif
                                            @if ($violation->penalty_percentage)
                                                {{ $violation->penalty_percentage }}%
                                            @endif
                                        </small>
                                    @endif
                                </div>
                                <span
                                    class="badge badge-{{ $violation->status == 'processed' ? 'success' : ($violation->status == 'forgiven' ? 'info' : 'warning') }}">
                                    {{ ucfirst($violation->status) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Performance Summary -->
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-line"></i> Ringkasan Performa
                </h3>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-right">
                            <h4 class="text-{{ $attendance->status == 'hadir' ? 'success' : 'danger' }}">
                                @if ($attendance->status == 'hadir')
                                    <i class="fas fa-check-circle"></i>
                                @else
                                    <i class="fas fa-times-circle"></i>
                                @endif
                            </h4>
                            <small>Kehadiran</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h4 class="text-{{ $attendance->late_minutes > 0 ? 'warning' : 'success' }}">
                            @if ($attendance->late_minutes > 0)
                                <i class="fas fa-clock"></i>
                            @else
                                <i class="fas fa-thumbs-up"></i>
                            @endif
                        </h4>
                        <small>Ketepatan</small>
                    </div>
                </div>
                <hr>
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-right">
                            <h4 class="text-{{ $workDuration ? 'info' : 'muted' }}">
                                @if ($workDuration)
                                    {{ $workDuration->format('%H:%I') }}
                                @else
                                    --:--
                                @endif
                            </h4>
                            <small>Durasi Kerja</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h4 class="text-{{ $violations->count() > 0 ? 'danger' : 'success' }}">
                            {{ $violations->count() }}
                        </h4>
                        <small>Pelanggaran</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Record Info -->
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info"></i> Info Record
                </h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td width="50%">Record ID:</td>
                        <td><code>{{ $attendance->id }}</code></td>
                    </tr>
                    <tr>
                        <td>Dibuat:</td>
                        <td>
                            <strong>{{ $attendance->created_at->format('d/m/Y H:i') }}</strong>
                            <br><small class="text-muted">{{ $attendance->created_at->diffForHumans() }}</small>
                        </td>
                    </tr>
                    <tr>
                        <td>Terakhir Update:</td>
                        <td>
                            <strong>{{ $attendance->updated_at->format('d/m/Y H:i') }}</strong>
                            <br><small class="text-muted">{{ $attendance->updated_at->diffForHumans() }}</small>
                        </td>
                    </tr>
                    @if ($attendance->created_by)
                        <tr>
                            <td>Dibuat Oleh:</td>
                            <td>
                                <span class="badge badge-info">
                                    <i class="fas fa-user-shield"></i> Admin
                                </span>
                            </td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>

        <!-- Action Buttons (Mobile) -->
        <div class="d-md-none">
            <div class="card">
                <div class="card-body">
                    <div class="btn-group-vertical btn-block">
                        <button type="button" class="btn btn-warning" data-toggle="modal"
                            data-target="#modalEditAttendance">
                            <i class="fas fa-edit"></i> Edit Record
                        </button>
                        <button type="button" class="btn btn-danger" data-toggle="modal"
                            data-target="#modalDeleteAttendance">
                            <i class="fas fa-trash"></i> Hapus Record
                        </button>
                        <a href="{{ route('admin.attendance.history') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Kembali ke Riwayat
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Attendance -->
<div class="modal fade" id="modalEditAttendance">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-edit"></i> Edit Record Absensi - {{ $attendance->user->name }}
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.attendance.update') }}" method="POST" id="formEditAttendance">
                @csrf
                <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Perhatian:</strong> Perubahan pada record absensi akan dicatat dalam log sistem.
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Check In <i class="fas fa-sign-in-alt text-success"></i></label>
                                <input type="time" name="check_in" class="form-control"
                                    value="{{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '' }}"
                                    id="check_in_time">
                                <small class="form-text text-muted">
                                    Kosongkan jika tidak ada check in
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Check Out <i class="fas fa-sign-out-alt text-danger"></i></label>
                                <input type="time" name="check_out" class="form-control"
                                    value="{{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '' }}"
                                    id="check_out_time">
                                <small class="form-text text-muted">
                                    Kosongkan jika tidak ada check out
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Status Kehadiran <span class="text-danger">*</span></label>
                        <select name="status" class="form-control" required id="status_select">
                            <option value="hadir" {{ $attendance->status == 'hadir' ? 'selected' : '' }}>
                                <i class="fas fa-check"></i> Hadir
                            </option>
                            <option value="izin" {{ $attendance->status == 'izin' ? 'selected' : '' }}>
                                <i class="fas fa-exclamation"></i> Izin
                            </option>
                            <option value="sakit" {{ $attendance->status == 'sakit' ? 'selected' : '' }}>
                                <i class="fas fa-thermometer"></i> Sakit
                            </option>
                            <option value="alpha" {{ $attendance->status == 'alpha' ? 'selected' : '' }}>
                                <i class="fas fa-times"></i> Alpha
                            </option>
                            <option value="cuti" {{ $attendance->status == 'cuti' ? 'selected' : '' }}>
                                <i class="fas fa-plane"></i> Cuti
                            </option>
                            <option value="libur" {{ $attendance->status == 'libur' ? 'selected' : '' }}>
                                <i class="fas fa-calendar"></i> Libur
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Catatan/Keterangan</label>
                        <textarea name="note" class="form-control" rows="3" placeholder="Tambahkan catatan jika diperlukan...">{{ $attendance->note }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Alasan Perubahan <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="2" required
                            placeholder="Jelaskan alasan mengapa record ini diubah (minimal 10 karakter)..."></textarea>
                        <small class="form-text text-muted">
                            Alasan ini akan dicatat dalam log sistem untuk audit trail.
                        </small>
                    </div>

                    <!-- Preview Perubahan -->
                    <div class="card bg-light">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-eye"></i> Preview Perubahan</h6>
                        </div>
                        <div class="card-body" id="preview-changes">
                            <small class="text-muted">Pratinjau perubahan akan muncul di sini saat Anda mengubah
                                data...</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Update Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Delete Attendance -->
<div class="modal fade" id="modalDeleteAttendance">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h4 class="modal-title text-white">
                    <i class="fas fa-trash"></i> Hapus Record Absensi
                </h4>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.attendance.delete') }}" method="POST" id="formDeleteAttendance">
                @csrf
                @method('DELETE')
                <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Peringatan!</strong> Tindakan ini tidak dapat dibatalkan dan akan menghapus semua data
                        terkait.
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Data yang akan dihapus:</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="40%"><strong>Karyawan:</strong></td>
                                    <td>{{ $attendance->user->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Tanggal:</strong></td>
                                    <td>{{ \Carbon\Carbon::parse($attendance->attendance_date)->format('d F Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge badge-secondary">{{ ucfirst($attendance->status) }}</span>
                                    </td>
                                </tr>
                                @if ($attendance->check_in)
                                    <tr>
                                        <td><strong>Check In:</strong></td>
                                        <td>{{ \Carbon\Carbon::parse($attendance->check_in)->format('H:i:s') }}</td>
                                    </tr>
                                @endif
                                @if ($attendance->check_out)
                                    <tr>
                                        <td><strong>Check Out:</strong></td>
                                        <td>{{ \Carbon\Carbon::parse($attendance->check_out)->format('H:i:s') }}</td>
                                    </tr>
                                @endif
                            </table>

                            @if ($attendance->photo_in || $attendance->photo_out)
                                <div class="alert alert-info">
                                    <i class="fas fa-image"></i>
                                    <strong>Foto akan ikut terhapus:</strong>
                                    @if ($attendance->photo_in)
                                        Foto Check In
                                    @endif
                                    @if ($attendance->photo_in && $attendance->photo_out)
                                        &
                                    @endif
                                    @if ($attendance->photo_out)
                                        Foto Check Out
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="form-group mt-3">
                        <label>Alasan Penghapusan <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required
                            placeholder="Jelaskan alasan mengapa record ini dihapus (minimal 10 karakter)..."></textarea>
                        <small class="form-text text-muted">
                            Alasan ini akan dicatat dalam log sistem untuk audit trail.
                        </small>
                    </div>

                    <div class="form-check mt-3">
                        <input type="checkbox" class="form-check-input" id="confirmDelete" required>
                        <label class="form-check-label" for="confirmDelete">
                            Saya memahami bahwa tindakan ini tidak dapat dibatalkan
                        </label>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-danger" id="btnConfirmDelete" disabled>
                        <i class="fas fa-trash"></i> Hapus Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Image Preview -->
<div class="modal fade" id="modalImagePreview">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="imageModalTitle">Preview Foto</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" class="img-fluid" style="max-height: 80vh;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <a id="downloadImageBtn" href="" target="_blank" class="btn btn-primary">
                    <i class="fas fa-download"></i> Download
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    .table-borderless td {
        border: none;
        padding: 0.5rem 0.75rem;
    }

    .photo-preview {
        cursor: pointer;
        transition: transform 0.2s ease-in-out;
        border: 2px solid #dee2e6;
    }

    .photo-preview:hover {
        transform: scale(1.05);
        border-color: #007bff;
    }

    .badge-lg {
        font-size: 0.9em;
        padding: 0.5rem 0.75rem;
    }

    .timeline {
        position: relative;
        margin: 0 0 30px 0;
        padding: 0;
        list-style: none;
    }

    .timeline:before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        left: 31px;
        width: 2px;
        background: #ddd;
    }

    .timeline>li {
        position: relative;
        margin-right: 10px;
        margin-bottom: 15px;
    }

    .timeline>li:before,
    .timeline>li:after {
        content: " ";
        display: table;
    }

    .timeline>li:after {
        clear: both;
    }

    .timeline>li>.timeline-item {
        margin-left: 65px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 3px;
        padding: 10px;
        position: relative;
    }

    .timeline>li>.timeline-item:before {
        content: '';
        position: absolute;
        left: -15px;
        top: 10px;
        width: 0;
        height: 0;
        border-top: 15px solid transparent;
        border-bottom: 15px solid transparent;
        border-right: 15px solid #ddd;
    }

    .timeline>li>.timeline-item:after {
        content: '';
        position: absolute;
        left: -14px;
        top: 10px;
        width: 0;
        height: 0;
        border-top: 15px solid transparent;
        border-bottom: 15px solid transparent;
        border-right: 15px solid #fff;
    }

    .timeline>li>.fa,
    .timeline>li>.fas,
    .timeline>li>.far,
    .timeline>li>.fab,
    .timeline>li>.fal,
    .timeline>li>.fad {
        width: 30px;
        height: 30px;
        font-size: 15px;
        line-height: 30px;
        position: absolute;
        color: #666;
        background: #ddd;
        border-radius: 50%;
        text-align: center;
        left: 18px;
        top: 0;
    }

    .timeline>.time-label>span {
        font-weight: 600;
        padding: 2px 5px;
        display: inline-block;
        background-color: #fff;
        border-radius: 4px;
        border: 1px solid #ddd;
        color: #333;
        font-size: 11px;
        text-transform: uppercase;
        margin-left: 0;
    }

    .timeline-header {
        margin-top: 0;
        color: #555;
        border-bottom: 1px solid #f4f4f4;
        padding-bottom: 5px;
        margin-bottom: 10px;
        line-height: 1.1;
        font-size: 16px;
    }

    .timeline-body,
    .timeline-footer {
        padding-top: 10px;
    }

    .time {
        color: #999;
        float: right;
        padding: 5px;
        font-size: 12px;
    }

    .card-outline.card-primary {
        border-top: 3px solid #007bff;
    }

    .card-outline.card-success {
        border-top: 3px solid #28a745;
    }

    .card-outline.card-info {
        border-top: 3px solid #17a2b8;
    }

    .card-outline.card-warning {
        border-top: 3px solid #ffc107;
    }

    .card-outline.card-danger {
        border-top: 3px solid #dc3545;
    }

    .card-outline.card-secondary {
        border-top: 3px solid #6c757d;
    }

    .btn-toolbar {
        flex-wrap: wrap;
    }

    @media (max-width: 768px) {
        .btn-toolbar {
            flex-direction: column;
            align-items: stretch;
        }

        .btn-group {
            margin-bottom: 0.5rem;
            width: 100%;
        }

        .btn-group .btn {
            flex: 1;
        }

        .timeline>li>.timeline-item {
            margin-left: 45px;
        }

        .timeline:before {
            left: 21px;
        }

        .timeline>li>.fa,
        .timeline>li>.fas,
        .timeline>li>.far,
        .timeline>li>.fab,
        .timeline>li>.fal,
        .timeline>li>.fad {
            left: 8px;
        }
    }

    .bg-gradient-success {
        background: linear-gradient(87deg, #2dce89 0, #2dcecc 100%) !important;
    }

    .bg-gradient-danger {
        background: linear-gradient(87deg, #f5365c 0, #f56036 100%) !important;
    }

    .bg-gradient-info {
        background: linear-gradient(87deg, #11cdef 0, #1171ef 100%) !important;
    }

    .alert {
        margin-bottom: 1rem;
    }

    .alert:last-child {
        margin-bottom: 0;
    }

    .border-right {
        border-right: 1px solid #e9ecef !important;
    }

    /* Custom styles for better UX */
    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .btn:focus {
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .modal-content {
        border-radius: 0.5rem;
    }

    .card {
        border-radius: 0.5rem;
    }

    .photo-link:hover {
        text-decoration: none;
    }

    #preview-changes {
        max-height: 200px;
        overflow-y: auto;
    }
</style>

<script>
    $(document).ready(function() {
        // Image preview functionality
        $('.photo-preview').on('click', function() {
            var src = $(this).attr('src');
            var alt = $(this).attr('alt');

            $('#modalImage').attr('src', src);
            $('#imageModalTitle').text(alt);
            $('#downloadImageBtn').attr('href', src);
            $('#modalImagePreview').modal('show');
        });

        // Confirm delete checkbox
        $('#confirmDelete').on('change', function() {
            $('#btnConfirmDelete').prop('disabled', !this.checked);
        });

        // Form validation for edit
        $('#formEditAttendance').on('submit', function(e) {
            var reason = $('textarea[name="reason"]').val().trim();
            if (reason.length < 10) {
                e.preventDefault();
                alert('Alasan perubahan harus diisi minimal 10 karakter');
                $('textarea[name="reason"]').focus();
                return false;
            }

            // Confirm before submit
            if (!confirm('Apakah Anda yakin ingin mengubah record absensi ini?')) {
                e.preventDefault();
                return false;
            }
        });

        // Form validation for delete
        $('#formDeleteAttendance').on('submit', function(e) {
            var reason = $(this).find('textarea[name="reason"]').val().trim();
            if (reason.length < 10) {
                e.preventDefault();
                alert('Alasan penghapusan harus diisi minimal 10 karakter');
                $(this).find('textarea[name="reason"]').focus();
                return false;
            }

            if (!confirm(
                    'Apakah Anda benar-benar yakin ingin menghapus record ini? Tindakan ini tidak dapat dibatalkan!'
                )) {
                e.preventDefault();
                return false;
            }
        });

        // Preview changes functionality
        function updatePreview() {
            var preview = $('#preview-changes');
            var changes = [];

            // Check for changes in check in
            var originalCheckIn =
                '{{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '' }}';
            var newCheckIn = $('#check_in_time').val();
            if (originalCheckIn !== newCheckIn) {
                changes.push(
                    `<strong>Check In:</strong> ${originalCheckIn || 'Kosong'} → ${newCheckIn || 'Kosong'}`);
            }

            // Check for changes in check out
            var originalCheckOut =
                '{{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '' }}';
            var newCheckOut = $('#check_out_time').val();
            if (originalCheckOut !== newCheckOut) {
                changes.push(
                    `<strong>Check Out:</strong> ${originalCheckOut || 'Kosong'} → ${newCheckOut || 'Kosong'}`
                );
            }

            // Check for changes in status
            var originalStatus = '{{ $attendance->status }}';
            var newStatus = $('#status_select').val();
            if (originalStatus !== newStatus) {
                changes.push(`<strong>Status:</strong> ${originalStatus} → ${newStatus}`);
            }

            if (changes.length > 0) {
                preview.html(
                    '<div class="alert alert-warning"><h6>Perubahan yang akan dilakukan:</h6><ul class="mb-0"><li>' +
                    changes.join('</li><li>') + '</li></ul></div>');
            } else {
                preview.html('<small class="text-muted">Belum ada perubahan...</small>');
            }
        }

        // Update preview on input change
        $('#check_in_time, #check_out_time, #status_select').on('change', updatePreview);

        // Initialize tooltips
        $('[title]').tooltip();

        // Auto-hide alerts after 5 seconds
        $('.alert').not('.alert-warning, .alert-danger').delay(5000).fadeOut();

        // Smooth scroll for timeline
        $('.timeline').scrollTop(0);

        // Enhanced form validation with real-time feedback
        $('textarea[name="reason"]').on('input', function() {
            var length = $(this).val().trim().length;
            var feedback = $(this).siblings('.invalid-feedback');

            if (length < 10) {
                $(this).addClass('is-invalid').removeClass('is-valid');
                if (feedback.length === 0) {
                    $(this).after('<div class="invalid-feedback">Minimal 10 karakter diperlukan</div>');
                }
            } else {
                $(this).addClass('is-valid').removeClass('is-invalid');
                feedback.remove();
            }
        });
    });
</script>
