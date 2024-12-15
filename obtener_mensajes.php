<?php
include 'db.php';
session_start();

if (isset($_GET['id_usuario'])) {
    $id_usuario_actual = $_SESSION['user_id'];
    $id_usuario_receptor = $_GET['id_usuario'];

    // Obtener los mensajes entre los dos usuarios
    $queryMensajes = "
        SELECT * FROM mensajes
        WHERE (id_remitente = ? AND id_destinatario = ?)
           OR (id_remitente = ? AND id_destinatario = ?)
        ORDER BY fecha_enviado ASC
    ";

    $stmt = $conn->prepare($queryMensajes);
    $stmt->bind_param("iiii", $id_usuario_actual, $id_usuario_receptor, $id_usuario_receptor, $id_usuario_actual);
    $stmt->execute();
    $resultMensajes = $stmt->get_result();

    $mensajes = [];
    while ($row = $resultMensajes->fetch_assoc()) {
        $mensajes[] = $row;
    }

    // Devolver los mensajes en formato JSON
    echo json_encode($mensajes);
}
?>
