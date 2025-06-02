<!-- resources/views/admin/page/outside-office-history.blade.php -->

@section('outside-office-history', 'active')
@section('main', 'menu-is-opening menu-open')

<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-info">
            <div class="card-header">
                <div class="card-title">
                    Riwayat Izin Keluar Kantor
                </div>
                <div class="card-tools">
                    <a href="{{ route('admin.attendance.index') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-arrow-left"></i> Kembali ke Absensi
                    </a>
                    <a href="{{ route('admin.attendance.outside-office.export', request()->query()) }}"
                        class="btn btn-success btn-sm">
                        <i class="fas fa-download"></i> Export
                    </a>
                </div>
            </div>

            <div class="card-body">
                <!-- Filter Section -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <form method="GET" class="form-inline">
                            <div class="form-group mr-3">
                                <label class="mr-2">Karyawan:</label>
                                <select name="employee_id" class="form-control">
                                    <option value="">Semua Karyawan</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id_user }}"
                                            {{ request('employee_id') == $employee->id_user ? 'selected' : '' }}>
                                            {{ $employee->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group mr-3">
                                <label class="mr-2">Bulan:</label>
                                <select name="month" class="form-control">
                                    @for ($i = 1; $i <= 12; $i++)
                                        <option value="{{ $i }}"
                                            {{ $selectedMonth == $i ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            <div class="form-group mr-3">
                                <label class="mr-2">Tahun:</label>
                                <select name="year" class="form-control">
                                    @for ($i = date('Y'); $i >= date('Y') - 2; $i--)
                                        <option value="{{ $i }}" {{ $selectedYear == $i ? 'selected' : '' }}>
                                            {{ $i }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            <div class="form-group mr-3">
                                <label class="mr-2">Status:</label>
                                <select name="status" class="form-control">
                                    <option value="">Semua Status</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif
                                    </option>
                                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>
                                        Selesai</option>
                                    <option value="violated" {{ request('status') == 'violated' ? 'selected' : '' }}>
                                        Pelanggaran</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-info">
                                <i class="fas fa-list"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Permintaan</span>
                                <span class="info-box-number">{{ $stats['total_requests'] }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-warning">
                                <i class="fas fa-clock"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Sedang Aktif</span>
                                <span class="info-box-number">{{ $stats['active_requests'] }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-success">
                                <i class="fas fa-check"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Selesai Normal</span>
                                <span class="info-box-number">{{ $stats['completed_requests'] }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Pelanggaran</span>
                                <span class="info-box-number">{{ $stats['violated_requests'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="outsideOfficeTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Tanggal</th>
                                <th>Waktu Keluar</th>
                                <th>Waktu Kembali</th>
                                <th>Aktual Kembali</th>
                                <th>Durasi</th>
                                <th>Status</th>
                                <th>Keterlambatan</th>
                                <th>Alasan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($outsideLogs as $log)
                                <tr
                                    class="{{ $log->status == 'violated' ? 'table-danger' : ($log->status == 'active' ? 'table-warning' : '') }}">
                                    <td>{{ $loop->iteration + ($outsideLogs->currentPage() - 1) * $outsideLogs->perPage() }}
                                    </td>
                                    <td>
                                        <strong>{{ $log->user->name }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            @switch($log->user->userDetail->jabatan ?? 0)
                                                @case(2)
                                                    <span class="badge badge-success">Kasir</span>
                                                @break

                                                @case(3)
                                                    <span class="badge badge-info">Teknisi</span>
                                                @break
                                            @endswitch
                                        </small>
                                    </td>
                                    <td>{{ $log->log_date->format('d/m/Y') }}</td>
                                    <td>{{ $log->start_time->format('H:i') }}</td>
                                    <td>
                                        @if ($log->end_time)
                                            {{ $log->end_time->format('H:i') }}
                                            @if ($log->status == 'active' && $log->is_overdue)
                                                <br><span class="badge badge-danger">Overdue</span>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($log->actual_return_time)
                                            {{ $log->actual_return_time->format('H:i') }}
                                        @elseif($log->status == 'active')
                                            <span class="badge badge-warning">Belum Kembali</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $log->formatted_duration }}</td>
                                    <td>
                                        @switch($log->status)
                                            @case('active')
                                                <span class="badge badge-warning">Aktif</span>
                                            @break

                                            @case('completed')
                                                <span class="badge badge-success">Selesai</span>
                                            @break

                                            @case('violated')
                                                <span class="badge badge-danger">Pelanggaran</span>
                                            @break
                                        @endswitch
                                    </td>
                                    <td>
                                        @if ($log->late_return_minutes > 0)
                                            <span class="badge badge-danger">
                                                {{ $log->late_return_minutes }} menit
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;"
                                            title="{{ $log->reason }}">
                                            {{ $log->reason }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group-vertical">
                                            @if ($log->status == 'active')
                                                <button class="btn btn-xs btn-info"
                                                    onclick="markReturn({{ $log->id }})">
                                                    <i class="fas fa-sign-in-alt"></i> Kembali
                                                </button>
                                            @endif

                                            <button class="btn btn-xs btn-secondary"
                                                onclick="showLogDetail({{ $log->id }})">
                                                <i class="fas fa-eye"></i> Detail
                                            </button>

                                            @if ($log->status == 'active')
                                                <button class="btn btn-xs btn-danger"
                                                    onclick="violateLog({{ $log->id }})">
                                                    <i class="fas fa-ban"></i> Reset
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center">Tidak ada data izin keluar kantor</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $outsideLogs->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detail Log -->
    <div class="modal fade" id="modalLogDetail">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Detail Izin Keluar Kantor</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="logDetailContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Loading...
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function markReturn(logId) {
            if (confirm('Konfirmasi kembali dari izin keluar kantor?')) {
                $.ajax({
                    url: "{{ route('admin.attendance.outside-office.mark-return-by-log') }}",
                    method: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        log_id: logId
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

        function violateLog(logId) {
            const reason = prompt('Alasan reset/pelanggaran:');
            if (reason && reason.trim() !== '') {
                $.ajax({
                    url: "{{ route('admin.attendance.outside-office.violate-log') }}",
                    method: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        log_id: logId,
                        reason: reason
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

        function showLogDetail(logId) {
            $.ajax({
                url: "{{ route('admin.attendance.outside-office.detail', ['id' => '__ID__']) }}".replace('__ID__',
                    logId),
                method: 'GET',
                success: function(response) {
                    $('#logDetailContent').html(response.html);
                    $('#modalLogDetail').modal('show');
                },
                error: function() {
                    $('#logDetailContent').html('<div class="alert alert-danger">Gagal memuat detail</div>');
                    $('#modalLogDetail').modal('show');
                }
            });
        }

        // DataTable initialization
        $(document).ready(function() {
            $('#outsideOfficeTable').DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "searching": true,
                "ordering": true,
                "info": true,
                "paging": false, // Karena sudah ada Laravel pagination
                "language": {
                    "search": "Cari:",
                    "zeroRecords": "Tidak ada data yang ditemukan",
                    "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                    "infoEmpty": "Menampilkan 0 sampai 0 dari 0 entri",
                    "infoFiltered": "(disaring dari _MAX_ total entri)"
                }
            });
        });
    </script>
