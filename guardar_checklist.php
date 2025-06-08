<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'tecnico') {
    header("Location: login.php");
    exit();
}

if (
    $_SERVER["REQUEST_METHOD"] == "POST" &&
    isset($_POST['checklist_id']) &&
    isset($_POST['estado']) &&
    isset($_POST['mantencion_id'])
) {
    $checklist_id  = intval($_POST['checklist_id']);
    $equipo_id     = intval($_POST['equipo_id']);
    $mantencion_id = intval($_POST['mantencion_id']);
    $usuario_id    = $_SESSION['usuario_id'];

    if (!$mantencion_id) {
        echo "<p style='color:red;'>❌ Error: No se encontró una mantención en curso para este equipo.</p>";
        exit();
    }

    // Guardar respuestas actualizando si ya existen
    foreach ($_POST['estado'] as $item_id => $estado) {
        if (!empty($estado)) {
            $stmt = $conn->prepare("DELETE FROM checklist_respuestas WHERE mantencion_id = ? AND item_id = ?");
            $stmt->bind_param("ii", $mantencion_id, $item_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare(
                "INSERT INTO checklist_respuestas (checklist_id, equipo_id, mantencion_id, usuario_id, item_id, estado, fecha) VALUES (?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->bind_param("iiiiss", $checklist_id, $equipo_id, $mantencion_id, $usuario_id, $item_id, $estado);
            $stmt->execute();
            $stmt->close();
        }
    }

    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo "<style>.msg{position:fixed;top:40%;left:50%;transform:translate(-50%,-50%);background:#2ecc71;color:#fff;padding:20px;border-radius:8px;font-size:18px;}</style>";
    echo "<script>setTimeout(function(){window.location.href='mantencion.php?equipo_id=$equipo_id';},1500);</script>";
    echo "</head><body><div class='msg'>✅ Checklist guardado correctamente.</div></body></html>";
    exit();
} else {
    echo "<p style='color:red;'>❌ Error: No se enviaron los datos correctamente.</p>";
}
?>
