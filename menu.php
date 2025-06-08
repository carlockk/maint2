<!-- MENÚ ADMIN RESPONSIVO -->
<div class="sidebar" id="sidebar">
    <div class="menu-header">
        <h2>Menú</h2>
        <button class="close-menu" id="closeMenuBtn" onclick="toggleMenu()">✖</button>
    </div>
    <a href="dashboard_admin.php">🏠 Inicio</a>
    <a href="gestion_clientes.php">🏢 Clientes</a>
    <a href="gestion_edificios.php">🏢 Edificios</a>
    <a href="gestion_equipos.php">⚙️ Equipos</a>
    <a href="gestion_checklist.php">📋 Checklist</a>
    <a href="gestion_usuarios.php">👤 Usuarios</a>
    <a href="historial.php">📜 Historial Mantenciones</a>
    <a href="logout.php">🚪 Cerrar Sesión</a>
    <div class="theme-container"><span>Modo </span><button id="themeToggle" onclick="toggleTheme()">🌙</button></div>
</div>

<!-- BOTÓN MENÚ HAMBURGUESA -->
<button class="menu-btn" id="menuBtn" onclick="toggleMenu()">
    <span></span>
    <span></span>
    <span></span>
</button>

<!-- JavaScript para el menú -->
<script>
    function toggleMenu() {
        if (window.innerWidth > 768) return; // solo en móviles

        let sidebar = document.getElementById("sidebar");
        let menuBtn = document.getElementById("menuBtn");
        let closeBtn = document.getElementById("closeMenuBtn");

        sidebar.classList.toggle("active");

        if (sidebar.classList.contains("active")) {
            menuBtn.style.display = "none";
            closeBtn.style.display = "block";
        } else {
            menuBtn.style.display = "block";
            closeBtn.style.display = "none";
        }
    }

    // Cerrar menú al hacer clic fuera del área
    document.addEventListener("click", function(event) {
        if (window.innerWidth > 768) return;

        let sidebar = document.getElementById("sidebar");
        let menuBtn = document.getElementById("menuBtn");
        let closeBtn = document.getElementById("closeMenuBtn");

        if (!sidebar.contains(event.target) && !menuBtn.contains(event.target) && !closeBtn.contains(event.target)) {
            sidebar.classList.remove("active");
            menuBtn.style.display = "block";
            closeBtn.style.display = "none";
        }
    });

    function toggleTheme() {
        document.body.classList.toggle('dark-mode');
        localStorage.setItem('theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
    }

    window.addEventListener('DOMContentLoaded', function() {
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }
    });
</script>

<!-- CSS para el menú -->
<style>
.sidebar {
    width: 250px;
    margin-right: 270px;;
    background: #2c3e50;
    color: white;
    padding: 20px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    overflow-y: auto;
    transition: transform 0.3s ease-in-out;
}

.sidebar a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 10px;
    margin: 5px 0;
}

.sidebar a.active {
    background: #1abc9c;
    font-weight: bold;
}

.main-content {
        margin-left: 250px; /* Igual al ancho del sidebar */
        transition: margin-left 0.3s ease-in-out;
    }

/* === BOTÓN MENÚ HAMBURGUESA === */
.menu-btn {
    display: none;
    position: fixed;
    top: 15px;
    left: 15px;
    border: none;
    background: none;
    cursor: pointer;
    z-index: 1001;
    padding: 5px;
}

.menu-btn span {
    display: block;
    width: 30px;
    height: 4px;
    margin: 5px 0;
    background: black;
    border-radius: 2px;
}

/* === BOTÓN CERRAR (X) === */
.close-menu {
    display: none;
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
    position: absolute;
    top: 10px;
    right: 15px;
}

.theme-container {
    position: absolute;
    bottom: 20px;
    left: 20px;
}

/* === RESPONSIVE: MENU HAMBURGUESA === */
@media screen and (max-width: 768px) {
    .sidebar {
    transform: translateX(-100%);
    position: fixed;
    width: 250px;
    height: 100vh;
    background: rgba(44, 62, 80, 0.5); /* Color oscuro con transparencia */
    backdrop-filter: blur(10px); /* Desenfoque tipo macOS */
    -webkit-backdrop-filter: blur(10px); /* Compatibilidad con Safari */
    z-index: 1000;
    transition: transform 0.3s ease;
    color: white;
}


    .sidebar.active {
        transform: translateX(0);
    }

    .menu-btn {
        display: block;
    }

    .close-menu {
        display: none;
    }

    /* Ajuste para que el menú no se sobreponga al contenido */
    .main-content {
        margin-top: 60px;
    }
}

@media screen and (min-width: 769px) {
    .menu-btn,
    .close-menu {
        display: none !important;
    }
}

/* Cuando el menú está oculto en móviles, el contenido ocupa todo el ancho */
@media screen and (max-width: 768px) {
    .main-content {
        margin-left: 0;
    }
}


</style>
