<?php
/**
 * Dashboard para Estudiantes
 * Sistema de Gestión Bibliotecaria FISI
 */

require_once '../includes/session.php';
protegerPagina('estudiante');

$usuario = obtenerUsuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Estudiante - Sistema Bibliotecario FISI</title>
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
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">
                            <span class="badge bg-light text-dark">Estudiante</span>
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
                    <div class="alert alert-custom alert-info">
                        <h4 class="alert-heading">
                            <i class="fas fa-hand-wave me-2"></i>
                            ¡Bienvenido, <?php echo htmlspecialchars($usuario['nombres']); ?>!
                        </h4>
                        <p class="mb-0">Has iniciado sesión como <strong>Estudiante</strong>. Aquí puedes consultar el catálogo, ver tus préstamos activos y renovar libros.</p>
                    </div>
                </div>
            </div>

            <!-- Tarjetas de Estadísticas -->
            <div class="row mb-4">
                <!-- Préstamos Activos -->
                <div class="col-md-4 mb-3">
                    <div class="stat-card estudiante">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-1">0</h3>
                                <p class="mb-0">Préstamos Activos</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-book"></i>
                            </div>
                        </div>
                        <small class="text-light">Máximo: 3 libros simultáneos</small>
                    </div>
                </div>

                <!-- Libros Disponibles -->
                <div class="col-md-4 mb-3">
                    <div class="stat-card estudiante">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-1">27</h3>
                                <p class="mb-0">Libros Disponibles</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-books"></i>
                            </div>
                        </div>
                        <small class="text-light">En todo el catálogo</small>
                    </div>
                </div>

                <!-- Días de Préstamo -->
                <div class="col-md-4 mb-3">
                    <div class="stat-card estudiante">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-1">3</h3>
                                <p class="mb-0">Días de Préstamo</p>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                        </div>
                        <small class="text-light">Período estándar</small>
                    </div>
                </div>
            </div>

            <!-- Sección de Funcionalidades -->
            <div class="row">
                <!-- Menú de Acciones -->
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-tasks me-2"></i>
                                Acciones Rápidas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <a href="#" class="list-group-item list-group-item-action">
                                    <i class="fas fa-search me-2 text-primary"></i>
                                    Buscar en el Catálogo
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <i class="fas fa-book-open me-2 text-success"></i>
                                    Mis Préstamos Activos
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <i class="fas fa-history me-2 text-info"></i>
                                    Historial de Préstamos
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <i class="fas fa-bookmark me-2 text-warning"></i>
                                    Mis Reservas
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del Usuario -->
                <div class="col-md-8 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-user-circle me-2"></i>
                                Mi Información
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Nombre Completo:</strong></p>
                                    <p class="text-muted"><?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?></p>
                                    
                                    <p><strong>Usuario:</strong></p>
                                    <p class="text-muted"><?php echo htmlspecialchars($usuario['nombre_usuario']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Correo Electrónico:</strong></p>
                                    <p class="text-muted"><?php echo htmlspecialchars($usuario['correo']); ?></p>
                                    
                                    <p><strong>Tipo de Usuario:</strong></p>
                                    <p><span class="badge bg-primary">Estudiante</span></p>
                                </div>
                            </div>

                            <hr>

                            <div class="alert alert-info mb-0">
                                <h6 class="alert-heading">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Información Importante
                                </h6>
                                <ul class="mb-0">
                                    <li>Puedes solicitar hasta <strong>3 libros simultáneos</strong></li>
                                    <li>Período de préstamo: <strong>3 días hábiles</strong></li>
                                    <li>Puedes renovar <strong>1 vez</strong> cada préstamo</li>
                                    <li>Multa por retraso: <strong>S/. 1.00 por día</strong></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nota de Funcionalidad Futura -->
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-warning">
                        <i class="fas fa-wrench me-2"></i>
                        <strong>Nota:</strong> Las funcionalidades de búsqueda, préstamos y reservas se implementarán en las siguientes iteraciones (HU-01 y HU-02).
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>