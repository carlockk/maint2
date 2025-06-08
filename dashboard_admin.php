<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Actualizar estados reales en asignaciones según mantenciones
$asignaciones_raw = $conn->query("SELECT * FROM asignaciones");
while ($row = $asignaciones_raw->fetch_assoc()) {
    $edificio_id = $row['edificio_id'];
    $tecnico_id = $row['tecnico_id'];

    // Buscar si hay equipos en ese edificio con mantención en proceso o completada por ese técnico
    $query = $conn->prepare("
        SELECT m.hora_fin 
        FROM mantenciones m
        JOIN equipos eq ON m.equipo_id = eq.id
        WHERE eq.edificio_id = ? AND m.usuario_id = ?
        ORDER BY m.fecha DESC, m.id DESC
        LIMIT 1
    ");
    $query->bind_param("ii", $edificio_id, $tecnico_id);
    $query->execute();
    $res = $query->get_result();
    $mantencion = $res->fetch_assoc();

    $nuevo_estado = 'Pendiente';
    if ($mantencion) {
        if ($mantencion['hora_fin']) {
            $nuevo_estado = 'Completado';
        } else {
            $nuevo_estado = 'En Proceso';
        }
    }

    // Actualizar estado si es diferente
    if ($row['estado'] !== $nuevo_estado) {
        $update = $conn->prepare("UPDATE asignaciones SET estado = ? WHERE id = ?");
        $update->bind_param("si", $nuevo_estado, $row['id']);
        $update->execute();
        $update->close();
    }

    $query->close();
}

// Obtener asignaciones con datos relevantes
$asignaciones = $conn->query("
    SELECT e.id AS edificio_id, 
           c.nombre_razon_social AS cliente, 
           e.nombre AS edificio, 
           e.direccion, 
           a.estado,
           GROUP_CONCAT(DISTINCT u.nombre SEPARATOR ', ') AS tecnicos
    FROM edificios e
    JOIN cliente_edificios ce ON ce.edificio_id = e.id
    JOIN clientes c ON ce.cliente_id = c.id
    LEFT JOIN asignaciones a ON a.edificio_id = e.id
    LEFT JOIN usuarios u ON a.tecnico_id = u.id
    GROUP BY e.id, c.id
");

// Verificar si hay algún estado en proceso o completado para mostrar columnas
$mostrar_estado = false;
foreach ($asignaciones as $row) {
    if (in_array($row['estado'], ['Pendiente', 'Finalizado'])) {
        $mostrar_estado = true;
        break;
    }
}


// Función para obtener color del estado
function obtenerColor($estado)
{
    switch ($estado) {
        case 'En Proceso':
            return 'orange';
        case 'Completado':
            return 'green';
        case 'Pendiente':
            return 'gray';
        default:
            return 'black';
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="style.css">



<meta name="theme-color" content="#4CAF50">
<!-- Service Worker -->
<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/maintcheck/service-worker.js')
      .then(function(registration) {
        console.log('Service Worker registrado con éxito:', registration);
      }).catch(function(error) {
        console.log('Fallo al registrar el Service Worker:', error);
      });
  }
</script>

</head>

<body>
<div class="admin-dashboard">
        <?php include 'menu.php'; ?>

        <div class="main-content">
            <h2>Asignación de Rutas</h2>
            <button class="btn-create" onclick="abrirModalAsignar()">➕ Asignar Técnico</button>

            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Edificio</th>
                        <th>Dirección</th>
                        <th>Técnico(s)</th>
                        <?php if ($mostrar_estado): ?>
                            <th>Estado</th>
                            <th>Color</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($asignaciones as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['cliente']) ?></td>
                            <td><?= htmlspecialchars($row['edificio']) ?></td>
                            <td><?= htmlspecialchars($row['direccion']) ?></td>
                            <td>
                                <button
                                    onclick="verTecnicos('<?= htmlspecialchars($row['edificio']) ?>', '<?= htmlspecialchars($row['tecnicos']) ?>')">👀
                                    Ver Técnicos</button>
                            </td>
                            <?php if ($mostrar_estado): ?>
                                <td><?= htmlspecialchars($row['estado']) ?></td>
                                <td>
                                    <?php
                                    $estado = htmlspecialchars($row['estado']);

                                    if ($estado === 'Pendiente') {
                                        echo '⏳';
                                    } elseif ($estado === 'Completado') {
                                        echo "<span class='check-circle'>✓</span>";
                                    } else {
                                        echo '';
                                    }
                                    ?>
                                </td>
                            <?php endif; ?>

                        </tr>
                    <?php endforeach; ?>
                </tbody>




            </table>
        </div>
    </div>

    <!-- MODAL VER TÉCNICOS -->
    <div id="modalVerTecnicos" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalVerTecnicos')">&times;</span>
            <h2>Técnicos Asignados a <span id="nombre-edificio"></span></h2>
            <div id="lista-tecnicos" class="contenedor-tecnicos"></div>
        </div>
    </div>

    <!-- MODAL ASIGNAR TÉCNICO -->
    <div id="modalAsignar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal('modalAsignar')">&times;</span>
            <h2>Asignar Técnico a Edificio</h2>
            <form id="formAsignarTecnico">
                <label>Técnico:</label>
                <select name="tecnico_id" id="tecnico_id" required></select>

                <label>Edificio:</label>
                <select name="edificio_id" id="edificio_id" required></select>

                <button type="submit">Asignar</button>
            </form>
        </div>
    </div>

    <!-- ALERTA FLOTANTE -->
    <div id="alerta" class="alerta" style="display:none;">
        <p id="mensaje-alerta"></p>
    </div>

    <script>

        document.addEventListener("DOMContentLoaded", () => {
            document.getElementById("formAsignarTecnico").addEventListener("submit", function (e) {
                e.preventDefault();
                asignarTecnico();
            });

            cargarSelects();
        });

        function abrirModalAsignar() {
            document.getElementById("modalAsignar").style.display = "flex";
        }

        function cerrarModal(id) {
            document.getElementById(id).style.display = "none";
        }

        function verTecnicos(edificio, tecnicos) {
            document.getElementById("nombre-edificio").innerText = edificio;
            let contenedor = document.getElementById("lista-tecnicos");
            contenedor.innerHTML = "";

            if (tecnicos) {
                tecnicos.split(",").forEach(nombre => {
                    contenedor.innerHTML += `
                <div class="tarjeta-tecnico">
                    <div class="avatar-icono">👷‍♂️</div>
                    <div class="nombre-tecnico">${nombre.trim()}</div>
                </div>
            `;
                });
            } else {
                contenedor.innerHTML = "<p>Sin técnicos asignados</p>";
            }

            document.getElementById("modalVerTecnicos").style.display = "flex";
        }


        function cargarSelects() {
            fetch("obtener_tecnicos_edificios.php")
                .then(response => response.json())
                .then(data => {
                    const tecnicoSelect = document.getElementById("tecnico_id");
                    const edificioSelect = document.getElementById("edificio_id");

                    tecnicoSelect.innerHTML = '<option value="">Seleccionar Técnico</option>';
                    edificioSelect.innerHTML = '<option value="">Seleccionar Edificio</option>';

                    data.tecnicos.forEach(t => {
                        tecnicoSelect.innerHTML += `<option value="${t.id}">${t.nombre}</option>`;
                    });
                    data.edificios.forEach(e => {
                        edificioSelect.innerHTML += `<option value="${e.id}">${e.nombre}</option>`;
                    });
                });
        }

        function asignarTecnico() {
            const tecnico_id = document.getElementById("tecnico_id").value;
            const edificio_id = document.getElementById("edificio_id").value;

            fetch("asignar_tecnico.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `tecnico_id=${tecnico_id}&edificio_id=${edificio_id}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarAlerta("✅ Técnico asignado correctamente.");
                        cerrarModal('modalAsignar');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        mostrarAlerta("❌ " + data.error);
                    }
                })
                .catch(() => mostrarAlerta("❌ Error en la solicitud."));
        }

        function mostrarAlerta(mensaje) {
            const alerta = document.getElementById("alerta");
            const mensajeElem = document.getElementById("mensaje-alerta");
            mensajeElem.innerText = mensaje;
            alerta.style.display = "block";

            setTimeout(() => alerta.style.display = "none", 3000);
        }
    </script>

    <style>
        /* Estilos del modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 600px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .modal .close {
            position: absolute;
            right: 50px;
            top: 10px;
            cursor: pointer;
            font-size: 20px;
            color: red;
        }

        .modal iframe {
            width: 100%;
            height: 600px;
            border: none;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 20px;
            cursor: pointer;
            color: #333;
        }

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
            z-index: 1001;
        }

        /*tarjeta para ver tecnicos*/
        .contenedor-tecnicos {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
            justify-content: flex-start;
        }

        .tarjeta-tecnico {
            background: #ecf0f1;
            border-radius: 8px;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            min-width: 140px;
        }

        .avatar-icono {
            font-size: 24px;
            margin-right: 10px;
        }

        .nombre-tecnico {
            font-weight: bold;
            color: #34495e;
        }

        .status-circle {
            display: inline-block;
            width: 24px;
            height: 24px;
            font-size: 18px;
            text-align: center;
            line-height: 24px;
            border-radius: 50%;
        }

        .status-pendiente::before {
            content: '⏳';
        }

        .circle {
            width: 18px;
            height: 18px;
            background-color: #000;
            border-radius: 50%;
            margin-right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
            position: relative;
            z-index: 1;
        }

        .step.completed .circle {
            background-color: #2bab46;
        }

        .step.completed .circle:after {
            content: '✓';
        }

        .step.current .circle {
            background-color: #000;
        }

        .check-circle {
            display: inline-block;
            width: 18px;
            height: 18px;
            background-color: #2bab46;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 18px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 6px;
        }
</style>
<script src="offline.js"></script>
</body>

</html>