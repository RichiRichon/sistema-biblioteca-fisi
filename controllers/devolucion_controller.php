<?php
/**
 * Controlador de Devoluciones
 * HU-02: Préstamos y Devoluciones - FASE 2
 * Sistema de Gestión Bibliotecaria FISI
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');

class DevolucionController {
    private $pdo;
    private $monto_multa_por_dia = 1.00; // S/. 1.00 por día de retraso
    
    public function __construct() {
        $db = Database::getInstance();
        $this->pdo = $db->getConexion();
    }
    
    /**
     * Buscar préstamo activo por código de usuario o ejemplar
     */
    public function buscarPrestamoActivo($termino) {
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
                    DATEDIFF(NOW(), p.fecha_devolucion_esperada) as dias_retraso
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
            $stmt->execute([$like, $like, $like, $like, $like]);
            $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular multa para cada préstamo
            foreach ($prestamos as &$prestamo) {
                $dias_retraso = (int)$prestamo['dias_retraso'];
                $prestamo['tiene_retraso'] = $dias_retraso > 0;
                $prestamo['multa_calculada'] = $dias_retraso > 0 ? ($dias_retraso * $this->monto_multa_por_dia) : 0;
            }
            
            return [
                'exito' => true,
                'prestamos' => $prestamos,
                'total' => count($prestamos)
            ];
            
        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al buscar préstamo: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener detalle de un préstamo específico
     */
    public function obtenerDetallePrestamo($id_prestamo) {
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
                    e.estado as ejemplar_estado,
                    b.nombres as bibliotecario_nombres,
                    b.apellidos as bibliotecario_apellidos,
                    DATEDIFF(NOW(), p.fecha_devolucion_esperada) as dias_retraso
                FROM prestamos p
                INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                INNER JOIN ejemplares_libros e ON p.id_ejemplar = e.id_ejemplar
                INNER JOIN libros l ON e.id_libro = l.id_libro
                LEFT JOIN usuarios b ON p.id_bibliotecario = b.id_usuario
                WHERE p.id_prestamo = ?
            ");
            
            $stmt->execute([$id_prestamo]);
            $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$prestamo) {
                return ['exito' => false, 'mensaje' => 'Préstamo no encontrado'];
            }
            
            // Calcular multa
            $dias_retraso = (int)$prestamo['dias_retraso'];
            $prestamo['tiene_retraso'] = $dias_retraso > 0;
            $prestamo['multa_calculada'] = $dias_retraso > 0 ? ($dias_retraso * $this->monto_multa_por_dia) : 0;
            
            return [
                'exito' => true,
                'prestamo' => $prestamo
            ];
            
        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener préstamo: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Registrar devolución de un préstamo
     */
    public function registrarDevolucion($data) {
        try {
            $this->pdo->beginTransaction();
            
            $id_prestamo = (int)$data['id_prestamo'];
            $id_bibliotecario = (int)$data['id_bibliotecario'];
            $observaciones = $data['observaciones'] ?? '';
            $estado_ejemplar = $data['estado_ejemplar'] ?? 'disponible';
            
            // 1. Obtener información del préstamo
            $stmt = $this->pdo->prepare("
                SELECT p.*, 
                       DATEDIFF(NOW(), p.fecha_devolucion_esperada) as dias_retraso,
                       e.id_ejemplar,
                       u.id_usuario
                FROM prestamos p
                INNER JOIN ejemplares_libros e ON p.id_ejemplar = e.id_ejemplar
                INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                WHERE p.id_prestamo = ? AND p.estado = 'activo'
            ");
            $stmt->execute([$id_prestamo]);
            $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$prestamo) {
                throw new Exception('Préstamo no encontrado o ya fue devuelto');
            }
            
            $dias_retraso = (int)$prestamo['dias_retraso'];
            $tiene_multa = $dias_retraso > 0;
            $monto_multa = 0;
            $id_multa = null;
            
            // 2. Si hay retraso, crear multa
            if ($tiene_multa) {
                $monto_multa = $dias_retraso * $this->monto_multa_por_dia;
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO multas 
                    (id_usuario, id_prestamo, monto, tipo, estado, dias_retraso, descripcion)
                    VALUES (?, ?, ?, 'retraso', 'pendiente', ?, ?)
                ");
                
                $descripcion = "Multa por retraso de {$dias_retraso} día(s) en la devolución";
                $stmt->execute([
                    $prestamo['id_usuario'],
                    $id_prestamo,
                    $monto_multa,
                    $dias_retraso,
                    $descripcion
                ]);
                
                $id_multa = $this->pdo->lastInsertId();
            }
            
            // 3. Actualizar préstamo como devuelto
            $stmt = $this->pdo->prepare("
                UPDATE prestamos 
                SET estado = 'devuelto',
                    fecha_devolucion_real = NOW(),
                    observaciones = CONCAT(COALESCE(observaciones, ''), ?)
                WHERE id_prestamo = ?
            ");
            
            $obs_adicional = "\n[Devolución] " . date('Y-m-d H:i:s') . ": " . $observaciones;
            $stmt->execute([$obs_adicional, $id_prestamo]);
            
            // 4. Actualizar estado del ejemplar
            $stmt = $this->pdo->prepare("
                UPDATE ejemplares_libros 
                SET estado = ?
                WHERE id_ejemplar = ?
            ");
            $stmt->execute([$estado_ejemplar, $prestamo['id_ejemplar']]);
            
            // 5. Registrar en historial
            $this->registrarHistorial($id_prestamo, 'devuelto', $id_bibliotecario, 
                "Devolución registrada. " . ($tiene_multa ? "Multa generada: S/. {$monto_multa}" : "Sin retraso"));
            
            $this->pdo->commit();
            
            return [
                'exito' => true,
                'mensaje' => 'Devolución registrada exitosamente',
                'tiene_multa' => $tiene_multa,
                'dias_retraso' => $dias_retraso,
                'monto_multa' => $monto_multa,
                'id_multa' => $id_multa
            ];
            
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'exito' => false,
                'mensaje' => 'Error al registrar devolución: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Listar préstamos vencidos
     */
    public function listarPrestamosVencidos() {
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
                    e.codigo_ejemplar,
                    DATEDIFF(NOW(), p.fecha_devolucion_esperada) as dias_retraso
                FROM prestamos p
                INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
                INNER JOIN ejemplares_libros e ON p.id_ejemplar = e.id_ejemplar
                INNER JOIN libros l ON e.id_libro = l.id_libro
                WHERE p.estado = 'activo'
                AND p.fecha_devolucion_esperada < NOW()
                ORDER BY dias_retraso DESC
            ");
            
            $stmt->execute();
            $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular multa para cada préstamo
            foreach ($prestamos as &$prestamo) {
                $dias_retraso = (int)$prestamo['dias_retraso'];
                $prestamo['multa_calculada'] = $dias_retraso * $this->monto_multa_por_dia;
            }
            
            return [
                'exito' => true,
                'prestamos' => $prestamos,
                'total' => count($prestamos)
            ];
            
        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al listar préstamos vencidos: ' . $e->getMessage()
            ];
        }
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
    $controller = new DevolucionController();
    $accion = $_REQUEST['accion'] ?? '';
    
    switch ($accion) {
        case 'buscar_prestamo':
            $termino = $_GET['termino'] ?? '';
            echo json_encode($controller->buscarPrestamoActivo($termino));
            break;
            
        case 'detalle_prestamo':
            $id_prestamo = $_GET['id_prestamo'] ?? 0;
            echo json_encode($controller->obtenerDetallePrestamo($id_prestamo));
            break;
            
        case 'registrar_devolucion':
            if (!in_array($rol_usuario, ['bibliotecario', 'administrador'])) {
                echo json_encode(['exito' => false, 'mensaje' => 'Sin permisos']);
                exit;
            }
            
            $data = [
                'id_prestamo' => $_POST['id_prestamo'] ?? 0,
                'id_bibliotecario' => $_SESSION['usuario_id'],
                'observaciones' => $_POST['observaciones'] ?? '',
                'estado_ejemplar' => $_POST['estado_ejemplar'] ?? 'disponible'
            ];
            
            echo json_encode($controller->registrarDevolucion($data));
            break;
            
        case 'listar_vencidos':
            echo json_encode($controller->listarPrestamosVencidos());
            break;
            
        default:
            echo json_encode(['exito' => false, 'mensaje' => 'Acción no válida']);
    }
    
} else {
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
}