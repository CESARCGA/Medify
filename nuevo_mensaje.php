<?php
// Incluir el archivo de conexión a la base de datos
include('db.php');

// Validar si el usuario ha iniciado sesión
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirigir al login si no está autenticado
    header("Location: login.php");
    exit();
}

// Usuario actual basado en la sesión
$id_usuario_actual = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_type = $_SESSION['user_type'];

// Obtener el tipo de usuario (profesionista o cliente)
$queryTipoUsuario = "SELECT tipo FROM cuentas WHERE id_cuenta = ?";
$stmt = $conn->prepare($queryTipoUsuario);
$stmt->bind_param("i", $id_usuario_actual);
$stmt->execute();
$resultTipoUsuario = $stmt->get_result();
$usuario = $resultTipoUsuario->fetch_assoc();
$stmt->close();

// Redirigir según el tipo de usuario y la acción seleccionada
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
                    header("Location: login.php");
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


// Obtener la foto de perfil del usuario desde la base de datos
$sql = "SELECT foto_perfil FROM cuentas WHERE id_cuenta = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario_actual);
$stmt->execute();
$stmt->bind_result($foto_perfil);
$stmt->fetch();
$stmt->close();

// Buscar cuentas por nombre
if (isset($_GET['nombre'])) {
    $nombre = $_GET['nombre'];
    $query = "SELECT * FROM cuentas WHERE nombre LIKE ? LIMIT 10";
    $stmt = $conn->prepare($query);
    $searchTerm = "%" . $nombre . "%"; // Añadir el comodín para la búsqueda
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = null;
}

// Enviar mensaje a una cuenta
if (isset($_GET['id_cuenta'])) {
    $id_usuario_actual = 1; // Aquí puedes poner el ID del usuario actual desde sesión o algo similar
    $id_cuenta_destino = $_GET['id_cuenta'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensaje'])) {
        $mensajeTexto = $_POST['mensaje'];

        // Insertar el mensaje en la base de datos
        $queryInsertarMensaje = "
            INSERT INTO mensajes (id_remitente, id_destinatario, mensaje, fecha_enviado) 
            VALUES (?, ?, ?, NOW())
        ";
        $stmt = $conn->prepare($queryInsertarMensaje);
        $stmt->bind_param("iis", $id_usuario_actual, $id_cuenta_destino, $mensajeTexto);
        $stmt->execute();

        // Redirigir para evitar reenvío de formulario
        header("Location: nuevo_mensaje.php?id_cuenta=" . $id_cuenta_destino);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="website icon" type="png" href="./images/logo.png">
    <link rel="stylesheet" href="CSS/nuevo_mesaje.css"> <!-- Para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Para iconos -->
    <title>Nuevo Mensaje</title>
</head>
<body>

<?php
    include 'nav.php'
?>
<div class="container">

    <h1>Enviar mensaje</h1>
    
    <!-- Formulario de búsqueda -->
    <form method="GET" action="">
        <input type="text" name="nombre" placeholder="Buscar por nombre" required>
        <button type="submit" class="boton-mensaje">Buscar</button>
    </form>
    
    <?php if ($result && $result->num_rows > 0): ?>
        <div class="results">
            <h2>Resultados de búsqueda</h2>
            <ul>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <li>
                        <a href="nuevo_mensaje.php?id_cuenta=<?= $row['id_cuenta'] ?>">Enviar mensaje a <?= htmlspecialchars($row['nombre']) ?></a>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    <?php elseif ($result && $result->num_rows == 0): ?>
        <p>No se encontraron resultados para "<?= htmlspecialchars($nombre) ?>"</p>
    <?php endif; ?>
    
    <?php if (isset($id_cuenta_destino)): ?>
        <h2>Enviar mensaje</h2>
    
        <!-- Formulario para enviar mensaje -->
        <form method="POST" action="">
            <textarea name="mensaje" rows="4" placeholder="Escribe tu mensaje..." required></textarea><br><br>
            <button type="submit" class="boton-mensaje">Enviar mensaje</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
