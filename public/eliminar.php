<?php
$conexion = new mysqli("localhost", "root", "", "sistemaIshume");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Validar ID
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);

    // Preparar y ejecutar la eliminación
    $stmt = $conexion->prepare("DELETE FROM proyectos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $stmt->close();
}

// Redirigir al tablero Kanban
header("Location: index.php");
exit;
