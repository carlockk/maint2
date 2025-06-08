<?php
session_start();
include 'db.php';
include 'includes/phpqrcode/qrlib.php';

if ($_SESSION['rol'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Crear carpeta 'qr' si no existe
$directorio_qr = 'qr/';
if (!file_exists($directorio_qr)) {
    mkdir($directorio_qr, 0777, true);
}

// Obtener edificios y tipos de equipo
$edificios = $conn->query("SELECT * FROM edificios");
$tipos = $conn->query("SELECT * FROM tipos_equipos");

// Agregar equipo
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $modelo = $_POST['modelo'];
    $anio = $_POST['anio'];
    $plan = $_POST['plan'];
    $edificio_id = $_POST['edificio_id'];
    $tipo_id = $_POST['tipo_id'];

    // Insertar el equipo en la base de datos para obtener su ID
    $stmt = $conn->prepare("INSERT INTO equipos (nombre, modelo, anio_fabricacion, plan_mantenimiento, edificio_id, tipo_id) 
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssissi", $nombre, $modelo, $anio, $plan, $edificio_id, $tipo_id);
    $stmt->execute();
    $equipo_id = $stmt->insert_id; // Obtener el ID generado
    $stmt->close();

    // Generar el código QR con el ID del equipo
    $qr_nombre = $equipo_id . ".png";  // Guardamos el ID como nombre de archivo
    $ruta_qr = $directorio_qr . $qr_nombre;
    $contenido_qr = (string)$equipo_id; // Solo el ID del equipo

    // Generar el código QR
    QRcode::png($contenido_qr, $ruta_qr, QR_ECLEVEL_L, 10);

    // Actualizar la base de datos con el nombre del archivo QR
    $stmt = $conn->prepare("UPDATE equipos SET qr_code = ? WHERE id = ?");
    $stmt->bind_param("si", $qr_nombre, $equipo_id);
    $stmt->execute();
    $stmt->close();

    // Enviar respuesta en formato JSON
    echo json_encode(["success" => true, "message" => "✅ Equipo agregado correctamente."]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="form-container">
    <h2>Crear Nuevo Equipo</h2>
    <form id="formCrearEquipo">
        <input type="text" name="nombre" placeholder="Nombre del Equipo" required>
        <input type="text" name="modelo" placeholder="Modelo" required>
        <input type="number" name="anio" placeholder="Año de Fabricación" required>
        <input type="text" name="plan" placeholder="Plan de Mantenimiento" required>

        <label>Seleccionar Edificio:</label>
        <select name="edificio_id" required>
            <option value="">Seleccionar Edificio</option>
            <?php while ($row = $edificios->fetch_assoc()) : ?>
                <option value="<?= $row['id'] ?>"><?= $row['nombre'] ?></option>
            <?php endwhile; ?>
        </select>

        <label>Seleccionar Tipo de Equipo:</label>
        <select name="tipo_id" required>
            <option value="">Seleccionar Tipo de Equipo</option>
            <?php while ($row = $tipos->fetch_assoc()) : ?>
                <option value="<?= $row['id'] ?>"><?= $row['nombre'] ?></option>
            <?php endwhile; ?>
        </select>

        <button type="submit">Agregar Equipo</button>
    </form>
</div>

<script>
    document.getElementById("formCrearEquipo").addEventListener("submit", function(event) {
        event.preventDefault(); // Evita la recarga de la página

        const formData = new FormData(this);

        fetch("crear_equipos.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.parent.mostrarAlerta(data.message); // Mostrar alerta en la página principal
                window.parent.cerrarModal(); // Cerrar el modal
                window.parent.location.reload(); // Actualizar la página principal
            } else {
                alert("❌ Error al agregar el equipo.");
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("❌ Error en la solicitud.");
        });
    });
</script>

<style>
    .form-container {
        padding: 20px;
    }

    .form-container input,
    .form-container select {
        width: 100%;
        padding: 10px;
        margin: 10px 0;
        border: 1px solid #ccc;
        border-radius: 5px;
    }

    .form-container button {
        width: 100%;
        padding: 10px;
        background: #3498db;
        color: white;
        border: none;
        cursor: pointer;
        margin-top: 10px;
        border-radius: 5px;
    }

    .form-container button:hover {
        background: #2980b9;
    }
</style>

</body>
</html>
