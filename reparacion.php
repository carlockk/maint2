<?php
session_start();
include 'db.php';

if ($_SESSION['rol'] !== 'tecnico') {
    header("Location: login.php");
    exit();
}

// Obtener edificios
$edificios = $conn->query("SELECT * FROM edificios");

// Obtener equipos del edificio seleccionado
$equipos = [];
if (isset($_POST['edificio_id'])) {
    $edificio_id = intval($_POST['edificio_id']);
    $equipos = $conn->query("SELECT * FROM equipos WHERE edificio_id = $edificio_id");
}

// Registrar reparaci車n
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar_reparacion'])) {
    $equipo_id = intval($_POST['equipo_id']);
    $usuario_id = $_SESSION['usuario_id'];
    $fecha = date('Y-m-d');
    $observaciones = trim($_POST['observaciones']);

    $stmt = $conn->prepare("INSERT INTO reparaciones (equipo_id, usuario_id, fecha, observaciones) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $equipo_id, $usuario_id, $fecha, $observaciones);
    $stmt->execute();
    $stmt->close();

    // Guardar en historial
    $stmt = $conn->prepare("INSERT INTO historial (tipo, equipo_id, usuario_id, fecha, detalles) VALUES ('reparacion', ?, ?, ?, ?)");
    $stmt->bind_param("iiss", $equipo_id, $usuario_id, $fecha, $observaciones);
    $stmt->execute();
    $stmt->close();

    header("Location: dashboard_tecnico.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Reparaci車n</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'menu_tecnico.php'; ?>
<div class="main-content">
    <h2>Registrar Reparaci車n</h2>

    <form method="POST">
        <label>Seleccionar Edificio:</label>
        <select name="edificio_id" onchange="this.form.submit()" required>
            <option value="">Seleccionar</option>
            <?php while ($row = $edificios->fetch_assoc()) : ?>
                <option value="<?= $row['id'] ?>" <?= (isset($edificio_id) && $edificio_id == $row['id']) ? 'selected' : '' ?>>
                    <?= $row['nombre'] ?>
                </option>
            <?php endwhile; ?>
        </select>
    </form>

    <?php if (!empty($equipos)) : ?>
    <form method="POST">
        <input type="hidden" name="edificio_id" value="<?= $edificio_id ?>">
        <label>Seleccionar Equipo:</label>
        <select name="equipo_id" required>
            <?php while ($row = $equipos->fetch_assoc()) : ?>
                <option value="<?= $row['id'] ?>"><?= $row['nombre'] ?> - <?= $row['modelo'] ?></option>
            <?php endwhile; ?>
        </select>

        <label>Observaciones:</label>
        <textarea name="observaciones" required></textarea>

        <button type="submit" name="registrar_reparacion">Registrar Reparaci車n</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
