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
    {{-- test --}}
    <script>
        function checkStatus() {
            console.log("Starting checkStatus function...");
            fetch('/api/whatsapp/status')
                .then(response => response.json())
                .then(data => {
                    console.log("API Response:", data); // Cek apakah API merespons

                    const statusDiv = document.getElementById('status');
                    const qrcodeDiv = document.getElementById('qrcode');

                    if (data.status === 'connected') {
                        statusDiv.className = 'status connected';
                        statusDiv.textContent = 'WhatsApp Connected!';
                        qrcodeDiv.innerHTML = ''; // Hapus QR jika sudah terhubung
                    } else {
                        statusDiv.className = 'status disconnected';
                        statusDiv.textContent = 'Waiting for connection...';

                        console.log("Checking QR Code field...");

                        if (data.qrCode) {
                            console.log("Raw QR Code:", data.qrCode); // Ini yang kita cek

                            qrcodeDiv.innerHTML = ''; // Reset QR Code div

                            let qrText = data.qrCode.split(',')[0]; // Ambil hanya bagian pertama

                            new QRCode(qrcodeDiv, {
                                text: qrText,
                                width: 256,
                                height: 256
                            });
                        } else {
                            console.warn("No QR Code found in API response!");
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching API:', error);
                    document.getElementById('status').textContent = 'Error connecting to WhatsApp service';
                });
        }
    </script>
</body>

</html>
