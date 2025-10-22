-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS Proyect;
USE Proyect;

-- Tabla Usuario (tabla principal)
CREATE TABLE Usuario (
    IdUsuario INT PRIMARY KEY AUTO_INCREMENT,
    Nombre VARCHAR(50) NOT NULL,
    Apellido VARCHAR(50) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    ContrasenaHash VARCHAR(255) NOT NULL,
    FotoPerfil VARCHAR(255),
    Descripcion TEXT,
    FechaRegistro DATETIME DEFAULT CURRENT_TIMESTAMP,
    EstadoCuenta ENUM('ACTIVO', 'INACTIVO', 'SUSPENDIDO') DEFAULT 'ACTIVO',
    UltimoAcceso DATETIME
);

-- Tabla Administrador
CREATE TABLE Administrador (
    IdUsuario INT PRIMARY KEY,
    FOREIGN KEY (IdUsuario) REFERENCES Usuario(IdUsuario) ON DELETE CASCADE
);

-- Tabla Cliente
CREATE TABLE Cliente (
    IdUsuario INT PRIMARY KEY,
    FOREIGN KEY (IdUsuario) REFERENCES Usuario(IdUsuario) ON DELETE CASCADE
);

-- Tabla Proveedor
CREATE TABLE Proveedor (
    IdUsuario INT PRIMARY KEY,
    AniosExperiencia INT DEFAULT 0,
    FOREIGN KEY (IdUsuario) REFERENCES Usuario(IdUsuario) ON DELETE CASCADE
);

-- Tabla Dato (para contactos)
CREATE TABLE Dato (
    IdUsuario INT,
    Tipo VARCHAR(50) NOT NULL,
    Contacto VARCHAR(100) NOT NULL,
    PRIMARY KEY (IdUsuario, Tipo),
    FOREIGN KEY (IdUsuario) REFERENCES Usuario(IdUsuario) ON DELETE CASCADE
);

-- Tabla Habilidad
CREATE TABLE Habilidad (
    IdUsuario INT,
    Habilidad VARCHAR(100) NOT NULL,
    AniosExperiencia INT DEFAULT 0,
    PRIMARY KEY (IdUsuario, Habilidad),
    FOREIGN KEY (IdUsuario) REFERENCES Usuario(IdUsuario) ON DELETE CASCADE
);

-- Tabla Servicio
CREATE TABLE Servicio (
    IdServicio INT PRIMARY KEY AUTO_INCREMENT,
    IdProveedor INT NOT NULL,
    Titulo VARCHAR(100) NOT NULL,
    Descripcion TEXT,
    Precio DECIMAL(10,2),
    FechaPublicacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    Estado ENUM('ACTIVO', 'INACTIVO') DEFAULT 'ACTIVO',
    FOREIGN KEY (IdProveedor) REFERENCES Proveedor(IdUsuario)
);

-- Tabla Categoria
CREATE TABLE Categoria (
    IdCategoria INT PRIMARY KEY AUTO_INCREMENT,
    Nombre VARCHAR(50) NOT NULL UNIQUE,
    Descripcion TEXT
);

-- Tabla Pertenece (relación Servicio-Categoria)
CREATE TABLE Pertenece (
    IdServicio INT,
    IdCategoria INT,
    PRIMARY KEY (IdServicio, IdCategoria),
    FOREIGN KEY (IdServicio) REFERENCES Servicio(IdServicio) ON DELETE CASCADE,
    FOREIGN KEY (IdCategoria) REFERENCES Categoria(IdCategoria) ON DELETE CASCADE
);

-- Tabla PalabraClave
CREATE TABLE PalabraClave (
    IdServicio INT,
    Palabra VARCHAR(50),
    PRIMARY KEY (IdServicio, Palabra),
    FOREIGN KEY (IdServicio) REFERENCES Servicio(IdServicio) ON DELETE CASCADE
);

-- Tabla Foto
CREATE TABLE Foto (
    IdFoto INT PRIMARY KEY AUTO_INCREMENT,
    IdServicio INT,
    RutaFoto VARCHAR(255) NOT NULL,
    FOREIGN KEY (IdServicio) REFERENCES Servicio(IdServicio) ON DELETE CASCADE
);

-- Tabla Disponibilidad
CREATE TABLE Disponibilidad (
    IdDisponibilidad INT PRIMARY KEY AUTO_INCREMENT,
    IdServicio INT,
    DiaSemana ENUM('LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO'),
    HoraInicio TIME,
    HoraFin TIME,
    FOREIGN KEY (IdServicio) REFERENCES Servicio(IdServicio) ON DELETE CASCADE
);

-- Tabla Reserva
CREATE TABLE Reserva (
    IdReserva INT PRIMARY KEY AUTO_INCREMENT,
    IdDisponibilidad INT,
    Estado ENUM('pendiente', 'confirmada', 'cancelada', 'finalizada') DEFAULT 'pendiente',
    Observacion TEXT,
    IdUsuario INT,
    IdServicio INT,
    FOREIGN KEY (IdDisponibilidad) REFERENCES Disponibilidad(IdDisponibilidad) ON DELETE CASCADE,
    FOREIGN KEY (IdUsuario) REFERENCES Usuario(IdUsuario),
    FOREIGN KEY (IdServicio) REFERENCES Servicio(IdServicio)
);

-- Tabla Reseña
CREATE TABLE Resena (
    IdReserva INT PRIMARY KEY,
    Calificacion INT CHECK (Calificacion BETWEEN 1 AND 5),
    Comentario TEXT,
    FechaReseña DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (IdReserva) REFERENCES Reserva(IdReserva)
);

-- Tabla Mensaje
CREATE TABLE Mensaje (
    IdMensaje INT PRIMARY KEY AUTO_INCREMENT,
    IdEmisor INT,
    IdReceptor INT,
    Contenido TEXT NOT NULL,
    FechaHora DATETIME DEFAULT CURRENT_TIMESTAMP,
    Estado ENUM('ENVIADO', 'LEIDO') DEFAULT 'ENVIADO',
    FOREIGN KEY (IdEmisor) REFERENCES Usuario(IdUsuario),
    FOREIGN KEY (IdReceptor) REFERENCES Usuario(IdUsuario)
);

-- Tabla Ubicacion
CREATE TABLE Ubicacion (
    IdUbicacion INT PRIMARY KEY AUTO_INCREMENT,
    Direccion VARCHAR(255) NOT NULL,
    Ciudad VARCHAR(100),
    Departamento VARCHAR(100),
    Latitud DECIMAL(10,8),
    Longitud DECIMAL(11,8)
);

-- Tabla UbicacionServicio (para servicios que se ofrecen en múltiples ubicaciones)
CREATE TABLE UbicacionServicio (
    IdServicio INT,
    IdUbicacion INT,
    PRIMARY KEY (IdServicio, IdUbicacion),
    FOREIGN KEY (IdServicio) REFERENCES Servicio(IdServicio) ON DELETE CASCADE,
    FOREIGN KEY (IdUbicacion) REFERENCES Ubicacion(IdUbicacion) ON DELETE CASCADE
);

-- Agregar IdUbicacion a Usuario (después de crear la tabla Ubicacion)
ALTER TABLE Usuario
ADD COLUMN IdUbicacion INT,
ADD FOREIGN KEY (IdUbicacion) REFERENCES Ubicacion(IdUbicacion);

-- Índices adicionales para mejorar el rendimiento
CREATE INDEX idx_email ON Usuario(Email);
CREATE INDEX idx_servicio_estado ON Servicio(Estado);
CREATE INDEX idx_reserva_estado ON Reserva(Estado);
CREATE INDEX idx_mensaje_fecha ON Mensaje(FechaHora);