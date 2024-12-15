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
// Incluye la conexión a la base de datos
include 'db.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

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
                case 'perfil':
                    header("Location: perfil.php");
                    break;
                case 'expediente':
                    header("Location: profesionista.php");
                    break;
                case 'cerrar':
                    header("Location: login.php");
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
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($foto_perfil);
$stmt->fetch();
$stmt->close();

try {
    // Consulta para obtener los accesos otorgados
    $query = "SELECT c.id_cuenta, c.nombre FROM accesos a 
              JOIN cuentas c ON a.id_cuenta_acceso = c.id_cuenta
              WHERE a.id_cuenta_otorgante = ?";
              
    $stmt = $conn->prepare($query);
    $id_cuenta2 = $user_id; // Cambia este valor al ID del usuario en sesión
    $stmt->bind_param("i", $id_cuenta2);
    $stmt->execute();
    $result = $stmt->get_result();

    // Obtener los datos
    $accesos = [];
    while ($row = $result->fetch_assoc()) {
        $accesos[] = $row;
    }

    $stmt->close();
} catch (Exception $e) {
    die("Error al realizar la consulta: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/profesionista.css">
    <link rel="website icon" type="png" href="./images/logo.png">
    <title>Medify</title>
</head>
<body>

<?php
    include 'nav.php'
?>

<div class="container">
    <div class="container-contenido">
        <h1>Panel de Profesionista</h1>
        <h2>Accesos Otorgados</h2>
        <table>
            <tr><th>Nombre</th><th>Acciones</th></tr>
            <?php foreach ($accesos as $acceso): ?>
            <tr>
                <td><?= htmlspecialchars($acceso['nombre']) ?></td>
                <td>
                    <!-- Botón para ver detalles del cliente -->
                    <button class="button-tabla" onclick="location.href='detalle_paciente.php?id_cuenta=<?= $acceso['id_cuenta'] ?>'">Ver Detalles</button>
                    
                    <!-- Botón para editar detalles del cliente -->
                    <button class="button-tabla" onclick="location.href='ver_detalle.php?id_cuenta=<?= $acceso['id_cuenta'] ?>'">Editar Cliente</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <button class="button-solicitud" onclick="location.href='solicitar_acceso.php'">Enviar Solicitud</button>
    </div>
</div>
</body>
</html>
