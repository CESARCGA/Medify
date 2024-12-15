<?php
ini_set('session.cookie_secure', 1);  // Solo cookies HTTPS
ini_set('session.cookie_httponly', 1); // Cookies no accesibles desde JavaScript
ini_set('session.use_strict_mode', 1); // Evita reutilizar IDs de sesión
// Asegúrate de que el usuario está autenticado
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");  // Redirige al login si no está autenticado
    exit();
}

$user_id = $_SESSION['user_id'];
// Incluye la conexión a la base de datos
include 'db.php';

$mensaje = '';

// Si se hace clic en eliminar acceso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'], $_POST['id_acceso'])) {
    $id_acceso = $_POST['id_acceso'];

    // Eliminar el acceso de la base de datos
    if ($_POST['accion'] === 'eliminar') {
        try {
            // Eliminar el acceso de la tabla
            $query = "DELETE FROM accesos WHERE id_acceso = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id_acceso);
            $stmt->execute();
            $stmt->close();
            $mensaje = "Acceso eliminado con éxito.";
        } catch (Exception $e) {
            $mensaje = "Error al eliminar el acceso: " . $e->getMessage();
        }
    }
}

// Consulta para obtener los accesos registrados
$accesos = [];
try {
    $query = "SELECT a.id_acceso, c1.nombre AS otorgante, c2.nombre AS acceso, a.fecha_otorgado 
              FROM accesos a
              JOIN cuentas c1 ON a.id_cuenta_otorgante = c1.id_cuenta
              JOIN cuentas c2 ON a.id_cuenta_acceso = c2.id_cuenta
              WHERE id_cuenta_acceso =  ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Obtener los accesos
    while ($row = $result->fetch_assoc()) {
        $accesos[] = $row;
    }
} catch (Exception $e) {
    $mensaje = "Error al obtener los accesos: " . $e->getMessage();
}

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


// Obtener la foto de perfil del usuario desde la base de datos
$sql = "SELECT foto_perfil FROM cuentas WHERE id_cuenta = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($foto_perfil);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="website icon" type="png" href="./images/logo.png">
    <link rel="stylesheet" href="CSS/dashboard_accesos.css">
    <title>Dashboard de Accesos</title>
    <style>

    </style>
</head>
<body>

    <?php
        include 'nav.php'
    ?>


<div class="container">
    <h1>Accesos</h1>

    <?php if (!empty($mensaje)): ?>
        <p class="mensaje"><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>

    <div class="accesos-container">
        <?php if (!empty($accesos)): ?>
            <?php foreach ($accesos as $acceso): ?>
                <div class="acceso">
                    <h3>Acceso de <?= htmlspecialchars($acceso['otorgante']) ?> a <?= htmlspecialchars($acceso['acceso']) ?></h3>
                    <p><strong>Fecha de otorgamiento:</strong> <?= htmlspecialchars($acceso['fecha_otorgado']) ?></p>
                    <form method="POST" action="">
                        <input type="hidden" name="id_acceso" value="<?= htmlspecialchars($acceso['id_acceso']) ?>">
                        <button type="submit" name="accion" value="eliminar">Eliminar Acceso</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No hay accesos registrados.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
