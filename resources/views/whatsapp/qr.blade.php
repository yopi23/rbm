<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan QR WhatsApp</title>
</head>

<body>
    {{-- <h2>Scan QR Code WhatsApp</h2>
    @if ($qr)
        <img src="{{ $qr }}" alt="Scan QR Code">
    @else
        <p>QR Code belum tersedia, silakan refresh halaman ini.</p>
    @endif --}}
    <div id="qr-code"></div>

    <script>
        async function checkStatus() {
            const response = await fetch('/api/whatsapp/status');
            const data = await response.json();

            if (data.qrCode) {
                // Tampilkan QR code (gunakan library qrcode.js atau yang lain)
                displayQR(data.qrCode);
            } else if (data.status === 'connected') {
                document.getElementById('qr-code').innerHTML = 'WhatsApp Connected!';
            }
        }

        // Cek status setiap 5 detik
        setInterval(checkStatus, 5000);
    </script>
</body>

</html>
