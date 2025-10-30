<?php
/**
 * Registrar Préstamo - Vista Bibliotecario
 * HU-02: Préstamos y Devoluciones - FASE 1
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
    <title>Registrar Préstamo - Sistema Bibliotecario FISI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .search-result-item {
            cursor: pointer;
            transition: background 0.2s;
        }
        .search-result-item:hover {
            background: #f8f9fa;
        }
        .selected-item {
            background: #e3f2fd;
            border: 2px solid #17a2b8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .info-badge {
            display: inline-block;
            margin: 5px;
            padding: 5px 10px;
            background: #e3f2fd;
            border-radius: 5px;
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
                            <a class="nav-link active" href="#">
                                <i class="fas fa-book-open me-2"></i>
                                Registrar Préstamo
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
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
                            <a class="nav-link" href="#">
                                <i class="fas fa-list me-2"></i>
                                Préstamos Activos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
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
                            <h2><i class="fas fa-hand-holding-book me-2"></i>Registrar Nuevo Préstamo</h2>
                            <p class="text-muted mb-0">Complete los datos para registrar el préstamo de un libro</p>
                        </div>
                        <a href="../bibliotecario_dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver al Dashboard
                        </a>
                    </div>

                    <!-- Mensajes -->
                    <div id="mensaje-container"></div>

                    <!-- Formulario de Préstamo -->
                    <div class="row">
                        <!-- Paso 1: Buscar Usuario -->
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user-search me-2"></i>
                                        Paso 1: Buscar Usuario
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Buscar por código, nombre o correo:</label>
                                        <div class="input-group">
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="buscar-usuario" 
                                                   placeholder="Ej: 20200111, Juan Pérez, juan.perez@unmsm.edu.pe">
                                            <button class="btn btn-primary" type="button" onclick="buscarUsuario()">
                                                <i class="fas fa-search"></i> Buscar
                                            </button>
                                        </div>
                                        <small class="text-muted">Presiona Enter para buscar</small>
                                    </div>

                                    <!-- Resultados de búsqueda de usuario -->
                                    <div id="resultados-usuario"></div>

                                    <!-- Usuario seleccionado -->
                                    <div id="usuario-seleccionado" style="display:none;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Paso 2: Buscar Libro -->
                        <div class="col-md-6 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-book-open me-2"></i>
                                        Paso 2: Buscar Libro Disponible
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Buscar por título, autor o código:</label>
                                        <div class="input-group">
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="buscar-libro" 
                                                   placeholder="Ej: Python, García Márquez, QA76.73">
                                            <button class="btn btn-success" type="button" onclick="buscarLibro()">
                                                <i class="fas fa-search"></i> Buscar
                                            </button>
                                        </div>
                                        <small class="text-muted">Solo se mostrarán ejemplares disponibles</small>
                                    </div>

                                    <!-- Resultados de búsqueda de libro -->
                                    <div id="resultados-libro"></div>

                                    <!-- Libro seleccionado -->
                                    <div id="libro-seleccionado" style="display:none;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 3: Confirmar y Registrar -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow-sm" id="card-confirmacion" style="display:none;">
                                <div class="card-header bg-warning">
                                    <h5 class="mb-0">
                                        <i class="fas fa-check-circle me-2"></i>
                                        Paso 3: Confirmar Préstamo
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-user me-2"></i>Usuario:</h6>
                                            <p id="confirm-usuario" class="text-muted"></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><i class="fas fa-book me-2"></i>Libro:</h6>
                                            <p id="confirm-libro" class="text-muted"></p>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Observaciones (opcional):</label>
                                        <textarea class="form-control" 
                                                  id="observaciones" 
                                                  rows="2" 
                                                  placeholder="Ej: Préstamo para proyecto de tesis"></textarea>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-secondary" onclick="limpiarFormulario()">
                                            <i class="fas fa-times me-2"></i>Cancelar
                                        </button>
                                        <button type="button" class="btn btn-success" onclick="registrarPrestamo()">
                                            <i class="fas fa-check me-2"></i>Confirmar Préstamo
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
        let usuarioSeleccionado = null;
        let libroSeleccionado = null;

        // Búsqueda al presionar Enter
        document.getElementById('buscar-usuario').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') buscarUsuario();
        });

        document.getElementById('buscar-libro').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') buscarLibro();
        });

        // Buscar usuario
        function buscarUsuario() {
            const termino = document.getElementById('buscar-usuario').value.trim();
            
            if (termino.length < 2) {
                mostrarMensaje('Por favor ingrese al menos 2 caracteres', 'warning');
                return;
            }

            fetch(`../../controllers/prestamo_controller.php?accion=buscar_usuario&termino=${encodeURIComponent(termino)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.exito) {
                        mostrarResultadosUsuario(data.usuarios);
                    } else {
                        mostrarMensaje(data.mensaje, 'danger');
                    }
                })
                .catch(error => {
                    mostrarMensaje('Error al buscar usuario', 'danger');
                    console.error(error);
                });
        }

        // Mostrar resultados de usuario
        function mostrarResultadosUsuario(usuarios) {
            const container = document.getElementById('resultados-usuario');
            
            if (usuarios.length === 0) {
                container.innerHTML = '<div class="alert alert-info">No se encontraron usuarios</div>';
                return;
            }

            let html = '<div class="list-group" style="max-height: 300px; overflow-y: auto;">';
            usuarios.forEach(usuario => {
                html += `
                    <div class="list-group-item search-result-item" onclick='seleccionarUsuario(${JSON.stringify(usuario)})'>
                        <h6 class="mb-1">${usuario.nombres} ${usuario.apellidos}</h6>
                        <small class="text-muted">
                            <i class="fas fa-id-card me-1"></i>${usuario.nombre_usuario} | 
                            <i class="fas fa-envelope me-1"></i>${usuario.correo}<br>
                            <span class="badge bg-info">${usuario.rol}</span>
                        </small>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }

        // Seleccionar usuario
        function seleccionarUsuario(usuario) {
            usuarioSeleccionado = usuario;
            
            const container = document.getElementById('usuario-seleccionado');
            container.style.display = 'block';
            container.innerHTML = `
                <div class="selected-item">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0"><i class="fas fa-check-circle text-success me-2"></i>Usuario Seleccionado</h6>
                        <button class="btn btn-sm btn-outline-danger" onclick="deseleccionarUsuario()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <p class="mb-1"><strong>${usuario.nombres} ${usuario.apellidos}</strong></p>
                    <div class="info-badge">
                        <small><i class="fas fa-id-card me-1"></i>${usuario.nombre_usuario}</small>
                    </div>
                    <div class="info-badge">
                        <small><i class="fas fa-user-tag me-1"></i>${usuario.rol}</small>
                    </div>
                </div>
            `;
            
            document.getElementById('resultados-usuario').innerHTML = '';
            verificarConfirmacion();
        }

        // Deshacer selección de usuario
        function deseleccionarUsuario() {
            usuarioSeleccionado = null;
            document.getElementById('usuario-seleccionado').style.display = 'none';
            verificarConfirmacion();
        }

        // Buscar libro
        function buscarLibro() {
            const termino = document.getElementById('buscar-libro').value.trim();
            
            if (termino.length < 2) {
                mostrarMensaje('Por favor ingrese al menos 2 caracteres', 'warning');
                return;
            }

            fetch(`../../controllers/prestamo_controller.php?accion=buscar_libro&termino=${encodeURIComponent(termino)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.exito) {
                        mostrarResultadosLibro(data.libros);
                    } else {
                        mostrarMensaje(data.mensaje, 'danger');
                    }
                })
                .catch(error => {
                    mostrarMensaje('Error al buscar libro', 'danger');
                    console.error(error);
                });
        }

        // Mostrar resultados de libro
        function mostrarResultadosLibro(libros) {
            const container = document.getElementById('resultados-libro');
            
            if (libros.length === 0) {
                container.innerHTML = '<div class="alert alert-info">No se encontraron libros disponibles</div>';
                return;
            }

            let html = '<div class="list-group" style="max-height: 300px; overflow-y: auto;">';
            libros.forEach(libro => {
                html += `
                    <div class="list-group-item search-result-item" onclick='seleccionarLibro(${JSON.stringify(libro)})'>
                        <h6 class="mb-1">${libro.titulo}</h6>
                        <small class="text-muted">
                            <i class="fas fa-user-edit me-1"></i>${libro.autor}<br>
                            <i class="fas fa-bookmark me-1"></i>${libro.clasificacion} |
                            <i class="fas fa-barcode me-1"></i>${libro.codigo_ejemplar}
                            ${libro.categoria ? `| <span class="badge bg-info">${libro.categoria}</span>` : ''}
                        </small>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }

        // Seleccionar libro
        function seleccionarLibro(libro) {
            libroSeleccionado = libro;
            
            const container = document.getElementById('libro-seleccionado');
            container.style.display = 'block';
            container.innerHTML = `
                <div class="selected-item">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0"><i class="fas fa-check-circle text-success me-2"></i>Libro Seleccionado</h6>
                        <button class="btn btn-sm btn-outline-danger" onclick="deseleccionarLibro()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <p class="mb-1"><strong>${libro.titulo}</strong></p>
                    <p class="mb-1"><small>por ${libro.autor}</small></p>
                    <div class="info-badge">
                        <small><i class="fas fa-bookmark me-1"></i>${libro.clasificacion}</small>
                    </div>
                    <div class="info-badge">
                        <small><i class="fas fa-barcode me-1"></i>${libro.codigo_ejemplar}</small>
                    </div>
                </div>
            `;
            
            document.getElementById('resultados-libro').innerHTML = '';
            verificarConfirmacion();
        }

        // Deshacer selección de libro
        function deseleccionarLibro() {
            libroSeleccionado = null;
            document.getElementById('libro-seleccionado').style.display = 'none';
            verificarConfirmacion();
        }

        // Verificar si se puede mostrar confirmación
        function verificarConfirmacion() {
            if (usuarioSeleccionado && libroSeleccionado) {
                document.getElementById('card-confirmacion').style.display = 'block';
                document.getElementById('confirm-usuario').textContent = 
                    `${usuarioSeleccionado.nombres} ${usuarioSeleccionado.apellidos} (${usuarioSeleccionado.rol})`;
                document.getElementById('confirm-libro').textContent = 
                    `${libroSeleccionado.titulo} - ${libroSeleccionado.autor}`;
            } else {
                document.getElementById('card-confirmacion').style.display = 'none';
            }
        }

        // Registrar préstamo
        function registrarPrestamo() {
            if (!usuarioSeleccionado || !libroSeleccionado) {
                mostrarMensaje('Debe seleccionar un usuario y un libro', 'warning');
                return;
            }

            const formData = new FormData();
            formData.append('accion', 'registrar');
            formData.append('id_usuario', usuarioSeleccionado.id_usuario);
            formData.append('id_ejemplar', libroSeleccionado.id_ejemplar);
            formData.append('observaciones', document.getElementById('observaciones').value);

            fetch('../../controllers/prestamo_controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.exito) {
                    mostrarMensaje(
                        `Préstamo registrado exitosamente. Fecha de devolución: ${new Date(data.fecha_devolucion).toLocaleDateString('es-PE')}`,
                        'success'
                    );
                    limpiarFormulario();
                    // Opcional: redireccionar después de 2 segundos
                    setTimeout(() => {
                        window.location.href = '../bibliotecario_dashboard.php';
                    }, 2000);
                } else {
                    mostrarMensaje(data.mensaje, 'danger');
                }
            })
            .catch(error => {
                mostrarMensaje('Error al registrar préstamo', 'danger');
                console.error(error);
            });
        }

        // Limpiar formulario
        function limpiarFormulario() {
            usuarioSeleccionado = null;
            libroSeleccionado = null;
            document.getElementById('buscar-usuario').value = '';
            document.getElementById('buscar-libro').value = '';
            document.getElementById('observaciones').value = '';
            document.getElementById('resultados-usuario').innerHTML = '';
            document.getElementById('resultados-libro').innerHTML = '';
            document.getElementById('usuario-seleccionado').style.display = 'none';
            document.getElementById('libro-seleccionado').style.display = 'none';
            document.getElementById('card-confirmacion').style.display = 'none';
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
            
            // Scroll al mensaje
            container.scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            // Auto-dismiss después de 5 segundos
            setTimeout(() => {
                const alert = container.querySelector('.alert');
                if (alert) {
                    alert.classList.remove('show');
                    setTimeout(() => { container.innerHTML = ''; }, 150);
                }
            }, 5000);
        }
    </script>
</body>
</html>