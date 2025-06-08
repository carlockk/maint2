<?php
session_start();
include 'db.php';

if ($_SESSION['rol'] !== 'admin') {
    exit("Acceso denegado.");
}

if (!isset($_GET['id'])) {
    exit("❌ Error: No se proporcionó un cliente.");
}

$cliente_id = $_GET['id'];

// Obtener datos del cliente
$stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$result = $stmt->get_result();
$cliente = $result->fetch_assoc();
$stmt->close();

if (!$cliente) {
    exit("❌ Cliente no encontrado.");
}

// Obtener edificios existentes
$edificios = $conn->query("SELECT * FROM edificios");

// Obtener edificios asignados al cliente
$edificios_asignados = [];
$res = $conn->prepare("SELECT edificio_id FROM cliente_edificios WHERE cliente_id = ?");
$res->bind_param("i", $cliente_id);
$res->execute();
$result = $res->get_result();
while ($row = $result->fetch_assoc()) {
    $edificios_asignados[] = $row['edificio_id'];
}
$res->close();

// Guardar cambios
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $rut = $_POST['rut'];
    $direccion = $_POST['direccion'];
    $comuna = $_POST['comuna'];
    $ciudad = $_POST['ciudad'];
    $correo = $_POST['correo'];
    $telefono = $_POST['telefono'];
    $edificios_seleccionados = $_POST['edificios'] ?? [];

    // Actualizar datos del cliente
    $stmt = $conn->prepare("UPDATE clientes SET nombre_razon_social=?, rut=?, direccion=?, comuna=?, ciudad=?, correo=?, telefono=? WHERE id=?");
    $stmt->bind_param("sssssssi", $nombre, $rut, $direccion, $comuna, $ciudad, $correo, $telefono, $cliente_id);
    $stmt->execute();
    $stmt->close();

    // Eliminar asignaciones antiguas
    $conn->query("DELETE FROM cliente_edificios WHERE cliente_id = $cliente_id");

    // Insertar nuevas asignaciones
    foreach ($edificios_seleccionados as $edificio_id) {
        $stmt = $conn->prepare("INSERT INTO cliente_edificios (cliente_id, edificio_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $cliente_id, $edificio_id);
        $stmt->execute();
        $stmt->close();
    }

    echo "<script>
        alert('✅ Cliente actualizado correctamente.');
        window.opener.location.reload();
        window.close();
    </script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="main-content">
    <h2>Editar Cliente</h2>
    <form method="POST">
        <input type="text" name="nombre" value="<?= htmlspecialchars($cliente['nombre_razon_social']) ?>" required>
        <input type="text" name="rut" value="<?= htmlspecialchars($cliente['rut']) ?>" required>
        <input type="text" name="direccion" value="<?= htmlspecialchars($cliente['direccion']) ?>" required>
        <input type="text" name="comuna" value="<?= htmlspecialchars($cliente['comuna']) ?>" required>
        <input type="text" name="ciudad" value="<?= htmlspecialchars($cliente['ciudad']) ?>" required>
        <input type="email" name="correo" value="<?= htmlspecialchars($cliente['correo']) ?>" required>
        <input type="text" name="telefono" value="<?= htmlspecialchars($cliente['telefono']) ?>" required>

        <label>Edificios Asignados:</label>
        <select name="edificios[]" multiple>
            <?php while ($row = $edificios->fetch_assoc()) : ?>
                <option value="<?= $row['id'] ?>" <?= in_array($row['id'], $edificios_asignados) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['nombre']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <button type="submit">💾 Guardar Cambios</button>
        <button type="button" onclick="window.close()">❌ Cancelar</button>
    </form>
</div>
</body>
</html>
