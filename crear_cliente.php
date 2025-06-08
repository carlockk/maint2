<?php
session_start();
include 'db.php';

if ($_SESSION['rol'] !== 'admin') {
    exit("Acceso denegado.");
}

// Obtener edificios existentes
$edificios = $conn->query("SELECT * FROM edificios");

// Guardar nuevo cliente
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $rut = $_POST['rut'];
    $direccion = $_POST['direccion'];
    $comuna = $_POST['comuna'];
    $ciudad = $_POST['ciudad'];
    $correo = $_POST['correo'];
    $telefono = $_POST['telefono'];
    $edificios_seleccionados = $_POST['edificios'] ?? [];

    // Insertar cliente
    $stmt = $conn->prepare("INSERT INTO clientes (nombre_razon_social, rut, direccion, comuna, ciudad, correo, telefono) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $nombre, $rut, $direccion, $comuna, $ciudad, $correo, $telefono);
    $stmt->execute();
    $cliente_id = $stmt->insert_id;
    $stmt->close();

    // Asignar edificios al cliente
    foreach ($edificios_seleccionados as $edificio_id) {
        $stmt = $conn->prepare("INSERT INTO cliente_edificios (cliente_id, edificio_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $cliente_id, $edificio_id);
        $stmt->execute();
        $stmt->close();
    }

    echo "<script>
        alert('✅ Cliente creado correctamente.');
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
    <title>Crear Cliente</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="main-content">
    <h2>Crear Cliente</h2>
    <form method="POST">
        <input type="text" name="nombre" placeholder="Nombre o Razón Social" required>
        <input type="text" name="rut" placeholder="RUT" required>
        <input type="text" name="direccion" placeholder="Dirección" required>
        <input type="text" name="comuna" placeholder="Comuna" required>
        <input type="text" name="ciudad" placeholder="Ciudad" required>
        <input type="email" name="correo" placeholder="Correo" required>
        <input type="text" name="telefono" placeholder="Teléfono" required>

        <label>Asignar Edificios:</label>
        <select name="edificios[]" multiple>
            <?php while ($row = $edificios->fetch_assoc()) : ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nombre']) ?></option>
            <?php endwhile; ?>
        </select>

        <button type="submit">✅ Crear Cliente</button>
        <button type="button" onclick="window.close()">❌ Cancelar</button>
    </form>
</
