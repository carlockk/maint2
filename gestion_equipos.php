<?php
session_start();
include 'db.php';

if ($_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Obtener lista de equipos
$equipos = $conn->query("SELECT e.*, ed.nombre AS edificio, te.nombre AS tipo 
                         FROM equipos e 
                         JOIN edificios ed ON e.edificio_id = ed.id 
                         JOIN tipos_equipos te ON e.tipo_id = te.id");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Equipos</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include 'menu.php'; ?>

        <div class="main-content">
            <h2>Gestión de Equipos</h2>

            <!-- Botón para abrir el modal de crear equipo -->
            <button class="btn-create" onclick="abrirModal()">➕ Crear Equipo</button>

            <table class="styled-table" id="tabla-equipos">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Modelo</th>
                        <th>Año</th>
                        <th>Plan</th>
                        <th>Edificio</th>
                        <th>Tipo</th>
                        <th>Código QR</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $equipos->fetch_assoc()) : ?>
                        <tr id="equipo-<?= $row['id'] ?>">
                            <td><?= $row['id'] ?></td>
                            <td><?= $row['nombre'] ?></td>
                            <td><?= $row['modelo'] ?></td>
                            <td><?= $row['anio_fabricacion'] ?></td>
                            <td><?= $row['plan_mantenimiento'] ?></td>
                            <td><?= $row['edificio'] ?></td>
                            <td><?= $row['tipo'] ?></td>
                            <td>
                                <img src="qr/<?= $row['qr_code'] ?>" alt="QR de <?= $row['nombre'] ?>" width="100">
                            </td>
                            <td>
                                <button class="btn-delete" onclick="eliminarEquipoVisual(<?= $row['id'] ?>)">🗑️ Eliminar</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL PARA CREAR EQUIPO -->
    <div id="modalEquipo" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal()">&times;</span>
            <iframe id="crearEquipoFrame" src="crear_equipos.php" width="100%" height="500px" frameborder="0"></iframe>
        </div>
    </div>

    <!-- ALERTA FLOTANTE -->
    <div id="alerta" class="alerta" style="display:none;">
        <p id="mensaje-alerta"></p>
    </div>

    <script>
        function abrirModal() {
            document.getElementById("modalEquipo").style.display = "flex";
        }

        function cerrarModal() {
            document.getElementById("modalEquipo").style.display = "none";
        }

        function mostrarAlerta(mensaje) {
    const alerta = document.getElementById("alerta");
    const mensajeAlerta = document.getElementById("mensaje-alerta");

    mensajeAlerta.innerText = mensaje;
    alerta.style.display = "block";
    alerta.style.opacity = "1";

    // Mostrar la alerta durante 6 segundos
    setTimeout(() => {
        alerta.style.opacity = "0";
        setTimeout(() => {
            alerta.style.display = "none";
            alerta.style.opacity = "1";
        }, 500); // tiempo del fade out
    }, 6000); // duración visible
}

function eliminarEquipoVisual(id) {
    if (!confirm("¿Eliminar este equipo?")) return;

    const fila = document.getElementById("equipo-" + id);
    if (fila) {
        fila.remove();
        mostrarAlerta("✅ Equipo eliminado de la vista.");
    }
}

    </script>

    <style>
        /* MODAL */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 20px;
            width: 500px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 20px;
            cursor: pointer;
            color: #333;
        }

        .close:hover {
            color: red;
        }

        /* ALERTA */
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
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
    </style>
</body>
</html>
