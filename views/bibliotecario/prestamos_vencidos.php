<?php
/**
 * Préstamos Vencidos - Vista Bibliotecario
 * HU-02: Préstamos y Devoluciones - FASE 2
 * Sistema de Gestión Bibliotecaria FISI
 */

require_once '../../includes/session.php';
protegerPagina(['bibliotecario', 'administrador']);

$usuario = obtenerUsuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Préstamos Vencidos - Sistema Bibliotecario FISI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
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
                            <a class="nav-link" href="#">
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
                            <a class="nav-link" href="#">
                                <i class="fas fa-search me-2"></i>
                                Buscar Libro
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
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
                            <a class="nav-link active" href="#">
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
                            <a class="nav-link" href="#">
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
                            <h2><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Préstamos Vencidos</h2>
                            <p class="text-muted mb-0">Lista de préstamos que superaron la fecha de devolución</p>
                        </div>
                        <div>
                            <button class="btn btn-primary" onclick="cargarVencidos()">
                                <i class="fas fa-sync-alt me-2"></i>Actualizar
                            </button>
                            <a href="../bibliotecario_dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Volver
                            </a>
                        </div>
                    </div>

                    <!-- Estadísticas Rápidas -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card border-danger">
                                <div class="card-body text-center">
                                    <h2 class="text-danger mb-0" id="total-vencidos">0</h2>
                                    <p class="mb-0">Préstamos Vencidos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-warning">
                                <div class="card-body text-center">
                                    <h2 class="text-warning mb-0" id="total-multas">S/. 0.00</h2>
                                    <p class="mb-0">Multas Totales</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <h2 class="text-info mb-0" id="promedio-dias">0</h2>
                                    <p class="mb-0">Promedio Días Retraso</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Préstamos Vencidos -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>
                                Lista de Préstamos Vencidos
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="loading" class="text-center py-4" style="display:none;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p class="mt-2">Cargando préstamos vencidos...</p>
                            </div>
                            
                            <div id="contenido-tabla">
                                <!-- Se llenará dinámicamente -->
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cargar automáticamente al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            cargarVencidos();
        });

        function cargarVencidos() {
            document.getElementById('loading').style.display = 'block';
            document.getElementById('contenido-tabla').innerHTML = '';

            fetch('../../controllers/devolucion_controller.php?accion=listar_vencidos')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loading').style.display = 'none';
                    
                    if (data.exito) {
                        mostrarPrestamosVencidos(data.prestamos);
                        actualizarEstadisticas(data.prestamos);
                    } else {
                        document.getElementById('contenido-tabla').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                Error al cargar préstamos: ${data.mensaje}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('contenido-tabla').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Error de conexión
                        </div>
                    `;
                    console.error(error);
                });
        }

        function mostrarPrestamosVencidos(prestamos) {
            const container = document.getElementById('contenido-tabla');
            
            if (prestamos.length === 0) {
                container.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        ¡Excelente! No hay préstamos vencidos en este momento.
                    </div>
                `;
                return;
            }

            let html = `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-danger">
                            <tr>
                                <th>Usuario</th>
                                <th>Libro</th>
                                <th>Código</th>
                                <th>Fecha Préstamo</th>
                                <th>Fecha Esperada</th>
                                <th class="text-center">Días Retraso</th>
                                <th class="text-end">Multa</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            prestamos.forEach(prestamo => {
                const severidad = prestamo.dias_retraso > 7 ? 'danger' : (prestamo.dias_retraso > 3 ? 'warning' : 'info');
                
                html += `
                    <tr>
                        <td>
                            <strong>${prestamo.nombres} ${prestamo.apellidos}</strong><br>
                            <small class="text-muted">${prestamo.nombre_usuario}</small>
                        </td>
                        <td>
                            <strong>${prestamo.libro_titulo}</strong><br>
                            <small class="text-muted">${prestamo.libro_autor}</small>
                        </td>
                        <td><code>${prestamo.codigo_ejemplar}</code></td>
                        <td>${formatearFecha(prestamo.fecha_prestamo)}</td>
                        <td>${formatearFecha(prestamo.fecha_devolucion_esperada)}</td>
                        <td class="text-center">
                            <span class="badge bg-${severidad}">
                                ${prestamo.dias_retraso} día(s)
                            </span>
                        </td>
                        <td class="text-end">
                            <strong class="text-danger">S/. ${prestamo.multa_calculada.toFixed(2)}</strong>
                        </td>
                        <td class="text-center">
                            <a href="registrar_devolucion.php" class="btn btn-sm btn-success" title="Registrar Devolución">
                                <i class="fas fa-undo"></i>
                            </a>
                        </td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            container.innerHTML = html;
        }

        function actualizarEstadisticas(prestamos) {
            const total = prestamos.length;
            document.getElementById('total-vencidos').textContent = total;

            if (total > 0) {
                const totalMultas = prestamos.reduce((sum, p) => sum + p.multa_calculada, 0);
                const promedioDias = Math.round(prestamos.reduce((sum, p) => sum + parseInt(p.dias_retraso), 0) / total);
                
                document.getElementById('total-multas').textContent = `S/. ${totalMultas.toFixed(2)}`;
                document.getElementById('promedio-dias').textContent = promedioDias;
            } else {
                document.getElementById('total-multas').textContent = 'S/. 0.00';
                document.getElementById('promedio-dias').textContent = '0';
            }
        }

        function formatearFecha(fecha) {
            return new Date(fecha).toLocaleDateString('es-PE', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
        }
    </script>
</body>
</html>