<?php
include 'db.php'; // Conexión a la base de datos

// Suponemos que ya tenemos el ID de cuenta (ya sea de la sesión o URL)
session_start();
$id_cuenta = isset($_SESSION['id_cuenta']) ? $_SESSION['id_cuenta'] : 0; // o $_GET['id_cuenta']

// Verificar que el ID de la cuenta es válido
if ($id_cuenta <= 0) {
    echo "ID de cuenta no válido.";
    exit;
}

// Recuperar la pregunta enviada
$pregunta = isset($_POST['pregunta']) ? strtolower(trim($_POST['pregunta'])) : '';

// Procesar la pregunta y buscar la respuesta en la base de datos
if (empty($pregunta)) {
    echo "Por favor, escribe una pregunta.";
    exit;
}

$response = 'Lo siento, no entendí la pregunta.';

// Buscar en la base de datos basado en el ID de cuenta seleccionado
if (strpos($pregunta, 'diagnóstico') !== false) {
    // Buscar diagnóstico del paciente con el ID seleccionado
    $query = "SELECT diagnostico FROM pacientes WHERE id_cuenta = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id_cuenta);
    $stmt->execute();
    $stmt->bind_result($diagnostico);
    
    if ($stmt->fetch()) {
        $response = "El diagnóstico del paciente con ID $id_cuenta es: $diagnostico";
    } else {
        $response = "No se encontró información para el paciente con ID $id_cuenta.";
    }
    $stmt->close();
} elseif (strpos($pregunta, 'fecha de ingreso') !== false) {
    // Buscar fecha de ingreso del paciente con el ID seleccionado
    $query = "SELECT fecha_ingreso FROM pacientes WHERE id_cuenta = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id_cuenta);
    $stmt->execute();
    $stmt->bind_result($fecha_ingreso);
    
    if ($stmt->fetch()) {
        $response = "La fecha de ingreso del paciente con ID $id_cuenta es: $fecha_ingreso";
    } else {
        $response = "No se encontró información para el paciente con ID $id_cuenta.";
    }
    $stmt->close();
} elseif (strpos($pregunta, 'enfermedad') !== false) {
    // Buscar si el paciente ha tenido alguna enfermedad, ejemplo: "diabetes"
    preg_match('/diabetes/', $pregunta, $matches);
    
    if ($matches) {
        $query = "SELECT historial_medico FROM pacientes WHERE id_cuenta = ? AND historial_medico LIKE '%diabetes%'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $id_cuenta);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $response = "Sí, el paciente ha tenido diabetes.";
        } else {
            $response = "No, el paciente no ha tenido diabetes.";
        }
        $stmt->close();
    }
} elseif (strpos($pregunta, 'tratamiento') !== false) {
    // Buscar tratamiento del paciente
    $query = "SELECT tratamiento FROM pacientes WHERE id_cuenta = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id_cuenta);
    $stmt->execute();
    $stmt->bind_result($tratamiento);
    
    if ($stmt->fetch()) {
        $response = "El tratamiento del paciente con ID $id_cuenta es: $tratamiento";
    } else {
        $response = "No se encontró información para el tratamiento del paciente con ID $id_cuenta.";
    }
    $stmt->close();
} else {
    $response = "Lo siento, no pude entender esa pregunta. Intenta con algo diferente.";
}

echo $response;
?>
