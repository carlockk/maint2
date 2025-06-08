<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tecnico_id']) && isset($_POST['edificio_id'])) {
    $tecnico_id = intval($_POST['tecnico_id']);
    $edificio_id = intval($_POST['edificio_id']);

    // Verificar si ya existe la asignación
    $stmt = $conn->prepare("SELECT id FROM asignaciones WHERE tecnico_id = ? AND edificio_id = ?");
    $stmt->bind_param("ii", $tecnico_id, $edificio_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Insertar la asignación
        $stmt = $conn->prepare("INSERT INTO asignaciones (tecnico_id, edificio_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $tecnico_id, $edificio_id);
        if ($stmt->execute()) {
            echo "✅ Asignación realizada con éxito.";
        } else {
            echo "❌ Error al asignar técnico.";
        }
    } else {
        echo "⚠️ El técnico ya está asignado a este edificio.";
    }

    $stmt->close();
} else {
    echo "❌ Error en la solicitud.";
}
?>
