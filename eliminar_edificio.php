<?php
session_start();
include 'db.php';

if ($_SESSION['rol'] !== 'admin') {
    echo json_encode(["success" => false, "error" => "No autorizado"]);
    exit();
}

if (!isset($_POST['id'])) {
    echo json_encode(["success" => false, "error" => "ID no proporcionado"]);
    exit();
}

$id = $_POST['id'];

// Eliminar el edificio
$stmt = $conn->prepare("DELETE FROM edificios WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => "Error al eliminar"]);
}

$stmt->close();
?>
