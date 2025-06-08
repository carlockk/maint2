<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['checklist_id']) || !isset($_GET['equipo_id']) || !isset($_GET['fecha'])) {
    echo "<p>Error: Faltan parámetros.</p>";
    exit();
}

$checklist_id = $_GET['checklist_id'];
$equipo_id = $_GET['equipo_id'];
$fecha = $_GET['fecha'];

// Obtener los ítems del checklist en la fecha seleccionada
$stmt = $conn->prepare("
    SELECT cr.item_id, ci.nombre AS item_nombre, ci.nivel, ci.padre_id, cr.estado 
    FROM checklist_respuestas cr
    JOIN checklist_items ci ON cr.item_id = ci.id
    WHERE cr.checklist_id = ? AND cr.equipo_id = ? AND DATE(cr.fecha) = ?
    ORDER BY ci.nivel ASC, ci.padre_id ASC, ci.id ASC
");
$stmt->bind_param("iis", $checklist_id, $equipo_id, $fecha);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows === 0) {
    echo "<p>No hay datos para este checklist.</p>";
    exit();
}

// Organizar los datos en una estructura jerárquica
$checklist_data = [];
while ($row = $result->fetch_assoc()) {
    $checklist_data[$row['padre_id']][] = $row;
}

function mostrar_items($padre_id, $checklist_data, $nivel = 0) {
    if (!isset($checklist_data[$padre_id])) {
        return;
    }

    echo "<ul>";
    foreach ($checklist_data[$padre_id] as $item) {
        $estilo = ($item['nivel'] == 1) ? "font-weight: bold; font-size: 18px;" : "";
        echo "<li style='$estilo'>";
        echo htmlspecialchars($item['item_nombre']) . " - <strong>" . htmlspecialchars($item['estado']) . "</strong>";
        mostrar_items($item['item_id'], $checklist_data, $nivel + 1);
        echo "</li>";
    }
    echo "</ul>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Checklist</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Detalle del Checklist - <?= htmlspecialchars($fecha) ?></h2>
    <p><a href="historial.php?fecha=<?= $fecha ?>">⬅ Volver al Historial</a></p>

    <?php mostrar_items(null, $checklist_data); ?>
</body>
</html>
