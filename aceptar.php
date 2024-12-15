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
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($foto_perfil);
$stmt->fetch();
$stmt->close();

$mensaje = '';

// Si se hace clic en aceptar o rechazar solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'], $_POST['id_solicitud'])) {
    $id_solicitud = $_POST['id_solicitud'];
    $accion = $_POST['accion'];

    // Establecer el nuevo estado según la acción
    $estado = ($accion === 'aceptar') ? 'aceptada' : 'rechazada';

    // Iniciar la transacción para asegurar que las consultas sean atómicas
    try {
        $conn->begin_transaction();

        // Actualizar el estado de la solicitud
        $query = "UPDATE solicitudes_acceso SET estado = ? WHERE id_solicitud = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $estado, $id_solicitud);
        $stmt->execute();

        // Si se acepta la solicitud, registrar en la tabla de accesos
        if ($estado === 'aceptada') {
            // Obtener los detalles de la solicitud para insertar en la tabla accesos
            $query_solicitud = "SELECT id_cuenta_solicitante, id_cuenta_destino FROM solicitudes_acceso WHERE id_solicitud = ?";
            $stmt_solicitud = $conn->prepare($query_solicitud);
            if ($stmt_solicitud) {
                // Consulta preparada correctamente
                $stmt_solicitud->bind_param("i", $id_solicitud);
                $stmt_solicitud->execute();
                $result_solicitud = $stmt_solicitud->get_result();

                // Verificar si hay resultados
                if ($result_solicitud->num_rows > 0) {
                    $solicitud = $result_solicitud->fetch_assoc();

                    // Insertar el acceso en la tabla accesos
                    $query_acceso = "INSERT INTO accesos (id_cuenta_otorgante, id_cuenta_acceso, fecha_otorgado) 
                                     VALUES (?, ?, NOW())";
                    $stmt_acceso = $conn->prepare($query_acceso);
                    $stmt_acceso->bind_param("ii", $solicitud['id_cuenta_solicitante'], $solicitud['id_cuenta_destino']);
                    $stmt_acceso->execute();
                } else {
                    // No se encontró la solicitud
                    throw new Exception("No se encontró la solicitud con ID: $id_solicitud");
                }
                // Cerrar el statement de solicitud
                $stmt_solicitud->close();
            } else {
                // Error al preparar la consulta de solicitud
                throw new Exception("Error al preparar la consulta para obtener la solicitud.");
            }
        }

        // Commit de la transacción
        $conn->commit();

        // Cerrar las consultas
        $stmt->close();
        if (isset($stmt_acceso)) {
            $stmt_acceso->close();
        }

        $mensaje = "Solicitud " . $estado . " con éxito.";
    } catch (Exception $e) {
        // Si ocurre un error, hacer rollback
        $conn->rollback();
        $mensaje = "Error al actualizar la solicitud: " . $e->getMessage();
    }
}

// Consulta para obtener las solicitudes pendientes
$solicitudes = [];
try {
    $query = "SELECT sa.id_solicitud, sa.estado, c1.nombre AS solicitante, c2.nombre AS destino 
              FROM solicitudes_acceso sa 
              JOIN cuentas c1 ON sa.id_cuenta_solicitante = c1.id_cuenta
              JOIN cuentas c2 ON sa.id_cuenta_destino = c2.id_cuenta
              WHERE sa.estado = 'pendiente'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    // Obtener las solicitudes pendientes
    while ($row = $result->fetch_assoc()) {
        $solicitudes[] = $row;
    }
} catch (Exception $e) {
    $mensaje = "Error al obtener las solicitudes: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/aceptar.css">
    <link rel="website icon" type="png" href="./images/logo.png">
    <title>Solicitudes</title>
</head>
<body>
    <?php
        include 'nav.php'
    ?>


<div class="container">
    <h1>Solicitudes de Acceso</h1>

    <?php if (!empty($mensaje)): ?>
        <p class="mensaje"><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>

    <div class="solicitudes-container">
        <?php if (!empty($solicitudes)): ?>
            <?php foreach ($solicitudes as $solicitud): ?>
                <div class="solicitud">
                    <h3>Solicitud de <?= htmlspecialchars($solicitud['solicitante']) ?> a <?= htmlspecialchars($solicitud['destino']) ?></h3>
                    <p>Estado: <?= htmlspecialchars($solicitud['estado']) ?></p>
                    <form method="POST" action="">
                        <input type="hidden" name="id_solicitud" value="<?= htmlspecialchars($solicitud['id_solicitud']) ?>">
                        <button type="submit" name="accion" value="aceptar" class="aceptar">Aceptar</button>
                        <button type="submit" name="accion" value="rechazar" class="rechazar">Rechazar</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No hay solicitudes pendientes.</p>
        <?php endif; ?>
    </div>

    <button onclick="location.href='dashboard_accesos.php'" class="boton-regreso">Regresar</button>
</div>
</body>
</html>
