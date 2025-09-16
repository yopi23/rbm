{{-- @extends('admin.layout.main') --}}

{{-- @section('content') --}}

@section('attendance_history', 'active')
@section('main', 'menu-is-opening menu-open')

<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-info collapsed-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-filter"></i> Filter Riwayat Absensi
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body" style="display: none;">
                <form method="GET" action="{{ route('admin.attendance.history') }}" class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Tampilan</label>
                            <select name="view_type" class="form-control" onchange="toggleDateFilter()">
                                <option value="monthly" {{ $viewType == 'monthly' ? 'selected' : '' }}>Bulanan</option>
                                <option value="daily" {{ $viewType == 'daily' ? 'selected' : '' }}>Harian</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3" id="monthly-filter" style="{{ $viewType == 'daily' ? 'display:none' : '' }}">
                        <div class="form-group">
                            <label>Bulan/Tahun</label>
                            <div class="row">
                                <div class="col-6">
                                    <select name="month" class="form-control">
                                        @for ($i = 1; $i <= 12; $i++)
                                            <option value="{{ $i }}"
                                                {{ $selectedMonth == $i ? 'selected' : '' }}>
                                                {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-6">
                                    <select name="year" class="form-control">
                                        @for ($i = date('Y'); $i >= date('Y') - 3; $i--)
                                            <option value="{{ $i }}"
                                                {{ $selectedYear == $i ? 'selected' : '' }}>
                                                {{ $i }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-2" id="daily-filter" style="{{ $viewType == 'monthly' ? 'display:none' : '' }}">
                        <div class="form-group">
                            <label>Tanggal</label>
                            <input type="date" name="date" class="form-control" value="{{ $selectedDate }}">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Karyawan</label>
                            <select name="employee_id" class="form-control">
                                <option value="">Semua Karyawan</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id_user }}"
                                        {{ $selectedEmployee == $employee->id_user ? 'selected' : '' }}>
                                        {{ $employee->name }}
                                        @switch($employee->jabatan)
                                            @case(2)
                                                (Kasir)
                                            @break

                                            @case(3)
                                                (Teknisi)
                                            @break
                                        @endswitch
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label> </label><br>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="{{ route('admin.attendance.history') }}" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['total_attendance'] }}</h3>
                <p>Total Record</p>
            </div>
            <div class="icon">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['present_count'] }}</h3>
                <p>Hadir</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-check"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['late_count'] }}</h3>
                <p>Terlambat</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $stats['absent_count'] }}</h3>
                <p>Tidak Hadir</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-times"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">Tingkat Kehadiran</h3>
            </div>
            <div class="card-body">
                <div class="progress mb-3">
                    <div class="progress-bar bg-success" role="progressbar"
                        style="width: {{ $stats['attendance_rate'] }}%"
                        aria-valuenow="{{ $stats['attendance_rate'] }}" aria-valuemin="0" aria-valuamax="100">
                        {{ number_format($stats['attendance_rate'], 2) }}%
                    </div>
                </div>
                <p class="mb-0">
                    <strong>{{ number_format($stats['attendance_rate'], 2) }}%</strong> tingkat kehadiran
                    @if ($stats['avg_late_minutes'] > 0)
                        <br><small class="text-muted">Rata-rata keterlambatan: {{ $stats['avg_late_minutes'] }}
                            menit</small>
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-body">
                <a href="{{ route('admin.attendance.index') }}" class="btn btn-primary mb-2">
                    <i class="fas fa-calendar-day"></i> Absensi Hari Ini
                </a>
                <a href="{{ route('admin.employee.monthly-report') }}" class="btn btn-success mb-2">
                    <i class="fas fa-chart-bar"></i> Laporan Bulanan
                </a>
                <form method="GET" action="{{ route('admin.attendance.export') }}" style="display: inline;">
                    <input type="hidden" name="employee_id" value="{{ $selectedEmployee }}">
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                    <input type="hidden" name="view_type" value="{{ $viewType }}">
                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                    <button type="submit" class="btn btn-warning mb-2">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-history"></i> Riwayat Absensi
                    @if ($selectedEmployee)
                        - {{ $employees->where('id_user', $selectedEmployee)->first()->name ?? 'Karyawan' }}
                    @endif
                    @if ($viewType == 'daily')
                        - {{ \Carbon\Carbon::parse($selectedDate)->format('d F Y') }}
                    @else
                        - {{ date('F', mktime(0, 0, 0, $selectedMonth, 1)) }} {{ $selectedYear }}
                    @endif
                </div>
                <div class="card-tools">
                    <span class="badge badge-info">{{ $attendances->total() }} record</span>
                </div>
            </div>
            <div class="card-body">
                @if ($attendances->count() > 0)
                    <div class="table-responsive">
                        {{-- PENAMBAHAN ID PADA TABEL --}}
                        <table id="attendance_history_table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="15%">Nama</th>
                                    <th width="10%">Jabatan</th>
                                    <th width="10%">Tanggal</th>
                                    <th width="8%">Check In</th>
                                    <th width="8%">Check Out</th>
                                    <th width="8%">Status</th>
                                    <th width="8%">Terlambat</th>
                                    <th width="15%">Lokasi</th>
                                    <th width="13%">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($attendances as $attendance)
                                    <tr>
                                        <td>{{ ($attendances->currentPage() - 1) * $attendances->perPage() + $loop->iteration }}
                                        </td>
                                        <td>
                                            <strong>{{ $attendance->user->name }}</strong>
                                            @if ($attendance->user->userDetail && $attendance->user->userDetail->is_outside_office)
                                                <br><small class="badge badge-warning">Sedang Keluar</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($attendance->user->userDetail)
                                                @switch($attendance->user->userDetail->jabatan)
                                                    @case(2)
                                                        <span class="badge badge-success">Kasir</span>
                                                    @break

                                                    @case(3)
                                                        <span class="badge badge-info">Teknisi</span>
                                                    @break
                                                @endswitch
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($attendance->attendance_date)->format('d/m/Y') }}
                                        </td>
                                        <td>
                                            @if ($attendance->check_in)
                                                <span class="text-success">
                                                    <i class="fas fa-sign-in-alt"></i>
                                                    {{ \Carbon\Carbon::parse($attendance->check_in)->format('H:i') }}
                                                </span>
                                                @if ($attendance->photo_in)
                                                    <br><a href="{{ asset('storage/' . $attendance->photo_in) }}"
                                                        target="_blank" class="text-primary">
                                                        <i class="fas fa-image"></i> Foto
                                                    </a>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($attendance->check_out)
                                                <span class="text-danger">
                                                    <i class="fas fa-sign-out-alt"></i>
                                                    {{ \Carbon\Carbon::parse($attendance->check_out)->format('H:i') }}
                                                </span>
                                                @if ($attendance->photo_out)
                                                    <br><a href="{{ asset('storage/' . $attendance->photo_out) }}"
                                                        target="_blank" class="text-primary">
                                                        <i class="fas fa-image"></i> Foto
                                                    </a>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @switch($attendance->status)
                                                @case('hadir')
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check"></i> Hadir
                                                    </span>
                                                @break

                                                @case('izin')
                                                    <span class="badge badge-warning">
                                                        <i class="fas fa-exclamation"></i> Izin
                                                    </span>
                                                @break

                                                @case('sakit')
                                                    <span class="badge badge-info">
                                                        <i class="fas fa-thermometer"></i> Sakit
                                                    </span>
                                                @break

                                                @case('alpha')
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-times"></i> Alpha
                                                    </span>
                                                @break

                                                @case('libur')
                                                    <span class="badge badge-secondary">
                                                        <i class="fas fa-calendar"></i> Libur
                                                    </span>
                                                @break

                                                @case('cuti')
                                                    <span class="badge badge-primary">
                                                        <i class="fas fa-plane"></i> Cuti
                                                    </span>
                                                @break
                                            @endswitch
                                        </td>
                                        <td>
                                            @if ($attendance->late_minutes > 0)
                                                <span class="badge badge-danger">
                                                    <i class="fas fa-clock"></i>
                                                    {{ $attendance->late_minutes }} menit
                                                </span>
                                            @else
                                                <span class="text-success">
                                                    <i class="fas fa-check"></i> Tepat waktu
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($attendance->location)
                                                @if (strpos($attendance->location, 'Scanned by Admin') !== false)
                                                    <span class="badge badge-info">
                                                        <i class="fas fa-qrcode"></i> QR Scan
                                                    </span>
                                                    <br><small class="text-muted">{{ $attendance->location }}</small>
                                                @elseif(strpos($attendance->location, ',') !== false)
                                                    <a href="https://www.google.com/maps/search/?api=1&query={{ $attendance->location }}"
                                                        target="_blank" class="text-primary">
                                                        <i class="fas fa-map-marker-alt"></i> Lihat Lokasi
                                                    </a>
                                                @else
                                                    <span class="text-muted">{{ $attendance->location }}</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($attendance->note)
                                                <span class="text-primary" title="{{ $attendance->note }}">
                                                    <i class="fas fa-sticky-note"></i>
                                                    {{ Str::limit($attendance->note, 20) }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="row mt-3">
                        <div class="col-sm-12 col-md-5">
                            <div class="dataTables_info">
                                Menampilkan {{ $attendances->firstItem() }} sampai {{ $attendances->lastItem() }}
                                dari {{ $attendances->total() }} record
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-7">
                            <div class="dataTables_paginate paging_simple_numbers float-right">
                                {{ $attendances->appends(request()->query())->links() }}
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Tidak ada data absensi</h5>
                        <p class="text-muted">Tidak ditemukan record absensi untuk filter yang dipilih.</p>
                        <a href="{{ route('admin.attendance.index') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Kelola Absensi Hari Ini
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- PENAMBAHAN SCRIPT UNTUK INISIALISASI DATATABLES --}}
{{-- @push('scripts') --}}
<script>
    $(function() {


        $("#attendance_history_table").DataTable({
            "responsive": true,
            "lengthChange": true, // Menampilkan pilihan jumlah data per halaman
            "autoWidth": false,
            "paging": true, // Mengaktifkan paginasi
            "searching": true, // Mengaktifkan fitur pencarian
            "ordering": false, // Mengaktifkan sorting
            "info": true, // Menampilkan info halaman (contoh: Showing 1 to 10 of 57 entries)

            "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
        }).buttons().container().appendTo('#tabel-buku-besar_wrapper .col-md-6:eq(0)');
    });
</script>
{{-- @endpush --}}


<script>
    function toggleDateFilter() {
        const viewType = document.querySelector('select[name="view_type"]').value;
        const monthlyFilter = document.getElementById('monthly-filter');
        const dailyFilter = document.getElementById('daily-filter');

        if (viewType === 'daily') {
            monthlyFilter.style.display = 'none';
            dailyFilter.style.display = 'block';
        } else {
            monthlyFilter.style.display = 'block';
            dailyFilter.style.display = 'none';
        }
    }

    // Auto-expand filter card if there are active filters
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        // Pengecekan filter yang lebih akurat
        const hasFilters = urlParams.has('employee_id') ||
            urlParams.has('month') ||
            urlParams.has('year') ||
            urlParams.has('date') ||
            urlParams.get('view_type') === 'daily';

        if (hasFilters && urlParams.toString() !== '') {
            const filterCard = document.querySelector('.card.collapsed-card');
            if (filterCard) {
                // Menggunakan fungsi bawaan AdminLTE untuk membuka card
                $(filterCard).CardWidget('expand');
            }
        }
    });
</script>

<style>
    .small-box {
        border-radius: 0.375rem;
        box-shadow: 0 0 1px rgba(0, 0, 0, .125), 0 1px 3px rgba(0, 0, 0, .2);
    }

    .table th {
        vertical-align: middle;
        text-align: center;
        background-color: #f8f9fa;
    }

    .table td {
        vertical-align: middle;
    }

    .progress {
        height: 20px;
    }

    .badge {
        font-size: 0.85em;
    }

    .card-outline.card-success {
        border-top: 3px solid #28a745;
    }

    .card-outline.card-info {
        border-top: 3px solid #17a2b8;
    }

    .card-outline.card-primary {
        border-top: 3px solid #007bff;
    }
</style>

{{-- @endsection --}}
