<?php
ini_set('session.cookie_secure', 1);  // Solo cookies HTTPS
ini_set('session.cookie_httponly', 1); // Cookies no accesibles desde JavaScript
ini_set('session.use_strict_mode', 1); // Evita reutilizar IDs de sesión
// Iniciar la sesión
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirigir si no está autenticado
    exit();
}

// Conectar a la base de datos
include 'db.php';

// Verificar que el ID del profesional está presente en la solicitud
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: ID del profesional no especificado.");
}

$id_profesional = intval($_GET['id']);
$id_paciente = $_SESSION['user_id']; // ID del usuario logueado
$user_name = $_SESSION['user_name'];
$user_type = $_SESSION['user_type'];

// Obtener el tipo de usuario (profesionista o cliente)
$queryTipoUsuario = "SELECT tipo FROM cuentas WHERE id_cuenta = ?";
$stmt = $conn->prepare($queryTipoUsuario);
$stmt->bind_param("i", $id_paciente);
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

//foto de perfil
$sql = "SELECT foto_perfil FROM cuentas WHERE id_cuenta = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_paciente);
$stmt->execute();
$stmt->bind_result($foto_perfil);
$stmt->fetch();
$stmt->close();

// Obtener los datos del profesional
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
WHERE cuentas.id_cuenta = ?
";
$stmt = $conn->prepare($sql2);
$stmt->bind_param("i", $id_profesional);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $profesionista = $result->fetch_assoc();
} else {
    echo "Profesional no encontrado.";
    exit;
}

$sql_agenda = "
    SELECT
        cuentas.nombre AS nombre_profesional, 
        profesiones.nombre_profesion, 
        especialidades.nombre_especialidad 
    FROM cuentas 
    LEFT JOIN profesiones ON cuentas.id_profesion = profesiones.id_profesion 
    LEFT JOIN especialidades ON cuentas.id_especialidad = especialidades.id_especialidad 
    WHERE cuentas.id_cuenta = ?";
$stmt = $conn->prepare($sql_agenda);
$stmt->bind_param("i", $id_profesional);
$stmt->execute();
$stmt->bind_result($nombre_profesional, $nombre_profesion, $nombre_especialidad);
$stmt->fetch();
$stmt->close();

// Procesar el formulario al enviarlo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha_cita = $_POST['fecha_cita'];
    $hora_cita = $_POST['hora_cita'];

    // Verificar que la hora no esté ocupada
    $check_sql = "SELECT * FROM cita WHERE id_profesionista = ? AND fecha_cita = ? AND hora_cita = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("iss", $id_profesional, $fecha_cita, $hora_cita);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $error = "La hora seleccionada ya está ocupada.";
    } else {
        // Insertar la cita en la base de datos
        $insert_sql = "INSERT INTO cita (fecha_cita, hora_cita, id_paciente, id_profesionista) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("ssii", $fecha_cita, $hora_cita, $id_paciente, $id_profesional);

        if ($stmt->execute()) {
            $success = "Cita agendada con éxito.";
        } else {
            $error = "Error al agendar la cita.";
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Cita</title>
    <link rel="website icon" type="png" href="./images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Para iconos -->
    <link rel="stylesheet" href="CSS/agendar_cita.css">
</head>
<body>
    <!-- Header -->
    <?php
    include 'nav.php' 
    ?>

    <div class="contenido-container">
            <div class="perfil-container">
                <img src="<?php echo $profesionista['foto_perfil'] ? $profesionista['foto_perfil'] : 'https://via.placeholder.com/150'; ?>" alt="Foto de Perfil">
                <h2><?php echo $profesionista['nombre']; ?></h2>
                <p><strong>Profesión:</strong> <?php echo $profesionista['nombre_profesion']; ?></p>
                <p><strong>Especialidad:</strong> <?php echo $profesionista['nombre_especialidad']; ?></p>
                <p><strong>Estado:</strong> <?php echo $profesionista['nombre_estado']; ?></p>
                <p><strong>Municipio:</strong> <?php echo $profesionista['nombre_municipio']; ?></p>
            </div>
        
        
            <div class="container">
                <!-- Mostrar mensajes de éxito o error -->
                <?php if (isset($success)): ?>
                    <div class="alert success"><?php echo $success; ?></div>
                <?php elseif (isset($error)): ?>
                    <div class="alert error"><?php echo $error; ?></div>
                <?php endif; ?>
        
                <form method="POST">
                    <!--<label for="fecha_cita">Fecha seleccionada:</label>-->
                    <input type="hidden" name="fecha_cita" id="fecha_cita" required>
        
                    <label for="hora_cita">Hora seleccionada:</label>
                    <input type="text" name="hora_cita" id="hora_cita" readonly required>
        
                    <!-- Contenedor del calendario -->
                    <div id="calendario" class="calendario"></div>
        
                    <button type="submit" class="boton-cita">Agendar Cita</button>
                </form>
        
                <a href="dashboard_cliente.php" class="btn-back">Volver al Dashboard</a>
            </div>
    </div>

    <!-- Modal para selección de horas -->
    <div id="modalHoras" class="modal">
        <div class="modal-content">
            <h2>Seleccionar Hora</h2>
            <div id="horasDisponibles">
                <!-- Horas serán cargadas dinámicamente -->
            </div>
            <button id="cerrarModal">Cerrar</button>
        </div>
    </div>

    <script>
        const modal = document.getElementById('modalHoras');
        const btnCerrarModal = document.getElementById('cerrarModal');
        const horasDisponibles = document.getElementById('horasDisponibles');
        const inputFechaCita = document.getElementById('fecha_cita');
        const inputHoraCita = document.getElementById('hora_cita');
        const calendario = document.getElementById('calendario');

        let fechaActual = new Date();

        // Función para generar el calendario
        function generarCalendario(fecha) {
            calendario.innerHTML = '';
            const mes = fecha.getMonth();
            const anio = fecha.getFullYear();
            const primerDiaMes = new Date(anio, mes, 1).getDay();
            const ultimoDiaMes = new Date(anio, mes + 1, 0).getDate();

            // Obtener días ocupados
            fetch(`dias_ocupados.php?id_profesional=<?php echo $id_profesional; ?>`)
                .then(response => response.json())
                .then(diasOcupados => {
                    const diasOcupadosSet = new Set(diasOcupados);

                    // Encabezado
                    const encabezado = document.createElement('div');
                    encabezado.classList.add('calendario-header');
                    encabezado.innerHTML = `
                        <button id="prevMes">←</button>
                        <span>${fecha.toLocaleString('es', { month: 'long', year: 'numeric' })}</span>
                        <button id="nextMes">→</button>
                    `;
                    calendario.appendChild(encabezado);

                    // Días de la semana
                    const diasSemana = document.createElement('div');
                    diasSemana.classList.add('dias-semana');
                    ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'].forEach(dia => {
                        const diaEl = document.createElement('div');
                        diaEl.textContent = dia;
                        diasSemana.appendChild(diaEl);
                    });
                    calendario.appendChild(diasSemana);

                    // Días del mes
                    const diasMes = document.createElement('div');
                    diasMes.classList.add('dias-mes');
                    for (let i = 0; i < primerDiaMes; i++) {
                        diasMes.appendChild(document.createElement('div')); // Espacios vacíos
                    }
                    for (let dia = 1; dia <= ultimoDiaMes; dia++) {
                        const fechaDia = new Date(anio, mes, dia).toISOString().split('T')[0];
                        const diaEl = document.createElement('div');
                        diaEl.textContent = dia;
                        diaEl.classList.add('dia');
                        
                        // Marcar días ocupados en rojo
                        if (diasOcupadosSet.has(fechaDia)) {
                            diaEl.classList.add('ocupado');
                            diaEl.title = "Día completo. No disponible.";
                        } else {
                            diaEl.addEventListener('click', () => {
                                const fechaSeleccionada = new Date(anio, mes, dia);
                                inputFechaCita.value = fechaSeleccionada.toISOString().split('T')[0];
                                cargarHorasDisponibles(inputFechaCita.value);
                            });
                        }
                        diasMes.appendChild(diaEl);
                    }
                    calendario.appendChild(diasMes);

                    document.getElementById('prevMes').addEventListener('click', () => {
                        fechaActual.setMonth(fechaActual.getMonth() - 1);
                        generarCalendario(fechaActual);
                    });
                    document.getElementById('nextMes').addEventListener('click', () => {
                        fechaActual.setMonth(fechaActual.getMonth() + 1);
                        generarCalendario(fechaActual);
                    });
                });
        }


        function cargarHorasDisponibles(fecha) {
            modal.style.display = 'flex';
            fetch(`horas_disponibles.php?id_profesional=<?php echo $id_profesional; ?>&fecha=${fecha}`)
                .then(response => response.json())
                .then(data => {
                    horasDisponibles.innerHTML = '';
                    data.forEach(hora => {
                        const btn = document.createElement('button');
                        btn.textContent = hora.hora;
                        btn.classList.add(hora.ocupada ? 'ocupada' : 'disponible');
                        btn.disabled = hora.ocupada;
                        btn.addEventListener('click', () => {
                            inputHoraCita.value = hora.hora;
                            modal.style.display = 'none';
                        });
                        horasDisponibles.appendChild(btn);
                    });
                });
        }

        btnCerrarModal.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        generarCalendario(fechaActual);

    </script>
</body>
</html>
