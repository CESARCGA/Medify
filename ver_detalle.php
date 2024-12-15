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
$id_cuenta = '';

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


// Verifica si el id_cuenta es pasado como parámetro en la URL
if (isset($_GET['id_cuenta'])) {
    $id_cuenta = $_GET['id_cuenta'];

    // Consulta para obtener el nombre de la cuenta del cliente
    $query2 = "SELECT nombre FROM cuentas WHERE id_cuenta = ?";
    $stmt = $conn->prepare($query2);
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

$mensaje = '';

// Consulta para obtener los pacientes ordenados por fecha de ingreso
$query = "SELECT p.*, c.nombre AS cuenta_nombre
          FROM pacientes p
          JOIN cuentas c ON p.id_cuenta = c.id_cuenta
          WHERE p.id_expediente = $user_id
          ORDER BY p.fecha_ingreso DESC";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Error en la preparación de la consulta: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();

$pacientes = [];
while ($row = $result->fetch_assoc()) {
    $pacientes[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Pacientes</title>
    <link rel="website icon" type="png" href="./images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
        }
        .card {
            background: #0073b1;
            margin: 10px 0;
            cursor: pointer;
            color: white;
        }
        .modal-header {
            background-color: #0073b1;
            color: white;
        }
        .modal-body {
            padding: 20px;
        }

        .container{
            padding: 70px 20px 20px 250px;
        }

        @media (max-width: 768px) {
            .container{
                padding: 55px 10px ;
            }
        }
    </style>
</head>
<body>

<?php
    include 'nav.php'
?>


<div class="container mt-5">
    <h1 class="text-center mb-4">Detalles de Pacientes</h1>

    <div class="row">
        <?php foreach ($pacientes as $paciente): ?>
            <div class="col-md-4">
                <div class="card" data-bs-toggle="modal" data-bs-target="#modalPaciente<?=$paciente['id_paciente']?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($paciente['nombre']) ?></h5>
                        <p class="card-text">Edad: <?= htmlspecialchars($paciente['edad']) ?> años</p>
                        <p class="card-text">Fecha de Ingreso: <?= htmlspecialchars($paciente['fecha_ingreso']) ?></p>
                    </div>
                </div>
            </div>

            <!-- Modal for displaying patient details -->
            <div class="modal fade" id="modalPaciente<?=$paciente['id_paciente']?>" tabindex="-1" aria-labelledby="modalPacienteLabel<?=$paciente['id_paciente']?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalPacienteLabel<?=$paciente['id_paciente']?>">Detalles del Paciente: <?= htmlspecialchars($paciente['nombre']) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <table class="table">
                                <tr><th>Cuenta</th><td><?= htmlspecialchars($paciente['cuenta_nombre']) ?></td></tr>
                                <tr><th>Edad</th><td><?= htmlspecialchars($paciente['edad']) ?></td></tr>
                                <tr><th>Sexo</th><td><?= htmlspecialchars($paciente['sexo']) ?></td></tr>
                                <tr><th>Fecha de Ingreso</th><td><?= htmlspecialchars($paciente['fecha_ingreso']) ?></td></tr>
                                <tr><th>Motivo de Consulta</th><td><?= htmlspecialchars($paciente['motivo_consulta']) ?></td></tr>
                                <tr><th>Historial Médico</th><td><?= htmlspecialchars($paciente['historial_medico']) ?></td></tr>
                                <tr><th>Examen Físico</th><td><?= htmlspecialchars($paciente['examen_fisico']) ?></td></tr>
                                <tr><th>Pruebas</th><td><?= htmlspecialchars($paciente['pruebas']) ?></td></tr>
                                <tr><th>Diagnóstico</th><td><?= htmlspecialchars($paciente['diagnostico']) ?></td></tr>
                                <tr><th>Tratamiento</th><td><?= htmlspecialchars($paciente['tratamiento']) ?></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
