<?php
/**
 * Dashboard para Docentes
 * Sistema de Gestión Bibliotecaria FISI
 */

require_once '../includes/session.php';
require_once '../config/database.php';
protegerPagina('docente');

$usuario = obtenerUsuarioActual();

// Obtener estadísticas del catálogo
try {
    $db = Database::getInstance();
    $pdo = $db->getConexion();
    
    // Contar libros únicos activos
    $stmt = $pdo->query("SELECT COUNT(*) FROM libros WHERE estado = 'activo'");
    $total_libros_activos = $stmt->fetchColumn();
    
    // Contar ejemplares disponibles
    $stmt = $pdo->query("SELECT COUNT(*) FROM ejemplares_libros WHERE estado = 'disponible'");
    $total_ejemplares_disponibles = $stmt->fetchColumn();
    
    // Contar préstamos activos del docente (preparado para HU-02)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM prestamos 
        WHERE id_usuario = ? AND estado = 'activo'
    ");
    $stmt->execute([$usuario['id_usuario']]);
    $prestamos_activos = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $total_libros_activos = 0;
    $total_ejemplares_disponibles = 0;
    $prestamos_activos = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Docente - Sistema Bibliotecario FISI</title>
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
                            <i class="fas fa-chalkboard-teacher me-1"></i>
                            Prof. <?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">
                            <span class="badge bg-warning text-dark">Docente</span>
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

    <div class="dashboard-container">
        <div class="container-fluid">
            <!-- Mensaje de Bienvenida -->
            <div class="row mb-4 fade-in">
                <div class="col-12">
                    <div class="alert alert-custom" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border: none;">
                        <h4 class="alert-heading">
                            <i class="fas fa-graduation-cap me-2"></i>
                            ¡Bienvenido, Profesor <?php echo htmlspecialchars($usuario['apellidos']); ?>!
                        </h4>
                        <p class="mb-0">Tiene acceso privilegiado como <strong>Docente</strong> con períodos de préstamo extendidos y mayor capacidad de solicitudes.</p>
                    </div>
                </div>
            </div>

            <!-- Tarjetas de Estadísticas -->
            <div class="row mb-4">
                <!-- Préstamos Activos -->
                <div class="col-md-3 mb-3">
                    <div class="stat-card docente">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-1"><?php echo $prestamos_activos; ?></h3>
                                <p class="mb-0">Préstamos Activos</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-book"></i>
                            </div>
                        </div>
                        <small class="text-light">Máximo: 5 libros</small>
                    </div>
                </div>

                <!-- Días de Préstamo -->
                <div class="col-md-3 mb-3">
                    <div class="stat-card docente">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-1">7</h3>
                                <p class="mb-0">Días de Préstamo</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-calendar-week"></i>
                            </div>
                        </div>
                        <small class="text-light">Período extendido</small>
                    </div>
                </div>

                <!-- Libros Disponibles -->
                <div class="col-md-3 mb-3">
                    <div class="stat-card docente">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-1"><?php echo number_format($total_ejemplares_disponibles); ?></h3>
                                <p class="mb-0">Ejemplares Disponibles</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-books"></i>
                            </div>
                        </div>
                        <small class="text-light"><?php echo number_format($total_libros_activos); ?> títulos únicos</small>
                    </div>
                </div>

                <!-- Renovaciones -->
                <div class="col-md-3 mb-3">
                    <div class="stat-card docente">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-1">1</h3>
                                <p class="mb-0">Renovaciones</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-redo"></i>
                            </div>
                        </div>
                        <small class="text-light">Por préstamo</small>
                    </div>
                </div>
            </div>

            <!-- Sección de Funcionalidades -->
            <div class="row">
                <!-- Menú de Acciones -->
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header text-white" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <h5 class="mb-0">
                                <i class="fas fa-tasks me-2"></i>
                                Acciones Rápidas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <a href="publico/catalogo_publico.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-search me-2 text-primary"></i>
                                    Buscar en el Catálogo
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <i class="fas fa-book-open me-2 text-success"></i>
                                    Mis Préstamos Activos
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <i class="fas fa-history me-2 text-info"></i>
                                    Historial Completo
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <i class="fas fa-bookmark me-2 text-warning"></i>
                                    Reservas Prioritarias
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <i class="fas fa-star me-2 text-danger"></i>
                                    Solicitar Material Especial
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del Usuario -->
                <div class="col-md-8 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header text-white" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <h5 class="mb-0">
                                <i class="fas fa-user-tie me-2"></i>
                                Información del Docente
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Nombre Completo:</strong></p>
                                    <p class="text-muted">Prof. <?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?></p>
                                    
                                    <p><strong>Usuario:</strong></p>
                                    <p class="text-muted"><?php echo htmlspecialchars($usuario['nombre_usuario']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Correo Institucional:</strong></p>
                                    <p class="text-muted"><?php echo htmlspecialchars($usuario['correo']); ?></p>
                                    
                                    <p><strong>Tipo de Usuario:</strong></p>
                                    <p><span class="badge bg-warning text-dark">Docente FISI</span></p>
                                </div>
                            </div>

                            <hr>

                            <div class="alert" style="background-color: #fff3cd; border-color: #f093fb;">
                                <h6 class="alert-heading">
                                    <i class="fas fa-crown me-2"></i>
                                    Privilegios de Docente
                                </h6>
                                <ul class="mb-0">
                                    <li>Capacidad: hasta <strong>5 libros simultáneos</strong></li>
                                    <li>Período de préstamo: <strong>7 días hábiles</strong></li>
                                    <li>Renovaciones: <strong>1 por préstamo</strong></li>
                                    <li>Prioridad en reservas sobre estudiantes</li>
                                    <li>Acceso a solicitudes especiales de material bibliográfico</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>