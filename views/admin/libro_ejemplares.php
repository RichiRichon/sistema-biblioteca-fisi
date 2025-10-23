<?php
/**
 * Gestión de Ejemplares de un Libro
 * HU-05: Gestión de inventario
 * Sistema de Gestión Bibliotecaria FISI
 */

require_once '../../includes/session.php';
require_once '../../config/database.php';

protegerPagina(['administrador', 'bibliotecario']);

$usuario = obtenerUsuarioActual();
$id_libro = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_libro <= 0) {
    $_SESSION['mensaje'] = 'ID de libro inválido';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: gestion_libros.php');
    exit;
}

try {
    $db = Database::getInstance();
    $pdo = $db->getConexion();
    
    // Obtener información del libro
    $stmt = $pdo->prepare("
        SELECT l.*, c.nombre as categoria
        FROM libros l
        LEFT JOIN libro_categorias lc ON l.id_libro = lc.id_libro
        LEFT JOIN categorias c ON lc.id_categoria = c.id_categoria
        WHERE l.id_libro = ?
    ");
    $stmt->execute([$id_libro]);
    $libro = $stmt->fetch();
    
    if (!$libro) {
        $_SESSION['mensaje'] = 'Libro no encontrado';
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: gestion_libros.php');
        exit;
    }
    
    // Obtener ejemplares del libro
    $stmt = $pdo->prepare("
        SELECT 
            e.*,
            CASE 
                WHEN p.id_prestamo IS NOT NULL AND p.estado = 'activo' THEN CONCAT('Prestado a: ', u.nombres, ' ', u.apellidos)
                ELSE NULL
            END as info_prestamo
        FROM ejemplares_libros e
        LEFT JOIN prestamos p ON e.id_ejemplar = p.id_ejemplar AND p.estado = 'activo'
        LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
        WHERE e.id_libro = ?
        ORDER BY e.codigo_ejemplar
    ");
    $stmt->execute([$id_libro]);
    $ejemplares = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al cargar los datos';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: gestion_libros.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ejemplares - <?php echo htmlspecialchars($libro['titulo']); ?></title>
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
                    </ul>
                </div>
            </nav>

            <!-- Contenido Principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="dashboard-container">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4 pt-3">
                        <div>
                            <h2><i class="fas fa-copy me-2"></i>Ejemplares del Libro</h2>
                            <p class="text-muted mb-0">Gestiona las copias físicas disponibles</p>
                        </div>
                        <a href="gestion_libros.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver a la Lista
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

                    <!-- Información del Libro -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4><?php echo htmlspecialchars($libro['titulo']); ?></h4>
                                    <p class="mb-1"><strong>Autor:</strong> <?php echo htmlspecialchars($libro['autor']); ?></p>
                                    <p class="mb-1"><strong>Clasificación:</strong> <code><?php echo htmlspecialchars($libro['clasificacion']); ?></code></p>
                                    <?php if ($libro['categoria']): ?>
                                        <p class="mb-0"><strong>Categoría:</strong> <span class="badge bg-info"><?php echo htmlspecialchars($libro['categoria']); ?></span></p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 text-end">
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAgregar">
                                        <i class="fas fa-plus me-2"></i>Agregar Ejemplares
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Ejemplares -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>
                                Ejemplares Registrados (<?php echo count($ejemplares); ?>)
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($ejemplares)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                    <p class="text-muted mt-3">No hay ejemplares registrados</p>
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAgregar">
                                        <i class="fas fa-plus me-2"></i>Agregar Primer Ejemplar
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Código del Ejemplar</th>
                                                <th>Estado</th>
                                                <th>Información</th>
                                                <th>Fecha de Registro</th>
                                                <th class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ejemplares as $ejemplar): ?>
                                                <tr>
                                                    <td><code><?php echo htmlspecialchars($ejemplar['codigo_ejemplar']); ?></code></td>
                                                    <td>
                                                        <?php
                                                        $badge_class = [
                                                            'disponible' => 'success',
                                                            'prestado' => 'warning',
                                                            'mantenimiento' => 'info',
                                                            'perdido' => 'danger'
                                                        ];
                                                        $estado_text = [
                                                            'disponible' => 'Disponible',
                                                            'prestado' => 'Prestado',
                                                            'mantenimiento' => 'Mantenimiento',
                                                            'perdido' => 'Perdido'
                                                        ];
                                                        ?>
                                                        <span class="badge bg-<?php echo $badge_class[$ejemplar['estado']]; ?>">
                                                            <?php echo $estado_text[$ejemplar['estado']]; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($ejemplar['info_prestamo']): ?>
                                                            <small class="text-muted"><?php echo htmlspecialchars($ejemplar['info_prestamo']); ?></small>
                                                        <?php elseif ($ejemplar['observaciones']): ?>
                                                            <small class="text-muted"><?php echo htmlspecialchars($ejemplar['observaciones']); ?></small>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo date('d/m/Y', strtotime($ejemplar['fecha_creacion'])); ?></td>
                                                    <td class="text-center">
                                                        <?php if ($ejemplar['estado'] == 'disponible'): ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-danger"
                                                                    onclick="confirmarEliminarEjemplar(<?php echo $ejemplar['id_ejemplar']; ?>, '<?php echo htmlspecialchars($ejemplar['codigo_ejemplar'], ENT_QUOTES); ?>')">
                                                                <i class="fas fa-trash"></i> Eliminar
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-muted"><small>No disponible</small></span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Agregar Ejemplares -->
    <div class="modal fade" id="modalAgregar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="../../controllers/libro_controller.php">
                    <input type="hidden" name="accion" value="agregar_ejemplar">
                    <input type="hidden" name="id_libro" value="<?php echo $id_libro; ?>">
                    
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Agregar Ejemplares</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="cantidad" class="form-label">Cantidad de ejemplares a agregar</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="cantidad" 
                                   name="cantidad" 
                                   min="1" 
                                   max="20" 
                                   value="1" 
                                   required>
                            <div class="form-text">
                                Se generarán códigos automáticamente basados en la clasificación del libro
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Agregar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmarEliminarEjemplar(id, codigo) {
            if (confirm('¿Estás seguro de eliminar el ejemplar ' + codigo + '?\n\nEsta acción no se puede deshacer.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../../controllers/libro_controller.php';
                
                const inputAccion = document.createElement('input');
                inputAccion.type = 'hidden';
                inputAccion.name = 'accion';
                inputAccion.value = 'eliminar_ejemplar';
                
                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'id_ejemplar';
                inputId.value = id;
                
                const inputRedirect = document.createElement('input');
                inputRedirect.type = 'hidden';
                inputRedirect.name = 'redirect';
                inputRedirect.value = window.location.href;
                
                form.appendChild(inputAccion);
                form.appendChild(inputId);
                form.appendChild(inputRedirect);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
