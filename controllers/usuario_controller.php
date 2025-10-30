<?php
/**
 * Controlador de Búsqueda de Usuarios
 * Sistema de Gestión Bibliotecaria FISI
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');

class UsuarioController {
    private $pdo;
    
    public function __construct() {
        $db = Database::getInstance();
        $this->pdo = $db->getConexion();
    }
    
    /**
     * Buscar usuarios
     */
    public function buscarUsuarios($termino = '', $rol = 'todos') {
        try {
            $where_rol = '';
            $params = [];
            
            if ($rol !== 'todos') {
                $where_rol = " AND u.rol = ?";
            }
            
            $sql = "
                SELECT 
                    u.*,
                    COUNT(DISTINCT p.id_prestamo) as total_prestamos,
                    SUM(CASE WHEN p.estado = 'activo' THEN 1 ELSE 0 END) as prestamos_activos,
                    SUM(CASE WHEN p.estado = 'devuelto' THEN 1 ELSE 0 END) as prestamos_devueltos,
                    COUNT(DISTINCT m.id_multa) as total_multas,
                    SUM(CASE WHEN m.estado = 'pendiente' THEN m.monto ELSE 0 END) as multas_pendientes
                FROM usuarios u
                LEFT JOIN prestamos p ON u.id_usuario = p.id_usuario
                LEFT JOIN multas m ON u.id_usuario = m.id_usuario
                WHERE u.rol IN ('estudiante', 'docente', 'bibliotecario', 'administrador')
                AND (
                    u.nombres LIKE ? OR 
                    u.apellidos LIKE ? OR 
                    u.nombre_usuario LIKE ? OR
                    u.correo LIKE ?
                )
                $where_rol
                GROUP BY u.id_usuario
                ORDER BY u.nombres ASC
                LIMIT 50
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $like = "%{$termino}%";
            
            $params = [$like, $like, $like, $like];
            if ($rol !== 'todos') {
                $params[] = $rol;
            }
            
            $stmt->execute($params);
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'exito' => true,
                'usuarios' => $usuarios,
                'total' => count($usuarios)
            ];
            
        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al buscar usuarios: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener detalle completo de un usuario
     */
    public function obtenerDetalleUsuario($id_usuario) {
        try {
            // Información del usuario
            $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
            $stmt->execute([$id_usuario]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                return ['exito' => false, 'mensaje' => 'Usuario no encontrado'];
            }
            
            // Préstamos activos
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.*,
                    l.titulo,
                    l.autor,
                    e.codigo_ejemplar,
                    DATEDIFF(p.fecha_devolucion_esperada, NOW()) as dias_restantes
                FROM prestamos p
                INNER JOIN ejemplares_libros e ON p.id_ejemplar = e.id_ejemplar
                INNER JOIN libros l ON e.id_libro = l.id_libro
                WHERE p.id_usuario = ? AND p.estado = 'activo'
                ORDER BY p.fecha_prestamo DESC
            ");
            $stmt->execute([$id_usuario]);
            $prestamos_activos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Historial de préstamos
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.*,
                    l.titulo,
                    l.autor,
                    e.codigo_ejemplar
                FROM prestamos p
                INNER JOIN ejemplares_libros e ON p.id_ejemplar = e.id_ejemplar
                INNER JOIN libros l ON e.id_libro = l.id_libro
                WHERE p.id_usuario = ? AND p.estado = 'devuelto'
                ORDER BY p.fecha_devolucion_real DESC
                LIMIT 10
            ");
            $stmt->execute([$id_usuario]);
            $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Multas
            $stmt = $this->pdo->prepare("
                SELECT 
                    m.*,
                    p.id_prestamo,
                    l.titulo
                FROM multas m
                LEFT JOIN prestamos p ON m.id_prestamo = p.id_prestamo
                LEFT JOIN ejemplares_libros e ON p.id_ejemplar = e.id_ejemplar
                LEFT JOIN libros l ON e.id_libro = l.id_libro
                WHERE m.id_usuario = ?
                ORDER BY m.fecha_creacion DESC
            ");
            $stmt->execute([$id_usuario]);
            $multas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'exito' => true,
                'usuario' => $usuario,
                'prestamos_activos' => $prestamos_activos,
                'historial' => $historial,
                'multas' => $multas
            ];
            
        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener detalle: ' . $e->getMessage()
            ];
        }
    }
}

// ============================================
// MANEJO DE PETICIONES
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if (!isset($_SESSION['usuario_id'])) {
        echo json_encode(['exito' => false, 'mensaje' => 'Sesión no válida']);
        exit;
    }
    
    $controller = new UsuarioController();
    $accion = $_GET['accion'] ?? '';
    
    switch ($accion) {
        case 'buscar':
            $termino = $_GET['termino'] ?? '';
            $rol = $_GET['rol'] ?? 'todos';
            echo json_encode($controller->buscarUsuarios($termino, $rol));
            break;
            
        case 'detalle':
            $id_usuario = $_GET['id_usuario'] ?? 0;
            echo json_encode($controller->obtenerDetalleUsuario($id_usuario));
            break;
            
        default:
            echo json_encode(['exito' => false, 'mensaje' => 'Acción no válida']);
    }
    
} else {
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
}