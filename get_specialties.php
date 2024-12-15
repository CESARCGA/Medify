
<?php
include 'db.php'; // Archivo de conexión a la base de datos

if (isset($_GET['id_profesion'])) {
    $id_profesion = $_GET['id_profesion'];

    // Obtener las especialidades de la profesión seleccionada
    $especialities = [];
    $sql = "SELECT id_especialidad, nombre_especialidad FROM especialidades WHERE id_profesion = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_profesion);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $especialities[] = $row;
        }
    }

    // Devolver las especialidades en formato JSON
    echo json_encode($especialities);
}
?>
