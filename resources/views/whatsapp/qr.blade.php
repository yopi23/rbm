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
            fetch('/api/whatsapp/status')
                .then(response => response.json())
                .then(data => {
                    console.log("API Response:", data); // Cek apakah API mengembalikan data

                    const statusDiv = document.getElementById('status');
                    const qrcodeDiv = document.getElementById('qrcode');

                    if (data.status === 'connected') {
                        statusDiv.className = 'status connected';
                        statusDiv.textContent = 'WhatsApp Connected!';
                        qrcodeDiv.innerHTML = ''; // Hapus QR jika sudah terhubung
                    } else {
                        statusDiv.className = 'status disconnected';
                        statusDiv.textContent = 'Waiting for connection...';

                        if (data.qrCode) {
                            console.log("Raw QR Code:", data.qrCode); // Cek bentuk asli data

                            qrcodeDiv.innerHTML = ''; // Reset QR Code div

                            // Coba gunakan QR Code secara langsung tanpa split
                            new QRCode(qrcodeDiv, {
                                text: data.qrCode,
                                width: 256,
                                height: 256
                            });
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('status').textContent = 'Error connecting to WhatsApp service';
                });
        }
    </script>
</body>

</html>
