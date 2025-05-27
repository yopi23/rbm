<!-- resources/views/admin/page/report-print.blade.php -->

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Gaji - {{ $report->user->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
        }

        .header p {
            margin: 5px 0;
        }

        .info-box {
            margin-bottom: 20px;
        }

        .info-box table {
            width: 100%;
        }

        .info-box td {
            padding: 3px 0;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table th,
        .table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }

        .table th {
            background-color: #f0f0f0;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            margin-top: 50px;
        }

        .signature {
            float: right;
            text-align: center;
            width: 200px;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>LAPORAN GAJI KARYAWAN</h1>
        <p>Periode: {{ date('F Y', mktime(0, 0, 0, $report->month, 1, $report->year)) }}</p>
    </div>

    <div class="info-box">
        <table>
            <tr>
                <td width="150">Nama Karyawan</td>
                <td>: {{ $report->user->name }}</td>
            </tr>
            <tr>
                <td>Jabatan</td>
                <td>: {{ $report->user->userDetail->jabatan == 2 ? 'Kasir' : 'Teknisi' }}</td>
            </tr>
            <tr>
                <td>Total Hari Kerja</td>
                <td>: {{ $report->total_working_days }} hari</td>
            </tr>
            <tr>
                <td>Total Kehadiran</td>
                <td>: {{ $report->total_present_days }} hari</td>
            </tr>
            <tr>
                <td>Total Tidak Hadir</td>
                <td>: {{ $report->total_absent_days }} hari</td>
            </tr>
            <tr>
                <td>Total Keterlambatan</td>
                <td>: {{ $report->total_late_minutes }} menit</td>
            </tr>
        </table>
    </div>

    <h3>Rincian Pendapatan</h3>
    <table class="table">
        <tr>
            <th width="50%">Keterangan</th>
            <th width="50%" class="text-right">Jumlah</th>
        </tr>
        <tr>
            <td>Gaji Pokok</td>
            <td class="text-right">Rp {{ number_format($report->user->salarySetting->basic_salary, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Total Service ({{ $report->total_service_units }} unit)</td>
            <td class="text-right">Rp {{ number_format($report->total_service_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Komisi Service ({{ $report->user->salarySetting->service_percentage }}%)</td>
            <td class="text-right">Rp {{ number_format($report->total_commission, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Bonus Target</td>
            <td class="text-right">Rp {{ number_format($report->total_bonus, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Total Pendapatan</th>
            <th class="text-right">Rp
                {{ number_format($report->user->salarySetting->basic_salary + $report->total_commission + $report->total_bonus, 0, ',', '.') }}
            </th>
        </tr>
    </table>

    <h3>Rincian Potongan</h3>
    <table class="table">
        <tr>
            <th>Tanggal</th>
            <th>Jenis Pelanggaran</th>
            <th>Keterangan</th>
            <th class="text-right">Jumlah</th>
        </tr>
        @foreach ($violations->where('status', 'processed') as $violation)
            <tr>
                <td>{{ \Carbon\Carbon::parse($violation->violation_date)->format('d M Y') }}</td>
                <td>{{ ucfirst($violation->type) }}</td>
                <td>{{ $violation->description }}</td>
                <td class="text-right">
                    @if ($violation->penalty_amount)
                        Rp {{ number_format($violation->penalty_amount, 0, ',', '.') }}
                    @else
                        {{ $violation->penalty_percentage }}%
                    @endif
                </td>
            </tr>
        @endforeach
        <tr>
            <th colspan="3">Total Potongan</th>
            <th class="text-right">Rp {{ number_format($report->total_penalties, 0, ',', '.') }}</th>
        </tr>
    </table>

    <h3>Total Gaji Diterima</h3>
    <table class="table">
        <tr>
            <th width="50%">Total Gaji Bersih</th>
            <th width="50%" class="text-right">Rp {{ number_format($report->final_salary, 0, ',', '.') }}</th>
        </tr>
    </table>

    <div class="footer">
        <div class="signature">
            <p>{{ date('d F Y') }}</p>
            <br><br><br>
            <p>(__________________)</p>
            <p>HRD/Admin</p>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>

</html>
