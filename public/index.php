<?php
// ================== Conexión a la BD ===================
$host = "localhost";
$user = "root";
$pass = "";
$db = "gestionProyectos";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Error: " . $conn->connect_error);
}

// ================== Funciones de Validación ===================
function validarTextoSoloLetras($texto) {
    // Solo permite letras (incluyendo acentos), espacios y apostrofes
    return preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s']+$/u", trim($texto));
}

function validarFecha($fecha) {
    $fechaActual = date('Y-m-d');
    $fechaMaxima = date('Y-12-31'); // Hasta fin de año actual
    
    return ($fecha >= $fechaActual && $fecha <= $fechaMaxima);
}

// ================== Procesar Registro ===================
$errores = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["nombreCliente"])) {
    $nombreCliente = trim($_POST["nombreCliente"] ?? "");
    $tipoEvento = $_POST["tipoEvento"] ?? "";
    $responsable = trim($_POST["responsable"] ?? "");
    $fechaInicio = $_POST["fechaInicio"] ?? "";
    $fechaFin = $_POST["fechaFin"] ?? "";
    $estado = $_POST["estado"] ?? "";
    $descripcion = trim($_POST["descripcion"] ?? "");

    // Validaciones
    if (empty($nombreCliente)) {
        $errores[] = "El nombre del cliente es obligatorio";
    } elseif (!validarTextoSoloLetras($nombreCliente)) {
        $errores[] = "El nombre del cliente solo debe contener letras y espacios";
    }

    if (empty($responsable)) {
        $errores[] = "El responsable es obligatorio";
    } elseif (!validarTextoSoloLetras($responsable)) {
        $errores[] = "El nombre del responsable solo debe contener letras y espacios";
    }

    if (empty($fechaInicio)) {
        $errores[] = "La fecha de inicio es obligatoria";
    } elseif (!validarFecha($fechaInicio)) {
        $errores[] = "La fecha de inicio debe estar entre hoy y el 31 de diciembre de " . date('Y');
    }

    if (empty($fechaFin)) {
        $errores[] = "La fecha de fin es obligatoria";
    } elseif (!empty($fechaInicio) && $fechaFin < $fechaInicio) {
        $errores[] = "La fecha de fin debe ser posterior a la fecha de inicio";
    }

    if (empty($tipoEvento)) {
        $errores[] = "El tipo de evento es obligatorio";
    }

    if (empty($estado)) {
        $errores[] = "El estado es obligatorio";
    }

    // Si no hay errores, procesar registro
    if (empty($errores)) {
        $stmt = $conn->prepare("INSERT INTO clientes (nomContacto, tipoCliente, telefono, ciudad, direccion) VALUES (?, 'Individual', '000000000', 'CiudadX', 'Sin Dirección')");
        $stmt->bind_param("s", $nombreCliente);
        $stmt->execute();
        $clienteId = $stmt->insert_id;

        $stmt = $conn->prepare("INSERT INTO proyectos (clienteId, tipoEvento, descripcion, fechaInicio, fechaFin, estado) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $clienteId, $tipoEvento, $descripcion, $fechaInicio, $fechaFin, $estado);
        $stmt->execute();
        $proyectoId = $stmt->insert_id;

        $stmtTrab = $conn->prepare("INSERT INTO trabajadores (nombre, telefono) VALUES (?, '000000000')");
        $stmtTrab->bind_param("s", $responsable);
        $stmtTrab->execute();
        $trabajadorId = $stmtTrab->insert_id;

        $stmtAsignar = $conn->prepare("INSERT INTO proyectosTrabajadores (proyectoId, trabajadorId, rolEnProyecto) VALUES (?, ?, 'Responsable')");
        $stmtAsignar->bind_param("ii", $proyectoId, $trabajadorId);
        $stmtAsignar->execute();

        echo "<script>alert('✅ Proyecto registrado correctamente'); window.location='index.php';</script>";
    }
}

$estados = ["Planificación", "Producción", "Postproducción", "Finalizado"];
$fechaActual = date('Y-m-d');
$fechaMaxima = date('Y-12-31');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proyectos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: system-ui, -apple-system, sans-serif;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Formulario - Bordes negros en campos de texto */
        .form-section {
            background: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(255, 255, 255, 1);
            border: 2px solid #000000ea;
        }

        .form-title {
            color: #FF8C00;
            margin-bottom: 25px;
            font-weight: 600;
        }

        .form-control, .form-select {
            border: 2px solid #000000ff !important;
            border-radius: 6px;
            padding: 10px 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: #fd950dff !important;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .form-control.is-invalid {
            border-color: #dc3545 !important;
        }

        .btn-primary {
            background-color: #FF8C00;
            border-color: #FF8C00;
            padding: 10px 30px;
            border-radius: 6px;
            font-weight: 600;
            border: 2px solid #FF8C00;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        /* Kanban */
        .kanban-section {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(243, 243, 243, 1);
            border: 2px solid #FF8C00;
        }

        .kanban-title {
            color: #FF8C00;
            margin-bottom: 25px;
            font-weight: 600;
        }

        .kanban-board {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .kanban-column {
            background: #ffffffff;
            border-radius: 6px;
            padding: 15px;
            min-height: 400px;
            border: 2px solid #b6b2b2ff;
        }

        .column-header {
            background: #FF8C00;
            color: white;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            font-weight: 500;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .kanban-card {
            background: white;
            border: 2px solid #000000ff;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: move;
            transition: box-shadow 0.2s;
        }

        .kanban-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .card-title {
            font-weight: 600;
            color: #000000ff;
            margin-bottom: 8px;
        }

        .card-client {
            color: #da6e44ff;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .card-dates {
            color: #cf821dff;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .card-description {
            color: #d38328ff;
            font-size: 13px;
            font-style: italic;
        }

        .drag-over {
            border: 2px dashed #0d6efd;
            background-color: #e7f1ff;
        }

        @media (max-width: 768px) {
            .kanban-board {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <!-- Formulario -->
        <div class="form-section">
            <h2 class="form-title">
                <i class="fas fa-plus-circle"></i> Nuevo Proyecto
            </h2>
            
            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger">
                    <strong>⚠️ Errores encontrados:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errores as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="proyectoForm">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Nombre Cliente *</label>
                        <input type="text" 
                               name="nombreCliente" 
                               id="nombreCliente"
                               class="form-control" 
                               value="<?= htmlspecialchars($_POST['nombreCliente'] ?? '') ?>"
                               pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s']+"
                               title="Solo se permiten letras y espacios"
                               required>
                        <div class="invalid-feedback">
                            Solo se permiten letras, espacios y apostrofes
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tipo Evento *</label>
                        <select name="tipoEvento" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            <?php 
                            $tiposEvento = ["Boda", "XV Años", "Bautizo", "Baby Shower", "Cumpleaños", "Sesión Fotográfica Escolar", "Filmación Escolar", "Otro"];
                            foreach($tiposEvento as $tipo): 
                            ?>
                                <option value="<?= $tipo ?>" <?= (($_POST['tipoEvento'] ?? '') == $tipo) ? 'selected' : '' ?>>
                                    <?= $tipo ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Responsable *</label>
                        <input type="text" 
                               name="responsable" 
                               id="responsable"
                               class="form-control" 
                               value="<?= htmlspecialchars($_POST['responsable'] ?? '') ?>"
                               pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s']+"
                               title="Solo se permiten letras y espacios"
                               required>
                        <div class="invalid-feedback">
                            Solo se permiten letras, espacios y apostrofes
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Fecha Inicio *</label>
                        <input type="date" 
                               name="fechaInicio" 
                               id="fechaInicio"
                               class="form-control" 
                               value="<?= $_POST['fechaInicio'] ?? '' ?>"
                               min="<?= $fechaActual ?>" 
                               max="<?= $fechaMaxima ?>"
                               required>
                        <small class="text-muted">Desde hoy hasta 31/12/<?= date('Y') ?></small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Fecha Fin *</label>
                        <input type="date" 
                               name="fechaFin" 
                               id="fechaFin"
                               class="form-control" 
                               value="<?= $_POST['fechaFin'] ?? '' ?>"
                               min="<?= $fechaActual ?>" 
                               max="<?= $fechaMaxima ?>"
                               required>
                        <small class="text-muted">Se calculará automáticamente (+2 semanas)</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Estado *</label>
                        <select name="estado" class="form-select" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach($estados as $estado): ?>
                                <option value="<?= $estado ?>" <?= (($_POST['estado'] ?? '') == $estado) ? 'selected' : '' ?>>
                                    <?= $estado ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" 
                                  class="form-control" 
                                  rows="3" 
                                  maxlength="500"
                                  placeholder="Descripción opcional (máx. 500 caracteres)"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
                        <small class="text-muted"><span id="charCount">0</span>/500 caracteres</small>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Registrar Proyecto
                    </button>
                </div>
            </form>
        </div>

        <!-- Tablero Kanban -->
        <div class="kanban-section">
            <h2 class="kanban-title">
                <i class="fas fa-columns"></i> Tablero de Proyectos
            </h2>
            
            <div class="kanban-board">
                <?php foreach ($estados as $estado): ?>
                    <div class="kanban-column" data-estado="<?= $estado ?>">
                        <div class="column-header">
                            <?= $estado ?>
                        </div>
                        
                        <?php
                        $stmt = $conn->prepare("SELECT p.proyectoId, c.nomContacto, p.tipoEvento, p.descripcion, p.fechaInicio, p.fechaFin 
                                                FROM proyectos p 
                                                JOIN clientes c ON p.clienteId = c.idCliente 
                                                WHERE p.estado=? ORDER BY p.fechaInicio ASC");
                        $stmt->bind_param("s", $estado);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()):
                        ?>
                            <div class="kanban-card" draggable="true" data-id="<?= $row['proyectoId'] ?>">
                                <div class="card-title">
                                    <?= htmlspecialchars($row['tipoEvento']) ?>
                                </div>
                                <div class="card-client">
                                    <i class="fas fa-user"></i> <?= htmlspecialchars($row['nomContacto']) ?>
                                </div>
                                <div class="card-dates">
                                    <i class="fas fa-calendar"></i> 
                                    <?= date('d/m/Y', strtotime($row['fechaInicio'])) ?> - <?= date('d/m/Y', strtotime($row['fechaFin'])) ?>
                                </div>
                                <?php if (!empty($row['descripcion'])): ?>
                                    <div class="card-description">
                                        <?= htmlspecialchars(substr($row['descripcion'], 0, 80)) ?><?= strlen($row['descripcion']) > 80 ? '...' : '' ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        // ================== Validaciones del Formulario ===================
        
        // Validación solo letras en tiempo real
        function validarSoloLetras(input) {
            input.addEventListener('input', function(e) {
                // Remover caracteres que no sean letras, espacios o apostrofes
                this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s']/g, '');
                
                // Validar patrón
                const esValido = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s']*$/.test(this.value);
                
                if (!esValido && this.value !== '') {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });
            
            // Prevenir pegar contenido inválido
            input.addEventListener('paste', function(e) {
                setTimeout(() => {
                    this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s']/g, '');
                    this.dispatchEvent(new Event('input'));
                }, 10);
            });
        }
        
        // Aplicar validación a campos de nombre
        validarSoloLetras(document.getElementById('nombreCliente'));
        validarSoloLetras(document.getElementById('responsable'));
        
        // Contador de caracteres para descripción
        const descripcionTextarea = document.querySelector('textarea[name="descripcion"]');
        const charCount = document.getElementById('charCount');
        
        if (descripcionTextarea && charCount) {
            descripcionTextarea.addEventListener('input', function() {
                charCount.textContent = this.value.length;
                if (this.value.length > 500) {
                    this.value = this.value.substring(0, 500);
                    charCount.textContent = 500;
                }
            });
            
            // Actualizar contador inicial
            charCount.textContent = descripcionTextarea.value.length;
        }
        
        // Cálculo automático de fecha fin
        const fechaInicio = document.getElementById('fechaInicio');
        const fechaFin = document.getElementById('fechaFin');
        
        fechaInicio.addEventListener('change', function() {
            if (this.value) {
                const fechaInicioDate = new Date(this.value);
                // Agregar 14 días (2 semanas)
                fechaInicioDate.setDate(fechaInicioDate.getDate() + 14);
                
                // Formatear fecha para input type="date" (YYYY-MM-DD)
                const fechaFinCalculada = fechaInicioDate.toISOString().split('T')[0];
                
                // Verificar que no exceda el máximo permitido
                const fechaMaxima = '<?= $fechaMaxima ?>';
                if (fechaFinCalculada <= fechaMaxima) {
                    fechaFin.value = fechaFinCalculada;
                } else {
                    fechaFin.value = fechaMaxima;
                    alert('⚠️ La fecha fin se ajustó al máximo permitido (31/12/<?= date('Y') ?>)');
                }
                
                // Actualizar el min del campo fecha fin
                fechaFin.min = this.value;
            }
        });
        
        // Validación adicional en el envío del formulario
        document.getElementById('proyectoForm').addEventListener('submit', function(e) {
            let hayErrores = false;
            
            // Validar campos de solo texto
            const camposTexto = [
                { campo: document.getElementById('nombreCliente'), nombre: 'Nombre Cliente' },
                { campo: document.getElementById('responsable'), nombre: 'Responsable' }
            ];
            
            camposTexto.forEach(item => {
                const valor = item.campo.value.trim();
                if (valor === '') {
                    item.campo.classList.add('is-invalid');
                    hayErrores = true;
                } else if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s']+$/.test(valor)) {
                    item.campo.classList.add('is-invalid');
                    alert(`⚠️ ${item.nombre} solo debe contener letras y espacios`);
                    hayErrores = true;
                } else {
                    item.campo.classList.remove('is-invalid');
                }
            });
            
            // Validar fechas
            const fechaInicioVal = fechaInicio.value;
            const fechaFinVal = fechaFin.value;
            const hoy = new Date().toISOString().split('T')[0];
            const maxFecha = '<?= $fechaMaxima ?>';
            
            if (fechaInicioVal < hoy || fechaInicioVal > maxFecha) {
                alert('⚠️ La fecha de inicio debe estar entre hoy y el 31/12/<?= date('Y') ?>');
                hayErrores = true;
            }
            
            if (fechaFinVal < fechaInicioVal) {
                alert('⚠️ La fecha de fin debe ser posterior a la fecha de inicio');
                hayErrores = true;
            }
            
            if (hayErrores) {
                e.preventDefault();
            }
        });
        
        // ================== Tablero Kanban ===================
        
        const tarjetas = document.querySelectorAll(".kanban-card");
        const columnas = document.querySelectorAll(".kanban-column");

        tarjetas.forEach(tarjeta => {
            tarjeta.addEventListener("dragstart", e => {
                e.dataTransfer.setData("id", tarjeta.dataset.id);
            });
        });

        columnas.forEach(columna => {
            columna.addEventListener("dragover", e => {
                e.preventDefault();
                columna.classList.add("drag-over");
            });

            columna.addEventListener("dragleave", () => {
                columna.classList.remove("drag-over");
            });

            columna.addEventListener("drop", e => {
                e.preventDefault();
                columna.classList.remove("drag-over");

                const idProyecto = e.dataTransfer.getData("id");
                const tarjeta = document.querySelector(`.kanban-card[data-id='${idProyecto}']`);
                columna.appendChild(tarjeta);

                const nuevoEstado = columna.dataset.estado;

                fetch("update_estado.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `id=${idProyecto}&estado=${nuevoEstado}`
                })
                .then(res => res.text())
                .then(data => console.log(data));
            });
        });
    </script>
</body>
</html>