<?php
include 'db.php';

// Verificar si el usuario está autenticado
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Datos del usuario logueado
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_type = $_SESSION['user_type'];

// Obtener el tipo de usuario (profesionista o cliente)
$queryTipoUsuario = "SELECT tipo FROM cuentas WHERE id_cuenta = ?";
$stmt = $conn->prepare($queryTipoUsuario);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resultTipoUsuario = $stmt->get_result();
$usuario = $resultTipoUsuario->fetch_assoc();
$stmt->close();

// Redirigir al dashboard correspondiente según el tipo de usuario
if ($usuario) {
    $tipo_usuario = $usuario['tipo'];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($tipo_usuario == 'profesionista') {
            switch ($action) {
                case 'inicio':
                    header("Location: dashboard_profesionista.php");
                    break;
                case 'red':
                    header("Location: Empleos.php");
                    break;
                case 'mensajes':
                    header("Location: mensajes.php");
                    break;
                case 'perfil':
                    header("Location: perfil.php");
                    break;
                case 'expediente':
                    header("Location: profesionista.php");
                    break;
                case 'cerrar':
                    header("Location: index.php");
                    break;
                /*case 'notificaciones':
                    header("Location: notificaciones_profesionista.php");
                    break;*/
            }
        } elseif ($tipo_usuario == 'cliente') {
            switch ($action) {
                case 'inicio':
                    header("Location: dashboard_cliente.php");
                    break;
                case 'red':
                    header("Location: Empleos.php");
                    break;
                case 'mensajes':
                    header("Location: mensajes.php");
                    break;
                case 'expediente':
                    header("Location: aceptar.php");
                    break;
                case 'cerrar':
                    header("Location: index.php");
                    break;
                case 'perfil':
                    header("Location: perfil.php");
                    break;
                /*case 'notificaciones':
                    header("Location: notificaciones_cliente.php");
                    break;*/
            }
        }
        exit();
    }
}

//foto de perfil
$foto = "SELECT foto_perfil FROM cuentas WHERE id_cuenta = ?";
$stmt = $conn->prepare($foto);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($foto_perfil);
$stmt->fetch();
$stmt->close();

// Manejar la creación de posts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contenido_post'])) {
    $contenido_post = $conn->real_escape_string($_POST['contenido_post']);
    $sql = "INSERT INTO posts (contenido, id_usuario, fecha_creacion) VALUES ('$contenido_post', '$user_id', NOW())";
    $conn->query($sql);
}

// Filtros de búsqueda
$search_query = $_GET['search'] ?? '';
$sql = "SELECT p.id_post, p.contenido, p.fecha_creacion, u.nombre AS autor
        FROM posts p
        JOIN cuentas u ON p.id_usuario = u.id_cuenta
        WHERE p.contenido LIKE '%$search_query%'
        ORDER BY p.fecha_creacion DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicaciones</title>
    <link rel="website icon" type="png" href="./images/logo.png">
    <link rel="stylesheet" href="CSS/Empleos.css">
</head>
<body>
    
    <?php
        include 'nav.php'
    ?>

    <div class="container-contenido">
        <div class="content">
            <section class="post-section">
                <div class="container">
                    <h3>Crear una publicación</h3>
                    <form action="Empleos.php" method="POST" class="post-form">
                        <textarea name="contenido_post" placeholder="Escribe tu publicación aquí..." required></textarea>
                        <button type="submit">Publicar</button>
                    </form>
                </div>
            </section>
        
            <section class="search-section">
                <div class="container">
                    <h3>Buscar publicaciones</h3>
                    <form action="Empleos.php" method="GET" class="search-form">
                        <input type="text" name="search" placeholder="Buscar..." value="<?php echo htmlspecialchars($search_query); ?>">
                        <button type="submit">Buscar</button>
                    </form>
                </div>
            </section>
        
            <section class="posts-section">
                <div class="container">
                    <h3>Publicaciones recientes</h3>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <div class="post-card">
                                <p class="post-content"><?php echo htmlspecialchars($row['contenido']); ?></p>
                                <p class="post-author">Publicado por: <?php echo htmlspecialchars($row['autor']); ?></p>
                                <p class="post-date">Fecha: <?php echo $row['fecha_creacion']; ?></p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No se encontraron publicaciones.</p>
                    <?php endif; ?>
                </div>
            </section>
        
            <footer class="main-footer">
                <div class="container">
                    <p>&copy; 2024 Medify. Todos los derechos reservados.</p>
                </div>
            </footer>
        </div>
    </div>


</body>
</html>
