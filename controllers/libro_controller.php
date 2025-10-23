<?php
/**
 * Controlador de Gestión de Libros (CRUD)
 * HU-05: Gestión de inventario
 * Sistema de Gestión Bibliotecaria FISI
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';

// Verificar autenticación y permisos (solo admin y bibliotecario)
protegerPagina(['administrador', 'bibliotecario']);

// Manejar acciones del formulario
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$respuesta = ['exito' => false, 'mensaje' => ''];

try {
    $pdo = Database::getInstance()->getConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    switch ($accion) {
        case 'crear':
            $respuesta = crearLibro($pdo);
            break;
            
        case 'editar':
            $respuesta = editarLibro($pdo);
            break;
            
        case 'eliminar':
            $respuesta = eliminarLibro($pdo);
            break;
            
        case 'obtener':
            $respuesta = obtenerLibro($pdo);
            break;
            
        case 'listar':
            $respuesta = listarLibros($pdo);
            break;
            
        case 'agregar_ejemplar':
            $respuesta = agregarEjemplar($pdo);
            break;
            
        case 'eliminar_ejemplar':
            $respuesta = eliminarEjemplar($pdo);
            break;
    }
    
} catch (PDOException $e) {
    $respuesta = [
        'exito' => false,
        'mensaje' => 'Error en la base de datos: ' . $e->getMessage()
    ];
}

// Devolver respuesta JSON para peticiones AJAX
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($respuesta);
    exit;
}

// Si no es AJAX, redirigir con mensaje
if ($accion !== '' && $accion !== 'obtener' && $accion !== 'listar') {
    $_SESSION['mensaje'] = $respuesta['mensaje'];
    $_SESSION['tipo_mensaje'] = $respuesta['exito'] ? 'success' : 'danger';
    
    // Redirección personalizada si se especifica
    if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
        header('Location: ' . $_POST['redirect']);
    } else {
        header('Location: ../views/admin/gestion_libros.php');
    }
    exit;
}

/**
 * Crear un nuevo libro
 */
function crearLibro($pdo) {
    // Validar datos requeridos
    $errores = validarDatosLibro($_POST);
    if (!empty($errores)) {
        return ['exito' => false, 'mensaje' => implode(', ', $errores)];
    }
    
    // Extraer datos
    $titulo = trim($_POST['titulo']);
    $autor = trim($_POST['autor']);
    $clasificacion = trim($_POST['clasificacion']);
    $año_publicacion = !empty($_POST['año_publicacion']) ? (int)$_POST['año_publicacion'] : null;
    $editorial = trim($_POST['editorial'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $id_categoria = !empty($_POST['id_categoria']) ? (int)$_POST['id_categoria'] : null;
    $cantidad_ejemplares = !empty($_POST['cantidad_ejemplares']) ? (int)$_POST['cantidad_ejemplares'] : 1;
    
    try {
        $pdo->beginTransaction();
        
        // Verificar si el libro ya existe
        $stmt = $pdo->prepare("
            SELECT id_libro FROM libros 
            WHERE LOWER(titulo) = LOWER(?) AND LOWER(autor) = LOWER(?)
        ");
        $stmt->execute([$titulo, $autor]);
        
        if ($stmt->fetch()) {
            $pdo->rollBack();
            return ['exito' => false, 'mensaje' => 'Ya existe un libro con ese título y autor'];
        }
        
        // Insertar libro
        $stmt = $pdo->prepare("
            INSERT INTO libros 
            (titulo, autor, clasificacion, año_publicacion, editorial, isbn, descripcion, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'activo')
        ");
        
        $stmt->execute([
            $titulo,
            $autor,
            $clasificacion,
            $año_publicacion,
            $editorial,
            $isbn,
            $descripcion
        ]);
        
        $id_libro = $pdo->lastInsertId();
        
        // Asociar con categoría si se proporcionó
        if ($id_categoria) {
            $stmt = $pdo->prepare("
                INSERT INTO libro_categorias (id_libro, id_categoria) VALUES (?, ?)
            ");
            $stmt->execute([$id_libro, $id_categoria]);
        }
        
        // Crear ejemplares automáticamente
        for ($i = 1; $i <= $cantidad_ejemplares; $i++) {
            $codigo_ejemplar = $clasificacion . '_' . str_pad($i, 3, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO ejemplares_libros (id_libro, codigo_ejemplar, estado) 
                VALUES (?, ?, 'disponible')
            ");
            $stmt->execute([$id_libro, $codigo_ejemplar]);
        }
        
        $pdo->commit();
        
        return [
            'exito' => true,
            'mensaje' => "Libro creado exitosamente con {$cantidad_ejemplares} ejemplar(es)",
            'id_libro' => $id_libro
        ];
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        return ['exito' => false, 'mensaje' => 'Error al crear el libro: ' . $e->getMessage()];
    }
}

/**
 * Editar un libro existente
 */
function editarLibro($pdo) {
    // Validar ID
    $id_libro = (int)($_POST['id_libro'] ?? 0);
    if ($id_libro <= 0) {
        return ['exito' => false, 'mensaje' => 'ID de libro inválido'];
    }
    
    // Validar datos
    $errores = validarDatosLibro($_POST, $id_libro);
    if (!empty($errores)) {
        return ['exito' => false, 'mensaje' => implode(', ', $errores)];
    }
    
    // Extraer datos
    $titulo = trim($_POST['titulo']);
    $autor = trim($_POST['autor']);
    $clasificacion = trim($_POST['clasificacion']);
    $año_publicacion = !empty($_POST['año_publicacion']) ? (int)$_POST['año_publicacion'] : null;
    $editorial = trim($_POST['editorial'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $id_categoria = !empty($_POST['id_categoria']) ? (int)$_POST['id_categoria'] : null;
    
    try {
        $pdo->beginTransaction();
        
        // Verificar que el libro existe
        $stmt = $pdo->prepare("SELECT id_libro FROM libros WHERE id_libro = ?");
        $stmt->execute([$id_libro]);
        if (!$stmt->fetch()) {
            $pdo->rollBack();
            return ['exito' => false, 'mensaje' => 'Libro no encontrado'];
        }
        
        // Actualizar libro
        $stmt = $pdo->prepare("
            UPDATE libros SET
                titulo = ?,
                autor = ?,
                clasificacion = ?,
                año_publicacion = ?,
                editorial = ?,
                isbn = ?,
                descripcion = ?,
                fecha_actualizacion = CURRENT_TIMESTAMP
            WHERE id_libro = ?
        ");
        
        $stmt->execute([
            $titulo,
            $autor,
            $clasificacion,
            $año_publicacion,
            $editorial,
            $isbn,
            $descripcion,
            $id_libro
        ]);
        
        // Actualizar categoría
        if ($id_categoria) {
            // Eliminar categorías anteriores
            $stmt = $pdo->prepare("DELETE FROM libro_categorias WHERE id_libro = ?");
            $stmt->execute([$id_libro]);
            
            // Insertar nueva categoría
            $stmt = $pdo->prepare("
                INSERT INTO libro_categorias (id_libro, id_categoria) VALUES (?, ?)
            ");
            $stmt->execute([$id_libro, $id_categoria]);
        }
        
        $pdo->commit();
        
        return [
            'exito' => true,
            'mensaje' => 'Libro actualizado exitosamente'
        ];
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        return ['exito' => false, 'mensaje' => 'Error al actualizar el libro: ' . $e->getMessage()];
    }
}

/**
 * Eliminar un libro (soft delete)
 */
function eliminarLibro($pdo) {
    $id_libro = (int)($_POST['id_libro'] ?? $_GET['id_libro'] ?? 0);
    
    if ($id_libro <= 0) {
        return ['exito' => false, 'mensaje' => 'ID de libro inválido'];
    }
    
    try {
        // Verificar si tiene préstamos activos
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM prestamos p
            INNER JOIN ejemplares_libros e ON p.id_ejemplar = e.id_ejemplar
            WHERE e.id_libro = ? AND p.estado = 'activo'
        ");
        $stmt->execute([$id_libro]);
        $resultado = $stmt->fetch();
        
        if ($resultado['total'] > 0) {
            return [
                'exito' => false,
                'mensaje' => 'No se puede eliminar. El libro tiene préstamos activos'
            ];
        }
        
        // Soft delete - cambiar estado a inactivo
        $stmt = $pdo->prepare("
            UPDATE libros SET estado = 'inactivo' WHERE id_libro = ?
        ");
        $stmt->execute([$id_libro]);
        
        // También inactivar ejemplares
        $stmt = $pdo->prepare("
            UPDATE ejemplares_libros 
            SET estado = 'perdido' 
            WHERE id_libro = ? AND estado = 'disponible'
        ");
        $stmt->execute([$id_libro]);
        
        return [
            'exito' => true,
            'mensaje' => 'Libro eliminado exitosamente'
        ];
        
    } catch (PDOException $e) {
        return ['exito' => false, 'mensaje' => 'Error al eliminar el libro: ' . $e->getMessage()];
    }
}

/**
 * Obtener datos de un libro específico
 */
function obtenerLibro($pdo) {
    $id_libro = (int)($_GET['id_libro'] ?? 0);
    
    if ($id_libro <= 0) {
        return ['exito' => false, 'mensaje' => 'ID de libro inválido'];
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                l.*,
                c.id_categoria,
                c.nombre as nombre_categoria,
                (SELECT COUNT(*) FROM ejemplares_libros WHERE id_libro = l.id_libro) as total_ejemplares
            FROM libros l
            LEFT JOIN libro_categorias lc ON l.id_libro = lc.id_libro
            LEFT JOIN categorias c ON lc.id_categoria = c.id_categoria
            WHERE l.id_libro = ?
        ");
        $stmt->execute([$id_libro]);
        $libro = $stmt->fetch();
        
        if (!$libro) {
            return ['exito' => false, 'mensaje' => 'Libro no encontrado'];
        }
        
        return [
            'exito' => true,
            'libro' => $libro
        ];
        
    } catch (PDOException $e) {
        return ['exito' => false, 'mensaje' => 'Error al obtener el libro: ' . $e->getMessage()];
    }
}

/**
 * Listar libros con filtros y paginación
 */
function listarLibros($pdo) {
    $busqueda = $_GET['busqueda'] ?? '';
    $id_categoria = (int)($_GET['id_categoria'] ?? 0);
    $estado = $_GET['estado'] ?? '';
    $pagina = max(1, (int)($_GET['pagina'] ?? 1));
    $por_pagina = 20;
    $offset = ($pagina - 1) * $por_pagina;
    
    try {
        // Construir query base
        $sql = "
            SELECT 
                l.id_libro,
                l.titulo,
                l.autor,
                l.clasificacion,
                l.año_publicacion,
                l.isbn,
                l.estado,
                c.nombre as categoria,
                COUNT(DISTINCT e.id_ejemplar) as total_ejemplares,
                SUM(CASE WHEN e.estado = 'disponible' THEN 1 ELSE 0 END) as ejemplares_disponibles
            FROM libros l
            LEFT JOIN libro_categorias lc ON l.id_libro = lc.id_libro
            LEFT JOIN categorias c ON lc.id_categoria = c.id_categoria
            LEFT JOIN ejemplares_libros e ON l.id_libro = e.id_libro
            WHERE 1=1
        ";
        
        $params = [];
        
        // Filtro de búsqueda
        if (!empty($busqueda)) {
            $sql .= " AND (l.titulo LIKE ? OR l.autor LIKE ? OR l.clasificacion LIKE ?)";
            $busqueda_like = "%{$busqueda}%";
            $params[] = $busqueda_like;
            $params[] = $busqueda_like;
            $params[] = $busqueda_like;
        }
        
        // Filtro de categoría
        if ($id_categoria > 0) {
            $sql .= " AND c.id_categoria = ?";
            $params[] = $id_categoria;
        }
        
        // Filtro de estado
        if (!empty($estado)) {
            $sql .= " AND l.estado = ?";
            $params[] = $estado;
        }
        
        $sql .= " GROUP BY l.id_libro ORDER BY l.titulo ASC";
        
        // Contar total de resultados
        $stmt_count = $pdo->prepare("SELECT COUNT(DISTINCT l.id_libro) as total FROM libros l LEFT JOIN libro_categorias lc ON l.id_libro = lc.id_libro LEFT JOIN categorias c ON lc.id_categoria = c.id_categoria WHERE 1=1" . 
            (!empty($busqueda) ? " AND (l.titulo LIKE ? OR l.autor LIKE ? OR l.clasificacion LIKE ?)" : "") .
            ($id_categoria > 0 ? " AND c.id_categoria = ?" : "") .
            (!empty($estado) ? " AND l.estado = ?" : "")
        );
        $stmt_count->execute($params);
        $total_registros = $stmt_count->fetch()['total'];
        $total_paginas = ceil($total_registros / $por_pagina);
        
        // Obtener registros paginados
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $por_pagina;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $libros = $stmt->fetchAll();
        
        return [
            'exito' => true,
            'libros' => $libros,
            'paginacion' => [
                'pagina_actual' => $pagina,
                'total_paginas' => $total_paginas,
                'total_registros' => $total_registros,
                'por_pagina' => $por_pagina
            ]
        ];
        
    } catch (PDOException $e) {
        return ['exito' => false, 'mensaje' => 'Error al listar libros: ' . $e->getMessage()];
    }
}

/**
 * Agregar ejemplar a un libro
 */
function agregarEjemplar($pdo) {
    $id_libro = (int)($_POST['id_libro'] ?? 0);
    $cantidad = max(1, (int)($_POST['cantidad'] ?? 1));
    
    if ($id_libro <= 0) {
        return ['exito' => false, 'mensaje' => 'ID de libro inválido'];
    }
    
    try {
        // Obtener clasificación del libro
        $stmt = $pdo->prepare("SELECT clasificacion FROM libros WHERE id_libro = ?");
        $stmt->execute([$id_libro]);
        $libro = $stmt->fetch();
        
        if (!$libro) {
            return ['exito' => false, 'mensaje' => 'Libro no encontrado'];
        }
        
        // Obtener último número de ejemplar
        $stmt = $pdo->prepare("
            SELECT codigo_ejemplar FROM ejemplares_libros 
            WHERE id_libro = ? 
            ORDER BY id_ejemplar DESC LIMIT 1
        ");
        $stmt->execute([$id_libro]);
        $ultimo = $stmt->fetch();
        
        // Calcular siguiente número
        $siguiente_num = 1;
        if ($ultimo) {
            preg_match('/_(\d+)$/', $ultimo['codigo_ejemplar'], $matches);
            $siguiente_num = isset($matches[1]) ? (int)$matches[1] + 1 : 1;
        }
        
        // Crear nuevos ejemplares
        $stmt = $pdo->prepare("
            INSERT INTO ejemplares_libros (id_libro, codigo_ejemplar, estado) 
            VALUES (?, ?, 'disponible')
        ");
        
        for ($i = 0; $i < $cantidad; $i++) {
            $codigo = $libro['clasificacion'] . '_' . str_pad($siguiente_num + $i, 3, '0', STR_PAD_LEFT);
            $stmt->execute([$id_libro, $codigo]);
        }
        
        return [
            'exito' => true,
            'mensaje' => "{$cantidad} ejemplar(es) agregado(s) exitosamente"
        ];
        
    } catch (PDOException $e) {
        return ['exito' => false, 'mensaje' => 'Error al agregar ejemplares: ' . $e->getMessage()];
    }
}

/**
 * Eliminar un ejemplar
 */
function eliminarEjemplar($pdo) {
    $id_ejemplar = (int)($_POST['id_ejemplar'] ?? 0);
    
    if ($id_ejemplar <= 0) {
        return ['exito' => false, 'mensaje' => 'ID de ejemplar inválido'];
    }
    
    try {
        // Verificar si está prestado
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM prestamos 
            WHERE id_ejemplar = ? AND estado = 'activo'
        ");
        $stmt->execute([$id_ejemplar]);
        
        if ($stmt->fetch()['total'] > 0) {
            return ['exito' => false, 'mensaje' => 'No se puede eliminar. El ejemplar está prestado'];
        }
        
        // Eliminar ejemplar
        $stmt = $pdo->prepare("DELETE FROM ejemplares_libros WHERE id_ejemplar = ?");
        $stmt->execute([$id_ejemplar]);
        
        return ['exito' => true, 'mensaje' => 'Ejemplar eliminado exitosamente'];
        
    } catch (PDOException $e) {
        return ['exito' => false, 'mensaje' => 'Error al eliminar ejemplar: ' . $e->getMessage()];
    }
}

/**
 * Validar datos del libro
 */
function validarDatosLibro($datos, $id_libro = null) {
    $errores = [];
    
    // Título (requerido, 3-255 caracteres)
    if (empty($datos['titulo']) || strlen(trim($datos['titulo'])) < 3) {
        $errores[] = 'El título es requerido (mínimo 3 caracteres)';
    }
    
    // Autor (requerido, 3-255 caracteres)
    if (empty($datos['autor']) || strlen(trim($datos['autor'])) < 3) {
        $errores[] = 'El autor es requerido (mínimo 3 caracteres)';
    }
    
    // Clasificación (requerida)
    if (empty($datos['clasificacion'])) {
        $errores[] = 'La clasificación es requerida';
    }
    
    // Año de publicación (opcional pero válido)
    if (!empty($datos['año_publicacion'])) {
        $año = (int)$datos['año_publicacion'];
        if ($año < 1800 || $año > date('Y') + 1) {
            $errores[] = 'El año de publicación debe estar entre 1800 y ' . (date('Y') + 1);
        }
    }
    
    // ISBN (opcional pero válido si se proporciona)
    if (!empty($datos['isbn'])) {
        $isbn = preg_replace('/[^0-9X]/', '', $datos['isbn']);
        if (strlen($isbn) != 10 && strlen($isbn) != 13) {
            $errores[] = 'El ISBN debe tener 10 o 13 dígitos';
        }
    }
    
    return $errores;
}
?>
