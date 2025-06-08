<?php
session_start();
include 'db.php';

if ($_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['item_id'])) {
    echo "<p>Error: No se proporcionó un ítem.</p>";
    exit();
}

$item_id = $_GET['item_id'];

// Obtener el nombre del ítem
$stmt = $conn->prepare("SELECT nombre FROM checklist_items WHERE id = ?");
if (!$stmt) {
    error_log("Error en la consulta SELECT: " . $conn->error);
    echo "<p>Error al cargar el ítem.</p>";
    exit();
}
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
$stmt->close();

if (!$item) {
    echo "<p>Error: No se encontró el ítem.</p>";
    exit();
}

// Obtener ítems con opciones para reutilizar
$items_existentes = $conn->query("SELECT DISTINCT ci.id, ci.nombre FROM checklist_items ci 
                                  JOIN checklist_opciones co ON ci.id = co.item_id");

// Agregar opciones
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agregar_opcion'])) {
    if ($_POST['tipo_opcion'] === 'nueva') {
        // Agregar nueva opción
        $opcion = trim($_POST['opcion']);
        if (!empty($opcion)) {
            $stmt = $conn->prepare("INSERT INTO checklist_opciones (item_id, opcion) VALUES (?, ?)");
            if (!$stmt) {
                error_log("Error en la consulta INSERT nueva: " . $conn->error);
                echo "<p>Error al insertar la opción.</p>";
                exit();
            }
            $stmt->bind_param("is", $item_id, $opcion);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        // Copiar todas las opciones de un ítem existente
        $item_origen = $_POST['item_origen'];

        $stmt = $conn->prepare("SELECT opcion FROM checklist_opciones WHERE item_id = ?");
        if (!$stmt) {
            error_log("Error en la consulta SELECT opciones existentes: " . $conn->error);
            echo "<p>Error al obtener opciones existentes.</p>";
            exit();
        }
        $stmt->bind_param("i", $item_origen);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $stmt_insert = $conn->prepare("INSERT INTO checklist_opciones (item_id, opcion) VALUES (?, ?)");
            if (!$stmt_insert) {
                error_log("Error en la consulta INSERT opciones copiadas: " . $conn->error);
                echo "<p>Error al copiar opciones existentes.</p>";
                exit();
            }
            $stmt_insert->bind_param("is", $item_id, $row['opcion']);
            $stmt_insert->execute();
            $stmt_insert->close();
        }
        $stmt->close();
    }
}

// Obtener opciones asignadas al ítem actual
$stmt = $conn->prepare("SELECT * FROM checklist_opciones WHERE item_id = ?");
if (!$stmt) {
    error_log("Error en la consulta SELECT opciones asignadas: " . $conn->error);
    echo "<p>Error al cargar opciones asignadas.</p>";
    exit();
}
$stmt->bind_param("i", $item_id);
$stmt->execute();
$opciones_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Opciones</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'menu.php'; ?>
    <div class="main-content">
    <h2>Gestión de Opciones para "<?= htmlspecialchars($item['nombre']) ?>"</h2>
    <form method="POST">
        <label>Seleccionar tipo de opción:</label>
        <select name="tipo_opcion" id="tipo_opcion">
            <option value="nueva">Nueva Opción</option>
            <option value="existente">Copiar de otro Ítem</option>
        </select>

        <div id="nueva_opcion">
            <input type="text" name="opcion" placeholder="Nueva Opción">
        </div>

        <div id="existente_opcion" style="display: none;">
            <label>Seleccionar Ítem para copiar sus opciones:</label>
            <select name="item_origen">
                <?php while ($row = $items_existentes->fetch_assoc()) : ?>
                    <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nombre']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <button type="submit" name="agregar_opcion">Agregar Opción</button>
    </form>

   <h3>Opciones Asignadas</h3>
<table class="opciones-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Opción</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $contador = 1;
        while ($row = $opciones_result->fetch_assoc()) : ?>
            <tr>
                <td><?= $contador++ ?></td>
                <td><?= htmlspecialchars($row['opcion']) ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>


    <script>
        document.getElementById("tipo_opcion").addEventListener("change", function() {
            document.getElementById("nueva_opcion").style.display = this.value === "nueva" ? "block" : "none";
            document.getElementById("existente_opcion").style.display = this.value === "existente" ? "block" : "none";
        });
    </script>
</body>
</html>
