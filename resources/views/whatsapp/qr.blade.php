<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan QR WhatsApp</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        .container {
            text-align: center;
            margin-top: 50px;
        }

        #qrcode {
            margin: 20px auto;
        }

        .status {
            margin: 20px;
            padding: 10px;
            border-radius: 5px;
        }

        .connected {
            background: #d4edda;
            color: #155724;
        }

        .disconnected {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>

<body>
    <h2>Scan QR Code WhatsApp</h2>
    <div id="qr-container"></div> <!-- Tempat QR Code -->

    <script>
        async function checkStatus() {
            try {
                const response = await fetch('/api/whatsapp/status');
                const data = await response.json();
                console.log("API Response:", data);

                if (data.qrCode) {
                    document.getElementById('qr-container').innerHTML = ""; // Hapus QR lama
                    new QRCode(document.getElementById("qr-container"), {
                        text: data.qrCode,
                        width: 200,
                        height: 200
                    });
                } else if (data.status === 'connected') {
                    document.getElementById('qr-container').innerHTML = '<p>WhatsApp Connected!</p>';
                }
            } catch (error) {
                console.error("Error fetching status:", error);
            }
        }

        // Cek status setiap 10 detik
        setInterval(checkStatus, 100000);
        checkStatus(); // Panggil pertama kali saat halaman dimuat
    </script>
</body>


</html>
