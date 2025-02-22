<!DOCTYPE html>
<html>
<head>
    <title>WhatsApp QR Code</title>
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
    <div class="container">
        <h2>WhatsApp Connection Status</h2>
        <div id="status" class="status"></div>
        <div id="qrcode"></div>
    </div>

    <script>
        function checkStatus() {
            // Ganti URL ini dengan domain server Anda
            fetch('/api/whatsapp/status')
                .then(response => response.json())
                .then(data => {
                    const statusDiv = document.getElementById('status');
                    const qrcodeDiv = document.getElementById('qrcode');

                    if (data.status === 'connected') {
                        statusDiv.className = 'status connected';
                        statusDiv.textContent = 'WhatsApp Connected!';
                        qrcodeDiv.innerHTML = ''; // Hapus QR code jika sudah terkoneksi
                    } else {
                        statusDiv.className = 'status disconnected';
                        statusDiv.textContent = 'Waiting for connection...';

                        if (data.qrCode) {
                            qrcodeDiv.innerHTML = ''; // Reset QR code div
                            new QRCode(qrcodeDiv, data.qrCode);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('status').textContent = 'Error connecting to WhatsApp service';
                });
        }

        // Cek status setiap 5 detik
        checkStatus();
        setInterval(checkStatus, 5000);
    </script>
</body>
</html>
