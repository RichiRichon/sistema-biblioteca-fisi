<?php
/**
 * Formulario de Libro - Crear/Editar
 * HU-05: Gestión de inventario
 * Sistema de Gestión Bibliotecaria FISI
 */

require_once '../../includes/session.php';
require_once '../../config/database.php';

protegerPagina(['administrador', 'bibliotecario']);

$usuario = obtenerUsuarioActual();

// Determinar si es edición o creación
$id_libro = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$modo_edicion = $id_libro > 0;
$titulo_pagina = $modo_edicion ? 'Editar Libro' : 'Agregar Nuevo Libro';

// Datos del libro (para edición)
$libro = null;
if ($modo_edicion) {
    try {
        $db = Database::getInstance();
        $pdo = $db->getConexion();
        
        $stmt = $pdo->prepare("
            SELECT 
                l.*,
                c.id_categoria,
                c.nombre as nombre_categoria,
                (SELECT COUNT(*) FROM ejemplares_libros WHERE id_libro = l.id_libro) as total_ejemplares
            FROM libros l
            LEFT JOIN libro_categorias lc ON l.id_libro = lc.id_libro
            LEFT JOIN categorias c ON lc.id_categoria = c.id_categoria
            WHERE l.id_libro = ?
        ");
        $stmt->execute([$id_libro]);
        $libro = $stmt->fetch();
        
        if (!$libro) {
            $_SESSION['mensaje'] = 'Libro no encontrado';
            $_SESSION['tipo_mensaje'] = 'danger';
            header('Location: gestion_libros.php');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = 'Error al cargar el libro';
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: gestion_libros.php');
        exit;
    }
}

// Obtener categorías
try {
    $db = Database::getInstance();
    $pdo = $db->getConexion();
    $stmt = $pdo->query("SELECT * FROM categorias ORDER BY nombre");
    $categorias = $stmt->fetchAll();
} catch (PDOException $e) {
    $categorias = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina; ?> - Sistema Bibliotecario FISI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .required::after {
            content: " *";
            color: red;
        }
        .form-help {
            font-size: 0.875rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="../admin_dashboard.php">
                <i class="fas fa-book-reader me-2"></i>
                Sistema Bibliotecario FISI - Panel Administrativo
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="fas fa-user-shield me-1"></i>
                            <?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">
                            <span class="badge bg-success">Administrador</span>
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
                        <span>DASHBOARD</span>
                    </h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../admin_dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Panel Principal
                            </a>
                        </li>
                    </ul>

                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                        <span>GESTIÓN DE INVENTARIO</span>
                    </h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="gestion_libros.php">
                                <i class="fas fa-book me-2"></i>
                                Gestión de Libros
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="libro_form.php">
                                <i class="fas fa-plus-circle me-2"></i>
                                Añadir Nuevo Libro
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="fas fa-copy me-2"></i>
                                Gestión de Ejemplares
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <i class="fas fa-tags me-2"></i>
                                Categorías
                            </a>
                        </li>
                    </ul>

                    <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                        <span>SISTEMA</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link" href="../admin_dashboard.php">
                                <i class="fas fa-arrow-left me-2"></i>
                                Volver al Dashboard
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Contenido Principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="dashboard-container">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4 pt-3">
                        <div>
                            <h2>
                                <i class="fas fa-<?php echo $modo_edicion ? 'edit' : 'plus-circle'; ?> me-2"></i>
                                <?php echo $titulo_pagina; ?>
                            </h2>
                            <p class="text-muted mb-0">
                                <?php echo $modo_edicion ? 'Modifica la información del libro' : 'Completa los datos del nuevo libro'; ?>
                            </p>
                        </div>
                        <a href="gestion_libros.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver a la Lista
                        </a>
                    </div>

                    <!-- Formulario -->
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="../../controllers/libro_controller.php" id="form-libro">
                                <input type="hidden" name="accion" value="<?php echo $modo_edicion ? 'editar' : 'crear'; ?>">
                                <?php if ($modo_edicion): ?>
                                    <input type="hidden" name="id_libro" value="<?php echo $libro['id_libro']; ?>">
                                <?php endif; ?>

                                <div class="row">
                                    <!-- Información Básica -->
                                    <div class="col-12">
                                        <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Información Básica</h5>
                                    </div>

                                    <!-- Título -->
                                    <div class="col-md-6 mb-3">
                                        <label for="titulo" class="form-label required">Título del Libro</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="titulo" 
                                               name="titulo" 
                                               value="<?php echo $modo_edicion ? htmlspecialchars($libro['titulo']) : ''; ?>"
                                               required
                                               minlength="3"
                                               maxlength="255">
                                        <div class="form-help">Ingresa el título completo del libro</div>
                                    </div>

                                    <!-- Autor -->
                                    <div class="col-md-6 mb-3">
                                        <label for="autor" class="form-label required">Autor</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="autor" 
                                               name="autor" 
                                               value="<?php echo $modo_edicion ? htmlspecialchars($libro['autor']) : ''; ?>"
                                               required
                                               minlength="3"
                                               maxlength="255">
                                        <div class="form-help">Nombre completo del autor o autores</div>
                                    </div>

                                    <!-- Clasificación -->
                                    <div class="col-md-4 mb-3">
                                        <label for="clasificacion" class="form-label required">Clasificación</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="clasificacion" 
                                               name="clasificacion" 
                                               value="<?php echo $modo_edicion ? htmlspecialchars($libro['clasificacion']) : ''; ?>"
                                               required
                                               placeholder="Ej: QA76.73">
                                        <div class="form-help">Código de clasificación de la biblioteca</div>
                                    </div>

                                    <!-- Año de Publicación -->
                                    <div class="col-md-4 mb-3">
                                        <label for="año_publicacion" class="form-label">Año de Publicación</label>
                                        <input type="number" 
                                               class="form-control" 
                                               id="año_publicacion" 
                                               name="año_publicacion" 
                                               value="<?php echo $modo_edicion ? $libro['año_publicacion'] : ''; ?>"
                                               min="1800"
                                               max="<?php echo date('Y') + 1; ?>"
                                               placeholder="<?php echo date('Y'); ?>">
                                        <div class="form-help">Año en que fue publicado</div>
                                    </div>

                                    <!-- Categoría -->
                                    <div class="col-md-4 mb-3">
                                        <label for="id_categoria" class="form-label">Categoría</label>
                                        <select class="form-select" id="id_categoria" name="id_categoria">
                                            <option value="">Sin categoría</option>
                                            <?php foreach ($categorias as $cat): ?>
                                                <option value="<?php echo $cat['id_categoria']; ?>"
                                                        <?php echo ($modo_edicion && $libro['id_categoria'] == $cat['id_categoria']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-help">Temática principal del libro</div>
                                    </div>

                                    <!-- Información Adicional -->
                                    <div class="col-12 mt-3">
                                        <h5 class="mb-3"><i class="fas fa-book-open me-2"></i>Información Adicional</h5>
                                    </div>

                                    <!-- ISBN -->
                                    <div class="col-md-6 mb-3">
                                        <label for="isbn" class="form-label">ISBN</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="isbn" 
                                               name="isbn" 
                                               value="<?php echo $modo_edicion ? htmlspecialchars($libro['isbn'] ?? '') : ''; ?>"
                                               placeholder="978-0-123456-78-9">
                                        <div class="form-help">Código ISBN (opcional)</div>
                                    </div>

                                    <!-- Editorial -->
                                    <div class="col-md-6 mb-3">
                                        <label for="editorial" class="form-label">Editorial</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="editorial" 
                                               name="editorial" 
                                               value="<?php echo $modo_edicion ? htmlspecialchars($libro['editorial'] ?? '') : ''; ?>"
                                               placeholder="Nombre de la editorial">
                                        <div class="form-help">Casa editorial que publicó el libro</div>
                                    </div>

                                    <!-- Descripción -->
                                    <div class="col-12 mb-3">
                                        <label for="descripcion" class="form-label">Descripción</label>
                                        <textarea class="form-control" 
                                                  id="descripcion" 
                                                  name="descripcion" 
                                                  rows="3"
                                                  maxlength="500"
                                                  placeholder="Breve descripción del contenido del libro..."><?php echo $modo_edicion ? htmlspecialchars($libro['descripcion'] ?? '') : ''; ?></textarea>
                                        <div class="form-help">Resumen o sinopsis del libro (máximo 500 caracteres)</div>
                                    </div>

                                    <!-- Cantidad de Ejemplares (solo en modo crear) -->
                                    <?php if (!$modo_edicion): ?>
                                        <div class="col-12 mt-3">
                                            <h5 class="mb-3"><i class="fas fa-copy me-2"></i>Ejemplares</h5>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="cantidad_ejemplares" class="form-label">Cantidad de Ejemplares</label>
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="cantidad_ejemplares" 
                                                   name="cantidad_ejemplares" 
                                                   value="1"
                                                   min="1"
                                                   max="50">
                                            <div class="form-help">¿Cuántas copias físicas quieres registrar?</div>
                                        </div>
                                    <?php else: ?>
                                        <div class="col-12 mb-3">
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Este libro tiene actualmente <strong><?php echo $libro['total_ejemplares']; ?></strong> ejemplar(es).
                                                Para agregar o eliminar ejemplares, usa la opción "Ver ejemplares" en la lista de libros.
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Botones -->
                                    <div class="col-12 mt-4">
                                        <div class="d-flex justify-content-between">
                                            <a href="gestion_libros.php" class="btn btn-secondary">
                                                <i class="fas fa-times me-2"></i>Cancelar
                                            </a>
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-save me-2"></i>
                                                <?php echo $modo_edicion ? 'Guardar Cambios' : 'Crear Libro'; ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación del formulario
        document.getElementById('form-libro').addEventListener('submit', function(e) {
            const titulo = document.getElementById('titulo').value.trim();
            const autor = document.getElementById('autor').value.trim();
            const clasificacion = document.getElementById('clasificacion').value.trim();
            
            if (titulo.length < 3) {
                e.preventDefault();
                alert('El título debe tener al menos 3 caracteres');
                document.getElementById('titulo').focus();
                return false;
            }
            
            if (autor.length < 3) {
                e.preventDefault();
                alert('El autor debe tener al menos 3 caracteres');
                document.getElementById('autor').focus();
                return false;
            }
            
            if (clasificacion.length < 2) {
                e.preventDefault();
                alert('La clasificación es requerida');
                document.getElementById('clasificacion').focus();
                return false;
            }
            
            return true;
        });

        // Contador de caracteres para descripción
        const descripcion = document.getElementById('descripcion');
        if (descripcion) {
            descripcion.addEventListener('input', function() {
                const remaining = 500 - this.value.length;
                const help = this.nextElementSibling;
                help.textContent = `Resumen o sinopsis del libro (${remaining} caracteres restantes)`;
            });
        }
    </script>
</body>
</html>
