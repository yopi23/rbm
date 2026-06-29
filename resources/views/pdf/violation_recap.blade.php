<!DOCTYPE html>
<html>
<head>
    <title>Rekap Pelanggaran Teknisi</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h2 { margin: 0; padding: 0; }
        .info { margin-bottom: 20px; }
        .info p { margin: 2px 0; }
        .badge { padding: 3px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .badge-pending { background-color: #ff9800; color: white; }
        .badge-processed { background-color: #f44336; color: white; }
        .badge-forgiven { background-color: #4caf50; color: white; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Rekap Pelanggaran Karyawan</h2>
        <p>Bulan: {{ str_pad($month, 2, '0', STR_PAD_LEFT) }} - Tahun: {{ $year }}</p>
    </div>

    <div class="info">
        <p><strong>Nama Teknisi:</strong> {{ $targetUser->name }}</p>
        <p><strong>ID Karyawan:</strong> {{ $targetUser->id }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Keterangan</th>
                <th>Denda (Rp)</th>
                <th>Denda (%)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($violations as $index => $v)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($v->violation_date)->format('d M Y') }}</td>
                    <td style="text-transform: capitalize;">{{ $v->type }}</td>
                    <td>{{ $v->description }}</td>
                    <td>{{ $v->penalty_amount > 0 ? 'Rp ' . number_format($v->penalty_amount, 0, ',', '.') : '-' }}</td>
                    <td>{{ $v->penalty_percentage > 0 ? $v->penalty_percentage . '%' : '-' }}</td>
                    <td>
                        @if($v->status == 'pending')
                            <span class="badge badge-pending">Tertunda</span>
                        @elseif($v->status == 'processed')
                            <span class="badge badge-processed">Diterapkan</span>
                        @else
                            <span class="badge badge-forgiven">Dimaafkan</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center;">Tidak ada data pelanggaran di bulan ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
