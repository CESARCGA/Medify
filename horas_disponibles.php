<?php
include 'db.php';

$id_profesional = intval($_GET['id_profesional']);
$fecha = $_GET['fecha'];

$sql = "SELECT hora_cita FROM cita WHERE id_profesionista = ? AND fecha_cita = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $id_profesional, $fecha);
$stmt->execute();
$result = $stmt->get_result();

$horas_ocupadas = [];
while ($row = $result->fetch_assoc()) {
    $horas_ocupadas[] = $row['hora_cita'];
}

$stmt->close();

// Generar lista de horas (por ejemplo, de 08:00 a 18:00)
$horas_disponibles = [];
for ($i = 8; $i < 18; $i++) {
    $hora = sprintf('%02d:00:00', $i);
    $horas_disponibles[] = [
        'hora' => $hora,
        'ocupada' => in_array($hora, $horas_ocupadas),
    ];
}

header('Content-Type: application/json');
echo json_encode($horas_disponibles);


// genera los dias ocupados
/*$total_horas = 15;

// Consulta para contar citas por día
$sql2 = "
    SELECT fecha_cita, COUNT(*) AS horas_ocupadas
    FROM cita
    WHERE id_profesionista = ?
    GROUP BY fecha_cita
    HAVING horas_ocupadas >= ?
";
$stmt = $conn->prepare($sql2);
$stmt->bind_param("ii", $id_profesional, $total_horas);
$stmt->execute();
$result = $stmt->get_result();

$dias_ocupados = [];
while ($row = $result->fetch_assoc()) {
    $dias_ocupados[] = $row['fecha_cita'];
}

$stmt->close();
echo json_encode($dias_ocupados);*/
?>
