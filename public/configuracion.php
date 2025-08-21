<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "sistemaIshume";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// =======================
// Procesar formulario de registro
// =======================
$mensaje = "";
$tipo_alerta = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registro'])) {
    $cliente = trim($_POST['cliente']);
    $tipo_evento = trim($_POST['tipo_evento']);
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $estado = $_POST['estado'];
    $descripcion = trim($_POST['descripcion']);
    $hoy = date("Y-m-d");

    if (empty($cliente) || empty($tipo_evento) || empty($fecha_inicio) || empty($fecha_fin)) {
        $mensaje = "Todos los campos son obligatorios.";
        $tipo_alerta = "danger";
    } elseif ($fecha_inicio < $hoy) {
        $mensaje = "La fecha de inicio no puede ser anterior a hoy.";
        $tipo_alerta = "danger";
    } elseif ($fecha_fin <= $fecha_inicio) {
        $mensaje = "La fecha de fin debe ser posterior a la fecha de inicio.";
        $tipo_alerta = "danger";
    } else {
        // Evitar duplicados
        $stmt = $conn->prepare("SELECT id FROM proyectos WHERE cliente=? AND tipo_evento=? AND fecha_inicio=?");
        $stmt->bind_param("sss", $cliente, $tipo_evento, $fecha_inicio);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $mensaje = "Este cliente ya tiene un proyecto en esa fecha.";
            $tipo_alerta = "warning";
        } else {
            $stmt = $conn->prepare("INSERT INTO proyectos (cliente, tipo_evento, fecha_inicio, fecha_fin, estado, descripcion) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $cliente, $tipo_evento, $fecha_inicio, $fecha_fin, $estado, $descripcion);
            if ($stmt->execute()) {
                $mensaje = "Proyecto registrado exitosamente.";
                $tipo_alerta = "success";
            } else {
                $mensaje = "Error: " . $stmt->error;
                $tipo_alerta = "danger";
            }
        }
        $stmt->close();
    }
}

// =======================
// API interna para actualizar estado vía AJAX
// =======================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cambiar_estado'])) {
    $id = intval($_POST['id']);
    $nuevo_estado = $_POST['nuevo_estado'];
    $stmt = $conn->prepare("UPDATE proyectos SET estado=? WHERE id=?");
    $stmt->bind_param("si", $nuevo_estado, $id);
    $stmt->execute();
    echo json_encode(["success" => true]);
    exit;
}

// =======================
// Obtener proyectos por estado
// =======================
$estados = ["Planificación", "Producción", "Postproducción", "Finalizado"];
$proyectos_por_estado = [];

foreach ($estados as $estado) {
    $stmt = $conn->prepare("SELECT * FROM proyectos WHERE estado=? ORDER BY fecha_inicio ASC");
    $stmt->bind_param("s", $estado);
    $stmt->execute();
    $result = $stmt->get_result();
    $proyectos_por_estado[$estado] = $result->fetch_all(MYSQLI_ASSOC);
}
$conn->close();
?>
