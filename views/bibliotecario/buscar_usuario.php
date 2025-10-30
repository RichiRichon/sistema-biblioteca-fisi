<?php
require_once __DIR__ . '/../../includes/session.php';
protegerPagina(['bibliotecario', 'administrador']);
$usuario = obtenerUsuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Usuario - Sistema Bibliotecario FISI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="../bibliotecario_dashboard.php">
                <i class="fas fa-book-reader me-2"></i>Sistema Bibliotecario FISI
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
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted"><span>GESTIÓN</span></h6>
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link" href="../bibliotecario_dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="registrar_prestamo.php"><i class="fas fa-book-open me-2"></i>Registrar Préstamo</a></li>
                        <li class="nav-item"><a class="nav-link" href="registrar_devolucion.php"><i class="fas fa-undo me-2"></i>Registrar Devolución</a></li>
                        <li class="nav-item"><a class="nav-link" href="renovar_prestamo.php"><i class="fas fa-redo me-2"></i>Renovar Préstamo</a></li>
                    </ul>
                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted"><span>CONSULTAS</span></h6>
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link" href="buscar_libro.php"><i class="fas fa-search me-2"></i>Buscar Libro</a></li>
                        <li class="nav-item"><a class="nav-link active" href="#"><i class="fas fa-users me-2"></i>Buscar Usuario</a></li>
                        <li class="nav-item"><a class="nav-link" href="prestamos_activos.php"><i class="fas fa-list me-2"></i>Préstamos Activos</a></li>
                        <li class="nav-item"><a class="nav-link" href="prestamos_vencidos.php"><i class="fas fa-exclamation-triangle me-2"></i>Préstamos Vencidos</a></li>
                    </ul>
                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted"><span>REPORTES</span></h6>
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link" href="multas_pendientes.php"><i class="fas fa-dollar-sign me-2"></i>Multas Pendientes</a></li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="dashboard-container">
                    <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
                        <div>
                            <h2><i class="fas fa-users me-2"></i>Buscar Usuario</h2>
                            <p class="text-muted mb-0">Consulta información y actividad de usuarios</p>
                        </div>
                        <a href="../bibliotecario_dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-search me-2"></i>Búsqueda de Usuarios</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <input type="text" class="form-control" id="buscar-usuario" 
                                           placeholder="Buscar por nombre, apellidos, usuario o correo...">
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="filtro-rol">
                                        <option value="todos">Todos los roles</option>
                                        <option value="estudiante">Estudiantes</option>
                                        <option value="docente">Docentes</option>
                                        <option value="bibliotecario">Bibliotecarios</option>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <button class="btn btn-primary w-100" onclick="buscarUsuarios()">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">Presiona Enter para buscar</small>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Resultados</h5>
                        </div>
                        <div class="card-body">
                            <div id="loading" class="text-center py-4" style="display:none;">
                                <div class="spinner-border text-primary"></div>
                                <p class="mt-2">Buscando...</p>
                            </div>
                            <div id="resultados-usuarios">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>Utiliza el buscador para encontrar usuarios
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div class="modal fade" id="modalDetalle" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-user me-2"></i>Detalle del Usuario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detalle-usuario"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('buscar-usuario').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') buscarUsuarios();
        });

        function buscarUsuarios() {
            const termino = document.getElementById('buscar-usuario').value.trim();
            const rol = document.getElementById('filtro-rol').value;
            
            document.getElementById('loading').style.display = 'block';
            document.getElementById('resultados-usuarios').innerHTML = '';

            fetch(`../../controllers/usuario_controller.php?accion=buscar&termino=${encodeURIComponent(termino)}&rol=${rol}`)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('loading').style.display = 'none';
                    if (data.exito) mostrarResultados(data.usuarios);
                    else document.getElementById('resultados-usuarios').innerHTML = `<div class="alert alert-danger">${data.mensaje}</div>`;
                })
                .catch(() => {
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('resultados-usuarios').innerHTML = '<div class="alert alert-danger">Error de conexión</div>';
                });
        }

        function mostrarResultados(usuarios) {
            const container = document.getElementById('resultados-usuarios');
            if (usuarios.length === 0) {
                container.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>No se encontraron usuarios</div>';
                return;
            }

            let html = `<p class="text-muted mb-3">Se encontraron ${usuarios.length} resultado(s)</p><div class="table-responsive"><table class="table table-hover"><thead class="table-light">
                <tr><th>Usuario</th><th>Rol</th><th>Contacto</th><th>Préstamos</th><th>Multas</th><th>Acciones</th></tr></thead><tbody>`;
            
            usuarios.forEach(u => {
                html += `<tr>
                    <td><strong>${u.nombres} ${u.apellidos}</strong><br><small class="text-muted">${u.nombre_usuario}</small></td>
                    <td><span class="badge bg-${u.rol === 'estudiante' ? 'primary' : u.rol === 'docente' ? 'success' : 'info'}">${u.rol}</span></td>
                    <td><small>${u.correo}</small></td>
                    <td><span class="badge bg-info">${u.prestamos_activos || 0} activos</span><br><small class="text-muted">Total: ${u.total_prestamos || 0}</small></td>
                    <td>${parseFloat(u.multas_pendientes) > 0 ? `<span class="badge bg-danger">S/. ${parseFloat(u.multas_pendientes).toFixed(2)}</span>` : '<span class="badge bg-success">Sin multas</span>'}</td>
                    <td><button class="btn btn-sm btn-primary" onclick='verDetalle(${u.id_usuario})'><i class="fas fa-eye"></i></button></td>
                </tr>`;
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
        }

        function verDetalle(id) {
            fetch(`../../controllers/usuario_controller.php?accion=detalle&id_usuario=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.exito) {
                        mostrarDetalle(data.usuario, data.prestamos_activos, data.historial, data.multas);
                        new bootstrap.Modal(document.getElementById('modalDetalle')).show();
                    }
                });
        }

        function mostrarDetalle(u, activos, historial, multas) {
            let html = `<div class="row mb-4"><div class="col-md-6">
                <h5>${u.nombres} ${u.apellidos}</h5>
                <p><strong>Usuario:</strong> ${u.nombre_usuario}<br>
                <strong>Correo:</strong> ${u.correo}<br>
                <strong>Rol:</strong> <span class="badge bg-info">${u.rol}</span></p>
                </div></div><hr>
                <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#activos">Préstamos Activos (${activos.length})</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#historial">Historial (${historial.length})</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#multas">Multas (${multas.length})</a></li>
                </ul><div class="tab-content mt-3">`;

            // Préstamos Activos
            html += '<div class="tab-pane fade show active" id="activos">';
            if (activos.length === 0) html += '<p class="text-muted">Sin préstamos activos</p>';
            else {
                html += '<table class="table table-sm"><thead><tr><th>Libro</th><th>Código</th><th>Prestado</th><th>Vence</th><th>Estado</th></tr></thead><tbody>';
                activos.forEach(p => {
                    const dias = parseInt(p.dias_restantes);
                    const estado = dias < 0 ? '<span class="badge bg-danger">Vencido</span>' : dias <= 3 ? '<span class="badge bg-warning">Por vencer</span>' : '<span class="badge bg-success">A tiempo</span>';
                    html += `<tr><td>${p.titulo}</td><td><code>${p.codigo_ejemplar}</code></td>
                        <td>${new Date(p.fecha_prestamo).toLocaleDateString('es-PE')}</td>
                        <td>${new Date(p.fecha_devolucion_esperada).toLocaleDateString('es-PE')}</td>
                        <td>${estado}</td></tr>`;
                });
                html += '</tbody></table>';
            }
            html += '</div>';

            // Historial
            html += '<div class="tab-pane fade" id="historial">';
            if (historial.length === 0) html += '<p class="text-muted">Sin historial de préstamos</p>';
            else {
                html += '<table class="table table-sm"><thead><tr><th>Libro</th><th>Prestado</th><th>Devuelto</th></tr></thead><tbody>';
                historial.forEach(p => {
                    html += `<tr><td>${p.titulo}</td>
                        <td>${new Date(p.fecha_prestamo).toLocaleDateString('es-PE')}</td>
                        <td>${new Date(p.fecha_devolucion_real).toLocaleDateString('es-PE')}</td></tr>`;
                });
                html += '</tbody></table>';
            }
            html += '</div>';

            // Multas
            html += '<div class="tab-pane fade" id="multas">';
            if (multas.length === 0) html += '<p class="text-muted">Sin multas registradas</p>';
            else {
                html += '<table class="table table-sm"><thead><tr><th>Tipo</th><th>Monto</th><th>Estado</th><th>Fecha</th></tr></thead><tbody>';
                multas.forEach(m => {
                    const badge = m.estado === 'pendiente' ? 'bg-danger' : m.estado === 'pagada' ? 'bg-success' : 'bg-info';
                    html += `<tr><td><span class="badge bg-secondary">${m.tipo}</span></td>
                        <td>S/. ${parseFloat(m.monto).toFixed(2)}</td>
                        <td><span class="badge ${badge}">${m.estado}</span></td>
                        <td>${new Date(m.fecha_creacion).toLocaleDateString('es-PE')}</td></tr>`;
                });
                html += '</tbody></table>';
            }
            html += '</div></div>';

            document.getElementById('detalle-usuario').innerHTML = html;
        }
    </script>
</body>
</html>