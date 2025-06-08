<?php
session_start();
include 'db.php';

// Obtener asignaciones
$stmt = $conn->prepare("
    SELECT a.id, u.nombre AS tecnico, e.nombre AS edificio 
    FROM asignaciones a
    JOIN usuarios u ON a.tecnico_id = u.id
    JOIN edificios e ON a.edificio_id = e.id
    ORDER BY a.id DESC
");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>" . htmlspecialchars($row['tecnico']) . "</td>
            <td>" . htmlspecialchars($row['edificio']) . "</td>
            <td><button class='btn-delete' onclick='eliminarAsignacion(" . $row['id'] . ")'>Eliminar</button></td>
          </tr>";
}

$stmt->close();
?>

<script>
function eliminarAsignacion(id) {
    if (confirm("¿Seguro que quieres eliminar esta asignación?")) {
        $.post("eliminar_asignacion.php", { id: id }, function(response) {
            alert(response);
            cargarAsignaciones();
        });
    }
}
</script>
