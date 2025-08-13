<?php
// seguridad básica de sesión y CSRF
session_start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// Zona horaria (Perú) y charset
date_default_timezone_set('America/Lima');

// Conexión segura
$conexion = new mysqli("localhost", "root", "", "gestion_proyectos");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
$conexion->set_charset('utf8mb4');

// Helper para escapar salidas (XSS)
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Estados válidos
$ESTADOS_VALIDOS = ['Planificación','Producción','Postproducción','Finalizado'];

// ---- Registro con validaciones estrictas ----
$errores = [];
$exito = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_proyecto'])) {

    // Validar CSRF
    $csrf = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'], $csrf)) {
        $errores[] = "Solicitud no válida (CSRF). Recarga la página.";
    }

    // Recoger datos
    $cliente      = trim($_POST['cliente'] ?? '');
    $tipo_evento  = trim($_POST['tipo_evento'] ?? '');
    $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
    $fecha_fin    = trim($_POST['fecha_fin'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');

    // Reglas de validación
    // Longitudes razonables
    if ($cliente === '' || mb_strlen($cliente) < 2 || mb_strlen($cliente) > 120) {
        $errores[] = "El nombre del cliente debe tener entre 2 y 120 caracteres.";
    }
    if ($tipo_evento === '' || mb_strlen($tipo_evento) < 3 || mb_strlen($tipo_evento) > 120) {
        $errores[] = "El tipo de evento debe tener entre 3 y 120 caracteres.";
    }
    if (mb_strlen($descripcion) > 2000) {
        $errores[] = "La descripción no puede exceder 2000 caracteres.";
    }

    // Fechas: formato y lógica
    $hoy = (new DateTime('today'))->format('Y-m-d');
    $fmt = '/^\d{4}-\d{2}-\d{2}$/';

    if (!preg_match($fmt, $fecha_inicio) || !preg_match($fmt, $fecha_fin)) {
        $errores[] = "Las fechas deben tener formato YYYY-MM-DD.";
    } else {
        // No permitir pasado y coherencia inicio/fin
        if ($fecha_inicio < $hoy) {
            $errores[] = "La fecha de inicio no puede ser anterior a hoy ($hoy).";
        }
        if ($fecha_fin < $fecha_inicio) {
            $errores[] = "La fecha de fin no puede ser anterior a la fecha de inicio.";
        }
    }

    // Regla de negocio: un cliente no puede tener 2+ servicios agendados (activos)
    // Activos = estados diferentes de 'Finalizado'
    if (empty($errores)) {
        $sql = "SELECT COUNT(*) AS c FROM proyectos WHERE cliente = ? AND estado <> 'Finalizado'";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('s', $cliente);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (($res['c'] ?? 0) > 0) {
            $errores[] = "El cliente ya tiene un servicio activo. Finaliza el actual antes de agendar otro.";
        }
    }

    // Insertar si todo está OK
    if (empty($errores)) {
        $estadoInicial = 'Planificación';
        $sql = "INSERT INTO proyectos (cliente, tipo_evento, fecha_inicio, fecha_fin, descripcion, estado) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('ssssss', $cliente, $tipo_evento, $fecha_inicio, $fecha_fin, $descripcion, $estadoInicial);

        if ($stmt->execute()) {
            $exito = "Proyecto registrado correctamente.";
        } else {
            $errores[] = "Error al guardar el proyecto: " . e($conexion->error);
        }
        $stmt->close();
    }
}

// ---- Consulta segura por estado ----
function obtenerProyectos($estado, $conexion) {
    $sql = "SELECT id, cliente, tipo_evento, fecha_inicio, fecha_fin, estado 
            FROM proyectos WHERE estado = ? ORDER BY fecha_inicio ASC, id DESC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('s', $estado);
    $stmt->execute();
    return $stmt->get_result();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Gestión de Proyectos - Kanban</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<style>
    body { background:#f5f7fb; }
    .kanban { display:flex; gap:15px; flex-wrap:wrap; }
    .columna { background:#fff; padding:12px; border-radius:8px; width:23%; min-width:260px; min-height:320px; border:1px solid #e9edf3; }
    .tarjeta { background:#d9ecff; padding:10px; margin-bottom:10px; border-radius:6px; border:1px solid #c7e0ff; }
    .tarjeta small { color:#3b5b7a; }
</style>
</head>
<body class="p-4">

<div class="container">
    <h1 class="mb-4">Gestión Visual de Proyectos</h1>

    <?php if ($errores): ?>
        <div class="alert alert-danger">
            <strong>Corrige lo siguiente:</strong><br>
            <?php foreach ($errores as $e) echo "• " . e($e) . "<br>"; ?>
        </div>
    <?php endif; ?>

    <?php if ($exito): ?>
        <div class="alert alert-success"><?= e($exito) ?></div>
    <?php endif; ?>

    <!-- Formulario para registrar proyecto -->
    <form method="POST" class="mb-4 p-3 bg-white border rounded">
        <h4>Registrar Proyecto</h4>
        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
        <div class="row mb-2">
            <div class="col">
                <input type="text" name="cliente" class="form-control" placeholder="Cliente" maxlength="120" required>
            </div>
            <div class="col">
                <input type="text" name="tipo_evento" class="form-control" placeholder="Tipo de evento" maxlength="120" required>
            </div>
        </div>
        <div class="row mb-2">
            <?php $hoyInput = (new DateTime('today'))->format('Y-m-d'); ?>
            <div class="col">
                <input type="date" name="fecha_inicio" class="form-control" required min="<?= e($hoyInput) ?>">
            </div>
            <div class="col">
                <input type="date" name="fecha_fin" class="form-control" required min="<?= e($hoyInput) ?>">
            </div>
        </div>
        <textarea name="descripcion" class="form-control mb-2" placeholder="Descripción (opcional)" maxlength="2000"></textarea>
        <button name="crear_proyecto" class="btn btn-primary">Guardar Proyecto</button>
    </form>

    <!-- Tablero Kanban -->
    <div class="kanban">
        <?php
        $estados = ['Planificación','Producción','Postproducción','Finalizado'];
        foreach ($estados as $estado):
        ?>
        <div class="columna">
            <h5 class="text-center"><?= e($estado) ?></h5>
            <?php $proyectos = obtenerProyectos($estado, $conexion); ?>
            <?php while ($p = $proyectos->fetch_assoc()): ?>
                <div class="tarjeta">
                    <strong><?= e($p['cliente']) ?></strong><br>
                    <small><?= e($p['tipo_evento']) ?></small><br>
                    <small><?= e($p['fecha_inicio']) ?> → <?= e($p['fecha_fin']) ?></small>
                </div>
            <?php endwhile; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>
