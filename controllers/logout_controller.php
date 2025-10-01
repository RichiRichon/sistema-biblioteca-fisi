<?php
/**
 * Controlador de Cierre de Sesión
 * Sistema de Gestión Bibliotecaria FISI
 */

// Usar ruta absoluta para el require
require_once __DIR__ . '/../includes/session.php';

// Limpiar todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión si existe
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destruir la sesión
session_destroy();

// Redirigir al login
header("Location: /biblioteca-fisi/views/login.php");
exit();
?>