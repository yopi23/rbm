<!-- resources/views/admin/page/monthly-report.blade.php -->

@section('monthly_report', 'active')
@section('main', 'menu-is-opening menu-open')


<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <div class="card-title">
                    Laporan Bulanan Karyawan
                </div>
                <div class="card-tools">
                    <form id="reportForm" action="{{ route('admin.employee.generate-monthly-report') }}" method="POST"
                        class="form-inline">
                        @csrf
                        <select name="year" id="yearSelect" class="form-control mr-2">
                            @for ($i = date('Y'); $i >= date('Y') - 5; $i--)
                                <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>
                                    {{ $i }}</option>
                            @endfor
                        </select>
                        <select name="month" id="monthSelect" class="form-control mr-2">
                            @for ($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ $month == $i ? 'selected' : '' }}>
                                    {{ date('F', mktime(0, 0, 0, $i, 1)) }}</option>
                            @endfor
                        </select>
                        <button type="button" class="btn btn-primary" onclick="submitReport()">
                            <i class="fas fa-sync"></i> Generate Laporan
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="laporan_dari_absen">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama</th>
                                <th>Bulan/Tahun</th>
                                <th class="text-nowrap">Hari Kerja</th>
                                <th>Hadir</th>
                                <th>Absen</th>
                                <th>Terlambat</th>
                                <th class="text-nowrap">Unit Service</th>
                                <th class="text-nowrap">Total Service</th>
                                <th>Komisi</th>
                                <th class="text-nowrap">Profit Toko</th>
                                <th class="text-nowrap">Garansi Dikerjakan</th>
                                <th class="text-nowrap">Unit klaim garansi</th>
                                <th>Bonus</th>
                                <th>Denda</th>
                                <th class="text-nowrap">Gaji Akhir</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reports as $report)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $report->user->name }}</td>
                                    <td>{{ date('F', mktime(0, 0, 0, $report->month, 1)) }}/{{ $report->year }}</td>
                                    <td>{{ $report->total_working_days }}</td>
                                    <td>{{ $report->total_present_days }}</td>
                                    <td>{{ $report->total_absent_days }}</td>
                                    <td>{{ $report->total_late_minutes }} menit</td>
                                    <td>{{ $report->total_service_units }} unit</td>
                                    <td>Rp {{ number_format($report->total_service_amount, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($report->total_commission, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($report->total_shop_profit, 0, ',', '.') }}</td>
                                    <td>{{ $report->total_claims_handled }} unit</td>
                                    <td>{{ $report->claims_from_own_work }} unit</td>
                                    <td>Rp {{ number_format($report->total_bonus, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($report->total_penalties, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($report->final_salary, 0, ',', '.') }}</td>
                                    <td>
                                        @switch($report->status)
                                            @case('draft')
                                                <span class="badge badge-warning">Draft</span>
                                            @break

                                            @case('finalized')
                                                <span class="badge badge-success">Final</span>
                                            @break

                                            @case('paid')
                                                <span class="badge badge-info">Dibayar</span>
                                            @break
                                        @endswitch
                                    </td>
                                    <td>
                                        @if ($report->status == 'draft')
                                            <button class="btn btn-success btn-xs"
                                                onclick="finalizeReport({{ $report->id }})">
                                                <i class="fas fa-check"></i> Finalisasi
                                            </button>
                                        @endif
                                        <a href="{{ route('admin.employee.report-detail', $report->id) }}"
                                            class="btn btn-primary btn-xs">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                        <a href="{{ route('admin.employee.report-print', $report->id) }}"
                                            class="btn btn-secondary btn-xs" target="_blank">
                                            <i class="fas fa-print"></i> Cetak
                                        </a>
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

<script>
    function finalizeReport(reportId) {
        if (confirm('Apakah Anda yakin ingin memfinalisasi laporan ini?')) {
            $.ajax({
                url: "{{ route('admin.employee.finalize-report') }}",
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    report_id: reportId
                },
                success: function(response) {
                    if (response.success) {
                        alert('Laporan berhasil difinalisasi.');
                        window.location.reload();
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan. Silakan coba lagi.');
                }
            });
        }
    }



    // FUNGSI YANG DIPERBAIKI
    function submitReport() {
        const form = document.getElementById('reportForm');

        // Cukup definisikan action sekali saja, karena sudah benar
        form.action = "{{ route('admin.employee.generate-monthly-report') }}";
        form.submit();
    }
</script>
<script>
    function submitReport() {
        const year = document.getElementById('yearSelect').value;
        const month = document.getElementById('monthSelect').value;
        const form = document.getElementById('reportForm');
        form.action = "{{ route('admin.employee.generate-monthly-report', ['year' => ':year', 'month' => ':month']) }}"
            .replace(':year', year)
            .replace(':month', month);
        form.submit();
    }
</script>
<script>
    $(function() {
        $('#laporan_dari_absen').DataTable({
            "paging": true,
            "lengthChange": false, // Menyembunyikan "Show X entries"
            "searching": true, // Mengaktifkan fitur pencarian
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            // Properti 'dom' ini adalah kunci untuk mengatur layout.
            // 'f' (filter/search) dan 'p' (pagination) ditempatkan di kolom kanan.
            "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",

            // Opsional: Tambahkan ini untuk menerjemahkan label ke Bahasa Indonesia
            "language": {
                "search": "Cari:",
                "paginate": {
                    "next": "Berikutnya",
                    "previous": "Sebelumnya"
                },
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
                "infoFiltered": "(difilter dari _MAX_ total data)",
                "zeroRecords": "Tidak ada data yang cocok",
                "lengthMenu": "Tampilkan _MENU_ data per halaman",
            }
        });
    });
</script>
