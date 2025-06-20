<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'tecnico') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['equipo_id'])) {
    echo "<p style='color:red;'>❌ Error: No se proporcionó un equipo.</p>";
    exit();
}

$equipo_id = intval($_GET['equipo_id']);
$usuario_id = $_SESSION['usuario_id'];

// Obtener datos del equipo
$stmt = $conn->prepare("SELECT e.*, ed.nombre AS edificio, ed.direccion, ed.id AS edificio_id
                        FROM equipos e
                        JOIN edificios ed ON e.edificio_id = ed.id
                        WHERE e.id = ?");
$stmt->bind_param("i", $equipo_id);
$stmt->execute();
$result = $stmt->get_result();
$equipo = $result->fetch_assoc();
$stmt->close();

if (!$equipo) {
    echo "<p style='color:red;'>❌ No se encontró el equipo.</p>";
    exit();
}

// Verificar que el técnico tenga asignado el edificio
$stmt = $conn->prepare("SELECT id FROM asignaciones WHERE edificio_id = ? AND tecnico_id = ?");
$stmt->bind_param("ii", $equipo['edificio_id'], $usuario_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    echo "<p style='color:red;'>❌ No tienes asignado este edificio.</p>";
    exit();
}
$stmt->close();

// Obtener checklist asignado
$stmt = $conn->prepare("SELECT c.id FROM checklists c JOIN checklist_equipos ce ON c.id = ce.checklist_id WHERE ce.equipo_id = ?");
$stmt->bind_param("i", $equipo_id);
$stmt->execute();
$result = $stmt->get_result();
$checklist = $result->fetch_assoc();
$stmt->close();
$checklist_id = $checklist ? $checklist['id'] : null;

// Bandera para saber si se debe iniciar una nueva mantención
$iniciar = isset($_GET['start']) && $_GET['start'] == '1';

// Buscar o crear mantención en curso
$stmt = $conn->prepare("SELECT id, hora_inicio FROM mantenciones WHERE equipo_id = ? AND usuario_id = ? AND hora_fin IS NULL");
$stmt->bind_param("ii", $equipo_id, $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$mantencion = $result->fetch_assoc();
$stmt->close();

$mantencion_id = $mantencion ? $mantencion['id'] : null;
$hora_inicio = $mantencion ? strtotime($mantencion['hora_inicio']) : time();

if (!$mantencion && $iniciar) {
    $stmt = $conn->prepare("INSERT INTO mantenciones (equipo_id, usuario_id, fecha, hora_inicio) VALUES (?, ?, NOW(), NOW())");
    $stmt->bind_param("ii", $equipo_id, $usuario_id);
    $stmt->execute();
    $mantencion_id = $stmt->insert_id;
    $stmt->close();
    $hora_inicio = time();
}

// Correos de clientes asociados al edificio
$clienteCorreos = [];
$stmt = $conn->prepare(
    "SELECT c.correo FROM clientes c JOIN cliente_edificios ce ON c.id = ce.cliente_id WHERE ce.edificio_id = ?"
);
$stmt->bind_param("i", $equipo['edificio_id']);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    if (!empty($r['correo'])) {
        $clienteCorreos[] = $r['correo'];
    }
}
$stmt->close();
// Si no hay mantención en curso y no se solicitó iniciar, mostrar aviso
if (!$mantencion_id && !$iniciar) {
    include 'menu_tecnico.php';
    echo "<div class='main-content'>";
    echo "    <h2>Mantención del Equipo</h2>";
    echo "    <p><strong>Edificio:</strong> " . htmlspecialchars($equipo['edificio']) . "</p>";
    echo "    <p><strong>Dirección:</strong> " . htmlspecialchars($equipo['direccion']) . "</p>";
    echo "    <hr>";
    echo "    <p><strong>Nombre del Equipo:</strong> " . htmlspecialchars($equipo['nombre']) . "</p>";
    echo "    <p><strong>Modelo:</strong> " . htmlspecialchars($equipo['modelo']) . "</p>";
    echo "    <a href='mantencion.php?equipo_id={$equipo_id}&start=1' class='button'>Iniciar Mantención</a>";
    echo "</div>";
    echo "</body></html>";
    exit();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mantención del Equipo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; }
        .modal-content {
            background:#fff; padding:20px; border-radius:8px; width:90%; max-width:400px;
        }
        .modal h3 { margin-top:0; }
    </style>
</head>
<body>

<?php include 'menu_tecnico.php'; ?>
<div class="main-content">
    <h2>Mantención del Equipo</h2>

    <p><strong>Edificio:</strong> <?= htmlspecialchars($equipo['edificio']) ?></p>
    <p><strong>Dirección:</strong> <?= htmlspecialchars($equipo['direccion']) ?></p>
    <hr>
    <p><strong>Nombre del Equipo:</strong> <?= htmlspecialchars($equipo['nombre']) ?></p>
    <p><strong>Modelo:</strong> <?= htmlspecialchars($equipo['modelo']) ?></p>

    <h3>⏳ Tiempo transcurrido: <span id="timer">00:00:00</span></h3>

    <h3>📋 Checklist de Mantención</h3>
    <?php if ($checklist_id): ?>
        <a href="checklist_mantencion.php?equipo_id=<?= $equipo_id ?>&checklist_id=<?= $checklist_id ?>"
           style="background: #2ecc71; color: white; padding: 10px; border-radius: 5px; text-decoration: none;">
           📋 Realizar Checklist
        </a>
    <?php else: ?>
        <p style="color: red;">❌ No hay checklist asignado a este equipo.</p>
    <?php endif; ?>

    <br><br>
    <div class="botones-acciones">
  <button id="finalizarBtn" onclick="abrirModal()">✅ Finalizar Mantención</button>
  <button id="cancelarBtn" onclick="cancelarMantencion()">❌ Cancelar Mantención</button>
</div>
</div>

<!-- Modal correos -->
<div id="modalCorreos" class="modal">
    <div class="modal-content">
        <h3>Selecciona destinatarios</h3>
        <form id="formCorreos">
            <?php foreach ($clienteCorreos as $correo): ?>
                <label><input type="checkbox" name="correos[]" value="<?= htmlspecialchars($correo) ?>"> <?= htmlspecialchars($correo) ?></label><br>
            <?php endforeach; ?>
            <br>
            <button type="button" onclick="enviarFinalizacion()">📤 Enviar</button>
            <button type="button" onclick="cerrarModal()">Cancelar</button>
        </form>
    </div>
</div>

<script>
    if (location.search.indexOf('start=1') !== -1) {
        history.replaceState(null, '', 'mantencion.php?equipo_id=<?= $equipo_id ?>');
    }

    let startTime = <?= $hora_inicio * 1000 ?>;
    let timerInterval;

    function startTimer() {
        timerInterval = setInterval(() => {
            let elapsed = Math.floor((Date.now() - startTime) / 1000);
            let h = Math.floor(elapsed / 3600).toString().padStart(2, '0');
            let m = Math.floor((elapsed % 3600) / 60).toString().padStart(2, '0');
            let s = (elapsed % 60).toString().padStart(2, '0');
            document.getElementById("timer").innerText = `${h}:${m}:${s}`;
        }, 1000);
    }

    function abrirModal() {
        document.getElementById("modalCorreos").style.display = "flex";
    }

    function cerrarModal() {
        document.getElementById("modalCorreos").style.display = "none";
    }

    function enviarFinalizacion() {
        clearInterval(timerInterval);
        const mantencionId = <?= $mantencion_id ?>;
        const btn = document.getElementById("finalizarBtn");
        btn.disabled = true;
        btn.innerText = "Procesando...";

        const seleccionados = Array.from(document.querySelectorAll("input[name='correos[]']:checked"))
                                   .map(input => input.value);

        cerrarModal();

        sendOrQueue(
            "finalizar_mantencion.php",
            new URLSearchParams({
                mantencion_id: mantencionId,
                correos: JSON.stringify(seleccionados)
            }).toString()
        )
        .then(res => res.offline ? {success:true, tiempo_legible:'0 segundos'} : res.json())
        .then(data => {
            if (data.success) {
                const resumen = data.tiempo_legible || "0 segundos";
                document.getElementById("timer").innerText = "00:00:00";
                const mensaje = document.createElement('p');
                mensaje.style.color = 'green';
                mensaje.style.fontWeight = 'bold';
                mensaje.style.marginTop = '10px';
                mensaje.textContent = `Mantención realizada en ${resumen}`;
                document.getElementById("timer").after(mensaje);
                const contenedor = document.querySelector('.botones-acciones');
                const pdf = document.createElement('a');
                pdf.href = `generar_reporte.php?mantencion_id=${mantencionId}`;
                pdf.textContent = '📄 Descargar PDF';
                pdf.className = 'btn-download';
                contenedor.appendChild(pdf);
                alert("✅ Mantención finalizada correctamente.");
                setTimeout(() => window.location.href = "dashboard_tecnico.php", 6000);
            } else {
                alert("❌ Error al finalizar: " + data.error);
                btn.disabled = false;
                btn.innerText = "✅ Finalizar Mantención";
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("❌ Error en la solicitud.");
            btn.disabled = false;
            btn.innerText = "✅ Finalizar Mantención";
        });
    }

    function cancelarMantencion() {
        const motivo = prompt("Ingrese motivo de cancelación:");
        if (motivo === null) return;
        clearInterval(timerInterval);
        const mantencionId = <?= $mantencion_id ?>;
        const btn = document.getElementById("cancelarBtn");
        btn.disabled = true;
        btn.innerText = "Cancelando...";

        sendOrQueue(
            "cancelar_mantencion.php",
            new URLSearchParams({ mantencion_id: mantencionId, motivo: motivo }).toString()
        )
        .then(res => res.offline ? {success:true} : res.json())
        .then(data => {
            if (data.success) {
                document.getElementById("timer").innerText = "00:00:00";
                alert("❌ Mantención cancelada correctamente.");
                window.location.href = "dashboard_tecnico.php";
            } else {
                alert("⚠️ No se pudo cancelar.");
                btn.disabled = false;
                btn.innerText = "❌ Cancelar Mantención";
            }
        })
        .catch(err => {
            console.error(err);
            alert("❌ Error en la solicitud.");
            btn.disabled = false;
            btn.innerText = "❌ Cancelar Mantención";
        });
    }

    document.addEventListener('DOMContentLoaded', startTimer);
    window.addEventListener('pageshow', function(e) {
        if (e.persisted) {
            startTimer();
        }
    });
</script>
<script src="offline.js"></script>
</body>
</html>
