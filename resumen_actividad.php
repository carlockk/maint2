<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'tecnico') {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$actividades = $conn->query("SELECT * FROM historial WHERE usuario_id = $usuario_id ORDER BY fecha DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Actividades</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Historial Completo de Actividades</h2>
    <ul>
        <?php while ($row = $actividades->fetch_assoc()) : ?>
            <li><?= $row['fecha'] ?> - <?= ucfirst($row['tipo']) ?> en equipo ID <?= $row['equipo_id'] ?></li>
        <?php endwhile; ?>
    </ul>
    <a href="dashboard_tecnico.php">Volver</a>
</body>
</html>
