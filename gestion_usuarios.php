<?php
session_start();
include 'db.php';

if ($_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Obtener lista de usuarios
$usuarios = $conn->query("SELECT id, nombre, rol FROM usuarios");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="admin-dashboard">
    <?php include 'menu.php'; ?>

    <div class="main-content">
        <h2>Gestión de Usuarios</h2>

        <button class="btn-create" onclick="abrirModal()">➕ Crear Usuario</button>

        <table class="styled-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $usuarios->fetch_assoc()): ?>
                    <tr data-id="<?= $row['id'] ?>">
                        <td><?= htmlspecialchars($row['nombre']) ?></td>
                        <td><?= htmlspecialchars($row['rol']) ?></td>
                        <td>
                            <button class="btn-delete" onclick="eliminarUsuario(<?= $row['id'] ?>)">🗑️ Eliminar</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL PARA CREAR USUARIO -->
<div id="modalUsuario" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModal()">&times;</span>
        <h2 id="modalTitulo">Crear Usuario</h2>
        <form id="formUsuario" method="POST">
            <input type="text" name="nombre" id="nombre" placeholder="Usuario" required>
            <input type="password" name="clave" id="clave" placeholder="Contraseña" required>
            <select name="rol" id="rol">
                <option value="admin">Admin</option>
                <option value="tecnico">Técnico</option>
            </select>
            <button type="submit">Guardar</button>
        </form>
    </div>
</div>

<!-- ALERTA FLOTANTE -->
<div id="alerta" class="alerta" style="display:none;"></div>

<script>
    function abrirModal() {
        document.getElementById("modalUsuario").style.display = "flex";
    }

    function cerrarModal() {
        document.getElementById("modalUsuario").style.display = "none";
    }

    // Agregar Usuario con AJAX
    document.getElementById("formUsuario").addEventListener("submit", function(event) {
        event.preventDefault();

        let formData = new FormData(this);

        fetch("crear_usuario.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta("✅ Usuario creado correctamente.");
                setTimeout(() => location.reload(), 2000);
            } else {
                mostrarAlerta("❌ Error: " + data.error);
            }
        })
        .catch(error => mostrarAlerta("❌ Error en la solicitud."));
    });

    // Eliminar Usuario con AJAX
    function eliminarUsuario(id) {
        if (!confirm("¿Eliminar este usuario?")) return;

        fetch("eliminar_usuario.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "id=" + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector(`tr[data-id='${id}']`).remove();
                mostrarAlerta("✅ Usuario eliminado correctamente.");
            } else {
                mostrarAlerta("❌ Error al eliminar.");
            }
        })
        .catch(error => mostrarAlerta("❌ Error en la solicitud."));
    }

    // Función para mostrar alerta flotante
    function mostrarAlerta(mensaje) {
        let alerta = document.getElementById("alerta");
        alerta.innerText = mensaje;
        alerta.style.display = "block";
        setTimeout(() => alerta.style.display = "none", 3000);
    }
</script>

<style>
/* === MODAL PARA CREAR USUARIO === */
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
    width: 400px;
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

/* Botón dentro del modal */
.modal-content button {
    width: 100%;
    padding: 10px;
    border: none;
    background: #3498db;
    color: white;
    cursor: pointer;
    margin-top: 10px;
}

.modal-content button:hover {
    background: #2980b9;
}

/* Estilos para la alerta flotante */
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
