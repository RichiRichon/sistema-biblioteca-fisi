<?php
/**
 * Renovar Préstamo - Vista Bibliotecario
 * HU-02: Préstamos y Devoluciones - FASE 3
 * Sistema de Gestión Bibliotecaria FISI
 */

require_once __DIR__ . '/../../includes/session.php';
protegerPagina(['bibliotecario', 'administrador']);

$usuario = obtenerUsuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renovar Préstamo - Sistema Bibliotecario FISI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .prestamo-item {
            cursor: pointer;
            transition: all 0.2s;
            border-left: 4px solid transparent;
        }
        .prestamo-item:hover {
            background: #f8f9fa;
            border-left-color: #17a2b8;
        }
        .renovable {
            border-left-color: #28a745 !important;
        }
        .no-renovable {
            border-left-color: #dc3545 !important;
            background: #fff5f5;
            opacity: 0.7;
        }
        .por-vencer {
            border-left-color: #ffc107 !important;
            background: #fffbf0;
        }
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
                            <a class="nav-link active" href="#">
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
                            <h2><i class="fas fa-redo me-2"></i>Renovar Préstamo</h2>
                            <p class="text-muted mb-0">Extender el plazo de devolución de un préstamo activo</p>
                        </div>
                        <a href="../bibliotecario_dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver al Dashboard
                        </a>
                    </div>

                    <!-- Mensajes -->
                    <div id="mensaje-container"></div>

                    <!-- Info de Renovaciones -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Información sobre Renovaciones:</h6>
                        <ul class="mb-0">
                            <li>Cada préstamo puede renovarse <strong>1 vez como máximo</strong></li>
                            <li>La renovación extiende el plazo por el mismo período del préstamo original</li>
                            <li>No se pueden renovar préstamos vencidos ni con multas pendientes</li>
                            <li>La renovación se cuenta desde la fecha de devolución actual, no desde hoy</li>
                        </ul>
                    </div>

                    <!-- Búsqueda -->
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-search me-2"></i>
                                        Buscar Préstamo para Renovar
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Buscar por usuario, código de ejemplar o título:</label>
                                        <div class="input-group">
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="buscar-prestamo" 
                                                   placeholder="Ej: María García, EJ001, Python">
                                            <button class="btn btn-primary" type="button" onclick="buscarPrestamo()">
                                                <i class="fas fa-search"></i> Buscar
                                            </button>
                                        </div>
                                        <small class="text-muted">Presiona Enter para buscar</small>
                                    </div>

                                    <!-- Resultados -->
                                    <div id="resultados-prestamo"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Préstamos por Vencer -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-warning">
                                    <h5 class="mb-0">
                                        <i class="fas fa-clock me-2"></i>
                                        Préstamos por Vencer (Próximos 3 días)
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="prestamos-vencer">
                                        <div class="text-center py-3">
                                            <button class="btn btn-warning" onclick="cargarProximosVencer()">
                                                <i class="fas fa-sync-alt me-2"></i>Cargar Préstamos por Vencer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Confirmar Renovación -->
    <div class="modal fade" id="modalRenovar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-redo me-2"></i>
                        Confirmar Renovación de Préstamo
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="detalle-renovacion"></div>
                    <div class="mb-3">
                        <label class="form-label">Observaciones (opcional):</label>
                        <textarea class="form-control" id="observaciones-renovacion" rows="2"
                                  placeholder="Ej: Renovación solicitada por el usuario"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="confirmarRenovacion()">
                        <i class="fas fa-check me-2"></i>Confirmar Renovación
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let prestamoSeleccionado = null;

        // Búsqueda al presionar Enter
        document.getElementById('buscar-prestamo').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') buscarPrestamo();
        });

        function buscarPrestamo() {
            const termino = document.getElementById('buscar-prestamo').value.trim();
            
            if (termino.length < 2) {
                mostrarMensaje('Por favor ingrese al menos 2 caracteres', 'warning');
                return;
            }

            fetch(`../../controllers/renovacion_controller.php?accion=buscar_renovables&termino=${encodeURIComponent(termino)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.exito) {
                        mostrarResultados(data.prestamos);
                    } else {
                        mostrarMensaje(data.mensaje, 'danger');
                    }
                })
                .catch(error => {
                    mostrarMensaje('Error al buscar préstamo', 'danger');
                    console.error(error);
                });
        }

        function mostrarResultados(prestamos) {
            const container = document.getElementById('resultados-prestamo');
            
            if (prestamos.length === 0) {
                container.innerHTML = '<div class="alert alert-info">No se encontraron préstamos activos</div>';
                return;
            }

            let html = '<div class="list-group">';
            prestamos.forEach(prestamo => {
                const esRenovable = prestamo.estado_renovacion === 'Renovable';
                const clase = esRenovable ? 'renovable' : 'no-renovable';
                const diasRestantes = parseInt(prestamo.dias_restantes);
                const porVencer = diasRestantes <= 3 && diasRestantes >= 0;
                
                html += `
                    <div class="list-group-item prestamo-item ${clase} ${porVencer ? 'por-vencer' : ''}" 
                         ${esRenovable ? `onclick='abrirModalRenovar(${JSON.stringify(prestamo)})'` : ''}>
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <i class="fas fa-user me-1"></i>
                                    ${prestamo.nombres} ${prestamo.apellidos}
                                </h6>
                                <p class="mb-1">
                                    <strong>${prestamo.libro_titulo}</strong><br>
                                    <small class="text-muted">
                                        <i class="fas fa-barcode me-1"></i>${prestamo.codigo_ejemplar} | 
                                        <i class="fas fa-calendar me-1"></i>Vence: ${formatearFecha(prestamo.fecha_devolucion_esperada)} |
                                        <i class="fas fa-redo me-1"></i>Renovaciones: ${prestamo.numero_renovaciones}/1
                                    </small>
                                </p>
                            </div>
                            <div class="text-end">
                                ${esRenovable ? `
                                    <span class="badge bg-success mb-1">
                                        <i class="fas fa-check me-1"></i>Renovable
                                    </span><br>
                                    <span class="badge bg-${diasRestantes <= 3 ? 'warning' : 'info'}">
                                        ${diasRestantes > 0 ? `${diasRestantes} días restantes` : 'Vence hoy'}
                                    </span>
                                ` : `
                                    <span class="badge bg-danger">
                                        <i class="fas fa-times me-1"></i>${prestamo.estado_renovacion}
                                    </span>
                                `}
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }

        function cargarProximosVencer() {
            const container = document.getElementById('prestamos-vencer');
            container.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-warning"></div></div>';

            fetch('../../controllers/renovacion_controller.php?accion=proximos_vencer')
                .then(response => response.json())
                .then(data => {
                    if (data.exito) {
                        if (data.prestamos.length === 0) {
                            container.innerHTML = `
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    No hay préstamos por vencer en los próximos 3 días
                                </div>
                            `;
                        } else {
                            let html = '<div class="list-group">';
                            data.prestamos.forEach(p => {
                                html += `
                                    <div class="list-group-item prestamo-item por-vencer" 
                                         onclick='abrirModalRenovar(${JSON.stringify(p)})'>
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong>${p.nombres} ${p.apellidos}</strong> - ${p.libro_titulo}<br>
                                                <small class="text-muted">Vence: ${formatearFecha(p.fecha_devolucion_esperada)}</small>
                                            </div>
                                            <span class="badge bg-warning">
                                                ${p.dias_restantes} días restantes
                                            </span>
                                        </div>
                                    </div>
                                `;
                            });
                            html += '</div>';
                            container.innerHTML = html;
                        }
                    } else {
                        container.innerHTML = `<div class="alert alert-danger">${data.mensaje}</div>`;
                    }
                })
                .catch(error => {
                    container.innerHTML = '<div class="alert alert-danger">Error al cargar préstamos</div>';
                    console.error(error);
                });
        }

        function abrirModalRenovar(prestamo) {
            if (prestamo.estado_renovacion !== 'Renovable') {
                mostrarMensaje('Este préstamo no puede ser renovado: ' + prestamo.estado_renovacion, 'warning');
                return;
            }

            prestamoSeleccionado = prestamo;
            
            // Calcular días adicionales según rol
            const diasPorRol = {
                'estudiante': 3,
                'docente': 7,
                'bibliotecario': 7,
                'administrador': 14
            };
            const diasAdicionales = diasPorRol[prestamo.rol] || 3;
            
            const fechaActual = new Date(prestamo.fecha_devolucion_esperada);
            const nuevaFecha = new Date(fechaActual);
            nuevaFecha.setDate(nuevaFecha.getDate() + diasAdicionales);

            const detalle = `
                <div class="alert alert-info">
                    <h6>Detalle del Préstamo:</h6>
                    <p class="mb-1"><strong>Usuario:</strong> ${prestamo.nombres} ${prestamo.apellidos} (${prestamo.rol})</p>
                    <p class="mb-1"><strong>Libro:</strong> ${prestamo.libro_titulo}</p>
                    <p class="mb-1"><strong>Fecha actual de devolución:</strong> ${formatearFecha(prestamo.fecha_devolucion_esperada)}</p>
                    <hr>
                    <p class="mb-1"><strong>Días adicionales:</strong> <span class="text-success">${diasAdicionales} días</span></p>
                    <p class="mb-0"><strong>Nueva fecha de devolución:</strong> <span class="text-primary">${nuevaFecha.toLocaleDateString('es-PE')}</span></p>
                </div>
            `;
            
            document.getElementById('detalle-renovacion').innerHTML = detalle;
            new bootstrap.Modal(document.getElementById('modalRenovar')).show();
        }

        function confirmarRenovacion() {
            if (!prestamoSeleccionado) return;

            const formData = new FormData();
            formData.append('accion', 'renovar');
            formData.append('id_prestamo', prestamoSeleccionado.id_prestamo);
            formData.append('observaciones', document.getElementById('observaciones-renovacion').value);

            fetch('../../controllers/renovacion_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.exito) {
                    mostrarMensaje(
                        `Préstamo renovado exitosamente. Nueva fecha de devolución: ${new Date(data.nueva_fecha).toLocaleDateString('es-PE')}`,
                        'success'
                    );
                    bootstrap.Modal.getInstance(document.getElementById('modalRenovar')).hide();
                    document.getElementById('buscar-prestamo').value = '';
                    document.getElementById('observaciones-renovacion').value = '';
                    document.getElementById('resultados-prestamo').innerHTML = '';
                } else {
                    mostrarMensaje(data.mensaje, 'danger');
                }
            })
            .catch(error => {
                mostrarMensaje('Error al renovar préstamo', 'danger');
                console.error(error);
            });
        }

        function formatearFecha(fecha) {
            return new Date(fecha).toLocaleDateString('es-PE', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
        }

        function mostrarMensaje(mensaje, tipo) {
            const container = document.getElementById('mensaje-container');
            container.innerHTML = `
                <div class="alert alert-${tipo} alert-dismissible fade show">
                    ${mensaje}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            container.scrollIntoView({ behavior: 'smooth', block: 'start' });
            setTimeout(() => {
                const alert = container.querySelector('.alert');
                if (alert) {
                    alert.classList.remove('show');
                    setTimeout(() => { container.innerHTML = ''; }, 150);
                }
            }, 8000);
        }
    </script>
</body>
</html>