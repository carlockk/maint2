<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=UTF-8');

session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['mantencion_id'])) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos.']);
    exit();
}

$mantencion_id = intval($_POST['mantencion_id']);

// Finalizar mantención
$stmt = $conn->prepare("UPDATE mantenciones SET hora_fin = NOW(), duracion = TIMEDIFF(NOW(), hora_inicio) WHERE id = ?");
$stmt->bind_param('i', $mantencion_id);
$success = $stmt->execute();
$stmt->close();

if (!$success) {
    echo json_encode(['success' => false, 'error' => 'No se pudo finalizar.']);
    exit();
}

// Obtener información
$stmt = $conn->prepare(
    "SELECT m.hora_inicio, m.hora_fin, m.equipo_id,
            TIME_TO_SEC(TIMEDIFF(m.hora_fin, m.hora_inicio)) AS total_segundos,
            u.nombre AS tecnico,
            e.nombre AS equipo, e.modelo,
            ed.nombre AS edificio, ed.direccion, ed.id AS edificio_id
     FROM mantenciones m
     JOIN usuarios u ON m.usuario_id = u.id
     JOIN equipos e ON m.equipo_id = e.id
     JOIN edificios ed ON e.edificio_id = ed.id
     WHERE m.id = ?"
);
$stmt->bind_param('i', $mantencion_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

// Obtener correos de clientes asociados al edificio
$clienteCorreos = [];
if ($data) {
    $stmt = $conn->prepare(
        "SELECT c.correo FROM clientes c JOIN cliente_edificios ce ON c.id = ce.cliente_id WHERE ce.edificio_id = ?"
    );
    $stmt->bind_param('i', $data['edificio_id']);
    $stmt->execute();
    $resCli = $stmt->get_result();
    while ($row = $resCli->fetch_assoc()) {
        if (!empty($row['correo'])) {
            $clienteCorreos[] = $row['correo'];
        }
    }
    $stmt->close();
}

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'No se encontró información.']);
    exit();
}

$total_segundos = intval($data['total_segundos']);
$minutos = floor($total_segundos / 60);
$segundos = $total_segundos % 60;

$tiempo_legible = '';
if ($minutos > 0) {
    $tiempo_legible .= "$minutos minuto" . ($minutos === 1 ? '' : 's');
}
if ($segundos > 0) {
    $tiempo_legible .= ($minutos > 0 ? ' ' : '') . "$segundos segundo" . ($segundos === 1 ? '' : 's');
}
if ($tiempo_legible === '') {
    $tiempo_legible = '0 segundos';
}

// Armar correo
$asunto = "Mantención finalizada - {$data['equipo']}";
$cuerpo = "Se ha finalizado una mantención:\n\n" .
          "Técnico: {$data['tecnico']}\n" .
          "Edificio: {$data['edificio']} ({$data['direccion']})\n" .
          "Equipo: {$data['equipo']} - Modelo: {$data['modelo']}\n" .
          "Tiempo total: $tiempo_legible\n" .
          "Fecha: " . date('d-m-Y') . "\n";

// Checklist asociado al equipo
$stmt = $conn->prepare("SELECT checklist_id FROM checklist_equipos WHERE equipo_id = ? LIMIT 1");
$stmt->bind_param('i', $data['equipo_id']);
$stmt->execute();
$chk = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($chk) {
    $checklist_id = $chk['checklist_id'];
    $stmt = $conn->prepare(
        "SELECT ci.id, ci.nombre, ci.nivel, ci.padre_id,
                (SELECT estado FROM checklist_respuestas cr WHERE cr.mantencion_id = ? AND cr.item_id = ci.id LIMIT 1) AS estado
         FROM checklist_items ci
         WHERE ci.checklist_id = ?
         ORDER BY ci.nivel ASC, ci.padre_id ASC, ci.id ASC"
    );
    $stmt->bind_param('ii', $mantencion_id, $checklist_id);
    $stmt->execute();
    $resItems = $stmt->get_result();
    $stmt->close();

    $tree = [];
    while ($row = $resItems->fetch_assoc()) {
        $pid = $row['padre_id'] ?? 0;
        $tree[$pid][] = $row;
    }

    $lines = [];
    $build = function($pid, $nivel) use (&$tree, &$build, &$lines) {
        if (!isset($tree[$pid])) return;
        foreach ($tree[$pid] as $it) {
            $prefix = str_repeat('|--', $nivel);
            $estado = $it['estado'] ?? '-';
            $lines[] = "$prefix{$it['nombre']} : $estado";
            $build($it['id'], $nivel + 1);
        }
    };
    $build(0, 0);
    if ($lines) {
        $cuerpo .= "\nChecklist:\n" . implode("\n", $lines) . "\n";
    }
}

// Obtener correos seleccionados
$destinos = [];
if (!empty($_POST['correos'])) {
    $seleccionados = json_decode($_POST['correos'], true);
    if (is_array($seleccionados)) {
        $destinos = $seleccionados;
    }
}

if (empty($destinos)) {
    $destinos = $clienteCorreos;
}

$destinos = array_unique($destinos);

$errores = [];
foreach ($destinos as $correo) {
    if (!mail($correo, $asunto, $cuerpo, 'From: notificaciones@coffeewaffles.cl')) {
        $errores[] = $correo;
    }
}

echo json_encode([
    'success' => true,
    'minutos' => $minutos,
    'segundos' => $segundos,
    'tiempo_legible' => $tiempo_legible,
    'errores' => $errores
]);
