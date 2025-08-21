<?php
$conn = new mysqli("localhost", "root", "", "sistemaIshume");
if ($conn->connect_error) die(json_encode(["success" => false]));

$id = intval($_POST['id']);
$cliente = $_POST['cliente'];
$tipo_evento = $_POST['tipo_evento'];
$fecha_inicio = $_POST['fecha_inicio'];
$fecha_fin = $_POST['fecha_fin'];
$estado = $_POST['estado'];
$descripcion = $_POST['descripcion'];

$stmt = $conn->prepare("UPDATE proyectos SET cliente=?, tipo_evento=?, fecha_inicio=?, fecha_fin=?, estado=?, descripcion=? WHERE id=?");
$stmt->bind_param("ssssssi", $cliente, $tipo_evento, $fecha_inicio, $fecha_fin, $estado, $descripcion, $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false]);
}
