<?php 
// Función para obtener todas las imágenes del directorio
function getImages($dir) {
    $images = [];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif']; // Extensiones permitidas

    if (!is_dir($dir)) {
        return $images; // Si no existe el directorio, regresamos un arreglo vacío
    }

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

// Conectar a la base de datos para obtener las profesiones, especialidades, estados y municipios
$professions = [];
$especialities = [];
$states = [];
$municipalities = [];

include 'db.php'; // Archivo de conexión a la base de datos

// Obtener las profesiones
$sqlProfesion = "SELECT id_profesion, nombre_profesion FROM profesiones";
$resultProfesion = $conn->query($sqlProfesion);
if ($resultProfesion && $resultProfesion->num_rows > 0) {
    while ($row = $resultProfesion->fetch_assoc()) {
        $professions[] = $row;
    }
}

// Obtener las especialidades
$sqlEspecialidad = "SELECT id_especialidad, nombre_especialidad FROM especialidades";
$resultEspecialidad = $conn->query($sqlEspecialidad);
if ($resultEspecialidad && $resultEspecialidad->num_rows > 0) {
    while ($row = $resultEspecialidad->fetch_assoc()) {
        $especialities[] = $row;
    }
}

// Obtener los estados
$sqlEstado = "SELECT id_estado, nombre_estado FROM estados";
$resultEstado = $conn->query($sqlEstado);
if ($resultEstado && $resultEstado->num_rows > 0) {
    while ($row = $resultEstado->fetch_assoc()) {
        $states[] = $row;
    }
}

// Obtener los municipios
$sqlMunicipio = "SELECT id_municipio, nombre_municipio FROM municipios";
$resultMunicipio = $conn->query($sqlMunicipio);
if ($resultMunicipio && $resultMunicipio->num_rows > 0) {
    while ($row = $resultMunicipio->fetch_assoc()) {
        $municipalities[] = $row;
    }
}

// Proceso de registro
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener los datos del formulario
    $correo_gmail = $_POST['correo_gmail'];
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_BCRYPT); // Cifrar contraseña
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo']; // Tipo de usuario (profesionista o paciente)
    $id_profesion = $_POST['id_profesion'];
    $id_especialidad = $_POST['id_especialidad'];
    $id_estado = $_POST['id_estado'];
    $id_municipio = $_POST['id_municipio'];

    // Validar que el tipo sea "profesionista" o "cliente"
    if ($tipo !== 'profesionista' && $tipo !== 'cliente') {
        echo "<script>alert('Tipo de usuario no válido. Debe ser profesionista o cliente.');</script>";
    } else {
        // Consulta SQL para insertar en la tabla 'cuentas'
        $sql = "INSERT INTO cuentas (correo_gmail, contrasena, nombre, tipo, id_profesion, id_especialidad, id_estado, id_municipio)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        // Preparar la consulta SQL
        $stmt = $conn->prepare($sql);

        // Asegúrate de que las variables coincidan con el tipo correcto para cada columna
        $stmt->bind_param("ssssiiii", $correo_gmail, $contrasena, $nombre, $tipo, $id_profesion, $id_especialidad, $id_estado, $id_municipio);

        // Ejecutar la consulta y verificar si el registro fue exitoso
        if ($stmt->execute()) {
            // Redirigir al login después de registro exitoso
            header('Location: login.php'); // Redirigir a la página de login
            exit(); // Detenemos la ejecución del script después de redirigir
        } else {
            // Mostrar un error si algo sale mal en la inserción
            echo "<script>alert('Error en el registro: " . $stmt->error . "');</script>";
        }

        // Cerrar la conexión
        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/register.css"> <!-- Para iconos -->
    <link rel="website icon" type="png" href="./images/logo.png">
    <title>Registro</title>
    <script>
        // Función para cargar especialidades dinámicamente
        function loadSpecialties() {
            const professionId = document.getElementById('id_profesion').value;
            const specialtySelect = document.getElementById('id_especialidad');

            // Limpiar las opciones anteriores
            specialtySelect.innerHTML = '<option value="" disabled selected>Selecciona tu Especialidad</option>';

            if (professionId) {
                // Realizar solicitud AJAX para obtener las especialidades
                const xhr = new XMLHttpRequest();
                xhr.open('GET', 'get_specialties.php?id_profesion=' + professionId, true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        const specialties = JSON.parse(xhr.responseText);
                        specialties.forEach(function(specialty) {
                            const option = document.createElement('option');
                            option.value = specialty.id_especialidad;
                            option.textContent = specialty.nombre_especialidad;
                            specialtySelect.appendChild(option);
                        });
                    }
                };
                xhr.send();
            }
        }

        // Función para cargar municipios dinámicamente
        function loadMunicipalities() {
            const stateId = document.getElementById('id_estado').value;
            const municipalitySelect = document.getElementById('id_municipio');

            // Limpiar las opciones anteriores
            municipalitySelect.innerHTML = '<option value="" disabled selected>Selecciona tu Municipio</option>';

            if (stateId) {
                // Realizar solicitud AJAX para obtener los municipios
                const xhr = new XMLHttpRequest();
                xhr.open('GET', 'get_municipalities.php?id_estado=' + stateId, true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        const municipalities = JSON.parse(xhr.responseText);
                        municipalities.forEach(function(municipality) {
                            const option = document.createElement('option');
                            option.value = municipality.id_municipio;
                            option.textContent = municipality.nombre_municipio;
                            municipalitySelect.appendChild(option);
                        });
                    }
                };
                xhr.send();
            }
        }

        // Función para mostrar/ocultar campos según el tipo de usuario
        function toggleFields() {
            const tipo = document.getElementById('tipo').value;
            const profesionSelect = document.getElementById('id_profesion');
            const especialidadSelect = document.getElementById('id_especialidad');

            if (tipo === 'cliente') {
                profesionSelect.disabled = true;
                especialidadSelect.disabled = true;
                profesionSelect.required = false;
                especialidadSelect.required = false;
            } else {
                profesionSelect.disabled = false;
                especialidadSelect.disabled = false;
                profesionSelect.required = true;
                especialidadSelect.required = true;
            }
        }
    </script>
</head>
<body>

<div class="container-global">
        <!-- Carrusel de Imágenes como fondo -->
        <div class="carousel">
            <img src="./images/1.png" class="carousel-image active" alt="Imagen 1">
            <img src="./images/2.png" class="carousel-image" alt="Imagen 2">
            <img src="./images/3.png" class="carousel-image" alt="Imagen 3">
            <img src="./images/5.png" class="carousel-image" alt="Imagen 4">
        </div>
        
        <div class="container">
            <div class="container2">
                <div class="register-container">
                    <h2>Registrar Usuario</h2>
                    <form action="register.php" method="POST">
                        <input type="email" name="correo_gmail" placeholder="Correo Gmail" required>
                        <input type="password" name="contrasena" placeholder="Contraseña" required>
                        <input type="text" name="nombre" placeholder="Nombre" required>
                        
                        <!-- Tipo de Usuario -->
                        <select name="tipo" required id="tipo" onchange="toggleFields()">
                            <option value="" disabled selected>Selecciona tu Tipo</option>
                            <option value="profesionista">Profesionista</option>
                            <option value="cliente">Paciente</option>
                        </select>
            
                        <!-- Profesión -->
                        <div id="profesionField">
                            <select name="id_profesion" id="id_profesion" onchange="loadSpecialties()" (opcional) : >
                                <option value="" disabled selected>Selecciona tu Profesión</option>
                                <?php
                                foreach ($professions as $profession) {
                                    echo "<option value=\"" . $profession['id_profesion'] . "\">" . $profession['nombre_profesion'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div id="especialidadField">
                            <!-- Especialidad -->
                            <select name="id_especialidad" id="id_especialidad" (opcional) : >
                                <option value="" disabled selected>Selecciona tu Especialidad</option>
                            </select>
                        </div>
            
                        <!-- Estado -->
                        <select name="id_estado" id="id_estado" onchange="loadMunicipalities()" required>
                            <option value="" disabled selected>Selecciona tu Estado</option>
                            <?php
                            foreach ($states as $estado) {
                                echo "<option value=\"" . $estado['id_estado'] . "\">" . $estado['nombre_estado'] . "</option>";
                            }
                            ?>
                        </select>
            
                        <!-- Municipio -->
                        <select name="id_municipio" id="id_municipio" required>
                            <option value="" disabled selected>Selecciona tu Municipio</option>
                        </select>
            
                        <input type="submit" value="Registrar">

                        <a href="login.php">
                            Ya tienes cuanta? Inicia secion
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!--si es paciente no muetsra la profecion ni la especialidad-->
    <!--<script>
        function toggleFields() {
            const role = document.getElementById("tipo").value;
            const profesionField = document.getElementById("profesionField");
            const especialidadField = document.getElementById("especialidadField");

            if (role === "cliente") {
            // Ocultar campos si se selecciona "Paciente"
            profesionField.style.display = "none";
            especialidadField.style.display = "none";
            } else {
            // Mostrar campos para otros roles
            profesionField.style.display = "block";
            especialidadField.style.display = "block";
            }
        }
    </script>-->
        
    
    <!-- Carrucel de img -->
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
