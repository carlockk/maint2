<?php
include 'db.php';

// Obtener equipos existentes para asignar el checklist
$equipos = $conn->query("SELECT id FROM equipos");

if ($equipos->num_rows === 0) {
    die("Error: No hay equipos registrados. Primero inserta equipos.");
}

while ($equipo = $equipos->fetch_assoc()) {
    $equipo_id = $equipo['id'];

    // Insertar el checklist para este equipo
    $stmt = $conn->prepare("INSERT INTO checklists (nombre, equipo_id) VALUES ('Checklist General', ?)");
    $stmt->bind_param("i", $equipo_id);
    $stmt->execute();
    $checklist_id = $stmt->insert_id;
    $stmt->close();

    // Definir los ítems y sub-ítems
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
        $padre_id = isset($item_ids[$padre_nombre]) ? $item_ids[$padre_nombre] : NULL;

        // Insertar el ítem en la base de datos
        $stmt = $conn->prepare("INSERT INTO checklist_items (checklist_id, nombre, nivel, padre_id) VALUES (?, ?, ?, ?)");
        $nivel = $padre_id ? 2 : 1;
        $stmt->bind_param("isii", $checklist_id, $nombre, $nivel, $padre_id);
        $stmt->execute();
        $item_ids[$nombre] = $stmt->insert_id;
        $stmt->close();

        // Si es un sub-ítem que requiere opciones de selección, agregarlas
        if (!$padre_id || in_array($nombre, [
            "Novedades", "Iluminación", "Tablero de fuerza", "Acceso", "Protección pasadas", "Aseo y limpieza", "Señalética de seguridad",
            "Botonera de inspección", "Enchufe lámpara portátil", "Final carrera de cabina", "Captador de posición", "Candado cables tractores"
        ])) {
            $opciones = ["Sin novedad", "Corregido", "Sin corregir", "No aplica"];
            foreach ($opciones as $opcion) {
                $stmt = $conn->prepare("INSERT INTO checklist_opciones (item_id, opcion) VALUES (?, ?)");
                $stmt->bind_param("is", $item_ids[$nombre], $opcion);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

echo "Checklists y ítems creados correctamente.";
?>
