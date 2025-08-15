<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "gestion_proyectos";

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
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Kanban Interactivo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .kanban-board { display: flex; gap: 15px; overflow-x: auto; padding-bottom: 10px; }
        .kanban-column { background: #f8f9fa; border-radius: 8px; flex: 1; min-width: 250px; padding: 10px; }
        .kanban-card { background: white; border-radius: 6px; padding: 10px; margin-bottom: 10px; cursor: grab; }
        .kanban-card.dragging { opacity: 0.5; }
        .kanban-column.drag-over { background: #e3f2fd; }
    </style>
</head>
<body class="bg-light">
<div class="container mt-4">

    <!-- Formulario -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white text-center">Registrar Proyecto</div>
        <div class="card-body">
            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?= $tipo_alerta ?>"><?= $mensaje ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="registro" value="1">
                <div class="row">
                    <div class="col-md-6 mb-3"><input type="text" name="cliente" placeholder="Cliente" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><input type="text" name="tipo_evento" placeholder="Tipo de evento" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><input type="date" name="fecha_inicio" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><input type="date" name="fecha_fin" class="form-control" required></div>
                    <div class="col-md-6 mb-3">
                        <select name="estado" class="form-select">
                            <?php foreach ($estados as $estado): ?>
                                <option value="<?= $estado ?>"><?= $estado ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-12 mb-3"><textarea name="descripcion" placeholder="Descripción" class="form-control"></textarea></div>
                </div>
                <button type="submit" class="btn btn-primary">Registrar</button>
            </form>
        </div>
    </div>

    <!-- Tablero Kanban -->
    <h4 class="mb-3 text-center">Tablero Kanban</h4>
    <div class="kanban-board">
        <?php foreach ($estados as $estado): ?>
            <div class="kanban-column" data-estado="<?= $estado ?>">
                <h5 class="text-center mb-3"><?= $estado ?></h5>
                <?php foreach ($proyectos_por_estado[$estado] as $proyecto): ?>
                    <div class="kanban-card" draggable="true" data-id="<?= $proyecto['id'] ?>">
                        <h6><?= htmlspecialchars($proyecto['cliente']) ?></h6>
                        <small><?= htmlspecialchars($proyecto['tipo_evento']) ?></small>
                        <p class="mb-1"><strong>Inicio:</strong> <?= $proyecto['fecha_inicio'] ?></p>
                        <p class="mb-1"><strong>Fin:</strong> <?= $proyecto['fecha_fin'] ?></p>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($proyecto['descripcion'])) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.querySelectorAll('.kanban-card').forEach(card => {
    card.addEventListener('dragstart', e => {
        card.classList.add('dragging');
        e.dataTransfer.setData('text/plain', card.dataset.id);
    });
    card.addEventListener('dragend', () => card.classList.remove('dragging'));
});

document.querySelectorAll('.kanban-column').forEach(column => {
    column.addEventListener('dragover', e => {
        e.preventDefault();
        column.classList.add('drag-over');
    });
    column.addEventListener('dragleave', () => column.classList.remove('drag-over'));
    column.addEventListener('drop', e => {
        e.preventDefault();
        column.classList.remove('drag-over');
        const idProyecto = e.dataTransfer.getData('text/plain');
        const card = document.querySelector(`.kanban-card[data-id="${idProyecto}"]`);
        column.appendChild(card);

        // Llamada AJAX para actualizar en la BD
        fetch("", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: `cambiar_estado=1&id=${idProyecto}&nuevo_estado=${encodeURIComponent(column.dataset.estado)}`
        });
    });
});
</script>
</body>
</html>
