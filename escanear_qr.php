<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'tecnico') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escanear Código QR</title>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <link rel="stylesheet" href="style.css">
</head>

<body>
<?php include 'menu_tecnico.php'; ?>
    <div class="main-content">
        <h2>Escanear Código QR</h2>

        <div id="reader" style="width: 300px;"></div>
        <p id="mensaje">Apunta la cámara al código QR</p>

        <script>
            let scanning = false;

            function processCode(cleanCode) {
                fetch("procesar_qr.php?qr=" + encodeURIComponent(cleanCode))
                    .then(r => r.json())
                    .then(data => {
                        console.log("📌 Respuesta del servidor: ", data);
                        if (data.success) {
                            window.location.href = "equipo_info.php?id=" + data.equipo_id;
                        } else {
                            alert("⚠️ " + data.error);
                            scanning = false;
                            html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess, onScanError);
                        }
                    })
                    .catch(err => {
                        console.error("Error en la solicitud:", err);
                        alert("⚠️ Error al procesar el código QR.");
                        scanning = false;
                        html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess, onScanError);
                    });
            }

            function onScanSuccess(qrCodeMessage) {
                if (scanning) return;
                scanning = true;
                html5QrCode.stop().then(() => {
                    console.log("📌 Código QR detectado: ", qrCodeMessage);
                    let qrCodeClean = qrCodeMessage.replace(/\D/g, '').trim();
                    if (!/^\d+$/.test(qrCodeClean)) {
                        alert("⚠️ Código QR inválido. Asegúrate de escanear un código válido.");
                        scanning = false;
                        html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess, onScanError);
                        return;
                    }
                    processCode(qrCodeClean);
                });
            }

            function onScanError(errorMessage) {
                if (!window.lastErrorTime || Date.now() - window.lastErrorTime > 3000) {
                    console.warn("⚠️ No se detectó código QR. Inténtalo de nuevo.");
                    window.lastErrorTime = Date.now();
                }
            }

            let html5QrCode = new Html5Qrcode("reader");
            let config = { fps: 10, qrbox: { width: 250, height: 250 } };

            html5QrCode.start(
                { facingMode: "environment" },
                config,
                onScanSuccess,
                onScanError
            ).catch(err => {
                console.error("Error al iniciar la cámara:", err);
                document.getElementById("mensaje").innerText = "⚠️ Error al acceder a la cámara. Verifica los permisos.";
            });
        </script>

</body>

</html>