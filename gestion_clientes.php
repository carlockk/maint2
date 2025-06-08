<?php
session_start();
include 'db.php';

if ($_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Obtener lista de clientes
$clientes = $conn->query("SELECT * FROM clientes");

// Eliminar un cliente
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $stmt = $conn->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: gestion_clientes.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes</title>
    <link rel="stylesheet" href="style.css">
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
    </style>
</head>
<body>
<div class="admin-dashboard">
<?php include 'menu.php'; ?>

    <div class="main-content">
        <h2>Gestión de Clientes</h2>

        <button onclick="abrirModal('crear_cliente.php')" class="btn-create">➕ Crear Cliente</button>

        <table class="styled-table">
            <thead>
                <tr>
                    <th>Nombre/Razón Social</th>
                    <th>RUT</th>
                    <th>Correo</th>
                    <th>Teléfono</th>
                    <th>Acciones</th>
                    <th>Ver Más</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $clientes->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nombre_razon_social']) ?></td>
                        <td><?= htmlspecialchars($row['rut']) ?></td>
                        <td><?= htmlspecialchars($row['correo']) ?></td>
                        <td><?= htmlspecialchars($row['telefono']) ?></td>
                        <td>
                            <button class="btn-edit" onclick="abrirModal('editar_cliente.php?id=<?= $row['id'] ?>')">
                                ✏️ Editar
                            </button>
                            <button class="btn-delete"><a style="color: #fff; text-decoration: none; " href="?eliminar=<?= $row['id'] ?>"
                               onclick="return confirm('¿Eliminar este cliente?')">🗑️ Eliminar</a></button>
                        </td>
                        <td>
                            <button class="btn-view"
                                    onclick="abrirModal('ver_edificios_cliente.php?id=<?= $row['id'] ?>')">
                                🏢 Ver Más
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModal()">✖</span>
        <iframe id="modalFrame"></iframe>
    </div>
</div>

<script>
    function abrirModal(url) {
        document.getElementById("modalFrame").src = url;
        document.getElementById("modal").style.display = "flex";
    }

    function cerrarModal() {
        document.getElementById("modal").style.display = "none";
        document.getElementById("modalFrame").src = "";
        location.reload(); // Recargar la página al cerrar el modal
    }
</script>

</body>
</html>
