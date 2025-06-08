<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    echo json_encode(["success" => false, "error" => "Acceso no autorizado."]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tecnico_id']) && isset($_POST['edificio_id'])) {
    $tecnico_id = intval($_POST['tecnico_id']);
    $edificio_id = intval($_POST['edificio_id']);

    // Obtener asignación existente del edificio
    $stmt = $conn->prepare("SELECT id, tecnico_id FROM asignaciones WHERE edificio_id = ? LIMIT 1");
    $stmt->bind_param("i", $edificio_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $asig = $res->fetch_assoc();
    $stmt->close();

    if ($asig) {
        if ($asig['tecnico_id'] == $tecnico_id) {
            echo json_encode(["success" => false, "error" => "⚠️ Este técnico ya está asignado a este edificio."]);
        } else {
            $stmt = $conn->prepare("UPDATE asignaciones SET tecnico_id = ?, estado = 'Pendiente' WHERE id = ?");
            $stmt->bind_param("ii", $tecnico_id, $asig['id']);
            $ok = $stmt->execute();
            $stmt->close();
            echo json_encode(["success" => $ok]);
        }
    } else {
        // Insertar nueva asignación
        $stmt = $conn->prepare("INSERT INTO asignaciones (tecnico_id, edificio_id, estado) VALUES (?, ?, 'Pendiente')");
        $stmt->bind_param("ii", $tecnico_id, $edificio_id);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode(["success" => $ok]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Solicitud inválida."]);
}
exit();
?>
