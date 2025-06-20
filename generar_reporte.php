<?php
require 'db.php';
require 'fpdf.php';

// Limpiar cualquier salida previa para evitar errores al enviar el PDF
if (ob_get_length()) {
    ob_end_clean();
}

if (!isset($_GET['mantencion_id'])) {
    die('ID no válido');
}
$id = intval($_GET['mantencion_id']);

$stmt = $conn->prepare("SELECT m.hora_inicio, m.hora_fin, u.nombre AS tecnico, e.nombre AS equipo, e.modelo, ed.nombre AS edificio, ed.direccion FROM mantenciones m JOIN usuarios u ON m.usuario_id = u.id JOIN equipos e ON m.equipo_id = e.id JOIN edificios ed ON e.edificio_id = ed.id WHERE m.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    die('Datos no encontrados');
}

$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'pdf';

if ($format !== 'pdf') {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<h1>Reporte de Mantención</h1>';
    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>Técnico</th><td>'.htmlspecialchars($data['tecnico']).'</td></tr>';
    echo '<tr><th>Equipo</th><td>'.htmlspecialchars($data['equipo'].' - '.$data['modelo']).'</td></tr>';
    echo '<tr><th>Edificio</th><td>'.htmlspecialchars($data['edificio'].' - '.$data['direccion']).'</td></tr>';
    echo '<tr><th>Inicio</th><td>'.$data['hora_inicio'].'</td></tr>';
    echo '<tr><th>Fin</th><td>'.$data['hora_fin'].'</td></tr>';
    echo '</table>';
    exit;
}

try {
    header('Content-Type: application/pdf');

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, utf8_decode('Reporte de Mantención'), 0, 1, 'C');
    $pdf->Ln(10);

    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(50, 8, utf8_decode('Técnico:'));
    $pdf->Cell(0, 8, utf8_decode($data['tecnico']), 0, 1);
    $pdf->Cell(50, 8, 'Equipo:');
    $pdf->Cell(0, 8, utf8_decode($data['equipo'] . ' - ' . $data['modelo']), 0, 1);
    $pdf->Cell(50, 8, 'Edificio:');
    $pdf->Cell(0, 8, utf8_decode($data['edificio'] . ' - ' . $data['direccion']), 0, 1);
    $pdf->Cell(50, 8, 'Inicio:');
    $pdf->Cell(0, 8, $data['hora_inicio'], 0, 1);
    $pdf->Cell(50, 8, 'Fin:');
    $pdf->Cell(0, 8, $data['hora_fin'], 0, 1);

    $pdf->Output('D', 'mantencion_'.$id.'.pdf');
} catch (Exception $e) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<h1>Reporte de Mantención</h1>';
    echo '<p>No se pudo generar el PDF. A continuación se muestra el informe:</p>';
    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>Técnico</th><td>'.htmlspecialchars($data['tecnico']).'</td></tr>';
    echo '<tr><th>Equipo</th><td>'.htmlspecialchars($data['equipo'].' - '.$data['modelo']).'</td></tr>';
    echo '<tr><th>Edificio</th><td>'.htmlspecialchars($data['edificio'].' - '.$data['direccion']).'</td></tr>';
    echo '<tr><th>Inicio</th><td>'.$data['hora_inicio'].'</td></tr>';
    echo '<tr><th>Fin</th><td>'.$data['hora_fin'].'</td></tr>';
    echo '</table>';
}
exit;
?>
