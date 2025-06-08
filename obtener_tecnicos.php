<?php
include 'db.php';

if (isset($_GET['edificio_id'])) {
    $edificio_id = intval($_GET['edificio_id']);

    $stmt = $conn->prepare("
        SELECT u.nombre 
        FROM asignaciones a
        JOIN usuarios u ON a.tecnico_id = u.id
        WHERE a.edificio_id = ?
    ");
    $stmt->bind_param("i", $edificio_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $tecnicos = [];
    while ($row = $result->fetch_assoc()) {
        $tecnicos[] = ["nombre" => $row['nombre']];
    }

    echo json_encode(["tecnicos" => $tecnicos]);
    exit();
}
?>
