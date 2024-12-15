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

// Obtener el número de mensajes no leídos
$sql_mensajes = "SELECT COUNT(*) FROM mensajes WHERE id_destinatario = ? AND leido = 0";
$stmt_mensajes = $conn->prepare($sql_mensajes);
$stmt_mensajes->bind_param("i", $user_id);
$stmt_mensajes->execute();
$stmt_mensajes->bind_result($mensajes_no_leidos);
$stmt_mensajes->fetch();
$stmt_mensajes->close();

// Marcar los mensajes como leídos cuando el usuario accede a la página de mensajes
if ($_SERVER['REQUEST_URI'] === '/mensajes.php') {
    $sql_leer = "UPDATE mensajes SET leido = 1 WHERE id_destinatario = ? AND leido = 0";
    $stmt_leer = $conn->prepare($sql_leer);
    $stmt_leer->bind_param("i", $user_id);
    $stmt_leer->execute();
    $stmt_leer->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_day = $_POST['day'] ?? null;
    $user_id = $_SESSION['user_id'];

    if (!$selected_day) {
        echo json_encode(['error' => 'No se ha seleccionado un día.']);
        exit();
    }

    // Obtener citas del día para el profesionista logueado
    $sql_cita = "
        SELECT 
            DATE_FORMAT(c.hora_cita, '%H:%i') AS hora, 
            p.nombre AS paciente_nombre
        FROM cita c
        JOIN cuentas p ON c.id_paciente = p.id_cuenta
        WHERE DATE(c.fecha_cita) = ? AND c.id_profesionista = ?
        ORDER BY c.hora_cita
    ";

    $stmt = $conn->prepare($sql_cita);
    $stmt->bind_param("si", $selected_day, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }

    $stmt->close();
    $conn->close();

    echo json_encode($appointments);
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medify</title>
    <link rel="website icon" type="png" href="./images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Para iconos -->
    <link rel="stylesheet" href="CSS/dashboard_profesionista.css">
</head>
<body>

<!-- Header -->

<?php
    include 'nav.php'
?>

<div class="container">
    <div class="contenido-container">
        <div class="calendar">
            <h2 id="calendar-month"></h2>
            <table id="calendar-table">
                <thead>
                    <tr>
                        <th>Dom</th>
                        <th>Lun</th>
                        <th>Mar</th>
                        <th>Mié</th>
                        <th>Jue</th>
                        <th>Vie</th>
                        <th>Sáb</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Aquí se generarán dinámicamente los días -->
                </tbody>
            </table>
        </div>
        <div id="appointments-modal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3 id="selected-date"></h3>
                <ul id="appointments-list"></ul>
            </div>
        </div>
    </div>
</div>
<script>
    // Función para generar el calendario
    function generateCalendar() {
        const calendarTable = document.querySelector('#calendar-table tbody');
        const calendarMonth = document.getElementById('calendar-month');
        const today = new Date();
        const year = today.getFullYear();
        const month = today.getMonth();
        const firstDayOfMonth = new Date(year, month, 1);
        const lastDayOfMonth = new Date(year, month + 1, 0);
        const startDay = firstDayOfMonth.getDay();
        const daysInLastMonth = new Date(year, month, 0).getDate();
        const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        calendarMonth.textContent = `${monthNames[month]} ${year}`;
        let calendarHTML = '<tr>';
        let dayCount = 1;
        let nextMonthDayCount = 1;

        for (let i = 0; i < startDay; i++) {
            calendarHTML += `<td class="inactive">${daysInLastMonth - startDay + i + 1}</td>`;
        }
        for (let day = 1; day <= lastDayOfMonth.getDate(); day++) {
            const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            calendarHTML += `<td class="active" data-date="${dateString}" onclick="fetchAppointments('${dateString}')">${day}</td>`;
            if ((startDay + day) % 7 === 0) {
                calendarHTML += '</tr><tr>';
            }
            dayCount++;
        }
        for (let i = dayCount; i <= 7; i++) {
            calendarHTML += `<td class="inactive">${nextMonthDayCount++}</td>`;
        }
        calendarHTML += '</tr>';
        calendarTable.innerHTML = calendarHTML;
    }

    function fetchAppointments(day) {
        fetch('dashboard_profesionista.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `day=${day}`
        })
            .then(response => response.json())
            .then(data => {
                const modal = document.getElementById('appointments-modal');
                const appointmentsList = document.getElementById('appointments-list');
                const selectedDateSpan = document.getElementById('selected-date');
                appointmentsList.innerHTML = '';
                selectedDateSpan.textContent = `Citas para el ${day}`;
                if (data.error) {
                    appointmentsList.innerHTML = `<li>${data.error}</li>`;
                } else if (data.length === 0) {
                    appointmentsList.innerHTML = '<li>No hay citas para este día</li>';
                } else {
                    data.forEach(appointment => {
                        const li = document.createElement('li');
                        li.textContent = `${appointment.hora}: ${appointment.paciente_nombre}`;
                        appointmentsList.appendChild(li);
                    });
                }
                modal.style.display = 'flex';
            })
            .catch(error => {
                console.error('Error al cargar citas:', error);
            });
    }

    document.addEventListener('DOMContentLoaded', () => {
        generateCalendar();
        const modal = document.getElementById('appointments-modal');
        const modalClose = document.querySelector('.close');
        modalClose.addEventListener('click', () => {
            modal.style.display = 'none';
        });
        window.addEventListener('click', event => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
</script>

</body>
</html>
