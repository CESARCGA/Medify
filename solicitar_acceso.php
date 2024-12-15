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

$mensaje = '';
$clientes = [];

// Si se ha enviado el formulario de solicitud de acceso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_cuenta_acceso'])) {
    // Obtén el ID del profesionista que solicita acceso (simulado para este ejemplo)
    $id_cuenta_otorgante = $user_id; // Cambia esto según el usuario en sesión
    $id_cuenta_acceso = $_POST['id_cuenta_acceso'];

    // Inserta la solicitud de acceso en la base de datos
    try {
        $query = "INSERT INTO solicitudes_acceso (id_cuenta_solicitante, id_cuenta_destino, estado)
                  VALUES (?, ?, 'pendiente')";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $id_cuenta_otorgante, $id_cuenta_acceso);
        $stmt->execute();
        $stmt->close();
        $mensaje = "Solicitud enviada con éxito.";
    } catch (Exception $e) {
        $mensaje = "Error al enviar la solicitud: " . $e->getMessage();
    }
}

// Si el formulario de búsqueda es enviado
if (isset($_POST['search'])) {
    $searchQuery = $_POST['search'];

    // Consulta SQL para buscar clientes por nombre (insensible a mayúsculas/minúsculas)
    try {
        $query = "SELECT id_cuenta, nombre, foto_perfil FROM cuentas WHERE tipo = 'cliente' AND LOWER(nombre) LIKE LOWER(?)";
        $stmt = $conn->prepare($query);
        $searchTerm = '%' . $searchQuery . '%';
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();

        // Obtener los resultados
        $clientes = [];
        while ($row = $result->fetch_assoc()) {
            $clientes[] = $row;
        }
    } catch (Exception $e) {
        $mensaje = "Error al obtener los clientes: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="website icon" type="png" href="./images/logo.png">
    <link rel="stylesheet" href="CSS/solicitar_acceso.css">
    <title>Enviar Solicitud</title>

</head>
<body>
<?php
    include 'nav.php'
?>

<div class="container">
    <h1>Enviar Solicitud de Acceso</h1>
    <?php if (!empty($mensaje)): ?>
        <p class="mensaje"><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>

    <!-- Formulario de búsqueda -->
    <form method="POST" id="search_form">
        <label for="search">Buscar Cliente:</label>
        <input type="text" name="search" id="search" placeholder="Escribe el nombre del cliente..." required>
        <button type="submit">Buscar</button>
    </form>

    <!-- Contenedor de los resultados (Tarjetas) -->
    <?php if (!empty($clientes)): ?>
        <div class="cards-container">
            <?php foreach ($clientes as $cliente): ?>
                <div class="card">
                    <img src="<?= htmlspecialchars($cliente['foto_perfil']) ?: 'default.jpg' ?>" alt="<?= htmlspecialchars($cliente['nombre']) ?>">
                    <h3><?= htmlspecialchars($cliente['nombre']) ?></h3>
                    <form method="POST" action="">
                        <input type="hidden" name="id_cuenta_acceso" value="<?= htmlspecialchars($cliente['id_cuenta']) ?>">
                        <button type="submit">Seleccionar</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif (isset($_POST['search']) && empty($clientes)): ?>
        <p>No se encontraron resultados.</p>
    <?php endif; ?>
</div>
</body>
</html>
