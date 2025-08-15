CREATE DATABASE gestion_proyectos;

USE gestion_proyectos;

-- Tabla de proyectos
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
