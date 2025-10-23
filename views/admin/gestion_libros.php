<?php
/**
 * Gestión de Libros - Vista Principal
 * HU-05: Gestión de inventario
 * Sistema de Gestión Bibliotecaria FISI
 */

require_once '../../includes/session.php';
require_once '../../config/database.php';

protegerPagina(['administrador', 'bibliotecario']);

$usuario = obtenerUsuarioActual();

// Obtener parámetros de búsqueda y filtros
$busqueda = $_GET['busqueda'] ?? '';
$id_categoria = $_GET['id_categoria'] ?? '';
$estado = $_GET['estado'] ?? 'activo';
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

try {
    $db = Database::getInstance();
    $pdo = $db->getConexion();
    
    // Obtener categorías para filtros
    $stmt = $pdo->query("SELECT * FROM categorias ORDER BY nombre");
    $categorias = $stmt->fetchAll();
    
    // Construir query con filtros
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
    
    if (!empty($estado)) {
        $sql .= " AND l.estado = ?";
        $params[] = $estado;
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
        WHERE 1=1";
        
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
    if (!empty($estado)) {
        $sql_count .= " AND l.estado = ?";
        $params_count[] = $estado;
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
    <title>Gestión de Libros - Sistema Bibliotecario FISI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="../admin_dashboard.php">
                <i class="fas fa-book-reader me-2"></i>
                Sistema Bibliotecario FISI - Panel Administrativo
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="fas fa-user-shield me-1"></i>
                            <?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">
                            <span class="badge bg-success">Administrador</span>
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

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                        <span>DASHBOARD</span>
                    </h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../admin_dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Panel Principal
                            </a>
                        </li>
                    </ul>

                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                        <span>GESTIÓN DE INVENTARIO</span>
                    </h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="gestion_libros.php">
                                <i class="fas fa-book me-2"></i>
                                Gestión de Libros
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="libro_form.php">
                                <i class="fas fa-plus-circle me-2"></i>
                                Añadir Nuevo Libro
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="fas fa-copy me-2"></i>
                                Gestión de Ejemplares
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="fas fa-tags me-2"></i>
                                Categorías
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="fas fa-file-import me-2"></i>
                                Importar desde Excel
                            </a>
                        </li>
                    </ul>

                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                        <span>GESTIÓN DE USUARIOS</span>
                    </h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="fas fa-users me-2"></i>
                                Lista de Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="fas fa-user-plus me-2"></i>
                                Nuevo Usuario
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="fas fa-user-shield me-2"></i>
                                Gestión de Roles
                            </a>
                        </li>
                    </ul>

                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                        <span>SISTEMA</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="fas fa-cog me-2"></i>
                                Configuración
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../../prueba_conexion.php" target="_blank">
                                <i class="fas fa-database me-2"></i>
                                Estado del Sistema
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Contenido Principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="dashboard-container">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4 pt-3">
                        <div>
                            <h2><i class="fas fa-book me-2"></i>Gestión de Libros</h2>
                            <p class="text-muted mb-0">Administra el catálogo completo de la biblioteca</p>
                        </div>
                        <a href="libro_form.php" class="btn btn-success btn-lg">
                            <i class="fas fa-plus-circle me-2"></i>Agregar Libro
                        </a>
                    </div>

                    <!-- Mensajes -->
                    <?php if (isset($_SESSION['mensaje'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['tipo_mensaje'] ?? 'info'; ?> alert-dismissible fade show">
                            <?php echo htmlspecialchars($_SESSION['mensaje']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
                    <?php endif; ?>

                    <!-- Búsqueda y Filtros -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label"><i class="fas fa-search me-1"></i> Buscar</label>
                                    <input type="text" name="busqueda" class="form-control" 
                                           placeholder="Título, autor o clasificación..." 
                                           value="<?php echo htmlspecialchars($busqueda); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><i class="fas fa-tag me-1"></i> Categoría</label>
                                    <select name="id_categoria" class="form-select">
                                        <option value="">Todas las categorías</option>
                                        <?php foreach ($categorias as $cat): ?>
                                            <option value="<?php echo $cat['id_categoria']; ?>" 
                                                    <?php echo $id_categoria == $cat['id_categoria'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label"><i class="fas fa-toggle-on me-1"></i> Estado</label>
                                    <select name="estado" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="activo" <?php echo $estado == 'activo' ? 'selected' : ''; ?>>Activos</option>
                                        <option value="inactivo" <?php echo $estado == 'inactivo' ? 'selected' : ''; ?>>Inactivos</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end gap-2">
                                    <button type="submit" class="btn btn-primary flex-fill">
                                        <i class="fas fa-filter me-1"></i> Filtrar
                                    </button>
                                    <a href="gestion_libros.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i> Limpiar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tabla de Libros -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>
                                    <strong><?php echo number_format($total_registros); ?></strong> libro(s) encontrado(s)
                                </span>
                                <span class="text-muted">
                                    Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Título</th>
                                            <th>Autor</th>
                                            <th>Clasificación</th>
                                            <th>Año</th>
                                            <th>Categoría</th>
                                            <th class="text-center">Ejemplares</th>
                                            <th class="text-center">Disponibles</th>
                                            <th>Estado</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($libros)): ?>
                                            <tr>
                                                <td colspan="9" class="text-center py-5">
                                                    <i class="fas fa-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                                    <p class="text-muted mt-2">No se encontraron libros</p>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($libros as $libro): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($libro['titulo']); ?></strong>
                                                        <?php if ($libro['isbn']): ?>
                                                            <br><small class="text-muted">ISBN: <?php echo htmlspecialchars($libro['isbn']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($libro['autor']); ?></td>
                                                    <td><code><?php echo htmlspecialchars($libro['clasificacion']); ?></code></td>
                                                    <td><?php echo $libro['año_publicacion'] ?? '-'; ?></td>
                                                    <td>
                                                        <?php if ($libro['categoria']): ?>
                                                            <span class="badge bg-info"><?php echo htmlspecialchars($libro['categoria']); ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-secondary"><?php echo $libro['total_ejemplares']; ?></span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-success"><?php echo $libro['ejemplares_disponibles']; ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if ($libro['estado'] == 'activo'): ?>
                                                            <span class="badge bg-success">Activo</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Inactivo</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="libro_form.php?id=<?php echo $libro['id_libro']; ?>" 
                                                               class="btn btn-outline-primary" 
                                                               title="Editar">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="libro_ejemplares.php?id=<?php echo $libro['id_libro']; ?>" 
                                                               class="btn btn-outline-info" 
                                                               title="Ver ejemplares">
                                                                <i class="fas fa-list"></i>
                                                            </a>
                                                            <button type="button" 
                                                                    class="btn btn-outline-danger"
                                                                    onclick="confirmarEliminar(<?php echo $libro['id_libro']; ?>, '<?php echo htmlspecialchars($libro['titulo'], ENT_QUOTES); ?>')"
                                                                    title="Eliminar">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Paginación -->
                        <?php if ($total_paginas > 1): ?>
                            <div class="card-footer bg-white">
                                <nav>
                                    <ul class="pagination pagination-sm justify-content-center mb-0">
                                        <li class="page-item <?php echo $pagina == 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?pagina=1&busqueda=<?php echo urlencode($busqueda); ?>&id_categoria=<?php echo $id_categoria; ?>&estado=<?php echo $estado; ?>">
                                                <i class="fas fa-angle-double-left"></i>
                                            </a>
                                        </li>
                                        <li class="page-item <?php echo $pagina == 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>&id_categoria=<?php echo $id_categoria; ?>&estado=<?php echo $estado; ?>">
                                                <i class="fas fa-angle-left"></i>
                                            </a>
                                        </li>

                                        <?php 
                                        $inicio = max(1, $pagina - 2);
                                        $fin = min($total_paginas, $pagina + 2);
                                        for ($i = $inicio; $i <= $fin; $i++): 
                                        ?>
                                            <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                                                <a class="page-link" href="?pagina=<?php echo $i; ?>&busqueda=<?php echo urlencode($busqueda); ?>&id_categoria=<?php echo $id_categoria; ?>&estado=<?php echo $estado; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>

                                        <li class="page-item <?php echo $pagina == $total_paginas ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?>&busqueda=<?php echo urlencode($busqueda); ?>&id_categoria=<?php echo $id_categoria; ?>&estado=<?php echo $estado; ?>">
                                                <i class="fas fa-angle-right"></i>
                                            </a>
                                        </li>
                                        <li class="page-item <?php echo $pagina == $total_paginas ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?pagina=<?php echo $total_paginas; ?>&busqueda=<?php echo urlencode($busqueda); ?>&id_categoria=<?php echo $id_categoria; ?>&estado=<?php echo $estado; ?>">
                                                <i class="fas fa-angle-double-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmarEliminar(id, titulo) {
            if (confirm('¿Estás seguro de eliminar el libro "' + titulo + '"?\n\nEsta acción marcará el libro como inactivo.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../../controllers/libro_controller.php';
                
                const inputAccion = document.createElement('input');
                inputAccion.type = 'hidden';
                inputAccion.name = 'accion';
                inputAccion.value = 'eliminar';
                
                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'id_libro';
                inputId.value = id;
                
                form.appendChild(inputAccion);
                form.appendChild(inputId);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
