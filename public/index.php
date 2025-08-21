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
</body>
<script src="../app/js/eventos.js"></script>
</html>