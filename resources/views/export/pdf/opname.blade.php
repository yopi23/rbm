<!DOCTYPE html>
<html>

<head>
    <title>Laporan Opname Stok Sparepart Tanggal {{ date('d-m-Y') }}</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
        integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>

<body>
    <style type="text/css">
        table tr td,
        table tr th {
            font-size: 9pt;
        }
    </style>
    <center>
        <h5>Laporan Opname Stok Sparepart</h4>
            <p>Tanggal {{ date('d-m-Y') }}</p>
    </center>

    <table class='table table-bordered'>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Sparepart</th>
                <th>Nama</th>
                <th>Stok (Toko)</th>
                <th>Rusak (Toko)</th>
                <th>Stok (Asli)</th>
                <th>Rusak (Asli) </th>
                <th>Ket</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sparepart as $item)
                @php
                    $sparepart_rusak = 0;
                    foreach ($data_sparepart_rusak as $r) {
                        if ($item->id == $r->kode_barang) {
                            $sparepart_rusak = $r->jumlah_rusak;
                        }
                    }
                @endphp
                <tr>
                    <td>{{ $loop->index + 1 }}</td>
                    <td>{{ $item->kode_sparepart }}</td>
                    <td>{{ $item->nama_sparepart }}</td>
                    <td>{{ $item->stok_sparepart }}</td>
                    <td>{{ $sparepart_rusak }}</td>
                    <td>{{ $item->stock_asli }}</td>
                    <td></td>
                    <td></td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
