<?php
include 'db.php';

// Obtener IDs de los tipos de equipo
$tipo_ascensor = $conn->query("SELECT id FROM tipos_equipos WHERE nombre = 'Ascensor'")->fetch_assoc()['id'];
$tipo_escalera = $conn->query("SELECT id FROM tipos_equipos WHERE nombre = 'Escalera'")->fetch_assoc()['id'];

// Obtener un edificio existente
$edificio_id = $conn->query("SELECT id FROM edificios LIMIT 1")->fetch_assoc()['id'];

if (!$edificio_id) {
    die("No hay edificios registrados.");
}

// Insertar equipos del tipo Ascensor y Escalera
$conn->query("INSERT INTO equipos (nombre, modelo, anio_fabricacion, plan_mantenimiento, edificio_id, tipo_id, qr_code) VALUES 
    ('Ascensor Z1', 'Modelo Alpha', 2021, 'Mensual', $edificio_id, $tipo_ascensor, 'QR_A1'),
    ('Escalera R1', 'Modelo Beta', 2020, 'Trimestral', $edificio_id, $tipo_escalera, 'QR_E1')");

echo "Equipos insertados correctamente.";
?>
