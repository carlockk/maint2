<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'tecnico') {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Definir registros por página
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$inicio = ($pagina_actual - 1) * $registros_por_pagina;

// Obtener edificios asignados
$stmt = $conn->prepare("
    SELECT e.id AS edificio_id, e.nombre AS destino, e.direccion, e.comuna
    FROM asignaciones a
    JOIN edificios e ON a.edificio_id = e.id
    WHERE a.tecnico_id = ?
    LIMIT ?, ?
");
$stmt->bind_param("iii", $usuario_id, $inicio, $registros_por_pagina);
$stmt->execute();
$result = $stmt->get_result();

$asignaciones = [];
$mostrar_estado = false;

while ($row = $result->fetch_assoc()) {
    $edificio_id = $row['edificio_id'];

    // Buscar última mantención de este técnico en este edificio
    $stmt2 = $conn->prepare("
        SELECT m.hora_inicio, m.hora_fin
        FROM mantenciones m
        JOIN equipos eq ON eq.id = m.equipo_id
        WHERE eq.edificio_id = ? AND m.usuario_id = ?
        ORDER BY m.id DESC LIMIT 1
    ");
    $stmt2->bind_param("ii", $edificio_id, $usuario_id);
    $stmt2->execute();
    $mantencion = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();

    if (!$mantencion) {
        $estado = 'Pendiente';
    } elseif ($mantencion['hora_inicio'] && !$mantencion['hora_fin']) {
        $estado = 'En Proceso';
        $mostrar_estado = true;
    } else {
        $estado = 'Finalizado';
        $mostrar_estado = true;
    }

    $row['estado'] = $estado;
    $asignaciones[] = $row;
}

function obtenerColorEstado($estado)
{
    switch ($estado) {
        case 'En Proceso': return '#f1c40f'; // Amarillo
        case 'Finalizado': return '#2ecc71'; // Verde
        default: return 'transparent';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Técnico</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
<?php include 'menu_tecnico.php'; ?>
<div class="main-content">
    <h2>Destinos Asignados</h2>

    <?php if (count($asignaciones) > 0): ?>
        <div style="overflow-x: auto;">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Destino</th>
                        <th>Dirección</th>
                        <th>Comuna</th>
                        <?php if ($mostrar_estado): ?>
                            <th>Estado</th>
                            <th>Color</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($asignaciones as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['destino']) ?></td>
                            <td><?= htmlspecialchars($row['direccion']) ?></td>
                            <td><?= htmlspecialchars($row['comuna']) ?></td>
                            <?php if ($mostrar_estado): ?>
                                <td><?= htmlspecialchars($row['estado']) ?></td>
                                <td>
                                    <span style="display:inline-block; width: 15px; height: 15px; border-radius: 50%; background: <?= obtenerColorEstado($row['estado']) ?>;"></span>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="pagination">
            <?php
            $total_resultados = $conn->query("
                SELECT COUNT(*) AS total
                FROM asignaciones
                WHERE tecnico_id = $usuario_id
            ")->fetch_assoc()['total'];
            $total_paginas = ceil($total_resultados / $registros_por_pagina);
            for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?pagina=<?= $i ?>" <?= $pagina_actual == $i ? 'class="active"' : '' ?>><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php else: ?>
        <p>No tienes destinos asignados.</p>
    <?php endif; ?>

    <h3>Escanear Código QR</h3>
    <a href="escanear_qr.php" class="btn-qr">📷 Escanear QR</a>
</div>
<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/maintcheck/service-worker.js')
      .catch(err => console.error('SW reg failed', err));
  }
</script>
</body>
</html>
