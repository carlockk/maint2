<?php
include 'includes/phpqrcode/qrlib.php';
include 'db.php';

function generarQR($equipo_id) {
    $archivo = "qr_codes/qr_" . $equipo_id . ".png";
    $texto = (string)$equipo_id;  // Guardamos SOLO el ID en el QR
    QRcode::png($texto, $archivo, QR_ECLEVEL_L, 5);
    return $archivo;
}
?>
