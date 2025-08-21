
CREATE DATABASE sistemaIshume;
USE sistemaIshume;

CREATE TABLE Usuarios(
	id_usuario INT PRIMARY KEY AUTO_INCREMENT,
    nombre_usuario VARCHAR(50) UNIQUE NOT NULL,
    contrasena_hash VARCHAR(200) NOT NULL,
    rol ENUM('Administrador','Asistente') NOT NULL,
    nombre_completo VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    telefono VARCHAR(20),
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultima_conexion DATETIME
)ENGINE = INNODB;

CREATE TABLE Clientes(
	id_cliente INT PRIMARY KEY AUTO_INCREMENT,
    nombre_contacto VARCHAR(100) NOT NULL,
    tipo_cliente ENUM('Individual','Institucional') NOT NULL,
    nombre_empresa_institucion VARCHAR(200) NULL,
    ruc_dni VARCHAR(20) UNIQUE,
    telefono VARCHAR(20),
    email VARCHAR(100) NULL,
    direccion VARCHAR(200),
    ciudad VARCHAR(100),
    observaciones TEXT,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
)ENGINE = INNODB;

CREATE TABLE ServiciosCatalogo (
    id_servicio_catalogo INT AUTO_INCREMENT PRIMARY KEY,
    nombre_servicio VARCHAR(100) UNIQUE NOT NULL,
    descripcion TEXT,
    tipo_categoria ENUM('Evento Social', 'Proyecto Escolar') NOT NULL,
    precio_base DECIMAL(10,2),
    activo BOOLEAN DEFAULT TRUE
)ENGINE = INNODB;

CREATE TABLE Cotizaciones (
    id_cotizacion INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_validez DATE,
    estado ENUM('Pendiente', 'Aceptada', 'Rechazada', 'Cancelada') NOT NULL,
    total_cotizacion DECIMAL(10,2),
    observaciones TEXT,
    creada_por_usuario INT NOT NULL,
    FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente),
    FOREIGN KEY (creada_por_usuario) REFERENCES Usuarios(id_usuario)
)ENGINE = INNODB;

CREATE TABLE DetalleCotizacion (
    id_detalle_cotizacion INT AUTO_INCREMENT PRIMARY KEY,
    id_cotizacion INT NOT NULL,
    id_servicio_catalogo INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario_acordado DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2),
    FOREIGN KEY (id_cotizacion) REFERENCES Cotizaciones(id_cotizacion),
    FOREIGN KEY (id_servicio_catalogo) REFERENCES ServiciosCatalogo(id_servicio_catalogo)
)ENGINE = INNODB;

CREATE TABLE Contratos (
    id_contrato INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    id_cotizacion INT NULL,
    numero_contrato VARCHAR(50) UNIQUE NOT NULL,
    fecha_firma DATE NOT NULL,
    fecha_inicio_servicio DATE NOT NULL,
    fecha_fin_servicio DATE,
    monto_total_contrato DECIMAL(10,2) NOT NULL,
    estado_contrato ENUM('Activo', 'Finalizado', 'Anulado') NOT NULL,
    condiciones_especiales TEXT,
    creado_por_usuario INT NOT NULL,
    FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente),
    FOREIGN KEY (id_cotizacion) REFERENCES Cotizaciones(id_cotizacion),
    FOREIGN KEY (creado_por_usuario) REFERENCES Usuarios(id_usuario)
)ENGINE = INNODB;

CREATE TABLE Pagos (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_contrato INT NOT NULL,
    monto_pago DECIMAL(10,2) NOT NULL,
    fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP,
    metodo_pago ENUM('Efectivo', 'Transferencia Bancaria', 'Yape', 'Plin', 'Tarjeta') NOT NULL,
    observaciones TEXT,
    registrado_por_usuario INT NOT NULL,
    FOREIGN KEY (id_contrato) REFERENCES Contratos(id_contrato),
    FOREIGN KEY (registrado_por_usuario) REFERENCES Usuarios(id_usuario)
)ENGINE = INNODB;

CREATE TABLE EventosProgramados (
    id_evento_programado INT AUTO_INCREMENT PRIMARY KEY,
    id_contrato INT NOT NULL,
    nombre_interno_evento VARCHAR(200) NOT NULL,
    tipo_evento ENUM(
        'Boda',
        'XV Años',
        'Bautizo',
        'Baby Shower',
        'Cumpleaños',
        'Sesión Fotográfica Escolar',
        'Filmación Escolar',
        'Otro'
    ) NOT NULL,
    fecha_hora_inicio DATETIME NOT NULL,
    fecha_hora_fin DATETIME,
    lugar_evento VARCHAR(255) NOT NULL,
    estado_logistico ENUM(
        'Programado',
        'En Progreso',
        'Completado',
        'Cancelado',
        'Pendiente Re-programación'
    ) NOT NULL,
    responsable_interno INT NOT NULL,
    observaciones_logistica TEXT,
    FOREIGN KEY (id_contrato) REFERENCES Contratos(id_contrato),
    FOREIGN KEY (responsable_interno) REFERENCES Usuarios(id_usuario)
)ENGINE = INNODB;

CREATE TABLE Promociones (
    id_promocion INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    nombre_promocion VARCHAR(100) NOT NULL,
    anio_promocion INT NOT NULL,
    fecha_entrega_final_prevista DATE,
    id_contrato INT NULL,
    FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente),
    FOREIGN KEY (id_contrato) REFERENCES Contratos(id_contrato)
)ENGINE = INNODB;

CREATE TABLE Secciones (
    id_seccion INT AUTO_INCREMENT PRIMARY KEY,
    id_promocion INT NOT NULL,
    nombre_seccion VARCHAR(50) NOT NULL,
    docente_cargo VARCHAR(100),
    contacto_seccion_tel VARCHAR(20),
    contacto_seccion_email VARCHAR(100),
    FOREIGN KEY (id_promocion) REFERENCES Promociones(id_promocion)
)ENGINE = INNODB;

-- CREATE TABLE Alumnos (
--    id_alumno INT AUTO_INCREMENT PRIMARY KEY,
--    id_seccion INT NOT NULL,
--    nombre_completo_alumno VARCHAR(150) NOT NULL,
--    dni_alumno VARCHAR(20) UNIQUE,
--    telefono_contacto_padre VARCHAR(20),
--   email_contacto_padre VARCHAR(100),
--    observaciones TEXT,
--    FOREIGN KEY (id_seccion) REFERENCES Secciones(id_seccion)
-- )ENGINE = INNODB;

CREATE TABLE PedidosProductoEscolar (
    id_pedido_producto_escolar INT AUTO_INCREMENT PRIMARY KEY,
    id_contrato INT NOT NULL,
    id_seccion INT NOT NULL,
    tipo_producto ENUM('Anuario', 'Cuadro de Promoción') NOT NULL,
    cantidad_ejemplares INT NOT NULL DEFAULT 1,
    estado_diseno ENUM('Pendiente Diseño', 'En Revisión', 'Aprobado', 'Rechazado') NOT NULL,
    estado_produccion ENUM('Pendiente Producción', 'En Proceso', 'Control de Calidad', 'Terminado', 'Entregado') NOT NULL,
    fecha_entrega_real DATE,
    observaciones_pedido TEXT,
    ruta_diseno_final VARCHAR(255),
    
    FOREIGN KEY (id_contrato) REFERENCES Contratos(id_contrato),
    FOREIGN KEY (id_seccion) REFERENCES Secciones(id_seccion)
)ENGINE = INNODB;


CREATE TABLE Historial_Cambios (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    tabla_afectada VARCHAR(100) NOT NULL,
    id_registro_afectado INT NOT NULL,
    accion ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    campo_cambiado VARCHAR(100),
    valor_anterior TEXT,
    valor_nuevo TEXT,
    descripcion TEXT,
    id_usuario_responsable INT NOT NULL,
    fecha_cambio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario_responsable) REFERENCES Usuarios(id_usuario)
) ENGINE=INNODB;


-- SISTEMA DE GESTION DE PROYECTOS (GRUPO 2)

-- TABLA PARA EL KANBAN
-- CREATE TABLE inicioProyecto(
--     id_proyecto     INT AUTO_INCREMENT PRIMARY KEY,
--     trabajador      VARCHAR(100) NOT NULL,

--     estado ENUM('Planificación','Producción','Postproducción','Finalizado') DEFAULT 'Planificación',

-- )ENGINE=INNODB;

-- TABLA DE LOS MATERIALES A LLEVAR
-- CREATE TABLE materialEvento(
--     id_material     INT AUTO_INCREMENT PRIMARY KEY,

--     id_evento_programado INT,
--     FOREIGN KEY (id_evento_programado) REFERENCES EventosProgramados(id_evento_programado)
-- )ENGINE=INNODB;

CREATE TABLE proyectos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente VARCHAR(100) NOT NULL,
    tipo_evento VARCHAR(100) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estado ENUM('Planificación','Producción','Postproducción','Finalizado') DEFAULT 'Planificación',
    descripcion TEXT
);

-- Tabla de recursos (equipos o personal)
CREATE TABLE recursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('Equipo','Personal') NOT NULL,
    disponibilidad BOOLEAN DEFAULT 1
);

-- Tabla de asignaciones de recursos a proyectos
CREATE TABLE asignaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto INT,
    id_recurso INT,
    fecha_asignacion DATE NOT NULL,
    FOREIGN KEY (id_proyecto) REFERENCES proyectos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_recurso) REFERENCES recursos(id) ON DELETE CASCADE,
    UNIQUE (id_proyecto, id_recurso) -- Evita asignar el mismo recurso dos veces a un mismo proyecto
);


-- TABLA DE CONTACTOS
CREATE TABLE IF NOT EXISTS contactos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    imagen VARCHAR(255),
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    telefono VARCHAR(20),
    direccion TEXT,
    edad INT,
    profesion VARCHAR(100),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)ENGINE=INNODB;
