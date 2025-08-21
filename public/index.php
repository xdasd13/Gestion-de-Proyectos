<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "sistemaIshume";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

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
                // 🔁 Redirigir tras éxito
                header("Location: index.php?success=1");
                exit;
            } else {
                $mensaje = "Error: " . $stmt->error;
                $tipo_alerta = "danger";
            }
        }
        $stmt->close();
    }
}


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
        .kanban-card { background: white; border-radius: 6px; padding: 10px; margin-bottom: 10px; cursor: grab; position: relative; }
        .iconos-accion { position: absolute; top: 5px; right: 10px; }
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
                    <div class="col-md-6 mb-3">
                        <select name="tipo_evento" class="form-select" required>
                            <option value="" disabled selected>Selecciona un tipo de evento</option>
                            <option value="Boda">Boda</option>
                            <option value="Bautizo">Bautizo</option>
                            <option value="XV Años">XV Años</option>
                            <option value="Cumpleaños">Cumpleaños</option>
                            <option value="Baby Shower">Baby Shower</option>
                            <option value="Filmación Escolar">Filmación Escolar</option>
                            <option value="Sesión Fotográfica Escolar">Sesión Fotográfica Escolar</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
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
                        <div class="iconos-accion">
                            <a href="editar_modal.php" class="text-primary btn-editar me-2" data-id="<?= $proyecto['id'] ?>" title="Editar">✏️</a>
                            <a href="eliminar.php?id=<?= $proyecto['id'] ?>" class="text-danger" onclick="return confirm('¿Eliminar este proyecto?')">🗑️</a>
                        </div>
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

<!-- Modal -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header bg-warning text-white">
      <h5 class="modal-title" id="modalEditarLabel">Editar Proyecto</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" id="formEditarContenido">
      <div class="text-center"><div class="spinner-border text-warning"></div></div>
    </div>
  </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../app/js/eventos.js"></script>
</body>
</html>
