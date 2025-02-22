<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan QR WhatsApp</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
        }

        .container {
            display: inline-block;
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
            background: white;
        }

        #qr-container {
            margin: 20px 0;
        }

        .status {
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
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
    <div class="container">
        <h2>Scan QR Code WhatsApp</h2>
        <div id="status-container">
            <p class="status disconnected">Menunggu koneksi...</p>
        </div>
        <div id="qr-container"></div> <!-- Tempat QR Code -->
    </div>

    <script>
        async function checkStatus() {
            try {
                const response = await fetch('/api/whatsapp/status');
                const data = await response.json();
                console.log("API Response:", data);

                const statusContainer = document.getElementById('status-container');
                const qrContainer = document.getElementById('qr-container');

                if (data.status === 'connected') {
                    statusContainer.innerHTML = '<p class="status connected">WhatsApp Connected!</p>';
                    qrContainer.innerHTML = ''; // Hapus QR jika sudah terhubung
                } else {
                    statusContainer.innerHTML = '<p class="status disconnected">Menunggu scan QR...</p>';
                    if (data.qrCode) {
                        qrContainer.innerHTML = ""; // Hapus QR lama
                        new QRCode(qrContainer, {
                            text: data.qrCode,
                            width: 200,
                            height: 200
                        });
                    }
                }
            } catch (error) {
                console.error("Error fetching status:", error);
            }
        }

        // Cek status setiap 10 detik
        setInterval(checkStatus, 10000);
        checkStatus(); // Panggil pertama kali saat halaman dimuat
    </script>
</body>

</html>
