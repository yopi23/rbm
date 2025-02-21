<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan QR WhatsApp</title>
</head>

<body>
    <h2>Scan QR Code WhatsApp</h2>
    @if ($qr)
        <img src="{{ $qr }}" alt="Scan QR Code">
    @else
        <p>QR Code belum tersedia, silakan refresh halaman ini.</p>
    @endif
</body>

</html>
