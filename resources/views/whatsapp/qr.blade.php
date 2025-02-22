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
    <script>
        async function checkStatus() {
            try {
                const response = await fetch('/api/whatsapp/status');

                // Debugging: Cek response sebelum parsing JSON
                const text = await response.text();
                console.log("Raw Response:", text);

                // Coba parsing ke JSON
                const data = JSON.parse(text);
                console.log("Parsed JSON:", data);

                if (data.qrCode) {
                    document.body.innerHTML += `<pre>${JSON.stringify(data, null, 2)}</pre>`;
                } else if (data.status === 'connected') {
                    document.getElementById('qr-code').innerHTML = 'WhatsApp Connected!';
                }
            } catch (error) {
                console.error("Error fetching status:", error);
            }
        }

        // Cek status setiap 10 detik (kurangi frekuensi)
        setInterval(checkStatus, 50000);
    </script>
</body>

</html>
