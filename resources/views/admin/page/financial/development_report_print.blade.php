{{-- resources/views/admin/page/financial/development_report_print.blade.php --}}
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $page }} - {{ $year }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 10px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 16px;
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

        .font-weight-bold {
            font-weight: bold;
        }

        .text-success {
            color: #28a745;
        }

        .text-danger {
            color: #dc3545;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Laporan Perkembangan Kekayaan</h1>
        <p>Tahun: {{ $year }}</p>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th rowspan="2" class="text-center">Bulan</th>
                <th colspan="4" class="text-center">Komposisi Kekayaan (Snapshot Akhir Bulan)</th>
                <th colspan="2" class="text-center">Alur Konversi Aset (Bulanan)</th>
                <th rowspan="2" class="text-center">Profit Bersih (Bulanan)</th>
                <th rowspan="2" class="text-center">Perubahan Kekayaan (Bulanan)</th>
            </tr>
            <tr>
                <th class="text-center">Modal Uang (Kas)</th>
                <th class="text-center">Modal Barang (Stok)</th>
                <th class="text-center">Modal Aset Tetap</th>
                <th class="text-center">Total Kekayaan</th>
                <th class="text-center">Uang ➝ Barang</th>
                <th class="text-center">Barang ➝ Uang</th>
            </tr>
        </thead>
        <tbody>
            @php $previousWealth = 0; @endphp
            @forelse ($developmentData['table'] as $month => $data)
                <tr>
                    <td><strong>{{ $data['monthName'] }}</strong></td>
                    <td class="text-right">Rp {{ number_format($data['saldoKas'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($data['totalNilaiBarang'], 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($data['totalNilaiAset'], 0, ',', '.') }}</td>
                    <td class="text-right font-weight-bold">Rp {{ number_format($data['totalKekayaan'], 0, ',', '.') }}
                    </td>
                    <td class="text-right text-danger">Rp {{ number_format($data['cashToGoods'], 0, ',', '.') }}</td>
                    <td class="text-right text-success">Rp {{ number_format($data['goodsToCash'], 0, ',', '.') }}</td>
                    <td class="text-right {{ $data['netProfit'] >= 0 ? 'text-success' : 'text-danger' }}">
                        Rp {{ number_format($data['netProfit'], 0, ',', '.') }}
                    </td>
                    <td class="text-right">
                        @php
                            $wealthChange = $previousWealth > 0 ? $data['totalKekayaan'] - $previousWealth : 0;
                        @endphp
                        <span class="{{ $wealthChange >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $wealthChange >= 0 ? '▲' : '▼' }} Rp
                            {{ number_format(abs($wealthChange), 0, ',', '.') }}
                        </span>
                    </td>
                </tr>
                @php $previousWealth = $data['totalKekayaan']; @endphp
            @empty
                <tr>
                    <td colspan="9" class="text-center">Belum ada data untuk tahun ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>

</html>
