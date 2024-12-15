<?php
// Datos de conexión a la base de datos
$host = "localhost";
$user = "root"; // Cambia esto si es diferente
$password = ""; // Cambia esto si tienes una contraseña
$database = "lol";

// Conexión a MySQL
$conn = new mysqli($host, $user, $password, $database);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


?>

