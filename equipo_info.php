<?php
session_start();
include 'db.php';

// Verificar si el usuario está autenticado y tiene el rol correcto
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'tecnico') {
    header("Location: login.php");
    exit();
}

// Validar que se recibió un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p style='color:red;'>❌ Error: No se proporcionó un ID válido de equipo.</p>";
    exit();
}

$equipo_id = intval($_GET['id']); // Convertir a entero para mayor seguridad
$usuario_id = $_SESSION['usuario_id'];

// Consultar la información del equipo
$stmt = $conn->prepare("
    SELECT e.*, ed.nombre AS edificio, ed.direccion, ed.id AS edificio_id
    FROM equipos e 
    JOIN edificios ed ON e.edificio_id = ed.id 
    WHERE e.id = ?
");
$stmt->bind_param("i", $equipo_id);
$stmt->execute();
$result = $stmt->get_result();
$equipo = $result->fetch_assoc();
$stmt->close();

// Si no se encuentra el equipo, mostrar error
if (!$equipo) {
    echo "<p style='color:red;'>❌ Error: No se encontró el equipo en la base de datos.</p>";
    exit();
}

// Verificar que el técnico tenga asignado el edificio del equipo
$stmt = $conn->prepare("SELECT id FROM asignaciones WHERE edificio_id = ? AND tecnico_id = ?");
$stmt->bind_param("ii", $equipo['edificio_id'], $usuario_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    echo "<p style='color:red;'>❌ No tienes asignado este edificio.</p>";
    exit();
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Información del Equipo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'menu_tecnico.php'; ?>
    <div class="main-content">
    <h2>Información del Equipo</h2>

    <p><strong>🏢 Edificio:</strong> <?= htmlspecialchars($equipo['edificio']) ?></p>
    <p><strong>📍 Dirección:</strong> <?= htmlspecialchars($equipo['direccion']) ?></p>
    <hr>
    <p><strong>🆔 ID del Equipo:</strong> <?= htmlspecialchars($equipo['id']) ?></p>
    <p><strong>🔧 Nombre del Equipo:</strong> <?= htmlspecialchars($equipo['nombre']) ?></p>
    <p><strong>📌 Modelo:</strong> <?= htmlspecialchars($equipo['modelo']) ?></p>
    <p><strong>📅 Año de Fabricación:</strong> <?= htmlspecialchars($equipo['anio_fabricacion']) ?></p>
    <p><strong>🛠️ Plan de Mantenimiento:</strong> <?= htmlspecialchars($equipo['plan_mantenimiento']) ?></p>
    

    <h3>Opciones de Visita</h3>
    <a class="btn-create" style= "padding: 6px; border-radius: 4px; text-decoration: none;" href="mantencion.php?equipo_id=<?= $equipo['id'] ?>&start=1">Iniciar Mantención</a>
    <a class="button-secondary2" style= "padding: 6px; border-radius: 4px; text-decoration: none;" href="reparacion.php?equipo_id=<?= $equipo['id'] ?>"> Registrar Reparación</a>
    <a class="btn-delete" style= "padding: 6px; border-radius: 4px; text-decoration: none;" href="emergencia.php?equipo_id=<?= $equipo['id'] ?>">Registrar Emergencia</a>
</body>
</html>
