<?php
// Archivo: obtener_especialidades.php

include 'db.php'; // Incluye la conexión a la base de datos

if (isset($_GET['id_profesion'])) {
    $id_profesion = $_GET['id_profesion'];

    // Preparar la consulta para obtener las especialidades correspondientes a la profesión seleccionada
    $sql = "SELECT id_especialidad, nombre_especialidad FROM especialidades WHERE id_profesion = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $id_profesion);
        $stmt->execute();
        $result = $stmt->get_result();

        // Crear un array para almacenar las especialidades
        $especialidades = [];
        while ($row = $result->fetch_assoc()) {
            $especialidades[] = $row;
        }

        // Devolver las especialidades en formato JSON
        echo json_encode($especialidades);
    } else {
        // Error al preparar la consulta
        echo json_encode(["error" => "Error al preparar la consulta."]);
    }
} else {
    // Si no se proporciona el parámetro id_profesion
    echo json_encode(["error" => "Parámetro id_profesion no proporcionado."]);
}
?>
