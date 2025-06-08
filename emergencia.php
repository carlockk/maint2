<?php
session_start();
include 'db.php';

if ($_SESSION['rol'] !== 'tecnico') {
    header("Location: login.php");
    exit();
}

// Obtener edificios
$edificios = $conn->query("SELECT * FROM edificios");

// Obtener equipos de un edificio seleccionado
$equipos = [];
if (isset($_POST['edificio_id'])) {
    $edificio_id = $_POST['edificio_id'];
    $equipos = $conn->query("SELECT * FROM equipos WHERE edificio_id = $edificio_id");
}

// Registrar emergencia
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar_emergencia'])) {
    $equipo_id = $_POST['equipo_id'];
    $usuario_id = $_SESSION['usuario_id'];
    $fecha = date('Y-m-d');
    $observaciones = $_POST['observaciones'];

    $stmt = $conn->prepare("INSERT INTO emergencias (equipo_id, usuario_id, fecha, observaciones) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $equipo_id, $usuario_id, $fecha, $observaciones);
    $stmt->execute();
    $stmt->close();

    // Guardar en historial
    $stmt = $conn->prepare("INSERT INTO historial (tipo, equipo_id, usuario_id, fecha, detalles) VALUES ('emergencia', ?, ?, ?, ?)");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Emergencia</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'menu_tecnico.php'; ?>
    <div class="main-content">
    <h2>Registrar Emergencia</h2>
    <form method="POST">
        <label>Seleccionar Edificio:</label>
        <select name="edificio_id" onchange="this.form.submit()">
            <option value="">Seleccionar</option>
            <?php while ($row = $edificios->fetch_assoc()) : ?>
                <option value="<?= $row['id'] ?>" <?= isset($edificio_id) && $edificio_id == $row['id'] ? 'selected' : '' ?>>
                    <?= $row['nombre'] ?>
                </option>
            <?php endwhile; ?>
        </select>
    </form>

    <?php if (!empty($equipos)) : ?>
    <form method="POST">
        <label>Seleccionar Equipo:</label>
        <select name="equipo_id">
            <?php while ($row = $equipos->fetch_assoc()) : ?>
                <option value="<?= $row['id'] ?>"><?= $row['nombre'] ?> - <?= $row['modelo'] ?></option>
            <?php endwhile; ?>
        </select>
        <label>Observaciones:</label>
        <textarea name="observaciones" required></textarea>
        <button type="submit" name="registrar_emergencia">Registrar Emergencia</button>
    </form>
    <?php endif; ?>
</body>
</html>
