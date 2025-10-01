<?php
/**
 * Página Principal del Sistema
 * Redirige al login o al dashboard según el estado de sesión
 */

require_once 'includes/session.php';

// Si ya está autenticado, redirigir al dashboard correspondiente
if (estaAutenticado()) {
    redirigirSegunRol();
} else {
    // Si no está autenticado, redirigir al login
    header("Location: views/login.php");
    exit();
}
?>