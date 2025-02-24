<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Gateway</title>
    <script src="https://cdn.socket.io/4.0.1/socket.io.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <h1>WhatsApp Gateway</h1>
    <div id="status">Checking status...</div>
    <div id="qr-container" style="display: none;">
        <h3>Scan QR Code</h3>
        <img id="qr-code" src="" alt="QR Code">
    </div>
    <button id="start-session" style="display: none;">Tambah Device</button>
    <button id="logout" style="display: none;">Logout</button>
    <button id="force-disconnect" style="display: none;">Hapus Sandingan</button>

    <script>
        const socket = io("http://localhost:3000");

        function checkStatus() {
            $.get("/api/whatsapp/status", function(data) {
                if (data.connected) {
                    $("#status").text("Device Connected");
                    $("#logout, #force-disconnect").show();
                    $("#start-session, #qr-container").hide();
                } else {
                    $("#status").text("No Device Connected");
                    $("#start-session").show();
                    $("#logout, #force-disconnect, #qr-container").hide();
                }
            });
        }

        socket.on("qr", function(qr) {
            $("#qr-code").attr("src", "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" +
                encodeURIComponent(qr));
            $("#qr-container").show();
        });

        $("#start-session").click(function() {
            $.post("/api/whatsapp/start", function() {
                $("#start-session").hide();
            });
        });

        $("#logout").click(function() {
            $.post("/api/whatsapp/logout", function() {
                checkStatus();
            });
        });

        $("#force-disconnect").click(function() {
            $.post("/api/whatsapp/force-disconnect", function() {
                checkStatus();
            });
        });

        checkStatus();
    </script>
</body>

</html>
