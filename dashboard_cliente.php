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

// Obtener todos los profesionales registrados de la base de datos
$sql2 = "
    SELECT
        cuentas.id_cuenta,
        cuentas.nombre, 
        cuentas.foto_perfil, 
        profesiones.nombre_profesion, 
        especialidades.nombre_especialidad, 
        estados.nombre_estado, 
        municipios.nombre_municipio
    FROM cuentas
    LEFT JOIN profesiones ON cuentas.id_profesion = profesiones.id_profesion
    LEFT JOIN especialidades ON cuentas.id_especialidad = especialidades.id_especialidad
    LEFT JOIN estados ON cuentas.id_estado = estados.id_estado
    LEFT JOIN municipios ON cuentas.id_municipio = municipios.id_municipio
    WHERE cuentas.tipo = 'profesionista';
";
$result = $conn->query($sql2);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medify</title>
    <link rel="website icon" type="png" href="./images/logo.png">
    <link rel="stylesheet" href="CSS/dashboard_cliente.css"> <!-- Para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Para iconos -->
</head>
<body>

<div class="container">

   <!-- Header -->
    <?php
     include 'nav.php'
    ?>

    <!-- Contenido principal -->
    <div class="container-contenido">
        <!-- Contenido principal -->
        <div class="content">

            <!-- Feed de publicaciones -->
            <div class="InputContainer">
                <input placeholder="Que estas buscando...?" id="input" class="input" name="text" type="text">  
            </div>

            <!-- Publicaciones -->
            <div class="profesionales-container">
                <?php while ($profesionista = $result->fetch_assoc()): ?>
                <div class="tarjeta-profesional" 
                    data_id_cuenta= "<?php echo strtolower($cuentas['id_cuenta']); ?>"
                    data-profesion="<?php echo strtolower($profesionista['nombre_profesion']); ?>" 
                    data-especialidad="<?php echo strtolower($profesionista['nombre_especialidad']); ?>" 
                    data-estado="<?php echo strtolower($profesionista['nombre_estado']); ?>" 
                    data-municipio="<?php echo strtolower($profesionista['nombre_municipio']); ?>">
                    
                    <?php if ($profesionista['foto_perfil']): ?>
                        <img src="<?php echo $profesionista['foto_perfil']; ?>" alt="Foto de Perfil">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/150" alt="Foto de Perfil">
                    <?php endif; ?>

                    <h2><?php echo $profesionista['nombre']; ?></h2>
                    <p><strong>Profesión:</strong> <?php echo $profesionista['nombre_profesion']; ?></p>
                    <p><strong>Especialidad:</strong> <?php echo $profesionista['nombre_especialidad']; ?></p>
                    <p><strong>Estado:</strong> <?php echo $profesionista['nombre_estado']; ?></p>
                    <p><strong>Municipio:</strong> <?php echo $profesionista['nombre_municipio']; ?></p>

                    <a href="agentar_cita.php?id=<?php echo $profesionista['id_cuenta'];?>" class="boton-agenda">Agenda tu cita

                        <svg fill="currentColor" viewBox="0 0 24 24" class="icon">
                            <path
                            clip-rule="evenodd"
                            d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zm4.28 10.28a.75.75 0 000-1.06l-3-3a.75.75 0 10-1.06 1.06l1.72 1.72H8.25a.75.75 0 000 1.5h5.69l-1.72 1.72a.75.75 0 101.06 1.06l3-3z"
                            fill-rule="evenodd"
                            ></path>
                        </svg>
                    </a>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>
<script>
    const input = document.getElementById("input");
    const tarjetas = document.querySelectorAll(".tarjeta-profesional");

    input.addEventListener("input", () => {
        const searchTerm = input.value.toLowerCase();
        tarjetas.forEach((tarjeta) => {
            const profesion = tarjeta.getAttribute("data-profesion");
            const especialidad = tarjeta.getAttribute("data-especialidad");
            const estado = tarjeta.getAttribute("data-estado");
            const municipio = tarjeta.getAttribute("data-municipio");

            // Mostrar tarjeta si coincide con alguno de los criterios
            if (
                profesion.includes(searchTerm) || 
                especialidad.includes(searchTerm) || 
                estado.includes(searchTerm) || 
                municipio.includes(searchTerm)
            ) {
                tarjeta.style.display = "block";
            } else {
                tarjeta.style.display = "none";
            }
        });
    });
</script>

</body>
</html>
