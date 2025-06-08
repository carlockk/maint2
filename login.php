<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $clave = $_POST['clave'];

    $stmt = $conn->prepare("SELECT id, clave, rol FROM usuarios WHERE nombre = ?");
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password, $rol);
        $stmt->fetch();

        if (password_verify($clave, $hashed_password)) {
            $_SESSION['usuario_id'] = $id;
            $_SESSION['rol'] = $rol;

            if ($rol == 'admin') {
                header("Location: dashboard_admin.php");
            } else {
                header("Location: dashboard_tecnico.php");
            }
            exit();
        } else {
            $error = "Clave incorrecta.";
        }
    } else {
        $error = "Usuario no encontrado.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">


<meta name="theme-color" content="#4CAF50">
<!-- Service Worker -->
<script>
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/maintcheck/service-worker.js')
      .then(function(registration) {
        console.log('Service Worker registrado con éxito:', registration);
      }).catch(function(error) {
        console.log('Fallo al registrar el Service Worker:', error);
      });
  }
</script>

</head>
<body class="login-page">
    <form class="login-form" method="POST">
        <h2>Iniciar Sesión</h2>
        <?php if(isset($error)) echo "<p class='error'>".htmlspecialchars($error)."</p>"; ?>
        <input type="text" name="nombre" placeholder="Usuario" required>
        <input type="password" name="clave" placeholder="Contraseña" required>
        <button type="submit">Ingresar</button>
        <button type="button" id="themeToggle" onclick="toggleTheme()">🌙</button>
    </form>

    <script>
      function toggleTheme() {
        document.body.classList.toggle('dark-mode');
        localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
      }
      if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
      }
    </script>
    <script src="offline.js"></script>
</body>
</html>
