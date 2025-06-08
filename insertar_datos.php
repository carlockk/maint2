<?php
include 'db.php';

// Hashear contraseña de prueba
$clave_admin = password_hash("admin123", PASSWORD_DEFAULT);
$clave_tecnico = password_hash("tecnico123", PASSWORD_DEFAULT);

// Insertar usuarios (Admin y Técnico)
$conn->query("INSERT INTO usuarios (nombre, clave, rol) VALUES 
    ('admin', '$clave_admin', 'admin'),
    ('tecnico', '$clave_tecnico', 'tecnico')");

// Insertar un edificio de prueba
$conn->query("INSERT INTO edificios (nombre, administrador, direccion) VALUES 
    ('Edificio Central', 'Juan Pérez', 'Av. Principal 123')");

// Obtener el ID del edificio recién insertado
$edificio_id = $conn->insert_id;

// Insertar tipos de equipos
$conn->query("INSERT INTO tipos_equipos (nombre) VALUES ('Ascensor'), ('Escalera')");

// Obtener IDs de tipos de equipos
$tipo_ascensor = $conn->insert_id - 1;
$tipo_escalera = $conn->insert_id;

// Insertar equipos de prueba
$conn->query("INSERT INTO equipos (nombre, modelo, anio_fabricacion, plan_mantenimiento, edificio_id, tipo_id, qr_code) VALUES 
    ('Ascensor A1', 'Modelo X', 2020, 'Mensual', $edificio_id, $tipo_ascensor, 'QR001'),
    ('Escalera E1', 'Modelo Y', 2019, 'Trimestral', $edificio_id, $tipo_escalera, 'QR002')");

// Obtener ID del ascensor para asociarlo al checklist
$equipo_id = $conn->insert_id - 1;

// Insertar un checklist para el ascensor
$conn->query("INSERT INTO checklists (nombre, equipo_id) VALUES ('Checklist General', $equipo_id)");
$checklist_id = $conn->insert_id;

// Insertar los ítems del checklist
$items = [
    ["Ingreso", NULL],
    ["Novedades", "Ingreso"],
    ["Llaves", "Ingreso"],
    ["Revisión inicial", NULL],
    ["Cuarto central hidráulica", NULL],
    ["Estado general", "Cuarto central hidráulica"],
    ["Iluminación", "Estado general"],
    ["Tablero de fuerza", "Estado general"],
    ["Acceso", "Estado general"],
    ["Protección pasadas", "Estado general"],
    ["Aseo y limpieza", "Estado general"],
    ["Señalética de seguridad", "Estado general"],
    ["Escotilla", NULL],
    ["Techo de cabina", "Escotilla"],
    ["Botonera de inspección", "Techo de cabina"],
    ["Enchufe lámpara portátil", "Techo de cabina"],
    ["Señalética de seguridad", "Techo de cabina"],
    ["Final carrera de cabina", "Techo de cabina"],
    ["Captador de posición", "Techo de cabina"],
    ["Candado cables tractores", "Techo de cabina"],
    ["Cabina", NULL],
    ["Pozo", NULL]
];

$item_ids = [];
foreach ($items as $item) {
    $nombre = $item[0];
    $padre_nombre = $item[1];

    // Obtener el ID del padre si existe
    $padre_id = $padre_nombre ? $item_ids[$padre_nombre] : NULL;

    // Insertar el ítem
    $stmt = $conn->prepare("INSERT INTO checklist_items (checklist_id, nombre, nivel, padre_id) VALUES (?, ?, ?, ?)");
    $nivel = $padre_id ? 2 : 1;
    $stmt->bind_param("isii", $checklist_id, $nombre, $nivel, $padre_id);
    $stmt->execute();
    $item_ids[$nombre] = $stmt->insert_id;
    $stmt->close();

    // Si es un sub-sub-ítem, insertamos opciones en el `<select>`
    if (!$padre_id || in_array($nombre, ["Novedades", "Iluminación", "Tablero de fuerza", "Acceso", "Protección pasadas", "Aseo y limpieza", "Señalética de seguridad", "Botonera de inspección", "Enchufe lámpara portátil", "Final carrera de cabina", "Captador de posición", "Candado cables tractores"])) {
        $opciones = ["Sin novedad", "Corregido", "Sin corregir", "No aplica"];
        foreach ($opciones as $opcion) {
            $stmt = $conn->prepare("INSERT INTO checklist_opciones (item_id, opcion) VALUES (?, ?)");
            $stmt->bind_param("is", $item_ids[$nombre], $opcion);
            $stmt->execute();
            $stmt->close();
        }
    }
}

echo "Datos insertados correctamente.";
?>
