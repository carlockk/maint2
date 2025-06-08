<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    echo json_encode(["success" => false, "error" => "Acceso no autorizado."]);
    exit();
}

// Obtener técnicos disponibles
$tecnicos = [];
$result = $conn->query("SELECT id, nombre FROM usuarios WHERE rol = 'tecnico'");
while ($row = $result->fetch_assoc()) {
    $tecnicos[] = $row;
}

// Obtener edificios disponibles
$edificios = [];
$result = $conn->query("
    SELECT e.id, e.nombre 
    FROM edificios e
    LEFT JOIN asignaciones a ON e.id = a.edificio_id
    GROUP BY e.id
");
while ($row = $result->fetch_assoc()) {
    $edificios[] = $row;
}

echo json_encode(["success" => true, "tecnicos" => $tecnicos, "edificios" => $edificios]);
exit();
?>
