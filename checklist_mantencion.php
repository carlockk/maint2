<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'tecnico') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['equipo_id'])) {
    echo "<p style='color:red;'>❌ Error: No se proporcionó un equipo.</p>";
    exit();
}

$equipo_id = intval($_GET['equipo_id']);
$usuario_id = $_SESSION['usuario_id'];

// Verificar asignación del técnico al edificio del equipo
$stmt = $conn->prepare("SELECT ed.id AS edificio_id FROM equipos e JOIN edificios ed ON e.edificio_id = ed.id WHERE e.id = ?");
$stmt->bind_param("i", $equipo_id);
$stmt->execute();
$res = $stmt->get_result();
$eq = $res->fetch_assoc();
$stmt->close();

if (!$eq) {
    echo "<p style='color:red;'>❌ Equipo no encontrado.</p>";
    exit();
}

$stmt = $conn->prepare("SELECT id FROM asignaciones WHERE edificio_id = ? AND tecnico_id = ?");
$stmt->bind_param("ii", $eq['edificio_id'], $usuario_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    echo "<p style='color:red;'>❌ No tienes asignado este edificio.</p>";
    exit();
}
$stmt->close();

// Obtener mantención en curso
$stmt = $conn->prepare("SELECT id FROM mantenciones WHERE equipo_id = ? AND usuario_id = ? AND hora_fin IS NULL ORDER BY hora_inicio DESC LIMIT 1");
$stmt->bind_param("ii", $equipo_id, $usuario_id);
$stmt->execute();
$res = $stmt->get_result();
$mant = $res->fetch_assoc();
$stmt->close();
$mantencion_id = $mant ? $mant['id'] : null;

if (!$mantencion_id) {
    echo "<p style='color:red;'>❌ Debes iniciar la mantención antes de completar el checklist.</p>";
    exit();
}

// Respuestas previas
$respuestas = [];
$stmt = $conn->prepare("SELECT item_id, estado FROM checklist_respuestas WHERE mantencion_id = ?");
$stmt->bind_param("i", $mantencion_id);
$stmt->execute();
$r = $stmt->get_result();
while ($row = $r->fetch_assoc()) {
    $respuestas[$row['item_id']] = $row['estado'];
}
$stmt->close();

// Obtener checklist asociado al equipo desde checklist_equipos
$stmt = $conn->prepare("
    SELECT c.id, c.nombre 
    FROM checklists c
    JOIN checklist_equipos ce ON c.id = ce.checklist_id
    WHERE ce.equipo_id = ?
");
$stmt->bind_param("i", $equipo_id);
$stmt->execute();
$result = $stmt->get_result();
$checklist = $result->fetch_assoc();
$stmt->close();

if (!$checklist) {
    echo "<p style='color:red;'>❌ No hay checklist asociado a este equipo.</p>";
    exit();
}

$checklist_id = $checklist['id'];

// Obtener ítems del checklist con jerarquía
$stmt = $conn->prepare("
    SELECT id, nombre, nivel, padre_id 
    FROM checklist_items 
    WHERE checklist_id = ? 
    ORDER BY nivel ASC, padre_id ASC, id ASC
");
$stmt->bind_param("i", $checklist_id);
$stmt->execute();
$items_result = $stmt->get_result();
$stmt->close();

if ($items_result->num_rows === 0) {
    echo "<p style='color:red;'>❌ No hay ítems en el checklist.</p>";
    exit();
}

// Organizar ítems en jerarquía correctamente
$items = [];
while ($row = $items_result->fetch_assoc()) {
    $padre_id = $row['padre_id'] ?? 0; // Si no tiene padre, lo asignamos a nivel raíz
    $items[$padre_id][] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist de Mantención</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'menu_tecnico.php'; ?>
<div class="main-content">
    <h2>Checklist de Mantención - <?= htmlspecialchars($checklist['nombre']) ?></h2>

    <form action="guardar_checklist.php" method="POST" class="offline-form">
        <input type="hidden" name="equipo_id" value="<?= $equipo_id ?>">
        <input type="hidden" name="checklist_id" value="<?= $checklist_id ?>">
        <input type="hidden" name="mantencion_id" value="<?= $mantencion_id ?>">

        <table class="checklist-table" border="1" width="100%">
            <tr>
                <th>Ítem</th>
                <th>Estado</th>
            </tr>

            <?php
            function mostrarItems($items, $padre_id = 0, $nivel = 0) {
                global $respuestas;
                if (!isset($items[$padre_id])) return;

                foreach ($items[$padre_id] as $item) {
                    echo "<tr>";
                    
                    // Aplicar formato visual según nivel
                    $padding = $nivel * 30; // Espaciado por nivel
                    $class = $nivel === 0 ? 'check-main' : ($nivel === 1 ? 'check-sub' : 'check-subsub');
                    echo "<td class='{$class}' style='padding-left: {$padding}px;'>" . htmlspecialchars($item['nombre']) . "</td>";

                    if ($nivel === 0) {
                        echo "<td></td>"; // Sin select para los ítems principales
                    } else {
                        $sel = $respuestas[$item['id']] ?? '';
                        echo "<td><select name='estado[{$item['id']}]' required>";
                        $opciones = ['Sin novedad', 'Corregido', 'Sin corregir', 'No aplica'];
                        echo "<option value=''>Seleccionar</option>";
                        foreach ($opciones as $op) {
                            $s = ($sel === $op) ? 'selected' : '';
                            echo "<option value='{$op}' {$s}>{$op}</option>";
                        }
                        echo "</select></td>";
                    }

                    echo "</tr>";

                    // Llamada recursiva para mostrar subítems
                    mostrarItems($items, $item['id'], $nivel + 1);
                }
            }

            // Llamada inicial para mostrar los ítems principales
            mostrarItems($items);
            ?>
        </table>

        <br>
        <button type="submit">✅ Guardar Checklist</button>
        <button type="button" onclick="window.location.href='mantencion.php?equipo_id=<?= $equipo_id ?>'">⬅ Volver Atrás</button>
    </form>

</div>

<script src="offline.js"></script>
</body>
</html>
