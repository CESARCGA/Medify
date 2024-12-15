<?php
// Incluir el archivo de conexión a la base de datos
include 'db.php';  // Asegúrate de que este archivo contiene la conexión correcta a tu base de datos

// Validación de login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener datos del formulario
    $username = $_POST['username'];  // correo_gmail
    $password = $_POST['password'];  // contrasena

    // Consulta SQL para buscar el usuario por correo
    $sql = "SELECT id_cuenta, correo_gmail, contrasena, nombre, tipo FROM cuentas WHERE correo_gmail = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);  // 's' es para string
    $stmt->execute();
    $result = $stmt->get_result();

    // Verificar si se encontró el usuario
    if ($result->num_rows > 0) {
        // Obtener el usuario
        $user = $result->fetch_assoc();

        // Verificar si la contraseña es correcta
        if (password_verify($password, $user['contrasena'])) {
            // Iniciar sesión y redirigir según el tipo de usuario
            session_start();
            $_SESSION['user_id'] = $user['id_cuenta'];
            $_SESSION['user_name'] = $user['nombre'];
            $_SESSION['user_type'] = $user['tipo'];

            // Redirigir al dashboard según el tipo de usuario
            if ($user['tipo'] == 'profesionista') {
                header("Location: dashboard_profesionista.php");  // Redirige al dashboard de profesionista
            } elseif ($user['tipo'] == 'cliente') {
                header("Location: dashboard_cliente.php");  // Redirige al dashboard de cliente
            } else {
                header("Location: dashboard_cliente.php");  // En caso de otro tipo de usuario
            }
            exit();
        } else {
            // Contraseña incorrecta
            echo "<script>alert('Contraseña incorrecta');</script>";
        }
    } else {
        // Usuario no encontrado
        echo "<script>alert('Usuario no encontrado');</script>";
    }

    // Cerrar la conexión
    $stmt->close();
    $conn->close();
}

// Función para obtener todas las imágenes del directorio (no se modifica esta parte)
function getImages($dir) {
    $images = [];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif']; // Extensiones permitidas

    // Escanea el directorio y filtra las imágenes válidas
    $files = scandir($dir);
    foreach ($files as $file) {
        $filePath = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_file($filePath)) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if (in_array(strtolower($ext), $allowedExtensions)) {
                $images[] = $filePath;
            }
        }
    }
    return $images;
}

// Ruta del directorio donde están las imágenes
$imageDirectory = 'images'; // Asegúrate de tener esta carpeta con las imágenes

// Obtener las imágenes del directorio
$images = getImages($imageDirectory);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medify</title>
    <link rel="website icon" type="png" href="./images/logo.png">
    <link rel="stylesheet" href="./CSS/login.css">
</head>
<body>

    <!-- Carrusel de Imágenes como fondo -->
    <div class="carousel">
        <img src="./images/1.png" class="carousel-image active" alt="Imagen 1">
        <img src="./images/2.png" class="carousel-image" alt="Imagen 2">
        <img src="./images/3.png" class="carousel-image" alt="Imagen 3">
        <img src="./images/5.png" class="carousel-image" alt="Imagen 4">
    </div>

    <div class="container">
        <!-- Formulario de Login en la parte derecha -->
        <div class="login-container">
            <h2>Iniciar Sesión</h2>
            <form action="login.php" method="POST">
                <input type="text" name="username" placeholder="Correo" required>
                <input type="password" name="password" placeholder="Contraseña" required>
                <input type="submit" value="Iniciar sesión">
            </form>
            <a href="register.php">No tienes cuenta? Registrate</a>
        </div>

    </div>


    <script>
        const images = document.querySelectorAll('.carousel-image');
        let currentIndex = 0;
        const intervalTime = 10000; // Duración en milisegundos (10 segundos)

        // Función para cambiar a la siguiente imagen
        function changeImage() {
            // Remover la clase 'active' de la imagen actual
            images[currentIndex].classList.remove('active');

            // Incrementar el índice para mostrar la siguiente imagen
            currentIndex = (currentIndex + 1) % images.length;

            // Añadir la clase 'active' a la nueva imagen
            images[currentIndex].classList.add('active');
        }

        // Cambiar la imagen cada 10 segundos
        setInterval(changeImage, intervalTime);
    </script>

</body>
</html>
