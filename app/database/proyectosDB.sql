-- Crear base de datos
CREATE DATABASE IF NOT EXISTS gestionProyectos;
USE gestionProyectos;

-- Tabla: clientes
CREATE TABLE clientes (
    idCliente INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    nomContacto VARCHAR(100) NOT NULL,
    tipoCliente ENUM('Individual', 'Institucional') NOT NULL,
    nomEmpresa VARCHAR(200) NULL,
    docIdentidad ENUM('RUC', 'DNI') DEFAULT 'DNI' NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    email VARCHAR(100) NULL,
    direccion VARCHAR(200) NOT NULL,
    ciudad VARCHAR(100) NOT NULL,
    observaciones TEXT NULL,
    fechaRegistro DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabla: proyectos
CREATE TABLE proyectos (
    proyectoId INT AUTO_INCREMENT PRIMARY KEY,
    clienteId INT NOT NULL,
    tipoEvento ENUM(
        'Boda', 
        'XV Años', 
        'Bautizo', 
        'Baby Shower', 
        'Cumpleaños',
        'Sesión Fotográfica Escolar', 
        'Filmación Escolar', 
        'Otro'
    ) NOT NULL,
    descripcion TEXT,
    fechaInicio DATE NOT NULL,
    fechaFin DATE NOT NULL,
    estado ENUM('Planificación', 'Producción', 'Postproducción', 'Finalizado') NOT NULL,
    FOREIGN KEY (clienteId) REFERENCES clientes(idCliente)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla: equipos
CREATE TABLE equipos (
    equipoId INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    nombreEquipo VARCHAR(100) NOT NULL,
    descripcion TEXT,
    tipoEquipo ENUM(
        'Cámaras',
        'Lentes',
        'Iluminación',
        'Audio',
        'Estabilizadores',
        'Accesorio de Estudio',
        'Computadoras',
        'Memorias',
        'Impresoras',
        'Drones',
        'Otro'
    ) NOT NULL,
    estadoEquipo ENUM('Disponible', 'En Uso', 'En Mantenimiento') DEFAULT 'Disponible'
) ENGINE=InnoDB;

-- Tabla: proyectosEquipos (Asignación de equipos a proyectos)
CREATE TABLE proyectosEquipos (
    id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    proyectoId INT NOT NULL,
    equipoId INT NOT NULL,
    fechaAsignacion DATE NOT NULL,
    fechaDevolucion DATE DEFAULT NULL,
    FOREIGN KEY (proyectoId) REFERENCES proyectos(proyectoId)
        ON DELETE CASCADE,
    FOREIGN KEY (equipoId) REFERENCES equipos(equipoId)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla: trabajadores
CREATE TABLE trabajadores (
    trabajadorId INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    direccion TEXT,
    rol VARCHAR(50), -- Ejemplo: Fotógrafo, Editor
    fechaIngreso DATE
) ENGINE=InnoDB;

-- Tabla: proyectosTrabajadores (Asignación de trabajadores a proyectos)
CREATE TABLE proyectosTrabajadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proyectoId INT NOT NULL,
    trabajadorId INT NOT NULL,
    rolEnProyecto VARCHAR(50), -- Ejemplo: Fotógrafo principal
    FOREIGN KEY (proyectoId) REFERENCES proyectos(proyectoId)
        ON DELETE CASCADE,
    FOREIGN KEY (trabajadorId) REFERENCES trabajadores(trabajadorId)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla: mantenimientos (registro de mantenimientos y reparaciones de equipos)
CREATE TABLE mantenimientos (
    mantenimientoId INT AUTO_INCREMENT PRIMARY KEY,
    equipoId INT NOT NULL,
    fechaMantenimiento DATE NOT NULL,
    tipoMantenimiento VARCHAR(100),
    descripcion TEXT,
    costo DECIMAL(10,2),
    realizadoPor VARCHAR(100),
    FOREIGN KEY (equipoId) REFERENCES equipos(equipoId)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabla: seguimientoTareas (planificación y seguimiento de tareas por proyecto)
CREATE TABLE seguimientoTareas (
    tareaId INT AUTO_INCREMENT PRIMARY KEY,
    proyectoId INT NOT NULL,
    descripcion TEXT NOT NULL,
    responsableId INT, -- referencia a trabajador responsable
    fechaInicio DATE,
    fechaFin DATE,
    estado ENUM('Pendiente', 'En Proceso', 'Completada', 'Retrasada') DEFAULT 'Pendiente',
    FOREIGN KEY (proyectoId) REFERENCES proyectos(proyectoId)
        ON DELETE CASCADE,
    FOREIGN KEY (responsableId) REFERENCES trabajadores(trabajadorId)
) ENGINE=InnoDB;

-- Tabla: historialEstadosProyecto (registro de cambios de estado de proyectos)
CREATE TABLE historialEstadosProyecto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proyectoId INT NOT NULL,
    estadoAnterior ENUM('Planificación', 'Producción', 'Postproducción', 'Finalizado'),
    estadoNuevo ENUM('Planificación', 'Producción', 'Postproducción', 'Finalizado'),
    fechaCambio DATETIME DEFAULT CURRENT_TIMESTAMP,
    usuarioCambio VARCHAR(100), -- quién hizo el cambio
    FOREIGN KEY (proyectoId) REFERENCES proyectos(proyectoId)
        ON DELETE CASCADE
) ENGINE=InnoDB;
