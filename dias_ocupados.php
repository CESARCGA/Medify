<?php
include 'db.php';

if (!isset($_GET['id_profesional'])) {
    die("Error: ID del profesional no especificado.");
}

$id_profesional = intval($_GET['id_profesional']);

// Suponiendo que las horas disponibles en un día son siempre 8 (de ejemplo).
$total_horas = 8;

// Consulta para contar citas por día
$sql = "
    SELECT fecha_cita, COUNT(*) AS horas_ocupadas
    FROM cita
    WHERE id_profesionista = ?
    GROUP BY fecha_cita
    HAVING horas_ocupadas >= ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_profesional, $total_horas);
$stmt->execute();
$result = $stmt->get_result();

$dias_ocupados = [];
while ($row = $result->fetch_assoc()) {
    $dias_ocupados[] = $row['fecha_cita'];
}

$stmt->close();
echo json_encode($dias_ocupados);
?>
