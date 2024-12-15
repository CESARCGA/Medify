<?php
// Asegúrate de que el usuario está autenticado
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");  // Redirige al login si no está autenticado
    exit();
}

// Datos del usuario logueado
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_type = $_SESSION['user_type'];

// Conectar a la base de datos
include 'db.php';

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

// Obtener los detalles del usuario desde la base de datos
$sql = "SELECT correo_gmail, foto_perfil FROM cuentas WHERE id_cuenta = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_email, $foto_perfil);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Subir nueva foto de perfil
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == 0) {
        // Verificar el tipo de archivo (solo imágenes)
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $fileInfo = pathinfo($_FILES['foto_perfil']['name']);
        $fileExtension = strtolower($fileInfo['extension']);
        
        if (in_array($fileExtension, $allowedExtensions)) {
            // Definir la ruta donde se guardará la imagen
            $uploadDir = 'imagesperfil/';
            $fileName = uniqid() . '.' . $fileExtension;  // Nombre único para la imagen
            $uploadFile = $uploadDir . $fileName;

            // Mover la imagen al directorio de imágenes
            if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $uploadFile)) {
                // Actualizar la ruta de la foto de perfil en la base de datos
                $sql = "UPDATE cuentas SET foto_perfil = ? WHERE id_cuenta = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $uploadFile, $user_id);
                $stmt->execute();
                $stmt->close();

                // Redirigir al perfil después de la actualización
                header("Location: perfil.php");
                exit();
            } else {
                echo "Error al subir la imagen.";
            }
        } else {
            echo "Formato de imagen no válido.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medify</title>
    <link rel="website icon" type="png" href="./images/logo.png">
    <link rel="stylesheet" href="CSS/perfil.css"> <!-- Para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Para iconos -->
    <style>
        
    </style>
</head>
<body>

<div class="container">
    <!--header-->
    <?php
        include 'nav.php'
    ?>

    <!-- Contenido principal -->
    <div class="content">

        <div class="perfil-conten">
            <div class="profile-header">
                <!-- Foto de perfil del usuario -->
                <?php if ($foto_perfil): ?>
                    <img src="<?php echo $foto_perfil; ?>" alt="Foto de Perfil">
                <?php else: ?>
                    <img src="https://via.placeholder.com/100" alt="Foto de Perfil">
                <?php endif; ?>
    
                <div>
                    <h2><?php echo $user_name; ?></h2>
                    <p><?php echo $user_email; ?></p>
                </div>
            </div>
    
            <!-- Formulario para actualizar la foto de perfil -->
            <div class="upload-photo-form">
                <form action="perfil.php" method="POST" enctype="multipart/form-data">
                    <input type="file" name="foto_perfil" accept="image/*" required>
                    <button type="submit">Subir Foto de Perfil</button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>
