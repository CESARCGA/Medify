<?php
// Incluye la conexión a la base de datos
include 'db.php';

if (isset($_GET['q'])) {
    $searchQuery = $_GET['q'];

    // Consulta SQL para buscar clientes por nombre
    try {
        $query = "SELECT id_cuenta, nombre, foto_perfil FROM cuentas WHERE tipo = 'cliente' AND nombre LIKE ?";
        $stmt = $conn->prepare($query);
        $searchTerm = '%' . $searchQuery . '%';
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Obtener los resultados en formato JSON
        $clients = [];
        while ($row = $result->fetch_assoc()) {
            $clients[] = $row;
        }
        
        echo json_encode($clients);
    } catch (Exception $e) {
        echo json_encode(["error" => "Error al obtener los clientes: " . $e->getMessage()]);
    }
}
?>
