<?php
session_start();
include 'db.php';

if ($_SESSION['rol'] !== 'tecnico') {
    header("Location: login.php");
    exit();
}

$equipo_id = $_GET['id'];

// Obtener información del equipo
$stmt = $conn->prepare("SELECT e.*, ed.nombre AS edificio FROM equipos e 
JOIN edificios ed ON e.edificio_id = ed.id WHERE e.id = ?");
$stmt->bind_param("i", $equipo_id);
$stmt->execute();
$equipo = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Equipo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Detalles del Equipo</h2>
    <p><strong>Nombre:</strong> <?= $equipo['nombre'] ?></p>
    <p><strong>Modelo:</strong> <?= $equipo['modelo'] ?></p>
    <p><strong>Año de Fabricación:</strong> <?= $equipo['anio_fabricacion'] ?></p>
    <p><strong>Plan de Mantenimiento:</strong> <?= $equipo['plan_mantenimiento'] ?></p>
    <p><strong>Ubicación:</strong> <?= $equipo['edificio'] ?></p>

    <h3>Opciones</h3>
    <a href="mantencion.php?equipo_id=<?= $equipo['id'] ?>&start=1">Iniciar Mantención</a>
    <a href="reparacion.php?equipo_id=<?= $equipo['id'] ?>">Registrar Reparación</a>
    <a href="emergencia.php?equipo_id=<?= $equipo['id'] ?>">Registrar Emergencia</a>
</body>
</html>
