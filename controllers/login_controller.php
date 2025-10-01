<?php
/**
 * Controlador de Autenticación
 * Sistema de Gestión Bibliotecaria FISI
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';

// Si ya está autenticado, redirigir
if (estaAutenticado()) {
    redirigirSegunRol();
}

$error = '';
$exito = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Obtener datos del formulario
    $nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
    $clave = $_POST['clave'] ?? '';
    
    // Validar que no estén vacíos
    if (empty($nombre_usuario) || empty($clave)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        try {
            // Obtener conexión a BD
            $db = Database::getInstance();
            $pdo = $db->getConexion();
            
            // Buscar usuario por nombre de usuario
            $sql = "SELECT id_usuario, nombre_usuario, clave_hash, nombres, apellidos, rol, estado, correo 
                    FROM usuarios 
                    WHERE nombre_usuario = :nombre_usuario";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['nombre_usuario' => $nombre_usuario]);
            $usuario = $stmt->fetch();
            
            if ($usuario) {
                // Verificar que el usuario esté activo
                if ($usuario['estado'] !== 'activo') {
                    $error = 'Su cuenta está inactiva o suspendida. Contacte al administrador.';
                }
                // Verificar contraseña
                elseif (password_verify($clave, $usuario['clave_hash'])) {
                    // Credenciales correctas - Iniciar sesión
                    iniciarSesion($usuario);
                    
                    // Registrar último acceso
                    $sql_update = "UPDATE usuarios SET fecha_actualizacion = NOW() WHERE id_usuario = :id";
                    $stmt_update = $pdo->prepare($sql_update);
                    $stmt_update->execute(['id' => $usuario['id_usuario']]);
                    
                    // Redirigir según rol
                    redirigirSegunRol();
                    
                } else {
                    $error = 'Usuario o contraseña incorrectos.';
                }
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
            
        } catch (PDOException $e) {
            $error = 'Error en el sistema. Por favor, intente más tarde.';
            error_log("Error de login: " . $e->getMessage());
        }
    }
}
?>