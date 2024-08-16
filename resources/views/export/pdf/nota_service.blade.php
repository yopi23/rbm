<!DOCTYPE html>
<html>

<head>
    <title>Nota Service YOYOYCELL</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
        integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>
<style>
    * {
        margin: 0;
        padding: 0;
        color: #000;
    }

    .ticket {
        width: 165px;
        max-width: 180px;
        font-weight: bold;
        color: #000;
    }
</style>
</head>

<body>
    <div class="ticket">
        <table style="width: 100%;">
            <tbody>
                <tr class="text-center">
                    <td colspan="3">===================</td>
                </tr>
                <tr class="text-center" style="font-size: 14px">
                    <td colspan="3">YOYOY CELL<p style="margin-bottom: 0;">085603124871 (CS) <br> www.yoyoycell.my.id
                        </p>
                    </td>
                </tr>
                <tr>
                    <td colspan="3">===================</td>
                </tr>
                <tr class="border border-dark text-center" style="font-size: 10pt">
                    <td colspan="3">{{ $data->kode_service }}</>
                    </td>
                </tr>
                <tr style="font-size: 8pt">
                    <td>Nama</td>
                    <td>:</td>
                    <td> {{ ucfirst($data->nama_pelanggan) }}</td>
                </tr>
                <tr style="font-size: 8pt">
                    <td>Device</td>
                    <td>:</td>
                    <td> {{ ucfirst($data->type_unit) }}</td>
                </tr>
                <tr style="font-size: 8pt">
                    <td>Ket</td>
                    <td>:</td>
                    <td>{{ ucfirst($data->keterangan) }}</td>
                </tr>
                <tr style="font-size: 8pt">
                    <td>Biaya</td>
                    <td>:</td>
                    <td>Rp.{{ number_format($data->total_biaya) }}</td>
                </tr>
                <tr style="font-size: 8pt">
                    <td>DP</td>
                    <td>:</td>
                    <td>Rp.{{ number_format($data->dp) }}</td>
                </tr>
            </tbody>
        </table>

        <center class="mt-2">
            <small style="font-size: 10pt">Mohon nota ini dibawa saat pengambilan</small>
            <table class="my-2" style="width: 100%; font-size:8pt">
                <tbody>
                    <tr class="border border-dark text-center">
                        <td>
                            <p style="margin-bottom: 0;">3 Bulan tidak diambil<br> berarti anda sudah memberikannya
                                kepada kami</p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div style="font-size: 12pt">TERIMAKASIH</div>
        </center>

    </div>
</body>

</html>
