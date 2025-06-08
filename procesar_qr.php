<?php
include 'db.php';

if (!isset($_GET['qr'])) {
    echo json_encode(['success' => false, 'error' => 'No se proporcionó un código QR.']);
    exit();
}

//$qr_code = trim($_GET['qr']); // Limpiar espacios en blanco
$qr_code = preg_replace('/\D/', '', $_GET['qr']); // Eliminar caracteres no numéricos


// Buscar directamente el ID en la columna qr_code
//$stmt = $conn->prepare("SELECT id FROM equipos WHERE qr_code = ?");
$stmt = $conn->prepare("SELECT id FROM equipos WHERE id = ?");

$stmt->bind_param("s", $qr_code);
$stmt->execute();
$result = $stmt->get_result();
$equipo = $result->fetch_assoc();
$stmt->close();

if ($equipo) {
    echo json_encode(['success' => true, 'equipo_id' => $equipo['id']]);
} else {
    echo json_encode(['success' => false, 'error' => 'El código QR no está asociado a ningún equipo.']);
}
?>
