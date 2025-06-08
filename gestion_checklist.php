<?php
session_start();
include 'db.php';

if ($_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$mensaje = ""; // Variable para almacenar el mensaje

// Obtener equipos
$equipos = $conn->query("SELECT id, nombre FROM equipos");

// Obtener checklists existentes
$checklists = $conn->query("SELECT id, nombre FROM checklists");

// Agregar un nuevo checklist o asociarlo a equipos
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'] ?? '';
    $nombre = $_POST['nombre'] ?? null;
    $equipo_ids = $_POST['equipo_ids'] ?? [];
    $checklist_existente = $_POST['checklist_existente'] ?? null;

    if ($accion === "crear") {
        if (!empty($nombre) && !empty($equipo_ids)) {
            // Seleccionar el primer equipo para guardarlo en checklists
            $primer_equipo = $equipo_ids[0];

            // Crear el nuevo checklist en la tabla checklists
            $stmt = $conn->prepare("INSERT INTO checklists (nombre, equipo_id) VALUES (?, ?)");
            $stmt->bind_param("si", $nombre, $primer_equipo);
            $stmt->execute();
            $checklist_id = $stmt->insert_id;
            $stmt->close();

            // Asociar checklist con todos los equipos seleccionados en checklist_equipos
            foreach ($equipo_ids as $equipo_id) {
                $stmt = $conn->prepare("INSERT INTO checklist_equipos (checklist_id, equipo_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $checklist_id, $equipo_id);
                $stmt->execute();
                $stmt->close();
            }

            $mensaje = "Checklist creado exitosamente y asignado a los equipos seleccionados.";
        } else {
            $mensaje = "Error: Debes ingresar un nombre y seleccionar al menos un equipo.";
        }
    } elseif ($accion === "asociar" && $checklist_existente) {
        // Asociar un checklist existente a los equipos seleccionados
        foreach ($equipo_ids as $equipo_id) {
            $stmt_check = $conn->prepare("SELECT id FROM checklist_equipos WHERE checklist_id = ? AND equipo_id = ?");
            $stmt_check->bind_param("ii", $checklist_existente, $equipo_id);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows == 0) {
                $stmt = $conn->prepare("INSERT INTO checklist_equipos (checklist_id, equipo_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $checklist_existente, $equipo_id);
                $stmt->execute();
                $stmt->close();
            }
            $stmt_check->close();
        }

        $mensaje = "Checklist existente asignado correctamente a los equipos seleccionados.";
    }
}

// Obtener checklists con los equipos asignados
$checklists_registrados = $conn->query("
    SELECT c.id, c.nombre, 
           GROUP_CONCAT(e.nombre SEPARATOR ', ') AS equipos_asignados 
    FROM checklists c
    LEFT JOIN checklist_equipos ce ON c.id = ce.checklist_id
    LEFT JOIN equipos e ON ce.equipo_id = e.id
    GROUP BY c.id, c.nombre
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Checklists</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function toggleForm() {
            let selectChecklist = document.getElementById("checklist_existente");
            let inputNombre = document.getElementById("nombre_checklist");
            let buttonSubmit = document.getElementById("btn_submit");

            if (selectChecklist.value === "crear_nuevo") {
                inputNombre.style.display = "block";
                inputNombre.required = true;
                buttonSubmit.innerText = "Crear Nuevo";
                document.getElementById("accion").value = "crear";
            } else {
                inputNombre.style.display = "none";
                inputNombre.required = false;
                buttonSubmit.innerText = "Guardar";
                document.getElementById("accion").value = "asociar";
            }
        }

        // Mostrar mensaje si hay un mensaje PHP
        window.onload = function() {
            let mensaje = "<?= $mensaje ?>";
            if (mensaje !== "") {
                alert(mensaje);
            }
        };
    </script>
</head>
<body>
<div class="admin-dashboard">
<?php include 'menu.php'; ?>

    <div class="main-content">
        <h2>Gestión de Checklists</h2>
        <form method="POST">
            <input type="hidden" id="accion" name="accion" value="asociar">

            <label>Selecciona uno o más equipos:</label>
            <select name="equipo_ids[]" multiple required>
                <?php while ($row = $equipos->fetch_assoc()) : ?>
                    <option value="<?= $row['id'] ?>"><?= $row['nombre'] ?></option>
                <?php endwhile; ?>
            </select>

            <label>Seleccionar un checklist existente o crear uno nuevo:</label>
            <select name="checklist_existente" id="checklist_existente" onchange="toggleForm()">
                <option value="">Seleccionar un checklist existente</option>
                <option value="crear_nuevo">Crear Nuevo</option>
                <?php while ($row = $checklists->fetch_assoc()) : ?>
                    <option value="<?= $row['id'] ?>"><?= $row['nombre'] ?></option>
                <?php endwhile; ?>
            </select>

            <input type="text" id="nombre_checklist" name="nombre" placeholder="Nombre del Checklist" style="display:none;">

            <button type="submit" id="btn_submit">Guardar</button>
        </form>

        <h3>Checklists Registrados</h3>
        <table border="1" width="100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Equipos Asignados</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $checklists_registrados->fetch_assoc()) : ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= $row['nombre'] ?></td>
                        <td><?= $row['equipos_asignados'] ?: "<i>No asignado</i>" ?></td>
                        <td>
                            <a href="gestion_items.php?checklist_id=<?= $row['id'] ?>">📝 Gestionar Ítems</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
