<?php
include "conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST["id"];
    $estado = $_POST["estado"];

    $stmt = $conn->prepare("UPDATE proyectos SET estado=? WHERE proyectoId=?");
    $stmt->bind_param("si", $estado, $id);

    if ($stmt->execute()) {
        echo "✅ Estado actualizado a: " . $estado;
    } else {
        echo "❌ Error al actualizar";
    }
}
?>
