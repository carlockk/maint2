<?php
session_start();
include 'db.php';

if ($_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo "<p style='color:red;'>❌ Error: No se proporcionó un cliente.</p>";
    exit();
}

$cliente_id = $_GET['id'];

// Obtener el cliente
$stmt = $conn->prepare("SELECT nombre_razon_social FROM clientes WHERE id = ?");
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$result = $stmt->get_result();
$cliente = $result->fetch_assoc();
$stmt->close();

if (!$cliente) {
    echo "<p style='color:red;'>❌ Cliente no encontrado.</p>";
    exit();
}

$stmt = $conn->prepare("
    SELECT e.id, e.nombre, e.direccion, e.comuna,
           a.tecnico_id, u.nombre AS tecnico
    FROM cliente_edificios ce
    JOIN edificios e ON ce.edificio_id = e.id
    LEFT JOIN asignaciones a ON a.edificio_id = e.id
    LEFT JOIN usuarios u ON a.tecnico_id = u.id
    WHERE ce.cliente_id = ?
");
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Obtener técnicos disponibles para asignar
$tecnicos_all = [];
$res = $conn->query("SELECT id, nombre FROM usuarios WHERE rol = 'tecnico'");
while ($t = $res->fetch_assoc()) {
    $tecnicos_all[] = $t;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edificios Asignados</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="main-content">
    <h2>Edificios Asignados a <?= htmlspecialchars($cliente['nombre_razon_social']) ?></h2>

    <table class="styled-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Dirección</th>
                <th>Comuna</th>
                <th>Técnico Asignado</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) : ?>
            <tr>
                <td><?= htmlspecialchars($row['nombre']) ?></td>
                <td><?= htmlspecialchars($row['direccion']) ?></td>
                <td><?= htmlspecialchars($row['comuna']) ?></td>
                <td>
                    <select onchange="asignarTecnico(this, <?= $row['id'] ?>)">
                        <option value="">Seleccionar Técnico</option>
                        <?php foreach ($tecnicos_all as $tec): ?>
                            <option value="<?= $tec['id'] ?>" <?php if ($row['tecnico_id'] == $tec['id']) echo 'selected'; ?>>
                                <?= htmlspecialchars($tec['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- ALERTA FLOTANTE -->
<div id="alerta" class="alerta" style="display:none;">
    <p id="mensaje-alerta"></p>
</div>

<script>
function asignarTecnico(selectElem, edificioId) {
    const tecnicoId = selectElem.value;
    if (!tecnicoId) return;
    fetch('asignar_tecnico.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `tecnico_id=${tecnicoId}&edificio_id=${edificioId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            mostrarAlerta('✅ Técnico guardado con éxito.');
        } else {
            mostrarAlerta('❌ ' + (data.error || 'Error'));
        }
    })
    .catch(() => mostrarAlerta('❌ Error en la solicitud.'));
}

function mostrarAlerta(msg) {
    const alerta = document.getElementById('alerta');
    document.getElementById('mensaje-alerta').innerText = msg;
    alerta.style.display = 'block';
    setTimeout(() => alerta.style.display = 'none', 3000);
}
</script>

<style>
    .alerta {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: #2ecc71;
        color: white;
        padding: 15px;
        border-radius: 5px;
        font-size: 16px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        z-index: 1001;
    }
</style>
</body>
</html>
