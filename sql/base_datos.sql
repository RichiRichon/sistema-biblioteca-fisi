-- =====================================================
-- SCRIPT DE CREACIÓN DE BASE DE DATOS
-- Sistema Web de Gestión Bibliotecaria FISI
-- VERSIÓN EN ESPAÑOL
-- =====================================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS biblioteca_fisi;
USE biblioteca_fisi;

-- Configurar charset
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- =====================================================
-- TABLA: usuarios (Usuarios del sistema)
-- =====================================================
CREATE TABLE usuarios (
    id_usuario INT PRIMARY KEY AUTO_INCREMENT,
    nombre_usuario VARCHAR(50) NOT NULL UNIQUE,
    correo VARCHAR(100) NOT NULL UNIQUE,
    clave_hash VARCHAR(255) NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    carnet_biblioteca VARCHAR(20) UNIQUE, -- Carnet de biblioteca
    rol ENUM('estudiante', 'docente', 'bibliotecario', 'administrador') DEFAULT 'estudiante',
    estado ENUM('activo', 'inactivo', 'suspendido') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_nombre_usuario (nombre_usuario),
    INDEX idx_correo (correo),
    INDEX idx_rol (rol),
    INDEX idx_estado (estado)
);

-- =====================================================
-- TABLA: categorias (Categorías de libros)
-- =====================================================
CREATE TABLE categorias (
    id_categoria INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    codigo VARCHAR(20) UNIQUE, -- Código de clasificación (ej: BC135)
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_nombre (nombre),
    INDEX idx_codigo (codigo)
);

-- =====================================================
-- TABLA: libros (Libros - títulos únicos)
-- =====================================================
CREATE TABLE libros (
    id_libro INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(255) NOT NULL,
    autor VARCHAR(255) NOT NULL,
    clasificacion VARCHAR(50), -- Clasificación biblioteca central
    isbn VARCHAR(20) UNIQUE,
    año_publicacion INT,
    editorial VARCHAR(100),
    descripcion TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_titulo (titulo),
    INDEX idx_autor (autor),
    INDEX idx_clasificacion (clasificacion),
    INDEX idx_isbn (isbn),
    INDEX idx_año_publicacion (año_publicacion),
    FULLTEXT idx_busqueda (titulo, autor, descripcion)
);

-- =====================================================
-- TABLA: libro_categorias (Relación muchos a muchos: libros-categorías)
-- =====================================================
CREATE TABLE libro_categorias (
    id_libro INT NOT NULL,
    id_categoria INT NOT NULL,
    
    PRIMARY KEY (id_libro, id_categoria),
    FOREIGN KEY (id_libro) REFERENCES libros(id_libro) ON DELETE CASCADE,
    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria) ON DELETE CASCADE
);

-- =====================================================
-- TABLA: ejemplares_libros (Ejemplares físicos de libros)
-- =====================================================
CREATE TABLE ejemplares_libros (
    id_ejemplar INT PRIMARY KEY AUTO_INCREMENT,
    id_libro INT NOT NULL,
    codigo_ejemplar VARCHAR(50) NOT NULL UNIQUE, -- Código del ejemplar
    estado ENUM('disponible', 'prestado', 'mantenimiento', 'perdido') DEFAULT 'disponible',
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_libro) REFERENCES libros(id_libro) ON DELETE CASCADE,
    INDEX idx_id_libro (id_libro),
    INDEX idx_codigo_ejemplar (codigo_ejemplar),
    INDEX idx_estado (estado)
);

-- =====================================================
-- TABLA: prestamos (Préstamos)
-- =====================================================
CREATE TABLE prestamos (
    id_prestamo INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    id_ejemplar INT NOT NULL,
    id_bibliotecario INT NOT NULL, -- Usuario que registró el préstamo
    fecha_prestamo DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_vencimiento DATETIME NOT NULL,
    fecha_devolucion DATETIME NULL,
    estado ENUM('activo', 'devuelto', 'vencido', 'renovado') DEFAULT 'activo',
    numero_renovaciones INT DEFAULT 0,
    monto_multa DECIMAL(10,2) DEFAULT 0.00,
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_ejemplar) REFERENCES ejemplares_libros(id_ejemplar) ON DELETE CASCADE,
    FOREIGN KEY (id_bibliotecario) REFERENCES usuarios(id_usuario),
    INDEX idx_id_usuario (id_usuario),
    INDEX idx_id_ejemplar (id_ejemplar),
    INDEX idx_id_bibliotecario (id_bibliotecario),
    INDEX idx_fecha_prestamo (fecha_prestamo),
    INDEX idx_fecha_vencimiento (fecha_vencimiento),
    INDEX idx_estado (estado)
);

-- =====================================================
-- TABLA: historial_prestamos (Historial de acciones en préstamos)
-- =====================================================
CREATE TABLE historial_prestamos (
    id_historial INT PRIMARY KEY AUTO_INCREMENT,
    id_prestamo INT NOT NULL,
    accion ENUM('creado', 'renovado', 'devuelto', 'vencido') NOT NULL,
    fecha_accion DATETIME DEFAULT CURRENT_TIMESTAMP,
    realizado_por INT NOT NULL, -- Usuario que realizó la acción
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_prestamo) REFERENCES prestamos(id_prestamo) ON DELETE CASCADE,
    FOREIGN KEY (realizado_por) REFERENCES usuarios(id_usuario),
    INDEX idx_id_prestamo (id_prestamo),
    INDEX idx_accion (accion),
    INDEX idx_fecha_accion (fecha_accion),
    INDEX idx_realizado_por (realizado_por)
);

-- =====================================================
-- TABLA: reservas (Reservas de libros)
-- =====================================================
CREATE TABLE reservas (
    id_reserva INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    id_libro INT NOT NULL,
    fecha_reserva DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_vencimiento DATETIME NOT NULL,
    estado ENUM('pendiente', 'cumplida', 'cancelada', 'vencida') DEFAULT 'pendiente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_libro) REFERENCES libros(id_libro) ON DELETE CASCADE,
    INDEX idx_id_usuario (id_usuario),
    INDEX idx_id_libro (id_libro),
    INDEX idx_fecha_reserva (fecha_reserva),
    INDEX idx_estado (estado)
);

-- =====================================================
-- TABLA: multas (Multas)
-- =====================================================
CREATE TABLE multas (
    id_multa INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    id_prestamo INT NULL, -- Puede ser NULL si es multa general
    monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tipo ENUM('retraso', 'daño', 'perdida') NOT NULL,
    estado ENUM('pendiente', 'pagada', 'perdonada') DEFAULT 'pendiente',
    descripcion TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_pago TIMESTAMP NULL,
    
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_prestamo) REFERENCES prestamos(id_prestamo) ON DELETE SET NULL,
    INDEX idx_id_usuario (id_usuario),
    INDEX idx_id_prestamo (id_prestamo),
    INDEX idx_tipo (tipo),
    INDEX idx_estado (estado)
);

-- =====================================================
-- DATOS INICIALES
-- =====================================================

-- Usuario administrador por defecto
INSERT INTO usuarios (nombre_usuario, correo, clave_hash, nombres, apellidos, rol, carnet_biblioteca) VALUES
('admin', 'admin@fisi.unmsm.edu.pe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'Sistema', 'administrador', 'ADMIN001');

-- Usuario bibliotecario de prueba
INSERT INTO usuarios (nombre_usuario, correo, clave_hash, nombres, apellidos, rol, carnet_biblioteca) VALUES
('bibliotecario', 'biblio@fisi.unmsm.edu.pe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'María', 'García', 'bibliotecario', 'LIB001');

-- Categorías básicas basadas en clasificación de biblioteca central
INSERT INTO categorias (nombre, descripcion, codigo) VALUES
('Filosofía y Lógica', 'Libros de lógica matemática, filosofía', 'BC'),
('Psicología', 'Libros de psicología y ciencias del comportamiento', 'BF'),
('Economía', 'Libros de principios económicos y finanzas', 'HB'),
('Ingeniería de Sistemas', 'Libros específicos de ingeniería de sistemas', 'IS'),
('Matemáticas', 'Libros de matemáticas aplicadas y puras', 'QA'),
('Computación', 'Libros de ciencias de la computación', 'QA76'),
('Tecnología', 'Libros de tecnología general', 'T'),
('General', 'Otros libros no clasificados', 'GEN');

-- =====================================================
-- TRIGGERS PARA ACTUALIZAR ESTADO DE EJEMPLARES
-- =====================================================

-- Trigger: Cuando se crea un préstamo, marcar ejemplar como prestado
DELIMITER //
CREATE TRIGGER despues_insertar_prestamo 
    AFTER INSERT ON prestamos 
    FOR EACH ROW 
BEGIN 
    UPDATE ejemplares_libros 
    SET estado = 'prestado' 
    WHERE id_ejemplar = NEW.id_ejemplar;
END//

-- Trigger: Cuando se devuelve un libro, marcar ejemplar como disponible
CREATE TRIGGER despues_devolver_libro 
    AFTER UPDATE ON prestamos 
    FOR EACH ROW 
BEGIN 
    IF NEW.estado = 'devuelto' AND OLD.estado != 'devuelto' THEN
        UPDATE ejemplares_libros 
        SET estado = 'disponible' 
        WHERE id_ejemplar = NEW.id_ejemplar;
    END IF;
END//
DELIMITER ;

-- =====================================================
-- VISTAS ÚTILES PARA CONSULTAS FRECUENTES
-- =====================================================

-- Vista: Información completa de préstamos activos
CREATE VIEW vista_prestamos_activos AS
SELECT 
    p.id_prestamo,
    p.fecha_prestamo,
    p.fecha_vencimiento,
    p.estado,
    p.numero_renovaciones,
    CONCAT(u.nombres, ' ', u.apellidos) AS nombre_usuario,
    u.nombre_usuario AS usuario,
    l.titulo AS titulo_libro,
    l.autor AS autor_libro,
    e.codigo_ejemplar,
    DATEDIFF(CURDATE(), p.fecha_vencimiento) AS dias_vencidos
FROM prestamos p
JOIN usuarios u ON p.id_usuario = u.id_usuario
JOIN ejemplares_libros e ON p.id_ejemplar = e.id_ejemplar
JOIN libros l ON e.id_libro = l.id_libro
WHERE p.estado IN ('activo', 'vencido', 'renovado');

-- Vista: Disponibilidad de libros
CREATE VIEW vista_disponibilidad_libros AS
SELECT 
    l.id_libro,
    l.titulo,
    l.autor,
    l.clasificacion,
    COUNT(e.id_ejemplar) AS total_ejemplares,
    SUM(CASE WHEN e.estado = 'disponible' THEN 1 ELSE 0 END) AS ejemplares_disponibles,
    SUM(CASE WHEN e.estado = 'prestado' THEN 1 ELSE 0 END) AS ejemplares_prestados
FROM libros l
LEFT JOIN ejemplares_libros e ON l.id_libro = e.id_libro
WHERE l.estado = 'activo'
GROUP BY l.id_libro, l.titulo, l.autor, l.clasificacion;