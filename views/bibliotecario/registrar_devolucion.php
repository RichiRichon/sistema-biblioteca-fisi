<?php
/**
 * Registrar Devolución - Vista Bibliotecario
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
    <title>Registrar Devolución - Sistema Bibliotecario FISI</title>
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
        .prestamo-vencido {
            border-left-color: #dc3545 !important;
            background: #fff5f5;
        }
        .badge-retraso {
            font-size: 0.9rem;
            padding: 5px 10px;
        }
        .detalle-prestamo {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #17a2b8;
        }
        .multa-warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
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
                            <a class="nav-link active" href="#">
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
                            <h2><i class="fas fa-undo me-2"></i>Registrar Devolución</h2>
                            <p class="text-muted mb-0">Busque el préstamo activo para procesar la devolución</p>
                        </div>
                        <a href="../bibliotecario_dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver al Dashboard
                        </a>
                    </div>

                    <!-- Mensajes -->
                    <div id="mensaje-container"></div>

                    <!-- Búsqueda de Préstamo -->
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-search me-2"></i>
                                        Buscar Préstamo Activo
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
                                        <small class="text-muted">Presiona Enter para buscar préstamos activos</small>
                                    </div>

                                    <!-- Resultados de búsqueda -->
                                    <div id="resultados-prestamo"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detalle del Préstamo Seleccionado -->
                    <div class="row" id="seccion-detalle" style="display:none;">
                        <div class="col-md-12 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Detalle del Préstamo
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="detalle-prestamo"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario de Devolución -->
                    <div class="row" id="seccion-devolucion" style="display:none;">
                        <div class="col-md-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-warning">
                                    <h5 class="mb-0">
                                        <i class="fas fa-check-circle me-2"></i>
                                        Confirmar Devolución
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="alerta-multa" style="display:none;"></div>

                                    <div class="mb-3">
                                        <label class="form-label">Estado del Ejemplar:</label>
                                        <select class="form-select" id="estado-ejemplar">
                                            <option value="disponible">Disponible (Buen estado)</option>
                                            <option value="mantenimiento">Requiere Mantenimiento</option>
                                            <option value="dañado">Dañado</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Observaciones:</label>
                                        <textarea class="form-control" 
                                                  id="observaciones-devolucion" 
                                                  rows="3" 
                                                  placeholder="Ej: Libro devuelto en buen estado"></textarea>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-secondary" onclick="cancelarDevolucion()">
                                            <i class="fas fa-times me-2"></i>Cancelar
                                        </button>
                                        <button type="button" class="btn btn-success" onclick="registrarDevolucion()">
                                            <i class="fas fa-check me-2"></i>Confirmar Devolución
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let prestamoSeleccionado = null;

        // Búsqueda al presionar Enter
        document.getElementById('buscar-prestamo').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') buscarPrestamo();
        });

        // Buscar préstamo
        function buscarPrestamo() {
            const termino = document.getElementById('buscar-prestamo').value.trim();
            
            if (termino.length < 2) {
                mostrarMensaje('Por favor ingrese al menos 2 caracteres', 'warning');
                return;
            }

            fetch(`../../controllers/devolucion_controller.php?accion=buscar_prestamo&termino=${encodeURIComponent(termino)}`)
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

        // Mostrar resultados
        function mostrarResultados(prestamos) {
            const container = document.getElementById('resultados-prestamo');
            
            if (prestamos.length === 0) {
                container.innerHTML = '<div class="alert alert-info">No se encontraron préstamos activos</div>';
                return;
            }

            let html = '<div class="list-group">';
            prestamos.forEach(prestamo => {
                const vencido = prestamo.tiene_retraso;
                const claseVencido = vencido ? 'prestamo-vencido' : '';
                
                html += `
                    <div class="list-group-item prestamo-item ${claseVencido}" onclick='seleccionarPrestamo(${JSON.stringify(prestamo)})'>
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
                                        <i class="fas fa-calendar me-1"></i>Prestado: ${formatearFecha(prestamo.fecha_prestamo)} |
                                        <i class="fas fa-clock me-1"></i>Debe devolver: ${formatearFecha(prestamo.fecha_devolucion_esperada)}
                                    </small>
                                </p>
                            </div>
                            <div class="text-end">
                                ${vencido ? `
                                    <span class="badge bg-danger badge-retraso">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        ${prestamo.dias_retraso} día(s) de retraso
                                    </span><br>
                                    <span class="badge bg-warning text-dark mt-1">
                                        Multa: S/. ${prestamo.multa_calculada.toFixed(2)}
                                    </span>
                                ` : `
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>A tiempo
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

        // Seleccionar préstamo
        function seleccionarPrestamo(prestamo) {
            prestamoSeleccionado = prestamo;
            
            // Mostrar detalle
            document.getElementById('seccion-detalle').style.display = 'block';
            document.getElementById('seccion-devolucion').style.display = 'block';
            
            const detalle = `
                <div class="detalle-prestamo">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6><i class="fas fa-user me-2"></i>Usuario</h6>
                            <p class="mb-1"><strong>${prestamo.nombres} ${prestamo.apellidos}</strong></p>
                            <p class="mb-0"><small class="text-muted">
                                <i class="fas fa-id-card me-1"></i>${prestamo.nombre_usuario} | 
                                <i class="fas fa-envelope me-1"></i>${prestamo.correo}
                            </small></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6><i class="fas fa-book me-2"></i>Libro</h6>
                            <p class="mb-1"><strong>${prestamo.libro_titulo}</strong></p>
                            <p class="mb-0"><small class="text-muted">
                                ${prestamo.libro_autor}<br>
                                <i class="fas fa-barcode me-1"></i>${prestamo.codigo_ejemplar}
                            </small></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <small class="text-muted">Fecha de Préstamo:</small><br>
                            <strong>${formatearFecha(prestamo.fecha_prestamo)}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Fecha de Devolución:</small><br>
                            <strong>${formatearFecha(prestamo.fecha_devolucion_esperada)}</strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Estado:</small><br>
                            ${prestamo.tiene_retraso ? 
                                `<span class="badge bg-danger">Vencido (${prestamo.dias_retraso} días)</span>` :
                                `<span class="badge bg-success">A tiempo</span>`
                            }
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('detalle-prestamo').innerHTML = detalle;
            
            // Mostrar alerta de multa si aplica
            const alertaMulta = document.getElementById('alerta-multa');
            if (prestamo.tiene_retraso) {
                alertaMulta.style.display = 'block';
                alertaMulta.innerHTML = `
                    <div class="multa-warning">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>ATENCIÓN: Este préstamo tiene retraso</h6>
                        <p class="mb-0">
                            <strong>Días de retraso:</strong> ${prestamo.dias_retraso} día(s)<br>
                            <strong>Multa a cobrar:</strong> S/. ${prestamo.multa_calculada.toFixed(2)}<br>
                            <small class="text-muted">La multa se generará automáticamente al registrar la devolución</small>
                        </p>
                    </div>
                `;
            } else {
                alertaMulta.style.display = 'none';
            }
            
            // Scroll hacia el detalle
            document.getElementById('seccion-detalle').scrollIntoView({ behavior: 'smooth' });
        }

        // Registrar devolución
        function registrarDevolucion() {
            if (!prestamoSeleccionado) {
                mostrarMensaje('Debe seleccionar un préstamo', 'warning');
                return;
            }

            if (!confirm('¿Está seguro de registrar esta devolución?')) {
                return;
            }

            const formData = new FormData();
            formData.append('accion', 'registrar_devolucion');
            formData.append('id_prestamo', prestamoSeleccionado.id_prestamo);
            formData.append('estado_ejemplar', document.getElementById('estado-ejemplar').value);
            formData.append('observaciones', document.getElementById('observaciones-devolucion').value);

            fetch('../../controllers/devolucion_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.exito) {
                    let mensaje = 'Devolución registrada exitosamente';
                    if (data.tiene_multa) {
                        mensaje += `<br><strong>Multa generada:</strong> S/. ${data.monto_multa.toFixed(2)} por ${data.dias_retraso} día(s) de retraso`;
                    }
                    mostrarMensaje(mensaje, 'success');
                    limpiarFormulario();
                    setTimeout(() => {
                        window.location.href = '../bibliotecario_dashboard.php';
                    }, 3000);
                } else {
                    mostrarMensaje(data.mensaje, 'danger');
                }
            })
            .catch(error => {
                mostrarMensaje('Error al registrar devolución', 'danger');
                console.error(error);
            });
        }

        // Cancelar devolución
        function cancelarDevolucion() {
            limpiarFormulario();
        }

        // Limpiar formulario
        function limpiarFormulario() {
            prestamoSeleccionado = null;
            document.getElementById('buscar-prestamo').value = '';
            document.getElementById('estado-ejemplar').value = 'disponible';
            document.getElementById('observaciones-devolucion').value = '';
            document.getElementById('resultados-prestamo').innerHTML = '';
            document.getElementById('seccion-detalle').style.display = 'none';
            document.getElementById('seccion-devolucion').style.display = 'none';
        }

        // Formatear fecha
        function formatearFecha(fecha) {
            return new Date(fecha).toLocaleDateString('es-PE', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
        }

        // Mostrar mensaje
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