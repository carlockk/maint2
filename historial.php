<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];
$rango = $_GET['rango'] ?? date("Y-m-d");
$fechas = preg_split('/\s*to\s*/', $rango);
$inicio = $fechas[0];
$fin = $fechas[1] ?? $fechas[0];

$per_page = 20;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$baseQuery = "
    SELECT 'mantencion' AS tipo,
           m.id AS registro_id,
           m.fecha,
           m.hora_inicio,
           m.hora_fin,
           TIMESTAMPDIFF(MINUTE, m.hora_inicio, m.hora_fin) AS duracion,
           m.usuario_id,
           e.id AS equipo_id,
           e.nombre AS equipo,
           u.nombre AS tecnico,
           (SELECT cr.checklist_id FROM checklist_respuestas cr WHERE cr.mantencion_id = m.id LIMIT 1) AS checklist_id,
           (SELECT c.nombre FROM checklist_respuestas cr JOIN checklists c ON cr.checklist_id = c.id WHERE cr.mantencion_id = m.id LIMIT 1) AS checklist_nombre,
           NULL AS observacion
    FROM mantenciones m
    JOIN equipos e ON m.equipo_id = e.id
    JOIN usuarios u ON m.usuario_id = u.id
    WHERE DATE(m.fecha) BETWEEN ? AND ?

    UNION

    SELECT 'reparacion', r.id, r.fecha, NULL, NULL, NULL, r.usuario_id, e.id, e.nombre, u.nombre, NULL, NULL, r.observaciones
    FROM reparaciones r
    JOIN equipos e ON r.equipo_id = e.id
    JOIN usuarios u ON r.usuario_id = u.id
    WHERE DATE(r.fecha) BETWEEN ? AND ?

    UNION

    SELECT 'emergencia', em.id, em.fecha, NULL, NULL, NULL, em.usuario_id, e.id, e.nombre, u.nombre, NULL, NULL, em.observaciones
    FROM emergencias em
    JOIN equipos e ON em.equipo_id = e.id
    JOIN usuarios u ON em.usuario_id = u.id
    WHERE DATE(em.fecha) BETWEEN ? AND ?

    UNION

    SELECT 'mantencion cancelada', h.id, h.fecha, NULL, NULL, 0 AS duracion, h.usuario_id, e.id, e.nombre, u.nombre, NULL, NULL, h.detalles
    FROM historial h
    JOIN equipos e ON h.equipo_id = e.id
    JOIN usuarios u ON h.usuario_id = u.id
    WHERE h.tipo = 'mantencion' AND DATE(h.fecha) BETWEEN ? AND ?
";

$query = "SELECT * FROM (" . $baseQuery . ") AS t ORDER BY fecha DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ssssssssii", $inicio, $fin, $inicio, $fin, $inicio, $fin, $inicio, $fin, $per_page, $offset);
$stmt->execute();
$resultado = $stmt->get_result();
$stmt->close();

$countQuery = "SELECT COUNT(*) AS total FROM (" . $baseQuery . ") AS total";
$stmt = $conn->prepare($countQuery);
$stmt->bind_param("ssssssss", $inicio, $fin, $inicio, $fin, $inicio, $fin, $inicio, $fin);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
$total_paginas = ceil($total / $per_page);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Historial de Actividades</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <style>
    .estado-curso { color: orange; font-weight: bold; }
    .btn-vermas { background: #eee; padding: 4px 8px; border-radius: 5px; cursor: pointer; }
    .modal {
        display: none; position: fixed; z-index: 1000; left: 0; top: 0;
        width: 100%; height: 100%; background-color: rgba(0,0,0,0.6);
        justify-content: center; align-items: center;
    }
    .modal-content {
        background: white; padding: 20px; border-radius: 10px; max-width: 500px;
    }
  </style>
</head>
<body>
<?php include $rol === 'admin' ? 'menu.php' : 'menu_tecnico.php'; ?>
<div class="main-content">
  <h2>📋 Historial de Actividades</h2>

  <label for="fecha_filtro">Seleccionar fechas:</label>
  <input type="text" id="fecha_filtro" value="<?= htmlspecialchars($rango) ?>" placeholder="YYYY-MM-DD to YYYY-MM-DD" style="padding: 6px;">
  <button onclick="filtrarFecha()">🔍 Buscar</button>
<?php if ($resultado->num_rows === 0): ?>
  <p style="color:red; font-weight:bold; margin-top: 20px;">❌ No hay registros para este rango.</p>
<?php else: ?>
  <table border="1" cellpadding="5" cellspacing="0">
    <thead>
      <tr>
        <th>Fecha</th>
        <th>Equipo</th>
        <th>Técnico</th>
        <th>Tipo</th>
        <th>Hora Inicio</th>
        <th>Hora Fin</th>
        <th>Duración</th>
        <th>Checklist</th>
        <th>Observación</th>
        <th>Detalle</th>
      </tr>
    </thead>
    <tbody>
    <?php while ($row = $resultado->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['fecha']) ?></td>
        <td><?= htmlspecialchars($row['equipo']) ?></td>
        <td><?= htmlspecialchars($row['tecnico']) ?></td>
        <td><?= ucfirst($row['tipo']) ?></td>
        <td><?= $row['hora_inicio'] ?? '-' ?></td>
        <td><?= $row['hora_fin'] ?? '-' ?></td>
        <td>
          <?php
            if ($row['tipo'] === 'mantencion cancelada') {
                echo '0 min';
            } elseif ($row['hora_fin']) {
                echo $row['duracion'] . ' min';
            } else {
                echo '-';
            }
          ?>
        </td>
        <td>
          <?= $row['checklist_nombre'] ? '✅ ' . htmlspecialchars($row['checklist_nombre']) : '❌' ?>
        </td>
        <td>
          <?php
            $texto = $row['observacion'] ?? '';
            if ($texto) {
              echo strlen($texto) > 20
                ? '<span class="btn-vermas" onclick="mostrarModal(`' . htmlspecialchars(addslashes($texto)) . '`)">📄 Ver más</span>'
                : htmlspecialchars($texto);
            } else {
              echo '<span style="color:gray;">Sin observación</span>';
            }
          ?>
        </td>
        <td>
          <?php if ($row['tipo'] === 'mantencion' && $row['checklist_id']): ?>
            <a href="detalle_checklist.php?checklist_id=<?= $row['checklist_id'] ?>&equipo_id=<?= $row['equipo_id'] ?>&mantencion_id=<?= $row['registro_id'] ?>&fecha=<?= $row['fecha'] ?>">🔍 Ver</a>
          <?php elseif ($row['tipo'] === 'mantencion' && is_null($row['hora_fin']) && $rol === 'tecnico' && $usuario_id == $row['usuario_id']): ?>
            <a href="mantencion.php?equipo_id=<?= $row['equipo_id'] ?>" style="color:orange;">🛠️ Continuar</a>
          <?php else: ?>
            ❌ No disponible
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
  <div style="margin-top:10px;">
    <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
      <?php if ($p == $page): ?>
        <strong><?= $p ?></strong>
      <?php else: ?>
        <a href="?rango=<?= urlencode($rango) ?>&page=<?= $p ?>"><?= $p ?></a>
      <?php endif; ?>
      <?php if ($p < $total_paginas) echo ' | '; ?>
    <?php endfor; ?>
  </div>
<?php endif; ?>
<!-- Modal para mostrar observación completa -->
<div id="modalObservacion" class="modal" onclick="cerrarModal()">
  <div class="modal-content" onclick="event.stopPropagation()">
    <h3>📝 Observación</h3>
    <p id="contenidoModal"></p>
    <button onclick="cerrarModal()">Cerrar</button>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
  // Inicializar selector de fecha
  flatpickr("#fecha_filtro", {
    mode: "range",
    dateFormat: "Y-m-d",
    disableMobile: true
  });

  // Redirigir al cambiar la fecha
  function filtrarFecha() {
    const rango = document.getElementById('fecha_filtro').value;
    if (rango) {
      window.location.href = "historial.php?rango=" + encodeURIComponent(rango);
    }
  }

  // Mostrar modal con observación completa
  function mostrarModal(texto) {
    document.getElementById("contenidoModal").innerText = texto;
    document.getElementById("modalObservacion").style.display = "flex";
  }

  // Cerrar modal
  function cerrarModal() {
    document.getElementById("modalObservacion").style.display = "none";
  }
</script>
</body>
</html>
