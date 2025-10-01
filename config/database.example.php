<?php
/**
 * Configuración de Conexión a Base de Datos - EJEMPLO
 * Copiar este archivo como database.php y configurar con tus datos
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'biblioteca_fisi');
define('DB_USER', 'root');
define('DB_PASS', ''); // Cambiar si tienes contraseña en MySQL
define('DB_CHARSET', 'utf8mb4');

/**
 * Clase Database - Patrón Singleton
 */
class Database {
    private static $instance = null;
    private $conexion;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            $opciones = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->conexion = new PDO($dsn, DB_USER, DB_PASS, $opciones);
            
        } catch(PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConexion() {
        return $this->conexion;
    }
    
    private function __clone() {}
    
    public function __wakeup() {
        throw new Exception("No se puede deserializar un singleton");
    }
}
?>