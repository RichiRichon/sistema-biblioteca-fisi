<?php
/**
 * Página de Login
 * Sistema de Gestión Bibliotecaria FISI
 */

require_once '../controllers/login_controller.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Bibliotecario FISI</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <!-- Logo y Título -->
                        <div class="text-center mb-4">
                            <i class="fas fa-book-reader fa-4x text-primary mb-3"></i>
                            <h2 class="fw-bold text-dark">Sistema Bibliotecario</h2>
                            <p class="text-muted">Facultad de Ingeniería de Sistemas e Informática</p>
                            <hr>
                        </div>

                        <!-- Mensajes de Error o Éxito -->
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($exito)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($exito); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Formulario de Login -->
                        <form method="POST" action="">
                            <!-- Usuario -->
                            <div class="mb-3">
                                <label for="nombre_usuario" class="form-label fw-semibold">
                                    <i class="fas fa-user me-1"></i> Usuario
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg" 
                                       id="nombre_usuario" 
                                       name="nombre_usuario" 
                                       placeholder="Ingrese su usuario"
                                       required 
                                       autofocus
                                       value="<?php echo htmlspecialchars($_POST['nombre_usuario'] ?? ''); ?>">
                            </div>

                            <!-- Contraseña -->
                            <div class="mb-4">
                                <label for="clave" class="form-label fw-semibold">
                                    <i class="fas fa-lock me-1"></i> Contraseña
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control form-control-lg" 
                                           id="clave" 
                                           name="clave" 
                                           placeholder="Ingrese su contraseña"
                                           required>
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            id="togglePassword">
                                        <i class="fas fa-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Botón de Login -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i> Iniciar Sesión
                                </button>
                            </div>
                        </form>

                        <!-- Información de usuarios de prueba -->
                        <div class="mt-4 p-3 bg-light rounded">
                            <p class="text-muted small mb-2 fw-bold">
                                <i class="fas fa-info-circle me-1"></i> Usuarios de prueba:
                            </p>
                            <small class="text-muted">
                                <strong>Administrador:</strong> admin / password<br>
                                <strong>Bibliotecario:</strong> bibliotecario / password
                            </small>
                        </div>

                        <!-- Footer -->
                        <div class="text-center mt-4">
                            <small class="text-muted">
                                © 2025 FISI - UNMSM | 
                                <a href="../prueba_conexion.php" class="text-decoration-none">
                                    Sistema de Verificación
                                </a>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script para mostrar/ocultar contraseña -->
    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('clave');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>