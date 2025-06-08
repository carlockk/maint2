<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['mantencion_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
    exit;
}

$mantencion_id = intval($_POST['mantencion_id']);
$motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';

// Obtener datos antes de eliminar
$stmt = $conn->prepare("SELECT equipo_id, usuario_id FROM mantenciones WHERE id = ? AND hora_fin IS NULL");
$stmt->bind_param("i", $mantencion_id);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();
$stmt->close();

$success = false;
if ($info) {
    $stmt = $conn->prepare("DELETE FROM mantenciones WHERE id = ? AND hora_fin IS NULL");
    $stmt->bind_param("i", $mantencion_id);
    $success = $stmt->execute();
    $stmt->close();

    if ($success && $motivo !== '') {
        $fecha = date('Y-m-d');
        $detalle = 'Cancelada: ' . $motivo;
        $stmt = $conn->prepare("INSERT INTO historial (tipo, equipo_id, usuario_id, fecha, detalles) VALUES ('mantencion', ?, ?, ?, ?)");
        $stmt->bind_param("iiss", $info['equipo_id'], $info['usuario_id'], $fecha, $detalle);
        $stmt->execute();
        $stmt->close();
    }
}

echo json_encode(['success' => $success]);
