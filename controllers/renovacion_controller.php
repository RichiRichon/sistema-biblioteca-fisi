<?php
/**
 * Controlador de Renovaciones
 * HU-02: Préstamos y Devoluciones - FASE 3
 * Sistema de Gestión Bibliotecaria FISI
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');

class RenovacionController {
    private $pdo;
    private $max_renovaciones = 1; // Máximo número de renovaciones permitidas
    
    public function __construct() {
        $db = Database::getInstance();
        $this->pdo = $db->getConexion();
    }
    
    /**
     * Buscar préstamos renovables de un usuario
     */
    public function buscarPrestamosRenovables($termino) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.*,
                    u.nombre_usuario,
                    u.nombres,
                    u.apellidos,
                    u.correo,
                    u.rol,
                    l.titulo as libro_titulo,
                    l.autor as libro_autor,
                    l.clasificacion,
                    e.codigo_ejemplar,
                    DATEDIFF(p.fecha_devolucion_esperada, NOW()) as dias_restantes,
                    CASE 
                        WHEN p.numero_renovaciones >= ? THEN 'Límite alcanzado'
                        WHEN p.fecha_devolucion_esperada < NOW() THEN 'Préstamo vencido'
                        WHEN EXISTS(
                            SELECT 1 FROM multas m 
                            WHERE m.id_usuario = p.id_usuario 
                            AND m.estado = 'pendiente'
                        ) THEN 'Tiene multas pendientes'
                        ELSE 'Renovable'
                    END as estado_renovacion
                FROM prestamos p
                INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                INNER JOIN ejemplares_libros e ON p.id_ejemplar = e.id_ejemplar
                INNER JOIN libros l ON e.id_libro = l.id_libro
                WHERE p.estado = 'activo'
                AND (u.nombre_usuario LIKE ? OR u.nombres LIKE ? OR u.apellidos LIKE ? 
                     OR e.codigo_ejemplar LIKE ? OR l.titulo LIKE ?)
                ORDER BY p.fecha_prestamo DESC
                LIMIT 20
            ");
            
            $like = "%{$termino}%";
            $stmt->execute([$this->max_renovaciones, $like, $like, $like, $like, $like]);
            $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'exito' => true,
                'prestamos' => $prestamos,
                'total' => count($prestamos)
            ];
            
        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al buscar préstamos: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Renovar un préstamo
     */
    public function renovarPrestamo($data) {
        try {
            $this->pdo->beginTransaction();
            
            $id_prestamo = (int)$data['id_prestamo'];
            $id_bibliotecario = (int)$data['id_bibliotecario'];
            $observaciones = $data['observaciones'] ?? '';
            
            // 1. Obtener información del préstamo
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.*,
                    u.id_usuario,
                    u.rol,
                    u.nombres,
                    u.apellidos,
                    l.titulo as libro_titulo
                FROM prestamos p
                INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                INNER JOIN ejemplares_libros e ON p.id_ejemplar = e.id_ejemplar
                INNER JOIN libros l ON e.id_libro = l.id_libro
                WHERE p.id_prestamo = ? AND p.estado = 'activo'
            ");
            $stmt->execute([$id_prestamo]);
            $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$prestamo) {
                throw new Exception('Préstamo no encontrado o ya fue devuelto');
            }
            
            // 2. Validar que se pueda renovar
            $validacion = $this->validarRenovacion($prestamo);
            if (!$validacion['puede_renovar']) {
                throw new Exception($validacion['mensaje']);
            }
            
            // 3. Calcular nueva fecha de devolución
            $dias_adicionales = $this->obtenerDiasPrestamo($prestamo['rol']);
            $nueva_fecha = date('Y-m-d H:i:s', strtotime($prestamo['fecha_devolucion_esperada'] . " +{$dias_adicionales} days"));
            
            // 4. Actualizar préstamo
            $stmt = $this->pdo->prepare("
                UPDATE prestamos 
                SET fecha_devolucion_esperada = ?,
                    numero_renovaciones = numero_renovaciones + 1,
                    observaciones = CONCAT(COALESCE(observaciones, ''), ?)
                WHERE id_prestamo = ?
            ");
            
            $obs = "\n[Renovación] " . date('Y-m-d H:i:s') . 
                   " - Renovado por {$dias_adicionales} días más. " . $observaciones;
            $stmt->execute([$nueva_fecha, $obs, $id_prestamo]);
            
            // 5. Registrar en historial
            $numero_renovacion = $prestamo['numero_renovaciones'] + 1;
            $this->registrarHistorial($id_prestamo, 'renovado', $id_bibliotecario, 
                "Renovación #{$numero_renovacion}. Nueva fecha: {$nueva_fecha}");
            
            $this->pdo->commit();
            
            return [
                'exito' => true,
                'mensaje' => 'Préstamo renovado exitosamente',
                'nueva_fecha' => $nueva_fecha,
                'dias_adicionales' => $dias_adicionales,
                'numero_renovacion' => $numero_renovacion
            ];
            
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'exito' => false,
                'mensaje' => 'Error al renovar préstamo: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validar si un préstamo puede ser renovado
     */
    private function validarRenovacion($prestamo) {
        // 1. Verificar número de renovaciones
        if ($prestamo['numero_renovaciones'] >= $this->max_renovaciones) {
            return [
                'puede_renovar' => false,
                'mensaje' => 'El préstamo ya alcanzó el límite de renovaciones (' . $this->max_renovaciones . ')'
            ];
        }
        
        // 2. Verificar que no esté vencido
        if (strtotime($prestamo['fecha_devolucion_esperada']) < time()) {
            return [
                'puede_renovar' => false,
                'mensaje' => 'No se puede renovar un préstamo vencido. Debe devolverlo primero'
            ];
        }
        
        // 3. Verificar que no tenga multas pendientes
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as multas_pendientes
            FROM multas
            WHERE id_usuario = ? AND estado = 'pendiente'
        ");
        $stmt->execute([$prestamo['id_usuario']]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado['multas_pendientes'] > 0) {
            return [
                'puede_renovar' => false,
                'mensaje' => 'El usuario tiene multas pendientes. Debe pagarlas antes de renovar'
            ];
        }
        
        return [
            'puede_renovar' => true,
            'mensaje' => 'Préstamo puede ser renovado'
        ];
    }
    
    /**
     * Obtener días de préstamo según rol
     */
    private function obtenerDiasPrestamo($rol) {
        $dias = [
            'estudiante' => 3,
            'docente' => 7,
            'bibliotecario' => 7,
            'administrador' => 14
        ];
        
        return $dias[$rol] ?? 3;
    }
    
    /**
     * Registrar acción en historial
     */
    private function registrarHistorial($id_prestamo, $accion, $realizado_por, $observaciones) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO historial_prestamos (id_prestamo, accion, realizado_por, observaciones)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$id_prestamo, $accion, $realizado_por, $observaciones]);
        } catch (Exception $e) {
            error_log("Error al registrar historial: " . $e->getMessage());
        }
    }
    
    /**
     * Listar préstamos por vencer (próximos 3 días)
     */
    public function listarProximosVencer() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.*,
                    u.nombre_usuario,
                    u.nombres,
                    u.apellidos,
                    u.correo,
                    l.titulo as libro_titulo,
                    l.autor as libro_autor,
                    e.codigo_ejemplar,
                    DATEDIFF(p.fecha_devolucion_esperada, NOW()) as dias_restantes
                FROM prestamos p
                INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                INNER JOIN ejemplares_libros e ON p.id_ejemplar = e.id_ejemplar
                INNER JOIN libros l ON e.id_libro = l.id_libro
                WHERE p.estado = 'activo'
                AND p.fecha_devolucion_esperada BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)
                AND p.numero_renovaciones < ?
                ORDER BY p.fecha_devolucion_esperada ASC
            ");
            
            $stmt->execute([$this->max_renovaciones]);
            $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'exito' => true,
                'prestamos' => $prestamos,
                'total' => count($prestamos)
            ];
            
        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al listar préstamos: ' . $e->getMessage()
            ];
        }
    }
}

// ============================================
// MANEJO DE PETICIONES
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    
    if (!isset($_SESSION['usuario_id'])) {
        echo json_encode(['exito' => false, 'mensaje' => 'Sesión no válida']);
        exit;
    }
    
    $rol_usuario = $_SESSION['rol'] ?? '';
    $controller = new RenovacionController();
    $accion = $_REQUEST['accion'] ?? '';
    
    switch ($accion) {
        case 'buscar_renovables':
            $termino = $_GET['termino'] ?? '';
            echo json_encode($controller->buscarPrestamosRenovables($termino));
            break;
            
        case 'renovar':
            if (!in_array($rol_usuario, ['bibliotecario', 'administrador'])) {
                echo json_encode(['exito' => false, 'mensaje' => 'Sin permisos']);
                exit;
            }
            
            $data = [
                'id_prestamo' => $_POST['id_prestamo'] ?? 0,
                'id_bibliotecario' => $_SESSION['usuario_id'],
                'observaciones' => $_POST['observaciones'] ?? ''
            ];
            
            echo json_encode($controller->renovarPrestamo($data));
            break;
            
        case 'proximos_vencer':
            echo json_encode($controller->listarProximosVencer());
            break;
            
        default:
            echo json_encode(['exito' => false, 'mensaje' => 'Acción no válida']);
    }
    
} else {
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
}