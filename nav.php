<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="CSS/nav.css">
    <title>Document</title>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <a href="#" class="logo">
            <img src="images/logo.png" alt="">
            Medify
        </a>

        <div  class="nav-container" method="POST">
            <div class="nav">
                <form method="POST" class="nav-form">
                    <button type="submit" name="action" value="inicio" class="nav-button">Inicio</button>
                </form>

                <form method="POST" class="nav-form">
                    <button type="submit" name="action" value="red" class="nav-button">Empleos</button>
                </form>

                <form method="POST" class="nav-form">
                    <button type="submit" name="action" value="mensajes" class="nav-button">Mensajes</button>
                </form>

                <!--<form method="POST" class="nav-form">
                    <button type="submit" name="action" value="notificaciones" class="nav-button">Notificaciones</button>
                </form>-->
            </div>
        </div>
        
        <div class="user-info">
            <!-- Foto de perfil -->
            <?php if ($foto_perfil): ?>
                <img src="<?php echo $foto_perfil; ?>" alt="Foto de Perfil">
            <?php else: ?>
                <img src="https://via.placeholder.com/40" alt="Foto de Perfil">
            <?php endif; ?>
            <span><?php echo $user_name; ?></span>
        </div>

        <button class="menu-toggle" aria-label="Toggle navigation">☰</button>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <form method="POST" class="sidebar-form">
            <button type="submit" name="action" value="inicio" class="sidebar-button active">
                <i class="fas fa-home"></i> Inicio
            </button>
        </form>
        <form method="POST" class="sidebar-form">
            <button type="submit" name="action" value="perfil" class="sidebar-button">
                <i class="fas fa-user"></i> Mi perfil
            </button>
        </form>
        <form method="POST" class="sidebar-form">
            <button type="submit" name="action" value="expediente" class="sidebar-button">
                <i class="fas fa-users"></i> Expedientes Clínicos
            </button>
        </form>
        <form method="POST" class="sidebar-form">
            <button type="submit" name="action" value="red" class="sidebar-button">
                <i class="fas fa-briefcase"></i> Empleos
            </button>
        </form>
        <form method="POST" class="sidebar-form">
            <button type="submit" name="action" value="mensajes" class="sidebar-button">
                <i class="fas fa-comment"></i> Mensajes
            </button>
        </form>
        <form method="POST" class="sidebar-form">
            <button type="submit" name="action" value="cerrar" class="sidebar-button">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </button>
        </form>
    </div>

    <script>
        // Selección de elementos  
        const menuToggle = document.querySelector('.menu-toggle');
        const navContainer = document.querySelector('.nav-container');
        const sidebar = document.querySelector('.sidebar');

        // Alternar visibilidad del menú en dispositivos móviles
        menuToggle.addEventListener('click', () => {
            console.log("Botón de menú clickeado");
            navContainer.classList.toggle('active');
            sidebar.classList.toggle('active');
        });
    </script>
</body>
</html>