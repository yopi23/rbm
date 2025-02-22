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
            background: #f4f4f4;
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

        #disconnect-btn {
            margin-top: 15px;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            background: #dc3545;
            color: white;
            font-size: 14px;
            cursor: pointer;
        }

        #disconnect-btn:hover {
            background: #c82333;
        }

        .loading {
            display: none;
            margin: 10px 0;
            font-style: italic;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Scan QR Code WhatsApp</h2>
        <div id="status-container">
            <p class="status disconnected">Menunggu koneksi...</p>
        </div>
        <div id="loading" class="loading">Memproses...</div>
        <div id="qr-container"></div>
        <div id="connected-info" style="display: none;">
            <p><strong>Terhubung dengan:</strong> <span id="connected-number"></span></p>
            <button id="disconnect-btn" onclick="disconnectWhatsApp()">Disconnect</button>
        </div>
    </div>

    <script>
        async function checkStatus() {
            try {
                const response = await fetch('/api/whatsapp/status');
                const data = await response.json();
                console.log("API Response:", data);

                const statusContainer = document.getElementById('status-container');
                const qrContainer = document.getElementById('qr-container');
                const connectedInfo = document.getElementById('connected-info');
                const connectedNumber = document.getElementById('connected-number');

                if (data.status === 'connected' && data.connectedNumber) { // Perhatikan property yang diubah
                    statusContainer.innerHTML = '<p class="status connected">WhatsApp Connected!</p>';
                    connectedNumber.textContent = data.connectedNumber;
                    connectedInfo.style.display = "block";
                    qrContainer.innerHTML = '';
                } else {
                    statusContainer.innerHTML = '<p class="status disconnected">Menunggu scan QR...</p>';
                    connectedInfo.style.display = "none";
                    if (data.qrCode) {
                        qrContainer.innerHTML = "";
                        new QRCode(qrContainer, {
                            text: data.qrCode,
                            width: 200,
                            height: 200
                        });
                    }
                }
            } catch (error) {
                console.error("Error fetching status:", error);
                const statusContainer = document.getElementById('status-container');
                statusContainer.innerHTML = '<p class="status disconnected">Error: Gagal terhubung ke server</p>';
            }
        }

        async function disconnectWhatsApp() {
            try {
                const loading = document.getElementById('loading');
                loading.style.display = 'block';

                const response = await fetch('/api/whatsapp/logout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                const result = await response.json();
                loading.style.display = 'none';

                if (result.success) {
                    alert("Berhasil disconnect!");
                    checkStatus(); // Perbarui tampilan
                } else {
                    alert(result.message || "Gagal disconnect!");
                }
            } catch (error) {
                console.error("Error disconnecting:", error);
                alert("Terjadi kesalahan saat mencoba disconnect");
                loading.style.display = 'none';
            }
        }

        // Cek status setiap 10 detik (100000ms terlalu lama)
        setInterval(checkStatus, 10000);
        checkStatus(); // Panggil pertama kali saat halaman dimuat
    </script>
</body>

</html>
