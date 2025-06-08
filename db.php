<?php
$host = "localhost";
$user = "coffeewa_maintcheck"; // Cambiar según la configuración
$password = "Irios.,._1A"; // Cambiar según la configuración
$dbname = "coffeewa_maintcheck";

// Conexión a la base de datos
$conn = new mysqli($host, $user, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>
