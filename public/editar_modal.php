<?php
$mysqli = new mysqli("localhost", "root", "", "sistemaIshume");
if ($mysqli->connect_error) die("Error de conexión");

$id = intval($_GET['id']);
$res = $mysqli->query("SELECT * FROM proyectos WHERE id = $id");
$proyecto = $res->fetch_assoc();

$estados = ["Planificación", "Producción", "Postproducción", "Finalizado"];
?>

<form id="formEditarProyecto">
    <input type="hidden" name="id" value="<?= $proyecto['id'] ?>">
    <div class="row">
        <div class="col-md-6 mb-3"><input type="text" name="cliente" class="form-control" value="<?= htmlspecialchars($proyecto['cliente']) ?>" required></div>
        <div class="col-md-6 mb-3">
            <select name="tipo_evento" class="form-select" required>
                <?php
                $tipos = ["Boda", "Bautizo", "XV Años", "Cumpleaños", "Baby Shower", "Filmación Escolar", "Sesión Fotográfica Escolar", "Otro"];
                foreach ($tipos as $tipo):
                ?>
                    <option value="<?= $tipo ?>" <?= $tipo === $proyecto['tipo_evento'] ? 'selected' : '' ?>><?= $tipo ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3"><input type="date" name="fecha_inicio" class="form-control" value="<?= $proyecto['fecha_inicio'] ?>" required></div>
        <div class="col-md-6 mb-3"><input type="date" name="fecha_fin" class="form-control" value="<?= $proyecto['fecha_fin'] ?>" required></div>
        <div class="col-md-6 mb-3">
            <select name="estado" class="form-select">
                <?php foreach ($estados as $estado): ?>
                    <option value="<?= $estado ?>" <?= $estado === $proyecto['estado'] ? 'selected' : '' ?>><?= $estado ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-12 mb-3"><textarea name="descripcion" class="form-control"><?= htmlspecialchars($proyecto['descripcion']) ?></textarea></div>
    </div>
    <button type="submit" class="btn btn-success">Guardar Cambios</button>
</form>

<script>
document.getElementById('formEditarProyecto').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('actualizar_ajax.php', {
        method: 'POST',
        body: formData
    }).then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Proyecto actualizado correctamente");
            location.reload();
        } else {
            alert("Error al actualizar");
        }
    });
});
</script>
