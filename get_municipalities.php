<?php
include 'db.php';

if (isset($_GET['id_estado'])) {
    $id_estado = $_GET['id_estado'];

    // Obtener los municipios correspondientes al estado
    $sql = "SELECT id_municipio, nombre_municipio FROM municipios WHERE id_estado = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_estado);
    $stmt->execute();
    $result = $stmt->get_result();

    $municipalities = [];
    while ($row = $result->fetch_assoc()) {
        $municipalities[] = $row;
    }

    echo json_encode($municipalities);
}
?>
