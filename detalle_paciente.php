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
// Conexión a la base de datos
include 'db.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_type = $_SESSION['user_type'];


// Obtener la foto de perfil del usuario desde la base de datos
$sql = "SELECT foto_perfil FROM cuentas WHERE id_cuenta = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($foto_perfil);
$stmt->fetch();
$stmt->close();

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

$mensaje = '';
$id_cuenta = '';
$nombre_cuenta = '';

// Verifica si el id_cuenta es pasado como parámetro en la URL
if (isset($_GET['id_cuenta'])) {
    $id_cuenta = $_GET['id_cuenta'];

    // Consulta para obtener el nombre de la cuenta del cliente
    $query = "SELECT nombre FROM cuentas WHERE id_cuenta = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_cuenta);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $nombre_cuenta = $row['nombre'];
    } else {
        $mensaje = "Cuenta no encontrada.";
    }

    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $post_data = json_encode($_POST);
    echo "<script>console.log('POST Data:', $post_data);</script>";


    // Obtener los valores del formulario
    $id_cuenta = $_POST['id_cuenta'];
    $nombre = $_POST['nombre'];
    $edad = $_POST['edad'];
    $sexo = $_POST['sexo'];
    $fecha_ingreso = $_POST['fecha_ingreso'];
    $motivo_consulta = $_POST['motivo_consulta'];
    $historial_medico = $_POST['historial_medico'];
    $examen_fisico = $_POST['examen_fisico'];
    $pruebas = $_POST['pruebas'];
    $diagnostico = $_POST['diagnostico'];
    $tratamiento = $_POST['tratamiento'];
    $expediente = $user_id;

    // Verificar y validar la fecha
    // Validar fecha
    if (!empty($fecha_ingreso)) {
        $fecha_obj = DateTime::createFromFormat('Y-m-d', $fecha_ingreso);
        if ($fecha_obj && $fecha_obj->format('Y-m-d') === $fecha_ingreso) {
            $fecha_ingreso = $fecha_obj->format('Y-m-d'); // Transformar a formato correcto
        } else {
            $mensaje = "Formato de fecha inválido. Use el formato YYYY-MM-DD.";
        }
    } else {
        $mensaje = "La fecha de ingreso es obligatoria.";
    }
    


    // Si la fecha está vacía o tiene el valor '0000-00-00', establecerla como NULL
   /*if (empty($fecha_ingreso) || $fecha_ingreso === '0000-00-00') {
        $fecha_ingreso = NULL;  // Establecer como NULL si no se proporciona una fecha
    } elseif (DateTime::createFromFormat('Y-m-d', $fecha_ingreso) === false) {
        $mensaje = "Formato de fecha inválido. Por favor, use el formato YYYY-MM-DD.";
    }*/

    if (empty($mensaje)) {
        // Inserción de datos en la base de datos
        $query = "INSERT INTO pacientes (id_cuenta, nombre, edad, sexo, fecha_ingreso, motivo_consulta, historial_medico, examen_fisico, pruebas, diagnostico, tratamiento, id_expediente) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "isissssssssi",  // Los tipos de datos de los parámetros
            $id_cuenta,
            $nombre,
            $edad,
            $sexo,
            $fecha_ingreso,
            $motivo_consulta,
            $historial_medico,
            $examen_fisico,
            $pruebas,
            $diagnostico,
            $tratamiento,
            $expediente
        );

        if ($stmt->execute()) {
            $mensaje = "Paciente registrado con éxito.";
        } else {
            $mensaje = "Error al registrar al paciente: " . $stmt->error;
        }

        $stmt->close();
    }
    echo "<script>console.log('Resultado de la operación: " . addslashes($mensaje) . "');</script>";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/detalle_paciente.css">
    <link rel="website icon" type="png" href="./images/logo.png">
    <title>Medify</title>

</head>
<body>
<?php
 include 'nav.php'
?>

<div class="contenido">
    <div class="container">
        <h1>Registrar Paciente</h1>
    
        <?php if (!empty($mensaje)): ?>
            <p class="mensaje"><?= htmlspecialchars($mensaje) ?></p>
        <?php endif; ?>
    
        <form method="POST">
            <label for="id_cuenta">ID Cuenta:</label>
            <input type="number" name="id_cuenta" id="id_cuenta" value="<?= htmlspecialchars($id_cuenta) ?>" readonly>
    
            <label for="nombre">Nombre del Paciente:</label>
            <input type="text" name="nombre" id="nombre" value="<?= htmlspecialchars($nombre_cuenta) ?>" readonly>
    
            <label for="edad">Edad:</label>
            <input type="number" name="edad" id="edad" required>
    
            <label for="sexo">Sexo:</label>
            <select name="sexo" id="sexo" required>
                <option value="Masculino">Masculino</option>
                <option value="Femenino">Femenino</option>
            </select>
    
            <label for="fecha_ingreso">Fecha de Ingreso:</label>
            <input type="date" name="fecha_ingreso" id="fecha_ingreso" required>
    
            <label for="motivo_consulta">Motivo de Consulta:</label>
            <textarea name="motivo_consulta" id="motivo_consulta" rows="4" required></textarea>
    
            <label for="historial_medico">Historial Médico:</label>
            <textarea name="historial_medico" id="historial_medico" rows="4" required></textarea>
    
            <label for="examen_fisico">Examen Físico:</label>
            <textarea name="examen_fisico" id="examen_fisico" rows="4" required></textarea>
    
            <label for="pruebas">Pruebas:</label>
            <textarea name="pruebas" id="pruebas" rows="4" required></textarea>
    
            <label for="diagnostico">Diagnóstico:</label>
            <textarea name="diagnostico" id="diagnostico" rows="4" required></textarea>
    
            <label for="tratamiento">Tratamiento:</label>
            <textarea name="tratamiento" id="tratamiento" rows="4" required></textarea>
    
            <button type="submit" class="guardar">Guardar Paciente</button>
        </form>
    </div>
</div>

</body>
</html>
