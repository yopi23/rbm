<!-- resources/views/admin/page/report-detail.blade.php -->


@section('monthly_report', 'active')
@section('main', 'menu-is-opening menu-open')


<div class="row">
    <!-- Summary Card -->
    <div class="col-md-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Laporan Detail: {{ $report->user->name }} -
                    {{ date('F Y', mktime(0, 0, 0, $report->month, 1, $report->year)) }}</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.employee.report-print', $report->id) }}" class="btn btn-sm btn-info"
                        target="_blank">
                        <i class="fas fa-print"></i> Cetak
                    </a>
                </div>
            </div>
            <!-- Add this near the top of the card-body -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <strong>Tipe Kompensasi:</strong>
                        {{ $report->compensation_type == 'fixed' ? 'Gaji Tetap' : 'Persentase Profit' }}
                        @if ($report->compensation_type == 'percentage')
                            ({{ $report->percentage_used }}% dari profit)
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-info"><i class="fas fa-calendar-check"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Kehadiran</span>
                                <span
                                    class="info-box-number">{{ $report->total_present_days }}/{{ $report->total_working_days }}
                                    hari</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-tools"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Service</span>
                                <span class="info-box-number">{{ $report->total_service_units }} unit</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-warning"><i class="fas fa-coins"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Komisi</span>
                                <span class="info-box-number">Rp
                                    {{ number_format($report->total_commission, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-primary"><i class="fas fa-money-bill-wave"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Gaji Akhir</span>
                                <span class="info-box-number">Rp
                                    {{ number_format($report->final_salary, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Detail -->
    <div class="col-md-12">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Detail Kehadiran</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Status</th>
                            <th>Keterlambatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attendances as $attendance)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($attendance->attendance_date)->format('d M Y') }}</td>
                                <td>{{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '-' }}
                                </td>
                                <td>{{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '-' }}
                                </td>
                                <td>
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
                                </td>
                                <td>{{ $attendance->late_minutes > 0 ? $attendance->late_minutes . ' menit' : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Service Detail -->
    <div class="col-md-12">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">Detail Service</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kode Service</th>
                            <th>Pelanggan</th>
                            <th>Unit</th>
                            <th>Total Biaya</th>
                            <th>Komisi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($services as $service)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($service->updated_at)->format('d M Y') }}</td>
                                <td>{{ $service->kode_service }}</td>
                                <td>{{ $service->nama_pelanggan }}</td>
                                <td>{{ $service->type_unit }}</td>
                                <td>Rp {{ number_format($service->total_biaya, 0, ',', '.') }}</td>
                                <td>Rp
                                    {{ number_format($service->total_biaya * ($report->user->salarySetting->service_percentage / 100), 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Violations Detail -->
    <div class="col-md-12">
        <div class="card card-outline card-danger">
            <div class="card-header">
                <h3 class="card-title">Detail Pelanggaran</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Tipe</th>
                            <th>Keterangan</th>
                            <th>Denda</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($violations as $violation)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($violation->violation_date)->format('d M Y') }}</td>
                                <td>
                                    @switch($violation->type)
                                        @case('telat')
                                            <span class="badge badge-warning">Terlambat</span>
                                        @break

                                        @case('alpha')
                                            <span class="badge badge-danger">Alpha</span>
                                        @break

                                        @case('kelalaian')
                                            <span class="badge badge-info">Kelalaian</span>
                                        @break

                                        @case('komplain')
                                            <span class="badge badge-secondary">Komplain</span>
                                        @break

                                        @default
                                            <span class="badge badge-dark">Lainnya</span>
                                    @endswitch
                                </td>
                                <td>{{ $violation->description }}</td>
                                <td>
                                    @if ($violation->penalty_amount)
                                        Rp {{ number_format($violation->penalty_amount, 0, ',', '.') }}
                                    @endif
                                    @if ($violation->penalty_percentage)
                                        {{ $violation->penalty_percentage }}%
                                    @endif
                                </td>
                                <td>
                                    @switch($violation->status)
                                        @case('pending')
                                            <span class="badge badge-warning">Pending</span>
                                        @break

                                        @case('processed')
                                            <span class="badge badge-success">Diproses</span>
                                        @break

                                        @case('forgiven')
                                            <span class="badge badge-info">Dimaafkan</span>
                                        @break
                                    @endswitch
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
