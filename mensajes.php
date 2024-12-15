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

// Obtener usuarios con los que se ha interactuado
$queryUsuarios = "
    SELECT DISTINCT 
        CASE 
            WHEN id_remitente = ? THEN id_destinatario
            ELSE id_remitente
        END AS id_usuario
    FROM mensajes 
    WHERE id_remitente = ? OR id_destinatario = ?
";
$stmt = $conn->prepare($queryUsuarios);
$stmt->bind_param("iii", $id_usuario_actual, $id_usuario_actual, $id_usuario_actual);
$stmt->execute();
$result = $stmt->get_result();

$usuarios = [];
while ($row = $result->fetch_assoc()) {
    $usuarios[] = $row['id_usuario'];
}

// Manejo del receptor seleccionado
$id_usuario_receptor = $_GET['id_usuario'] ?? null;
$mensajes = [];
if ($id_usuario_receptor) {
    // Recuperar mensajes entre usuarios
    $queryMensajes = "
        SELECT * FROM mensajes
        WHERE (id_remitente = ? AND id_destinatario = ?)
           OR (id_remitente = ? AND id_destinatario = ?)
        ORDER BY fecha_enviado ASC
    ";
    $stmt = $conn->prepare($queryMensajes);
    $stmt->bind_param("iiii", $id_usuario_actual, $id_usuario_receptor, $id_usuario_receptor, $id_usuario_actual);
    $stmt->execute();
    $resultMensajes = $stmt->get_result();

    while ($row = $resultMensajes->fetch_assoc()) {
        $mensajes[] = $row;
    }

    // Marcar mensajes como leídos
    $queryMarcarLeidos = "
        UPDATE mensajes 
        SET leido = 1 
        WHERE (id_remitente = ? AND id_destinatario = ?) OR (id_remitente = ? AND id_destinatario = ?) AND leido = 0
    ";
    $stmt = $conn->prepare($queryMarcarLeidos);
    $stmt->bind_param("iiii", $id_usuario_receptor, $id_usuario_actual, $id_usuario_actual, $id_usuario_receptor);
    $stmt->execute();

    // Enviar un mensaje
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensaje'])) {
        $mensajeTexto = trim($_POST['mensaje']);
        if (!empty($mensajeTexto)) {
            $queryInsertarMensaje = "
                INSERT INTO mensajes (id_remitente, id_destinatario, mensaje, fecha_enviado) 
                VALUES (?, ?, ?, NOW())
            ";
            $stmt = $conn->prepare($queryInsertarMensaje);
            $stmt->bind_param("iis", $id_usuario_actual, $id_usuario_receptor, $mensajeTexto);
            $stmt->execute();

            // Redirigir para evitar reenvío
            header("Location: mensajes.php?id_usuario=" . $id_usuario_receptor);
            exit();
        }
    }
}

// Obtener el número de mensajes no leídos
$queryMensajesNoLeidos = "
    SELECT COUNT(*) FROM mensajes 
    WHERE id_destinatario = ? AND leido = 0
";
$stmt = $conn->prepare($queryMensajesNoLeidos);
$stmt->bind_param("i", $id_usuario_actual);
$stmt->execute();
$stmt->bind_result($mensajes_no_leidos);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="website icon" type="png" href="./images/logo.png">
    <link rel="stylesheet" href="CSS/mensajes.css"> <!-- Para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Para iconos -->
    <title>Medify</title>

</head>
<body>

<?php
 include 'nav.php'
?>

<div class="container">
    <a href="nuevo_mensaje.php">Nuevo Mensaje</a>

    <!-- Sidebar de usuarios -->
    <div class="usuarios">
        <h2>Chats</h2>
        <?php if (!empty($usuarios)): ?>
            <?php foreach ($usuarios as $usuario_id): ?>
                <?php
                    // Obtener el nombre y la foto del perfil de cada usuario
                    $queryUsuario = "SELECT nombre, foto_perfil FROM cuentas WHERE id_cuenta = ?";
                    $stmt = $conn->prepare($queryUsuario);
                    $stmt->bind_param("i", $usuario_id);
                    $stmt->execute();
                    $resultUsuario = $stmt->get_result();
                    $usuario = $resultUsuario->fetch_assoc();
                ?>
                <div class="usuario-item" onclick="location.href='mensajes.php?id_usuario=<?= $usuario_id ?>'">
                    <img src="<?= $usuario['foto_perfil'] ?: 'default.jpg' ?>" alt="Usuario">
                    <span><?= htmlspecialchars($usuario['nombre']) ?></span>
                    <?php if ($usuario_id == $id_usuario_receptor && $mensajes_no_leidos > 0): ?>
                        <span class="badge"><?= $mensajes_no_leidos ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No tienes chats aún.</p>
        <?php endif; ?>
    </div>

    <!-- Chat -->
    <div class="chat">
        <div class="chat-header">
            <span>Chat</span>
        </div>
        <div class="messages">
            <?php if (!empty($mensajes)): ?>
                <?php foreach ($mensajes as $mensaje): ?>
                    <div class="message <?= $mensaje['id_remitente'] == $id_usuario_actual ? 'sent' : 'received' ?>">
                        <?= htmlspecialchars($mensaje['mensaje']) ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No hay mensajes aún.</p>
            <?php endif; ?>
        </div>

        <form method="post" class="message-input">
            <textarea name="mensaje" placeholder="Escribe tu mensaje..."></textarea>
            <button type="submit">Enviar</button>
        </form>
    </div>
</div>

</body>
</html>
