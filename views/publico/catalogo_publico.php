<?php
/**
 * Catálogo Público de Libros
 * HU-01: Catálogo de libros disponibles
 * Sistema de Gestión Bibliotecaria FISI
 * Accesible para: Estudiantes y Docentes
 */

require_once '../../includes/session.php';
require_once '../../config/database.php';

protegerPagina(['estudiante', 'docente']);

$usuario = obtenerUsuarioActual();
$es_estudiante = ($usuario['rol'] === 'estudiante');

// Obtener parámetros de búsqueda y filtros
$busqueda = $_GET['busqueda'] ?? '';
$id_categoria = $_GET['id_categoria'] ?? '';
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

try {
    $db = Database::getInstance();
    $pdo = $db->getConexion();
    
    // Obtener categorías para filtros
    $stmt = $pdo->query("SELECT * FROM categorias ORDER BY nombre");
    $categorias = $stmt->fetchAll();
    
    // Construir query con filtros (solo libros activos)
    $sql = "
        SELECT 
            l.id_libro,
            l.titulo,
            l.autor,
            l.clasificacion,
            l.año_publicacion,
            l.isbn,
            l.editorial,
            l.descripcion,
            c.nombre as categoria,
            COUNT(DISTINCT e.id_ejemplar) as total_ejemplares,
            SUM(CASE WHEN e.estado = 'disponible' THEN 1 ELSE 0 END) as ejemplares_disponibles
        FROM libros l
        LEFT JOIN libro_categorias lc ON l.id_libro = lc.id_libro
        LEFT JOIN categorias c ON lc.id_categoria = c.id_categoria
        LEFT JOIN ejemplares_libros e ON l.id_libro = e.id_libro
        WHERE l.estado = 'activo'
    ";
    
    $params = [];
    
    if (!empty($busqueda)) {
        $sql .= " AND (l.titulo LIKE ? OR l.autor LIKE ? OR l.clasificacion LIKE ?)";
        $like = "%{$busqueda}%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    
    if (!empty($id_categoria)) {
        $sql .= " AND c.id_categoria = ?";
        $params[] = $id_categoria;
    }
    
    $sql .= " GROUP BY l.id_libro ORDER BY l.titulo ASC LIMIT ? OFFSET ?";
    $params[] = $por_pagina;
    $params[] = $offset;
    
    // Ejecutar query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $libros = $stmt->fetchAll();
    
    // Contar total
    $sql_count = "SELECT COUNT(DISTINCT l.id_libro) as total FROM libros l 
        LEFT JOIN libro_categorias lc ON l.id_libro = lc.id_libro
        LEFT JOIN categorias c ON lc.id_categoria = c.id_categoria
        WHERE l.estado = 'activo'";
        
    $params_count = [];
    if (!empty($busqueda)) {
        $sql_count .= " AND (l.titulo LIKE ? OR l.autor LIKE ? OR l.clasificacion LIKE ?)";
        $params_count[] = $like;
        $params_count[] = $like;
        $params_count[] = $like;
    }
    if (!empty($id_categoria)) {
        $sql_count .= " AND c.id_categoria = ?";
        $params_count[] = $id_categoria;
    }
    
    $stmt = $pdo->prepare($sql_count);
    $stmt->execute($params_count);
    $total_registros = $stmt->fetch()['total'];
    $total_paginas = ceil($total_registros / $por_pagina);
    
} catch (PDOException $e) {
    $libros = [];
    $categorias = [];
    $total_registros = 0;
    $total_paginas = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Libros - Sistema Bibliotecario FISI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .book-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        .disponibilidad-badge {
            font-size: 0.9rem;
            padding: 0.4rem 0.8rem;
        }
        .libro-descripcion {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            max-height: 3em;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="../<?php echo $es_estudiante ? 'estudiante' : 'docente'; ?>_dashboard.php">
                <i class="fas fa-book-reader me-2"></i>
                Sistema Bibliotecario FISI
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">
                            <span class="badge bg-<?php echo $es_estudiante ? 'light text-dark' : 'warning'; ?>">
                                <?php echo ucfirst($usuario['rol']); ?>
                            </span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../../controllers/logout_controller.php">
                            <i class="fas fa-sign-out-alt me-1"></i> Salir
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="fas fa-books me-2"></i>Catálogo de Libros</h2>
                <p class="text-muted mb-0">Explora nuestra colección y encuentra tu próxima lectura</p>
            </div>
            <a href="../<?php echo $es_estudiante ? 'estudiante' : 'docente'; ?>_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>

        <!-- Búsqueda y Filtros -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-search me-1"></i> Buscar</label>
                        <input type="text" 
                               name="busqueda" 
                               class="form-control form-control-lg" 
                               placeholder="Busca por título, autor o clasificación..." 
                               value="<?php echo htmlspecialchars($busqueda); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-tag me-1"></i> Categoría</label>
                        <select name="id_categoria" class="form-select form-select-lg">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id_categoria']; ?>" 
                                        <?php echo $id_categoria == $cat['id_categoria'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary btn-lg flex-fill">
                            <i class="fas fa-search me-1"></i> Buscar
                        </button>
                        <a href="catalogo_publico.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resultados -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-book-open me-2"></i>
                        Resultados: <strong><?php echo number_format($total_registros); ?></strong> libro(s) encontrado(s)
                    </h5>
                    <?php if ($total_paginas > 1): ?>
                        <span class="text-muted">
                            Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($libros)): ?>
                    <!-- Sin resultados -->
                    <div class="text-center py-5">
                        <i class="fas fa-search" style="font-size: 4rem; color: #ddd;"></i>
                        <h4 class="mt-3 text-muted">No se encontraron libros</h4>
                        <p class="text-muted">Intenta con otros términos de búsqueda o categorías</p>
                        <a href="catalogo_publico.php" class="btn btn-primary mt-2">
                            <i class="fas fa-redo me-2"></i>Ver todo el catálogo
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Grid de Libros -->
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php foreach ($libros as $libro): ?>
                            <div class="col">
                                <div class="card h-100 book-card">
                                    <div class="card-body">
                                        <!-- Título -->
                                        <h5 class="card-title text-primary">
                                            <?php echo htmlspecialchars($libro['titulo']); ?>
                                        </h5>
                                        
                                        <!-- Autor -->
                                        <h6 class="card-subtitle mb-2 text-muted">
                                            <i class="fas fa-user-edit me-1"></i>
                                            <?php echo htmlspecialchars($libro['autor']); ?>
                                        </h6>
                                        
                                        <!-- Clasificación y Año -->
                                        <p class="mb-2">
                                            <small>
                                                <i class="fas fa-bookmark me-1"></i>
                                                <code><?php echo htmlspecialchars($libro['clasificacion']); ?></code>
                                                <?php if ($libro['año_publicacion']): ?>
                                                    | <i class="fas fa-calendar me-1"></i><?php echo $libro['año_publicacion']; ?>
                                                <?php endif; ?>
                                            </small>
                                        </p>
                                        
                                        <!-- Categoría -->
                                        <?php if ($libro['categoria']): ?>
                                            <p class="mb-2">
                                                <span class="badge bg-info">
                                                    <i class="fas fa-tag me-1"></i>
                                                    <?php echo htmlspecialchars($libro['categoria']); ?>
                                                </span>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <!-- Descripción -->
                                        <?php if ($libro['descripcion']): ?>
                                            <p class="libro-descripcion text-muted small mb-3">
                                                <?php echo htmlspecialchars($libro['descripcion']); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <!-- Editorial e ISBN -->
                                        <?php if ($libro['editorial'] || $libro['isbn']): ?>
                                            <small class="text-muted d-block mb-3">
                                                <?php if ($libro['editorial']): ?>
                                                    <i class="fas fa-building me-1"></i>
                                                    <?php echo htmlspecialchars($libro['editorial']); ?>
                                                    <?php if ($libro['isbn']): ?><br><?php endif; ?>
                                                <?php endif; ?>
                                                <?php if ($libro['isbn']): ?>
                                                    <i class="fas fa-barcode me-1"></i>
                                                    ISBN: <?php echo htmlspecialchars($libro['isbn']); ?>
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                        
                                        <hr>
                                        
                                        <!-- Disponibilidad -->
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <small class="text-muted d-block">Disponibilidad</small>
                                                <?php if ($libro['ejemplares_disponibles'] > 0): ?>
                                                    <span class="badge bg-success disponibilidad-badge">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        <?php echo $libro['ejemplares_disponibles']; ?> disponible(s)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger disponibilidad-badge">
                                                        <i class="fas fa-times-circle me-1"></i>
                                                        No disponible
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">
                                                Total: <?php echo $libro['total_ejemplares']; ?> ejemplar(es)
                                            </small>
                                        </div>
                                        
                                        <!-- Nota informativa -->
                                        <?php if ($libro['ejemplares_disponibles'] > 0): ?>
                                            <div class="alert alert-info mt-3 mb-0 py-2">
                                                <small>
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Disponible para préstamo.
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <div class="card-footer bg-white">
                    <nav>
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?php echo $pagina == 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=1&busqueda=<?php echo urlencode($busqueda); ?>&id_categoria=<?php echo $id_categoria; ?>">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            <li class="page-item <?php echo $pagina == 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>&id_categoria=<?php echo $id_categoria; ?>">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>

                            <?php 
                            $inicio = max(1, $pagina - 2);
                            $fin = min($total_paginas, $pagina + 2);
                            for ($i = $inicio; $i <= $fin; $i++): 
                            ?>
                                <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $i; ?>&busqueda=<?php echo urlencode($busqueda); ?>&id_categoria=<?php echo $id_categoria; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <li class="page-item <?php echo $pagina == $total_paginas ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>&id_categoria=<?php echo $id_categoria; ?>">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                            <li class="page-item <?php echo $pagina == $total_paginas ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $total_paginas; ?>&busqueda=<?php echo urlencode($busqueda); ?>&id_categoria=<?php echo $id_categoria; ?>">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>