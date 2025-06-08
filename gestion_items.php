<?php
session_start();
include 'db.php';

if ($_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['checklist_id'])) {
    echo "<p>Error: No se proporcionó un checklist.</p>";
    exit();
}

$checklist_id = $_GET['checklist_id'];
$mensaje = "";

// Obtener nombre del checklist
$stmt = $conn->prepare("SELECT nombre FROM checklists WHERE id = ?");
$stmt->bind_param("i", $checklist_id);
$stmt->execute();
$result = $stmt->get_result();
$checklist = $result->fetch_assoc();
$stmt->close();

if (!$checklist) {
    echo "<p>Error: No se encontró el checklist.</p>";
    exit();
}

// Guardar edición
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_edicion'])) {
    $edit_id = intval($_POST['edit_id']);
    $edit_nombre = trim($_POST['edit_nombre']);
    $edit_nivel = intval($_POST['edit_nivel']);
    $edit_padre_id = $_POST['edit_padre_id'] !== "" ? intval($_POST['edit_padre_id']) : NULL;

    $stmt = $conn->prepare("UPDATE checklist_items SET nombre = ?, nivel = ?, padre_id = ? WHERE id = ?");
    $stmt->bind_param("siii", $edit_nombre, $edit_nivel, $edit_padre_id, $edit_id);
    $stmt->execute();
    $stmt->close();
    $mensaje = "✅ Cambios guardados con éxito.";
}

// Agregar ítem
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['agregar_item'])) {
    $nombre = $_POST['nombre'] ?? null;
    $nivel = $_POST['nivel'] ?? 1;
    $padre_id = isset($_POST['padre_id']) && $_POST['padre_id'] !== "" ? $_POST['padre_id'] : NULL;
    $item_existente = $_POST['item_existente'] ?? null;

    if ($item_existente) {
        $stmt = $conn->prepare("UPDATE checklist_items SET checklist_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $checklist_id, $item_existente);
        $stmt->execute();
        $stmt->close();
    } elseif (!empty($nombre)) {
        if ($nivel == 1) {
            $stmt = $conn->prepare("INSERT INTO checklist_items (checklist_id, nombre, nivel) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $checklist_id, $nombre, $nivel);
        } else {
            $stmt = $conn->prepare("INSERT INTO checklist_items (checklist_id, nombre, nivel, padre_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isii", $checklist_id, $nombre, $nivel, $padre_id);
        }
        $stmt->execute();
        $stmt->close();
    }
}
// Obtener ítems del checklist
$items = $conn->prepare("SELECT * FROM checklist_items WHERE checklist_id = ?");
$items->bind_param("i", $checklist_id);
$items->execute();
$items_result = $items->get_result();
$items->close();

// Ítems padres disponibles
$items_padres = $conn->prepare("SELECT id, nombre FROM checklist_items WHERE checklist_id = ? AND nivel < 3");
$items_padres->bind_param("i", $checklist_id);
$items_padres->execute();
$items_padres_result = $items_padres->get_result();
$items_padres->close();

// Ítems existentes en otros checklists
$items_existentes = $conn->query("SELECT id, nombre, nivel FROM checklist_items WHERE checklist_id != $checklist_id");
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Ítems</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
    .modal-content { background: white; padding: 20px; border-radius: 10px; max-width: 400px; width: 90%; }
    .acciones button { margin-right: 5px; }
  </style>
  <script>
    function toggleItemSelection() {
      const sel = document.getElementById("item_existente");
      const nombre = document.getElementById("nombre");
      const nivel = document.getElementById("nivel");
      const padre = document.getElementById("padre_id");
      const disabled = sel.value !== "";
      nombre.disabled = disabled;
      nivel.disabled = disabled;
      padre.disabled = disabled;
    }
    function abrirEditar(id, nombre, nivel, padre_id) {
      document.getElementById("edit_id").value = id;
      document.getElementById("edit_nombre").value = nombre;
      document.getElementById("edit_nivel").value = nivel;
      document.getElementById("edit_padre_id").value = padre_id || "";
      document.getElementById("modal_editar").style.display = "flex";
    }
    function cerrarModalEditar() {
      document.getElementById("modal_editar").style.display = "none";
    }
    function confirmarEliminar(id) {
      if (confirm("¿Eliminar este ítem?")) {
        window.location.href = "eliminar_item.php?id=" + id + "&checklist_id=<?= $checklist_id ?>";
      }
    }
  </script>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="main-content">
  <h2>Gestión de Ítems para "<?= htmlspecialchars($checklist['nombre']) ?>"</h2>

  <?php if (!empty($mensaje)) : ?>
    <p style="color: green; font-weight: bold;"><?= $mensaje ?></p>
  <?php endif; ?>

  <form method="POST">
    <label>Seleccionar un ítem existente o crear uno nuevo:</label>
    <select name="item_existente" id="item_existente" onchange="toggleItemSelection()">
      <option value="">Crear Nuevo Ítem</option>
      <?php while ($row = $items_existentes->fetch_assoc()) : ?>
        <option value="<?= $row['id'] ?>"><?= $row['nombre'] ?> (Nivel <?= $row['nivel'] ?>)</option>
      <?php endwhile; ?>
    </select>

    <input type="text" id="nombre" name="nombre" placeholder="Nombre del Ítem" required>

    <select name="nivel" id="nivel">
      <option value="1">Ítem Principal (sin opciones)</option>
      <option value="2">Sub-Ítem</option>
      <option value="3">Sub-Sub-Ítem</option>
    </select>

    <select name="padre_id" id="padre_id">
      <option value="">Sin padre</option>
      <?php while ($row = $items_padres_result->fetch_assoc()) : ?>
        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nombre']) ?></option>
      <?php endwhile; ?>
    </select>

    <button type="submit" name="agregar_item">Agregar Ítem</button>
  </form>
  <h3>Ítems del Checklist</h3>
  <table class="checklist-table">
    <thead>
      <tr>
        <th>Ítem</th>
        <th>Nivel</th>
        <th>Tipo</th>
        <th>Opciones</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $items = $conn->prepare("SELECT * FROM checklist_items WHERE checklist_id = ?");
      $items->bind_param("i", $checklist_id);
      $items->execute();
      $items_result = $items->get_result();
      while ($row = $items_result->fetch_assoc()) :
      ?>
        <tr>
          <td><?= htmlspecialchars($row['nombre']) ?></td>
          <td><?= $row['nivel'] ?></td>
          <td>
            <?= $row['nivel'] == 1 ? 'Ítem Principal' : ($row['nivel'] == 2 ? 'Sub-Ítem' : 'Sub-Sub-Ítem') ?>
          </td>
          <td>
          <?php if ($row['nivel'] > 1): ?>
              <a style="text-decoration: none;" href="gestion_opciones.php?item_id=<?= $row['id'] ?>" title="Gestionar opciones">⚙️ Opciones del checklist</a>
            <?php endif; ?>
            </td>
          <td class="acciones">
            <button onclick="abrirEditar('<?= $row['id'] ?>','<?= htmlspecialchars($row['nombre'], ENT_QUOTES) ?>','<?= $row['nivel'] ?>','<?= $row['padre_id'] ?>')" title="Editar">
              ✏️
            </button>
            <button onclick="confirmarEliminar(<?= $row['id'] ?>)" title="Eliminar">
              🗑️
            </button>
          </td>
        </tr>
      <?php endwhile; $items->close(); ?>
    </tbody>
  </table>
</div>
<!-- Modal de Edición -->
<div id="modal_editar" class="modal">
  <div class="modal-content">
    <h3>✏️ Editar Ítem</h3>
    <form method="POST">
      <input type="hidden" name="edit_id" id="edit_id">

      <label>Nombre:</label>
      <input type="text" name="edit_nombre" id="edit_nombre" required>

      <label>Nivel:</label>
      <select name="edit_nivel" id="edit_nivel">
        <option value="1">Ítem Principal</option>
        <option value="2">Sub-Ítem</option>
        <option value="3">Sub-Sub-Ítem</option>
      </select>

      <label>Ítem Padre:</label>
      <select name="edit_padre_id" id="edit_padre_id">
        <option value="">Sin padre</option>
        <?php
        $stmt_padres = $conn->prepare("SELECT id, nombre FROM checklist_items WHERE checklist_id = ? AND nivel < 3");
        $stmt_padres->bind_param("i", $checklist_id);
        $stmt_padres->execute();
        $padres_result = $stmt_padres->get_result();
        while ($padre = $padres_result->fetch_assoc()) {
            echo "<option value='{$padre['id']}'>" . htmlspecialchars($padre['nombre']) . "</option>";
        }
        $stmt_padres->close();
        ?>
      </select>

      <br><br>
      <button type="submit" name="guardar_edicion">Guardar Cambios</button>
      <button type="button" onclick="cerrarModalEditar()">Cancelar</button>
    </form>
  </div>
</div>
<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$checklist_id = isset($_GET['checklist_id']) ? intval($_GET['checklist_id']) : 0;

if ($id > 0) {
    // Eliminar el ítem
    $stmt = $conn->prepare("DELETE FROM checklist_items WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

// Redirigir de nuevo al checklist
header("Location: gestion_items.php?checklist_id=$checklist_id");
exit();
