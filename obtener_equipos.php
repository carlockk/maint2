<?php
include 'db.php';

$edificio_id = $_GET['edificio_id'];
$equipos = $conn->query("SELECT * FROM equipos WHERE edificio_id = $edificio_id");

while ($row = $equipos->fetch_assoc()) {
    echo '<input type="checkbox" name="equipos[]" value="'.$row['id'].'"> '.$row['nombre'].' ('.$row['modelo'].')<br>';
}
?>
