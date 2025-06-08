<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];

// Validación de parámetros obligatorios
if (!isset($_GET['checklist_id']) || !isset($_GET['equipo_id']) || !isset($_GET['mantencion_id'])) {
    echo "<p style='color:red;'>❌ Error: Faltan parámetros.</p>";
    exit();
}

$checklist_id = intval($_GET['checklist_id']);
$equipo_id = intval($_GET['equipo_id']);
$mantencion_id = intval($_GET['mantencion_id']);

// Obtener el nombre del checklist
$stmt = $conn->prepare("SELECT nombre FROM checklists WHERE id = ?");
$stmt->bind_param("i", $checklist_id);
$stmt->execute();
$result = $stmt->get_result();
$checklist = $result->fetch_assoc();
$stmt->close();

if (!$checklist) {
    echo "<p style='color:red;'>❌ No se encontró el checklist.</p>";
    exit();
}

// Nueva consulta mejorada: evitar duplicados usando subconsulta para el estado
$query = "
    SELECT ci.id, ci.nombre, ci.nivel, ci.padre_id,
        (
            SELECT estado 
            FROM checklist_respuestas cr 
            WHERE cr.item_id = ci.id 
            AND cr.checklist_id = ? 
            AND cr.equipo_id = ? 
            AND cr.mantencion_id = ?
            LIMIT 1
        ) AS estado
    FROM checklist_items ci
    WHERE ci.checklist_id = ?
    ORDER BY ci.nivel, ci.padre_id IS NULL, ci.padre_id, ci.id
";

$stmt = $conn->prepare($query);
$stmt->bind_param("iiii", $checklist_id, $equipo_id, $mantencion_id, $checklist_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Agrupar los ítems jerárquicamente
$items = [];
while ($row = $result->fetch_assoc()) {
    $padre_id = $row['padre_id'] ?? 0;
    $items[$padre_id][] = $row;
}

// Función recursiva para mostrar ítems jerárquicamente
function mostrarItems($padre_id, $items, $nivel = 0) {
    if (!isset($items[$padre_id])) return '';

    $html = '';
    foreach ($items[$padre_id] as $item) {
        $indentacion = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $nivel * 2);
        $negrita = ($item['nivel'] == 1) ? "font-weight: bold;" : "";

        $html .= "<tr>";
        $html .= "<td style='{$negrita}'>{$indentacion}" . htmlspecialchars($item['nombre']) . "</td>";

        if ($item['nivel'] > 1) {
            $html .= "<td>" . htmlspecialchars($item['estado']) . "</td>";
        } else {
            $html .= "<td></td>"; // vacío para ítems principales
        }

        $html .= "</tr>";

        $html .= mostrarItems($item['id'], $items, $nivel + 1);
    }
    return $html;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Checklist</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Menú dinámico -->
<div class="sidebar">
    <h2>Menú</h2>
    <?php if ($rol === 'admin'): ?>
        <?php include 'menu.php'; ?>
    <?php else: ?>
        <?php include 'menu_tecnico.php'; ?>
    <?php endif; ?>
</div>

<div class="main-content">
    <h2>📋 Detalle del Checklist: <?= htmlspecialchars($checklist['nombre']) ?></h2>
    <p><strong>🆔 Mantención ID:</strong> <?= htmlspecialchars($mantencion_id) ?></p>

    <h3>📝 Respuestas Registradas</h3>

    <?php if (empty($items)) : ?>
        <p>No hay ítems registrados en este checklist.</p>
    <?php else : ?>
        <table border="1" cellpadding="6" cellspacing="0">
            <tr style="background: #3498db; color: white;">
                <th>Ítem</th>
                <th>Estado</th>
            </tr>
            <?= mostrarItems(0, $items) ?>
        </table>
    <?php endif; ?>

    <p><button style="margin-top:15px;" onclick="window.history.back()">⬅ Volver Atrás</button></p>
</div>

</body>
</html>
