-- =====================================================
-- AJUSTES ADICIONALES PARA CATÁLOGO REAL FISI
-- Ejecutar DESPUÉS del script principal
-- VERSIÓN EN ESPAÑOL
-- =====================================================

USE biblioteca_fisi;

-- Ajustar tamaño de codigo_ejemplar para clasificaciones largas
ALTER TABLE ejemplares_libros MODIFY codigo_ejemplar VARCHAR(100) NOT NULL UNIQUE;

-- Agregar campo para clasificación base (sin número de ejemplar)
ALTER TABLE libros ADD COLUMN clasificacion_base VARCHAR(50) AFTER clasificacion;

-- Actualizar índices para optimizar búsquedas
ALTER TABLE libros ADD INDEX idx_clasificacion_base (clasificacion_base);

-- Insertar algunos libros reales como ejemplo
-- (Basados en el análisis del Excel del catálogo)

-- Libro 1: Lógica Matemática (3 ejemplares)
INSERT INTO libros (titulo, autor, clasificacion, clasificacion_base, año_publicacion, descripcion, estado) VALUES
('LOGICA MATEMATICA - un enfoque axiomatico', 'Augusto Cortez Vasquez', 'BC135.C78', 'BC135.C78', 1998, 'Enfoque axiomatico de la lógica matemática', 'activo');

SET @libro1_id = LAST_INSERT_ID();

INSERT INTO ejemplares_libros (id_libro, codigo_ejemplar, estado) VALUES
(@libro1_id, 'BC135.C78_001', 'disponible'),
(@libro1_id, 'BC135.C78_002', 'disponible'),  
(@libro1_id, 'BC135.C78_003', 'disponible');

-- Asociar a categoría Filosofía y Lógica
INSERT INTO libro_categorias (id_libro, id_categoria) 
SELECT @libro1_id, id_categoria FROM categorias WHERE codigo = 'BC';

-- Libro 2: Tratado de Psicología (11 ejemplares)
INSERT INTO libros (titulo, autor, clasificacion, clasificacion_base, año_publicacion, descripcion, estado) VALUES
('Tratado de PSICOLOGIA REVOLUCIONARIA', 'Samuel Aun Weor', 'BF176.A88', 'BF176.A88', 2014, 'Tratado completo de psicología revolucionaria', 'activo');

SET @libro2_id = LAST_INSERT_ID();

INSERT INTO ejemplares_libros (id_libro, codigo_ejemplar, estado) VALUES
(@libro2_id, 'BF176.A88_001', 'disponible'),
(@libro2_id, 'BF176.A88_002', 'disponible'),
(@libro2_id, 'BF176.A88_003', 'disponible'),
(@libro2_id, 'BF176.A88_004', 'disponible'),
(@libro2_id, 'BF176.A88_005', 'disponible'),
(@libro2_id, 'BF176.A88_006', 'disponible'),
(@libro2_id, 'BF176.A88_007', 'disponible'),
(@libro2_id, 'BF176.A88_008', 'disponible'),
(@libro2_id, 'BF176.A88_009', 'disponible'),
(@libro2_id, 'BF176.A88_010', 'disponible'),
(@libro2_id, 'BF176.A88_011', 'disponible');

-- Asociar a categoría Psicología
INSERT INTO libro_categorias (id_libro, id_categoria) 
SELECT @libro2_id, id_categoria FROM categorias WHERE codigo = 'BF';

-- Libro 3: Principios de Economía (7 ejemplares)
INSERT INTO libros (titulo, autor, clasificacion, clasificacion_base, año_publicacion, descripcion, estado) VALUES
('Principios de Economia', 'N. Gregory Mankiw', 'HB171.5.M22', 'HB171.5.M22', NULL, 'Principios fundamentales de economía', 'activo');

SET @libro3_id = LAST_INSERT_ID();

INSERT INTO ejemplares_libros (id_libro, codigo_ejemplar, estado) VALUES
(@libro3_id, 'HB171.5.M22_001', 'disponible'),
(@libro3_id, 'HB171.5.M22_002', 'disponible'),
(@libro3_id, 'HB171.5.M22_003', 'disponible'),
(@libro3_id, 'HB171.5.M22_004', 'disponible'),
(@libro3_id, 'HB171.5.M22_005', 'disponible'),
(@libro3_id, 'HB171.5.M22_006', 'disponible'),
(@libro3_id, 'HB171.5.M22_007', 'disponible');

-- Asociar a categoría Economía
INSERT INTO libro_categorias (id_libro, id_categoria) 
SELECT @libro3_id, id_categoria FROM categorias WHERE codigo = 'HB';

-- Agregar más ejemplos representativos para la demo
INSERT INTO libros (titulo, autor, clasificacion, clasificacion_base, año_publicacion, estado) VALUES
('Estructura de Datos y Algoritmos', 'Alfred V. Aho', 'QA76.9.D35A36', 'QA76.9.D35A36', 1988, 'activo'),
('Introducción a la Ingeniería de Software', 'Ian Sommerville', 'QA76.758.S65', 'QA76.758.S65', 2016, 'activo'),
('Redes de Computadoras', 'Andrew S. Tanenbaum', 'TK5105.5.T36', 'TK5105.5.T36', 2011, 'activo');

-- Crear ejemplares para estos libros adicionales
SET @libro4_id = (SELECT id_libro FROM libros WHERE titulo = 'Estructura de Datos y Algoritmos');
SET @libro5_id = (SELECT id_libro FROM libros WHERE titulo = 'Introducción a la Ingeniería de Software');
SET @libro6_id = (SELECT id_libro FROM libros WHERE titulo = 'Redes de Computadoras');

INSERT INTO ejemplares_libros (id_libro, codigo_ejemplar, estado) VALUES
(@libro4_id, 'QA76.9.D35A36_001', 'disponible'),
(@libro4_id, 'QA76.9.D35A36_002', 'disponible'),
(@libro5_id, 'QA76.758.S65_001', 'disponible'),
(@libro5_id, 'QA76.758.S65_002', 'disponible'),
(@libro5_id, 'QA76.758.S65_003', 'disponible'),
(@libro6_id, 'TK5105.5.T36_001', 'disponible');

-- Asociar a categorías apropiadas
INSERT INTO libro_categorias (id_libro, id_categoria) 
SELECT @libro4_id, id_categoria FROM categorias WHERE codigo = 'QA76'
UNION ALL
SELECT @libro5_id, id_categoria FROM categorias WHERE codigo = 'IS'  
UNION ALL
SELECT @libro6_id, id_categoria FROM categorias WHERE codigo = 'T';

-- Actualizar vista de disponibilidad para incluir clasificacion_base
DROP VIEW IF EXISTS vista_disponibilidad_libros;
CREATE VIEW vista_disponibilidad_libros AS
SELECT 
    l.id_libro,
    l.titulo,
    l.autor,
    l.clasificacion,
    l.clasificacion_base,
    l.año_publicacion,
    COUNT(e.id_ejemplar) AS total_ejemplares,
    SUM(CASE WHEN e.estado = 'disponible' THEN 1 ELSE 0 END) AS ejemplares_disponibles,
    SUM(CASE WHEN e.estado = 'prestado' THEN 1 ELSE 0 END) AS ejemplares_prestados,
    SUM(CASE WHEN e.estado = 'mantenimiento' THEN 1 ELSE 0 END) AS ejemplares_mantenimiento,
    SUM(CASE WHEN e.estado = 'perdido' THEN 1 ELSE 0 END) AS ejemplares_perdidos
FROM libros l
LEFT JOIN ejemplares_libros e ON l.id_libro = e.id_libro
WHERE l.estado = 'activo'
GROUP BY l.id_libro, l.titulo, l.autor, l.clasificacion, l.clasificacion_base, l.año_publicacion;

-- Verificar datos insertados
SELECT 
    'RESUMEN DE DATOS INSERTADOS' as informacion,
    (SELECT COUNT(*) FROM libros WHERE estado = 'activo') as total_libros,
    (SELECT COUNT(*) FROM ejemplares_libros WHERE estado = 'disponible') as total_ejemplares,
    (SELECT COUNT(*) FROM categorias) as total_categorias;

-- Mostrar ejemplos insertados
SELECT 
    l.titulo as 'Título',
    l.autor as 'Autor', 
    l.clasificacion_base as 'Clasificación',
    COUNT(e.id_ejemplar) as 'Ejemplares'
FROM libros l 
LEFT JOIN ejemplares_libros e ON l.id_libro = e.id_libro 
GROUP BY l.id_libro 
ORDER BY COUNT(e.id_ejemplar) DESC;