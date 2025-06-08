<?php
session_start();
include 'db.php';

if ($_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$clientes = $conn->query("SELECT id, nombre_razon_social FROM clientes");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_edificio'])) {
    $id = isset($_POST['id']) && $_POST['id'] ? $_POST['id'] : null;
    $nombre = $_POST['nombre'];
    $administrador = $_POST['administrador'];
    $direccion = $_POST['direccion'];
    $comuna = $_POST['comuna'];
    $clientes_asignados = isset($_POST['clientes']) ? $_POST['clientes'] : [];

    if ($id) {
        $stmt = $conn->prepare("UPDATE edificios SET nombre=?, administrador=?, direccion=?, comuna=? WHERE id=?");
        $stmt->bind_param("ssssi", $nombre, $administrador, $direccion, $comuna, $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM cliente_edificios WHERE edificio_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO edificios (nombre, administrador, direccion, comuna) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nombre, $administrador, $direccion, $comuna);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
    }

    foreach ($clientes_asignados as $cliente_id) {
        $stmt = $conn->prepare("INSERT INTO cliente_edificios (edificio_id, cliente_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $id, $cliente_id);
        $stmt->execute();
        $stmt->close();
    }

    echo "<script>window.location.href='gestion_edificios.php?success=1';</script>";
    exit();
}

$edificios = $conn->query("
    SELECT e.*, GROUP_CONCAT(c.nombre_razon_social SEPARATOR ', ') AS clientes
    FROM edificios e
    LEFT JOIN cliente_edificios ce ON e.id = ce.edificio_id
    LEFT JOIN clientes c ON ce.cliente_id = c.id
    GROUP BY e.id
");

$clientes_por_edificio = [];
$result = $conn->query("SELECT * FROM cliente_edificios");
while ($row = $result->fetch_assoc()) {
    $clientes_por_edificio[$row['edificio_id']][] = $row['cliente_id'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Edificios</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="admin-dashboard">
<?php include 'menu.php'; ?>

<div class="main-content">
    <h2>Gestión de Edificios</h2>
    <button class="btn-create" onclick="abrirModal()">➕ Crear Edificio</button>

    <table class="styled-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Administrador</th>
                <th>Dirección</th>
                <th>Comuna</th>
                <th>Clientes Asignados</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $edificios->fetch_assoc()): ?>
                <tr data-id="<?= $row['id'] ?>">
                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                    <td><?= htmlspecialchars($row['administrador']) ?></td>
                    <td><?= htmlspecialchars($row['direccion']) ?></td>
                    <td><?= htmlspecialchars($row['comuna']) ?></td>
                    <td><?= $row['clientes'] ? htmlspecialchars($row['clientes']) : "Sin Cliente" ?></td>
                    <td>
                        <button class="btn-edit"
                            onclick="editarEdificio(
                                <?= $row['id'] ?>,
                                '<?= htmlspecialchars($row['nombre']) ?>',
                                '<?= htmlspecialchars($row['administrador']) ?>',
                                '<?= htmlspecialchars($row['direccion']) ?>',
                                '<?= htmlspecialchars($row['comuna']) ?>',
                                '<?= implode(",", $clientes_por_edificio[$row['id']] ?? []) ?>'
                            )">
                            ✏️ Editar
                        </button>
                        <button class="btn-delete" onclick="eliminarEdificio(<?= $row['id'] ?>)">🗑️ Eliminar</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</div>

<!-- MODAL -->
<div id="modalEdificio" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModal()">&times;</span>
        <h2 id="modalTitulo">Crear Edificio</h2>
        <form method="POST">
            <input type="hidden" name="id" id="edificio_id">
            <input type="text" name="nombre" id="nombre" placeholder="Nombre del Edificio" required>
            <input type="text" name="administrador" id="administrador" placeholder="Administrador" required>
            <input type="text" name="direccion" id="direccion" placeholder="Dirección" required>
            <input type="text" name="comuna" id="comuna" placeholder="Comuna" required>

            <label>Asignar Cliente(s):</label>
            <select name="clientes[]" id="clientes" multiple>
                <?php $clientes->data_seek(0); while ($cliente = $clientes->fetch_assoc()): ?>
                    <option value="<?= $cliente['id'] ?>"><?= htmlspecialchars($cliente['nombre_razon_social']) ?></option>
                <?php endwhile; ?>
            </select>

            <button type="submit" name="guardar_edificio">Guardar</button>
        </form>
    </div>
</div>

<script>
function abrirModal() {
    document.getElementById("modalEdificio").style.display = "flex";
}

function cerrarModal() {
    document.getElementById("modalEdificio").style.display = "none";
}

function editarEdificio(id, nombre, administrador, direccion, comuna, clientes) {
    abrirModal();
    document.getElementById("modalTitulo").innerText = "Editar Edificio";
    document.getElementById("edificio_id").value = id;
    document.getElementById("nombre").value = nombre;
    document.getElementById("administrador").value = administrador;
    document.getElementById("direccion").value = direccion;
    document.getElementById("comuna").value = comuna;

    // reset
    document.querySelectorAll("#clientes option").forEach(opt => opt.selected = false);
    clientes.split(',').forEach(cid => {
        let opt = document.querySelector(`#clientes option[value='${cid}']`);
        if (opt) opt.selected = true;
    });
}
</script>

<!-- ALERTA FLOTANTE -->
<div id="alerta" class="alerta" style="display: none;">
    <p id="mensaje-alerta"></p>
</div>

<script>
// Mostrar alerta si se creó o editó correctamente
document.addEventListener("DOMContentLoaded", function () {
    const params = new URLSearchParams(window.location.search);
    if (params.has("success")) {
        mostrarAlerta("✅ Edificio guardado correctamente.");
    }
});

function mostrarAlerta(mensaje) {
    const alerta = document.getElementById("alerta");
    const mensajeAlerta = document.getElementById("mensaje-alerta");

    mensajeAlerta.innerText = mensaje;
    alerta.style.display = "block";

    setTimeout(() => {
        alerta.style.opacity = "0";
        setTimeout(() => {
            alerta.style.display = "none";
            alerta.style.opacity = "1";
        }, 500);
    }, 4000); // Mostrar 4 segundos
}
</script>

<style>

      /* === MODAL PARA CREAR / EDITAR EDIFICIO === */
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

.alerta {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: #2ecc71;
    color: white;
    padding: 15px 25px;
    border-radius: 5px;
    font-size: 16px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    z-index: 2000;
    transition: opacity 0.3s ease;
}
</style>

</body>
</html>
