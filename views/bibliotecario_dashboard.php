<?php
/**
 * Dashboard para Bibliotecarios
 * Sistema de Gestión Bibliotecaria FISI
 */

require_once '../includes/session.php';
require_once '../config/database.php';
protegerPagina('bibliotecario');

$usuario = obtenerUsuarioActual();

// Obtener estadísticas del sistema
try {
    $db = Database::getInstance();
    $pdo = $db->getConexion();
    
    // Total de libros activos
    $stmt = $pdo->query("SELECT COUNT(*) FROM libros WHERE estado = 'activo'");
    $total_libros = $stmt->fetchColumn();
    
    // Total de ejemplares disponibles
    $stmt = $pdo->query("SELECT COUNT(*) FROM ejemplares_libros WHERE estado = 'disponible'");
    $ejemplares_disponibles = $stmt->fetchColumn();
    
    // Total de préstamos activos
    $stmt = $pdo->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'activo'");
    $prestamos_activos = $stmt->fetchColumn();
    
    // Total de usuarios estudiantes
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'estudiante'");
    $total_estudiantes = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $total_libros = 0;
    $ejemplares_disponibles = 0;
    $prestamos_activos = 0;
    $total_estudiantes = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Bibliotecario - Sistema Bibliotecario FISI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
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
                        <a class="nav-link" href="../controllers/logout_controller.php">
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
                            <a class="nav-link active" href="#">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="bibliotecario/registrar_prestamo.php">
                                <i class="fas fa-book-open me-2"></i>
                                Registrar Préstamo
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="bibliotecario/registrar_devolucion.php">
                                <i class="fas fa-undo me-2"></i>
                                Registrar Devolución
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="bibliotecario/renovar_prestamo.php">
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
                            <a class="nav-link" href="bibliotecario/buscar_libro.php">
                                <i class="fas fa-search me-2"></i>
                                Buscar Libro
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="bibliotecario/buscar_usuario.php">
                                <i class="fas fa-users me-2"></i>
                                Buscar Usuario
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="fas fa-list me-2"></i>
                                Préstamos Activos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="bibliotecario/prestamos_vencidos.php">
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
                            <a class="nav-link" href="#">
                                <i class="fas fa-chart-bar me-2"></i>
                                Estadísticas Diarias
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="bibliotecario/multas_pendientes.php">
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
                    <!-- Mensaje de Bienvenida -->
                    <div class="row mb-4 fade-in">
                        <div class="col-12">
                            <div class="alert alert-custom alert-primary">
                                <h4 class="alert-heading">
                                    <i class="fas fa-briefcase me-2"></i>
                                    Panel de Control - Bibliotecario
                                </h4>
                                <p class="mb-0">Bienvenido al panel operativo. Desde aquí puedes gestionar préstamos, devoluciones y consultar el estado del sistema.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjetas de Estadísticas -->
                    <div class="row mb-4">
                        <!-- Total de Libros -->
                        <div class="col-md-3 mb-3">
                            <div class="stat-card bibliotecario">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $total_libros; ?></h3>
                                        <p class="mb-0">Libros en Catálogo</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-books"></i>
                                    </div>
                                </div>
                                <small class="text-light">Títulos únicos</small>
                            </div>
                        </div>

                        <!-- Ejemplares Disponibles -->
                        <div class="col-md-3 mb-3">
                            <div class="stat-card bibliotecario">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $ejemplares_disponibles; ?></h3>
                                        <p class="mb-0">Ejemplares Disponibles</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-book"></i>
                                    </div>
                                </div>
                                <small class="text-light">Listos para préstamo</small>
                            </div>
                        </div>

                        <!-- Préstamos Activos -->
                        <div class="col-md-3 mb-3">
                            <div class="stat-card bibliotecario">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $prestamos_activos; ?></h3>
                                        <p class="mb-0">Préstamos Activos</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-book-open"></i>
                                    </div>
                                </div>
                                <small class="text-light">Sin devolver</small>
                            </div>
                        </div>

                        <!-- Total Usuarios -->
                        <div class="col-md-3 mb-3">
                            <div class="stat-card bibliotecario">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $total_estudiantes; ?></h3>
                                        <p class="mb-0">Usuarios Activos</p>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                                <small class="text-light">Estudiantes</small>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones Rápidas -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-bolt me-2"></i>
                                        Acciones Rápidas
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-3 mb-3">
                                            <a href="bibliotecario/registrar_prestamo.php" class="btn btn-outline-primary btn-lg w-100">
                                                <i class="fas fa-plus-circle fa-2x mb-2"></i><br>
                                                <span>Nuevo Préstamo</span>
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="bibliotecario/registrar_devolucion.php" class="btn btn-outline-success btn-lg w-100">
                                                <i class="fas fa-check-circle fa-2x mb-2"></i><br>
                                                <span>Registrar Devolución</span>
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="bibliotecario/buscar_libro.php" class="btn btn-outline-warning btn-lg w-100">
                                                <i class="fas fa-search fa-2x mb-2"></i><br>
                                                <span>Buscar Libro</span>
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="bibliotecario/prestamos_vencidos.php" class="btn btn-outline-danger btn-lg w-100">
                                                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
                                                <span>Ver Vencidos</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actividad Reciente -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-history me-2"></i>
                                        Actividad Reciente del Sistema
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($prestamos_activos == 0): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Sistema Operativo:</strong> No hay préstamos registrados aún. Las funcionalidades de préstamos y devoluciones se implementarán en la HU-02.
                                    </div>
                                    <?php else: ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <strong>Sistema Activo:</strong> Hay <?php echo $prestamos_activos; ?> préstamo(s) activo(s) en el sistema.
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>