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
        .form-kanban-width {
            max-width: 100vw;
            width: 100%;
        }
        @media (min-width: 992px) {
            .form-kanban-width {
                width: 100%;
                min-width: 0;
            }
        }
    </style>
</head>
<body class="bg-light">
<div class="container mt-4">
    <!-- Formulario -->
    <div class="row justify-content-center">
        <div class="form-kanban-width">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h4 class="mb-0"><i class="bi bi-journal-plus me-2"></i>Registrar Proyecto</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($mensaje)): ?>
                        <div class="alert alert-<?= $tipo_alerta ?> mb-4 text-center"><?= $mensaje ?></div>
                    <?php endif; ?>
                    <form method="POST" onsubmit="return validarFormulario()">
                        <input type="hidden" name="registro" value="1">
                        <div class="row g-3">
                            <!-- Cliente solo texto -->
                            <div class="col-md-6 form-floating">
                                <input type="text" id="cliente" name="cliente" class="form-control" placeholder="Cliente" required pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+" title="Solo letras y espacios" value="<?= isset($_POST['cliente']) ? htmlspecialchars($_POST['cliente']) : '' ?>">
                                <label for="cliente">Cliente</label>
                            </div>
                            <!-- Tipo de Evento -->
                            <div class="col-md-6 form-floating">
                                <select id="tipo_evento" name="tipo_evento" class="form-select" required onchange="mostrarCampoOtro(this)">
                                    <option value="" disabled <?= !isset($_POST['tipo_evento']) ? 'selected' : '' ?>>Selecciona un tipo de evento</option>
                                    <option value="Boda" <?= (isset($_POST['tipo_evento']) && $_POST['tipo_evento'] == 'Boda') ? 'selected' : '' ?>>Boda</option>
                                    <option value="Bautizo" <?= (isset($_POST['tipo_evento']) && $_POST['tipo_evento'] == 'Bautizo') ? 'selected' : '' ?>>Bautizo</option>
                                    <option value="XV Años" <?= (isset($_POST['tipo_evento']) && $_POST['tipo_evento'] == 'XV Años') ? 'selected' : '' ?>>XV Años</option>
                                    <option value="Cumpleaños" <?= (isset($_POST['tipo_evento']) && $_POST['tipo_evento'] == 'Cumpleaños') ? 'selected' : '' ?>>Cumpleaños</option>
                                    <option value="Baby Shower" <?= (isset($_POST['tipo_evento']) && $_POST['tipo_evento'] == 'Baby Shower') ? 'selected' : '' ?>>Baby Shower</option>
                                    <option value="Filmación Escolar" <?= (isset($_POST['tipo_evento']) && $_POST['tipo_evento'] == 'Filmación Escolar') ? 'selected' : '' ?>>Filmación Escolar</option>
                                    <option value="Sesión Fotográfica Escolar" <?= (isset($_POST['tipo_evento']) && $_POST['tipo_evento'] == 'Sesión Fotográfica Escolar') ? 'selected' : '' ?>>Sesión Fotográfica Escolar</option>
                                    <option value="Otro" <?= (isset($_POST['tipo_evento']) && $_POST['tipo_evento'] == 'Otro') ? 'selected' : '' ?>>Otro</option>
                                </select>
                                <label for="tipo_evento">Tipo de Evento</label>
                            </div>
                            <!-- Campo "Otro" -->
                            <div class="col-md-12 form-floating" id="campo_otro_evento" style="display: none;">
                                <input type="text" id="otro_evento" class="form-control" placeholder="Especifica el tipo de evento" value="<?= isset($_POST['otro_evento']) ? htmlspecialchars($_POST['otro_evento']) : '' ?>">
                                <label for="otro_evento">Especifica el tipo de evento</label>
                            </div>
                            <!-- Fechas -->
                            <div class="col-md-6 form-floating">
                                <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" placeholder="Fecha de Inicio" required value="<?= isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '' ?>">
                                <label for="fecha_inicio">Fecha de Inicio</label>
                            </div>
                            <div class="col-md-6 form-floating">
                                <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" placeholder="Fecha de Fin" required value="<?= isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : '' ?>">
                                <label for="fecha_fin">Fecha de Fin</label>
                            </div>
                            <!-- Estado -->
                            <div class="col-md-6 form-floating">
                                <select name="estado" id="estado" class="form-select" required placeholder="Estado">
                                    <?php foreach ($estados as $estado): ?>
                                        <option value="<?= $estado ?>" <?= (isset($_POST['estado']) && $_POST['estado'] == $estado) ? 'selected' : '' ?>><?= $estado ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="estado">Estado</label>
                            </div>
                            <!-- Descripción -->
                            <div class="col-md-12 form-floating">
                                <textarea name="descripcion" id="descripcion" class="form-control" placeholder="Descripción del proyecto" style="height: 100px"><?= isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : '' ?></textarea>
                                <label for="descripcion">Descripción</label>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4 gap-2">
                            <button type="button" class="btn btn-secondary px-4" onclick="limpiarFormulario()"><i class="bi bi-eraser me-2"></i>Limpiar registro</button>
                            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-plus-circle me-2"></i>Registrar</button>
                        </div>
                    </form>
                </div>
            </div>
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
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<script src="../app/js/eventos.js"></script>
<script>
function limpiarFormulario() {
    const form = document.querySelector('form[method="POST"]');
    form.reset();
    // Limpiar selects con floating label
    document.getElementById('tipo_evento').selectedIndex = 0;
    document.getElementById('estado').selectedIndex = 0;
    // Limpiar campo otro si está visible
    document.getElementById('campo_otro_evento').style.display = 'none';
    // Limpiar min y value de fecha fin
    document.getElementById('fecha_fin').min = '';
    document.getElementById('fecha_fin').value = '';
    // Limpiar los valores de los campos que se mantienen por PHP
    document.getElementById('cliente').value = '';
    document.getElementById('fecha_inicio').value = '';
    document.getElementById('descripcion').value = '';
    // Ocultar mensaje de alerta si existe
    const alerta = document.querySelector('.alert');
    if (alerta) alerta.remove();
}
</script>
<script>
// Validación de fechas en el formulario
document.addEventListener('DOMContentLoaded', function() {
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');

    // Limitar fecha de inicio hasta el 31 de diciembre del año actual
    const hoy = new Date();
    const finDiciembre = new Date(hoy.getFullYear(), 11, 31); // Mes 11 = diciembre
    fechaInicio.max = finDiciembre.toISOString().split('T')[0];

    // Cuando cambia la fecha de inicio, ajustar el mínimo de fecha de fin y autocompletar el valor
    fechaInicio.addEventListener('change', function() {
        if (fechaInicio.value) {
            const inicio = new Date(fechaInicio.value);
            // Sumar 14 días (2 semanas)
            const minFin = new Date(inicio.getTime() + 14 * 24 * 60 * 60 * 1000);
            const minFinStr = minFin.toISOString().split('T')[0];
            fechaFin.min = minFinStr;
            fechaFin.value = minFinStr;
        } else {
            fechaFin.min = '';
            fechaFin.value = '';
        }
    });

    // Inicializar el mínimo de fecha fin si ya hay valor en fecha inicio
    if (fechaInicio.value) {
        const inicio = new Date(fechaInicio.value);
        const minFin = new Date(inicio.getTime() + 14 * 24 * 60 * 60 * 1000);
        const minFinStr = minFin.toISOString().split('T')[0];
        fechaFin.min = minFinStr;
        if (!fechaFin.value || fechaFin.value < minFinStr) {
            fechaFin.value = minFinStr;
        }
    }
});
</script>
</body>
</html>
