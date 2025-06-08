<?php
session_start();

// Si el usuario ya inició sesión, redirigir al dashboard correspondiente
if (isset($_SESSION['usuario_id'])) {
    if ($_SESSION['rol'] === 'admin') {
        header("Location: dashboard_admin.php");
    } else {
        header("Location: dashboard_tecnico.php");
    }
    exit();
}

// Si no hay sesión, mostrar el login
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Mantenimiento</title>
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
    <form class="login-form" method="POST" action="login.php">
        <h2>Iniciar Sesión</h2>
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
