<?php
/**
 * Multas Pendientes - Vista Bibliotecario/Administrador
 * HU-02: Préstamos y Devoluciones - FASE 3
 * Sistema de Gestión Bibliotecaria FISI
 */

require_once __DIR__ . '/../../includes/session.php';
protegerPagina(['bibliotecario', 'administrador']);

$usuario = obtenerUsuarioActual();
$es_admin = ($usuario['rol'] === 'administrador');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multas Pendientes - Sistema Bibliotecario FISI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="../<?php echo $usuario['rol']; ?>_dashboard.php">
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
                            <span class="badge bg-info text-dark"><?php echo ucfirst($usuario['rol']); ?></span>
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
                            <a class="nav-link" href="../<?php echo $usuario['rol']; ?>_dashboard.php">
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
                            <a class="nav-link active" href="#">
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
                            <h2><i class="fas fa-dollar-sign me-2 text-warning"></i>Multas Pendientes</h2>
                            <p class="text-muted mb-0">Gestión de multas por retrasos y otros conceptos</p>
                        </div>
                        <div>
                            <button class="btn btn-primary" onclick="cargarMultas()">
                                <i class="fas fa-sync-alt me-2"></i>Actualizar
                            </button>
                            <a href="../<?php echo $usuario['rol']; ?>_dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Volver
                            </a>
                        </div>
                    </div>

                    <!-- Mensajes -->
                    <div id="mensaje-container"></div>

                    <!-- Estadísticas -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card border-warning">
                                <div class="card-body text-center">
                                    <h2 class="text-warning mb-0" id="total-multas">0</h2>
                                    <p class="mb-0">Multas Pendientes</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-danger">
                                <div class="card-body text-center">
                                    <h2 class="text-danger mb-0" id="total-monto">S/. 0.00</h2>
                                    <p class="mb-0">Monto Total</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <h2 class="text-success mb-0" id="total-pagadas">0</h2>
                                    <p class="mb-0">Pagadas</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-info">
                                <div class="card-body text-center">
                                    <h2 class="text-info mb-0" id="total-perdonadas">0</h2>
                                    <p class="mb-0">Perdonadas</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Multas -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-warning">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>
                                Lista de Multas Pendientes
                            </h5>
                        </div>
                        <div class="card-body">
                            <div id="loading" class="text-center py-4" style="display:none;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <p class="mt-2">Cargando multas...</p>
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

    <!-- Modal Registrar Pago -->
    <div class="modal fade" id="modalPago" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle me-2"></i>
                        Registrar Pago de Multa
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="detalle-multa-pago"></div>
                    <div class="mb-3">
                        <label class="form-label">Observaciones:</label>
                        <textarea class="form-control" id="obs-pago" rows="3" 
                                  placeholder="Ej: Pago en efectivo"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="confirmarPago()">
                        <i class="fas fa-check me-2"></i>Confirmar Pago
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Perdonar Multa -->
    <?php if ($es_admin): ?>
    <div class="modal fade" id="modalPerdonar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-heart me-2"></i>
                        Perdonar Multa
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="detalle-multa-perdonar"></div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Esta acción solo puede ser realizada por administradores
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motivo del perdón (requerido):</label>
                        <textarea class="form-control" id="motivo-perdonar" rows="3" required
                                  placeholder="Ej: Error del sistema, caso especial, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-info" onclick="confirmarPerdon()">
                        <i class="fas fa-heart me-2"></i>Perdonar Multa
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let multaActual = null;
        const esAdmin = <?php echo $es_admin ? 'true' : 'false'; ?>;

        // Cargar automáticamente
        document.addEventListener('DOMContentLoaded', function() {
            cargarMultas();
            cargarEstadisticas();
        });

        function cargarMultas() {
            document.getElementById('loading').style.display = 'block';
            document.getElementById('contenido-tabla').innerHTML = '';

            fetch('../../controllers/multa_controller.php?accion=listar_pendientes')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loading').style.display = 'none';
                    
                    if (data.exito) {
                        mostrarMultas(data.multas);
                        document.getElementById('total-multas').textContent = data.total;
                        document.getElementById('total-monto').textContent = 'S/. ' + data.total_monto.toFixed(2);
                    } else {
                        mostrarError(data.mensaje);
                    }
                })
                .catch(error => {
                    document.getElementById('loading').style.display = 'none';
                    mostrarError('Error de conexión');
                    console.error(error);
                });
        }

        function cargarEstadisticas() {
            fetch('../../controllers/multa_controller.php?accion=estadisticas')
                .then(response => response.json())
                .then(data => {
                    if (data.exito) {
                        const stats = data.estadisticas;
                        document.getElementById('total-pagadas').textContent = stats.pagadas || 0;
                        document.getElementById('total-perdonadas').textContent = stats.perdonadas || 0;
                    }
                });
        }

        function mostrarMultas(multas) {
            const container = document.getElementById('contenido-tabla');
            
            if (multas.length === 0) {
                container.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        ¡Excelente! No hay multas pendientes.
                    </div>
                `;
                return;
            }

            let html = `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-warning">
                            <tr>
                                <th>Usuario</th>
                                <th>Libro</th>
                                <th>Concepto</th>
                                <th class="text-center">Días</th>
                                <th class="text-end">Monto</th>
                                <th>Fecha Creación</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            multas.forEach(multa => {
                html += `
                    <tr>
                        <td>
                            <strong>${multa.nombres} ${multa.apellidos}</strong><br>
                            <small class="text-muted">${multa.nombre_usuario}</small>
                        </td>
                        <td>
                            ${multa.libro_titulo ? `
                                <strong>${multa.libro_titulo}</strong><br>
                                <small class="text-muted">${multa.libro_autor}</small>
                            ` : '<em class="text-muted">Sin libro asociado</em>'}
                        </td>
                        <td>
                            <span class="badge bg-${multa.tipo === 'retraso' ? 'warning' : 'danger'}">
                                ${multa.tipo}
                            </span>
                        </td>
                        <td class="text-center">
                            ${multa.dias_retraso ? `<strong>${multa.dias_retraso}</strong>` : '-'}
                        </td>
                        <td class="text-end">
                            <strong class="text-danger">S/. ${parseFloat(multa.monto).toFixed(2)}</strong>
                        </td>
                        <td>${formatearFecha(multa.fecha_creacion)}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-success me-1" 
                                    onclick='abrirModalPago(${JSON.stringify(multa)})' 
                                    title="Registrar Pago">
                                <i class="fas fa-check"></i>
                            </button>
                            ${esAdmin ? `
                                <button class="btn btn-sm btn-info" 
                                        onclick='abrirModalPerdonar(${JSON.stringify(multa)})' 
                                        title="Perdonar Multa">
                                    <i class="fas fa-heart"></i>
                                </button>
                            ` : ''}
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

        function abrirModalPago(multa) {
            multaActual = multa;
            const detalle = `
                <div class="alert alert-info">
                    <h6>Detalle de la Multa:</h6>
                    <p class="mb-1"><strong>Usuario:</strong> ${multa.nombres} ${multa.apellidos}</p>
                    <p class="mb-1"><strong>Concepto:</strong> ${multa.tipo}</p>
                    <p class="mb-1"><strong>Monto:</strong> <span class="text-danger">S/. ${parseFloat(multa.monto).toFixed(2)}</span></p>
                    ${multa.libro_titulo ? `<p class="mb-0"><strong>Libro:</strong> ${multa.libro_titulo}</p>` : ''}
                </div>
            `;
            document.getElementById('detalle-multa-pago').innerHTML = detalle;
            new bootstrap.Modal(document.getElementById('modalPago')).show();
        }

        function confirmarPago() {
            if (!multaActual) return;

            const formData = new FormData();
            formData.append('accion', 'registrar_pago');
            formData.append('id_multa', multaActual.id_multa);
            formData.append('observaciones', document.getElementById('obs-pago').value);

            fetch('../../controllers/multa_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.exito) {
                    mostrarMensaje('Pago registrado exitosamente', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('modalPago')).hide();
                    cargarMultas();
                    cargarEstadisticas();
                    document.getElementById('obs-pago').value = '';
                } else {
                    mostrarMensaje(data.mensaje, 'danger');
                }
            })
            .catch(error => {
                mostrarMensaje('Error al registrar pago', 'danger');
                console.error(error);
            });
        }

        function abrirModalPerdonar(multa) {
            multaActual = multa;
            const detalle = `
                <div class="alert alert-warning">
                    <h6>Detalle de la Multa:</h6>
                    <p class="mb-1"><strong>Usuario:</strong> ${multa.nombres} ${multa.apellidos}</p>
                    <p class="mb-1"><strong>Concepto:</strong> ${multa.tipo}</p>
                    <p class="mb-1"><strong>Monto a perdonar:</strong> <span class="text-danger">S/. ${parseFloat(multa.monto).toFixed(2)}</span></p>
                    ${multa.libro_titulo ? `<p class="mb-0"><strong>Libro:</strong> ${multa.libro_titulo}</p>` : ''}
                </div>
            `;
            document.getElementById('detalle-multa-perdonar').innerHTML = detalle;
            new bootstrap.Modal(document.getElementById('modalPerdonar')).show();
        }

        function confirmarPerdon() {
            if (!multaActual) return;

            const motivo = document.getElementById('motivo-perdonar').value.trim();
            if (!motivo) {
                alert('Debe especificar el motivo del perdón');
                return;
            }

            const formData = new FormData();
            formData.append('accion', 'perdonar_multa');
            formData.append('id_multa', multaActual.id_multa);
            formData.append('motivo', motivo);

            fetch('../../controllers/multa_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.exito) {
                    mostrarMensaje('Multa perdonada exitosamente', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('modalPerdonar')).hide();
                    cargarMultas();
                    cargarEstadisticas();
                    document.getElementById('motivo-perdonar').value = '';
                } else {
                    mostrarMensaje(data.mensaje, 'danger');
                }
            })
            .catch(error => {
                mostrarMensaje('Error al perdonar multa', 'danger');
                console.error(error);
            });
        }

        function formatearFecha(fecha) {
            return new Date(fecha).toLocaleDateString('es-PE', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
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
            container.scrollIntoView({ behavior: 'smooth' });
            setTimeout(() => {
                const alert = container.querySelector('.alert');
                if (alert) {
                    alert.classList.remove('show');
                    setTimeout(() => { container.innerHTML = ''; }, 150);
                }
            }, 5000);
        }

        function mostrarError(mensaje) {
            document.getElementById('contenido-tabla').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    ${mensaje}
                </div>
            `;
        }
    </script>
</body>
</html>