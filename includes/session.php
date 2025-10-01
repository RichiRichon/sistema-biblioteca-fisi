<?php
/**
 * Manejo de Sesiones del Sistema
 * Sistema de Gestión Bibliotecaria FISI
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verificar si el usuario está autenticado
 */
function estaAutenticado() {
    return isset($_SESSION['usuario_id']) && isset($_SESSION['rol']);
}

/**
 * Verificar si el usuario tiene un rol específico
 */
function tieneRol($rol) {
    if (!estaAutenticado()) {
        return false;
    }
    return $_SESSION['rol'] === $rol;
}

/**
 * Verificar si el usuario tiene uno de varios roles
 */
function tieneAlgunRol($roles) {
    if (!estaAutenticado()) {
        return false;
    }
    return in_array($_SESSION['rol'], $roles);
}

/**
 * Obtener información del usuario actual
 */
function obtenerUsuarioActual() {
    if (!estaAutenticado()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['usuario_id'],
        'nombre_usuario' => $_SESSION['nombre_usuario'],
        'nombres' => $_SESSION['nombres'],
        'apellidos' => $_SESSION['apellidos'],
        'rol' => $_SESSION['rol'],
        'correo' => $_SESSION['correo']
    ];
}

/**
 * Iniciar sesión de usuario
 */
function iniciarSesion($usuario) {
    $_SESSION['usuario_id'] = $usuario['id_usuario'];
    $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
    $_SESSION['nombres'] = $usuario['nombres'];
    $_SESSION['apellidos'] = $usuario['apellidos'];
    $_SESSION['rol'] = $usuario['rol'];
    $_SESSION['correo'] = $usuario['correo'];
    $_SESSION['ultima_actividad'] = time();
}

/**
 * Cerrar sesión
 */
function cerrarSesion() {
    // Eliminar todas las variables de sesión
    $_SESSION = array();
    
    // Destruir la sesión
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    
    // Redirigir al login
    header("Location: login.php");
    exit();
}

/**
 * Verificar tiempo de inactividad (30 minutos)
 */
function verificarTiempoInactividad() {
    $tiempo_max_inactividad = 1800; // 30 minutos en segundos
    
    if (isset($_SESSION['ultima_actividad'])) {
        $tiempo_inactivo = time() - $_SESSION['ultima_actividad'];
        
        if ($tiempo_inactivo > $tiempo_max_inactividad) {
            cerrarSesion();
        }
    }
    
    $_SESSION['ultima_actividad'] = time();
}

/**
 * Proteger página (requiere autenticación)
 */
function protegerPagina($roles_permitidos = null) {
    if (!estaAutenticado()) {
        header("Location: login.php");
        exit();
    }
    
    verificarTiempoInactividad();
    
    if ($roles_permitidos !== null) {
        if (is_array($roles_permitidos)) {
            if (!tieneAlgunRol($roles_permitidos)) {
                header("Location: acceso_denegado.php");
                exit();
            }
        } else {
            if (!tieneRol($roles_permitidos)) {
                header("Location: acceso_denegado.php");
                exit();
            }
        }
    }
}

/**
 * Redirigir según rol del usuario
 */
/**
 * Redirigir según rol del usuario
 */
function redirigirSegunRol() {
    if (!estaAutenticado()) {
        header("Location: " . $_SERVER['DOCUMENT_ROOT'] . "/biblioteca-fisi/views/login.php");
        exit();
    }
    
    $base_url = "/biblioteca-fisi/views/";
    
    switch ($_SESSION['rol']) {
        case 'estudiante':
            header("Location: " . $base_url . "estudiante_dashboard.php");
            break;
        case 'docente':
            header("Location: " . $base_url . "docente_dashboard.php");
            break;
        case 'bibliotecario':
            header("Location: " . $base_url . "bibliotecario_dashboard.php");
            break;
        case 'administrador':
            header("Location: " . $base_url . "admin_dashboard.php");
            break;
        default:
            header("Location: " . $base_url . "login.php");
    }
    exit();
}
?>