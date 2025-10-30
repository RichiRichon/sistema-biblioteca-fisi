<?php
/**
 * Buscar Libro - Vista Bibliotecario
 * Sistema de Gestión Bibliotecaria FISI
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/database.php';
protegerPagina(['bibliotecario', 'administrador']);

$usuario = obtenerUsuarioActual();

try {
    $db = Database::getInstance();
    $pdo = $db->getConexion();
    
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT l.id_libro) as total_titulos,
            COUNT(e.id_ejemplar) as total_ejemplares,
            SUM(CASE WHEN e.estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
            SUM(CASE WHEN e.estado = 'prestado' THEN 1 ELSE 0 END) as prestados
        FROM libros l
        LEFT JOIN ejemplares_libros e ON l.id_libro = e.id_libro
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $stats = ['total_titulos' => 0, 'total_ejemplares' => 0, 'disponibles' => 0, 'prestados' => 0];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Libro - Sistema Bibliotecario FISI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .libro-card {
            cursor: pointer;
            transition: all 0.2s;
            border-left: 4px solid #17a2b8;
        }
        .libro-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .badge-disponible { background: #28a745; }
        .badge-prestado { background: #ffc107; color: #000; }
        .badge-no-disponible { background: #dc3545; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="../bibliotecario_dashboard.php">
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
                            <i class="fas fa-user-tie me-1"></i>
                            <?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">
                            <span class="badge bg-info text-dark">Bibliotecario</span>
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
                        <span>GESTIÓN</span>
                    </h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../bibliotecario_dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="registrar_prestamo.php">
                                <i class="fas fa-book-open me-2"></i>
                                Registrar Préstamo
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="registrar_devolucion.php">
                                <i class="fas fa-undo me-2"></i>
                                Registrar Devolución
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="renovar_prestamo.php">
                                <i class="fas fa-redo me-2"></i>
                                Renovar Préstamo
                            </a>
                        </li>
                    </ul>

                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                        <span>CONSULTAS</span>
                    </h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#">
                                <i class="fas fa-search me-2"></i>
                                Buscar Libro
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="buscar_usuario.php">
                                <i class="fas fa-users me-2"></i>
                                Buscar Usuario
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="prestamos_activos.php">
                                <i class="fas fa-list me-2"></i>
                                Préstamos Activos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="prestamos_vencidos.php">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Préstamos Vencidos
                            </a>
                        </li>
                    </ul>

                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                        <span>REPORTES</span>
                    </h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="multas_pendientes.php">
                                <i class="fas fa-dollar-sign me-2"></i>
                                Multas Pendientes
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Contenido Principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="dashboard-container">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
                        <div>
                            <h2><i class="fas fa-search me-2"></i>Buscar Libro</h2>
                            <p class="text-muted mb-0">Consulta el catálogo y disponibilidad de libros</p>
                        </div>
                        <a href="../bibliotecario_dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>

                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card border-primary">
                                <div class="card-body text-center">
                                    <h3 class="text-primary mb-0"><?php echo $stats['total_titulos']; ?></h3>
                                    <p class="mb-0">Títulos Únicos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <h3 class="text-info mb-0"><?php echo $stats['total_ejemplares']; ?></h3>
                                    <p class="mb-0">Total Ejemplares</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <h3 class="text-success mb-0"><?php echo $stats['disponibles']; ?></h3>
                                    <p class="mb-0">Disponibles</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-warning">
                                <div class="card-body text-center">
                                    <h3 class="text-warning mb-0"><?php echo $stats['prestados']; ?></h3>
                                    <p class="mb-0">Prestados</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Búsqueda -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-search me-2"></i>
                                Búsqueda en el Catálogo
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <input type="text" 
                                           class="form-control" 
                                           id="buscar-libro" 
                                           placeholder="Buscar por título, autor, ISBN, editorial, clasificación o categoría...">
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="filtro-disponibilidad">
                                        <option value="todos">Todos los libros</option>
                                        <option value="disponibles">Solo disponibles</option>
                                        <option value="prestados">Solo prestados</option>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <button class="btn btn-primary w-100" onclick="buscarLibros()">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">Presiona Enter para buscar</small>
                        </div>
                    </div>

                    <!-- Resultados -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-book me-2"></i>
                                Resultados de la Búsqueda
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="loading" class="text-center py-4" style="display:none;">
                                <div class="spinner-border text-primary"></div>
                                <p class="mt-2">Buscando libros...</p>
                            </div>
                            
                            <div id="resultados-libros">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Utiliza el buscador para encontrar libros en el catálogo
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Detalle del Libro -->
    <div class="modal fade" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-book me-2"></i>
                        Detalle del Libro
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detalle-libro">
                    <!-- Se llenará dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Buscar al presionar Enter
        document.getElementById('buscar-libro').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') buscarLibros();
        });

        function buscarLibros() {
            const termino = document.getElementById('buscar-libro').value.trim();
            const filtro = document.getElementById('filtro-disponibilidad').value;
            
            document.getElementById('loading').style.display = 'block';
            document.getElementById('resultados-libros').innerHTML = '';

            let url = `../../controllers/libro_controller.php?accion=listar&busqueda=${encodeURIComponent(termino)}`;
            if (filtro !== 'todos') {
                url += `&estado=${filtro}`;
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loading').style.display = 'none';
                    
                    if (data.exito) {
                        mostrarResultados(data.libros);
                    } else {
                        document.getElementById('resultados-libros').innerHTML = `
                            <div class="alert alert-danger">${data.mensaje}</div>
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('resultados-libros').innerHTML = `
                        <div class="alert alert-danger">Error de conexión</div>
                    `;
                });
        }

        function mostrarResultados(libros) {
            const container = document.getElementById('resultados-libros');
            
            if (libros.length === 0) {
                container.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No se encontraron libros con los criterios de búsqueda
                    </div>
                `;
                return;
            }

            let html = `<p class="text-muted mb-3">Se encontraron ${libros.length} resultado(s)</p>`;
            
            libros.forEach(libro => {
                const disponibilidad = parseInt(libro.ejemplares_disponibles || 0) > 0 ? 'disponible' : 'no-disponible';
                
                html += `
                    <div class="card libro-card mb-3" onclick="verDetalle(${libro.id_libro})">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5 class="mb-2">${libro.titulo}</h5>
                                    <p class="mb-1"><strong>Autor:</strong> ${libro.autor}</p>
                                    <p class="mb-1">
                                        <small class="text-muted">
                                            <strong>Editorial:</strong> ${libro.editorial || 'N/A'} | 
                                            <strong>Clasificación:</strong> ${libro.clasificacion || 'N/A'}
                                        </small>
                                    </p>
                                    ${libro.categoria ? `<span class="badge bg-info">${libro.categoria}</span>` : ''}
                                </div>
                                <div class="col-md-4 text-end">
                                    <h6 class="mb-2">Disponibilidad:</h6>
                                    <span class="badge badge-disponible me-1">${libro.ejemplares_disponibles || 0} disponibles</span>
                                    <p class="mt-2 mb-0"><small class="text-muted">Total: ${libro.total_ejemplares || 0} ejemplar(es)</small></p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        function verDetalle(id_libro) {
            fetch(`../../controllers/libro_controller.php?accion=obtener&id_libro=${id_libro}`)
                .then(response => response.json())
                .then(data => {
                    if (data.exito) {
                        mostrarDetalle(data.libro, data.ejemplares);
                        new bootstrap.Modal(document.getElementById('modalDetalle')).show();
                    }
                });
        }

        function mostrarDetalle(libro, ejemplares) {
            let html = `
                <div class="row mb-3">
                    <div class="col-md-12">
                        <h4>${libro.titulo}</h4>
                        <p class="text-muted mb-3">${libro.autor}</p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Editorial:</strong> ${libro.editorial || 'N/A'}</p>
                                <p><strong>Año:</strong> ${libro.anio_publicacion || 'N/A'}</p>
                                <p><strong>ISBN:</strong> ${libro.isbn || 'N/A'}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Clasificación:</strong> ${libro.clasificacion || 'N/A'}</p>
                                <p><strong>Categoría:</strong> ${libro.categoria || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <h5 class="mb-3">Ejemplares (${ejemplares.length})</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Estado</th>
                                <th>Ubicación</th>
                                <th>Información</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            ejemplares.forEach(ej => {
                const estadoBadge = {
                    'disponible': '<span class="badge bg-success">Disponible</span>',
                    'prestado': '<span class="badge bg-warning text-dark">Prestado</span>',
                    'mantenimiento': '<span class="badge bg-info">Mantenimiento</span>',
                    'dañado': '<span class="badge bg-danger">Dañado</span>'
                };

                let info = '';
                if (ej.estado === 'prestado' && ej.prestado_a) {
                    info = `Prestado a: ${ej.prestado_a}<br>Devuelve: ${new Date(ej.fecha_devolucion).toLocaleDateString('es-PE')}`;
                }

                html += `
                    <tr>
                        <td><code>${ej.codigo_ejemplar}</code></td>
                        <td>${estadoBadge[ej.estado]}</td>
                        <td>${ej.ubicacion || 'N/A'}</td>
                        <td><small>${info}</small></td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            document.getElementById('detalle-libro').innerHTML = html;
        }
    </script>
</body>
</html>