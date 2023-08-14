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
        <center>
            <table class="my-2" style="width: 100%; font-size:8pt">
                <tbody>
                    <tr class="border border-dark text-center">
                        <td>
                            <p style="margin-bottom: 0;">{{ ucfirst($data->nama_sparepart) }}<br>
                                Rp.{{ number_format($data->harga_jual) }}
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </center>

    </div>
</body>

</html>
