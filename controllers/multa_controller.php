<?php
/**
 * Controlador de Multas
 * HU-02: Préstamos y Devoluciones - FASE 3
 * Sistema de Gestión Bibliotecaria FISI
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');

class MultaController {
    private $pdo;
    
    public function __construct() {
        $db = Database::getInstance();
        $this->pdo = $db->getConexion();
    }
    
    /**
     * Listar todas las multas pendientes
     */
    public function listarMultasPendientes() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    m.*,
                    u.nombre_usuario,
                    u.nombres,
                    u.apellidos,
                    u.correo,
                    u.rol,
                    p.fecha_prestamo,
                    p.fecha_devolucion_esperada,
                    p.fecha_devolucion_real,
                    l.titulo as libro_titulo,
                    l.autor as libro_autor
                FROM multas m
                INNER JOIN usuarios u ON m.id_usuario = u.id_usuario
                LEFT JOIN prestamos p ON m.id_prestamo = p.id_prestamo
                LEFT JOIN ejemplares_libros e ON p.id_ejemplar = e.id_ejemplar
                LEFT JOIN libros l ON e.id_libro = l.id_libro
                WHERE m.estado = 'pendiente'
                ORDER BY m.fecha_creacion DESC
            ");
            
            $stmt->execute();
            $multas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'exito' => true,
                'multas' => $multas,
                'total' => count($multas),
                'total_monto' => array_sum(array_column($multas, 'monto'))
            ];
            
        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al listar multas: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Listar multas de un usuario específico
     */
    public function listarMultasUsuario($id_usuario) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    m.*,
                    p.fecha_prestamo,
                    p.fecha_devolucion_esperada,
                    p.fecha_devolucion_real,
                    l.titulo as libro_titulo,
                    l.autor as libro_autor
                FROM multas m
                LEFT JOIN prestamos p ON m.id_prestamo = p.id_prestamo
                LEFT JOIN ejemplares_libros e ON p.id_ejemplar = e.id_ejemplar
                LEFT JOIN libros l ON e.id_libro = l.id_libro
                WHERE m.id_usuario = ?
                ORDER BY m.fecha_creacion DESC
            ");
            
            $stmt->execute([$id_usuario]);
            $multas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular totales por estado
            $totales = [
                'pendiente' => 0,
                'pagada' => 0,
                'perdonada' => 0
            ];
            
            foreach ($multas as $multa) {
                $totales[$multa['estado']] += $multa['monto'];
            }
            
            return [
                'exito' => true,
                'multas' => $multas,
                'total' => count($multas),
                'totales' => $totales
            ];
            
        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al listar multas del usuario: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Registrar pago de multa
     */
    public function registrarPago($data) {
        try {
            $this->pdo->beginTransaction();
            
            $id_multa = (int)$data['id_multa'];
            $id_bibliotecario = (int)$data['id_bibliotecario'];
            $observaciones = $data['observaciones'] ?? '';
            
            // Obtener información de la multa
            $stmt = $this->pdo->prepare("
                SELECT m.*, u.nombres, u.apellidos
                FROM multas m
                INNER JOIN usuarios u ON m.id_usuario = u.id_usuario
                WHERE m.id_multa = ? AND m.estado = 'pendiente'
            ");
            $stmt->execute([$id_multa]);
            $multa = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$multa) {
                throw new Exception('Multa no encontrada o ya fue procesada');
            }
            
            // Actualizar estado de la multa
            $stmt = $this->pdo->prepare("
                UPDATE multas 
                SET estado = 'pagada',
                    fecha_pago = NOW(),
                    descripcion = CONCAT(COALESCE(descripcion, ''), ?)
                WHERE id_multa = ?
            ");
            
            $obs = "\n[Pago registrado] " . date('Y-m-d H:i:s') . 
                   " por bibliotecario ID: {$id_bibliotecario}. " . $observaciones;
            $stmt->execute([$obs, $id_multa]);
            
            $this->pdo->commit();
            
            return [
                'exito' => true,
                'mensaje' => 'Pago registrado exitosamente',
                'multa' => $multa
            ];
            
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'exito' => false,
                'mensaje' => 'Error al registrar pago: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Perdonar multa
     */
    public function perdonarMulta($data) {
        try {
            $this->pdo->beginTransaction();
            
            $id_multa = (int)$data['id_multa'];
            $id_administrador = (int)$data['id_administrador'];
            $motivo = $data['motivo'] ?? '';
            
            if (empty($motivo)) {
                throw new Exception('Debe especificar el motivo del perdón');
            }
            
            // Obtener información de la multa
            $stmt = $this->pdo->prepare("
                SELECT m.*, u.nombres, u.apellidos
                FROM multas m
                INNER JOIN usuarios u ON m.id_usuario = u.id_usuario
                WHERE m.id_multa = ? AND m.estado = 'pendiente'
            ");
            $stmt->execute([$id_multa]);
            $multa = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$multa) {
                throw new Exception('Multa no encontrada o ya fue procesada');
            }
            
            // Actualizar estado de la multa
            $stmt = $this->pdo->prepare("
                UPDATE multas 
                SET estado = 'perdonada',
                    descripcion = CONCAT(COALESCE(descripcion, ''), ?)
                WHERE id_multa = ?
            ");
            
            $obs = "\n[Multa perdonada] " . date('Y-m-d H:i:s') . 
                   " por administrador ID: {$id_administrador}. Motivo: {$motivo}";
            $stmt->execute([$obs, $id_multa]);
            
            $this->pdo->commit();
            
            return [
                'exito' => true,
                'mensaje' => 'Multa perdonada exitosamente',
                'multa' => $multa
            ];
            
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'exito' => false,
                'mensaje' => 'Error al perdonar multa: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener estadísticas de multas
     */
    public function obtenerEstadisticas() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) as total_multas,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN estado = 'pagada' THEN 1 ELSE 0 END) as pagadas,
                    SUM(CASE WHEN estado = 'perdonada' THEN 1 ELSE 0 END) as perdonadas,
                    SUM(CASE WHEN estado = 'pendiente' THEN monto ELSE 0 END) as monto_pendiente,
                    SUM(CASE WHEN estado = 'pagada' THEN monto ELSE 0 END) as monto_pagado,
                    SUM(CASE WHEN estado = 'perdonada' THEN monto ELSE 0 END) as monto_perdonado
                FROM multas
            ");
            
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'exito' => true,
                'estadisticas' => $stats
            ];
            
        } catch (Exception $e) {
            return [
                'exito' => false,
                'mensaje' => 'Error al obtener estadísticas: ' . $e->getMessage()
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
    $controller = new MultaController();
    $accion = $_REQUEST['accion'] ?? '';
    
    switch ($accion) {
        case 'listar_pendientes':
            if (!in_array($rol_usuario, ['bibliotecario', 'administrador'])) {
                echo json_encode(['exito' => false, 'mensaje' => 'Sin permisos']);
                exit;
            }
            echo json_encode($controller->listarMultasPendientes());
            break;
            
        case 'listar_usuario':
            $id_usuario = $_GET['id_usuario'] ?? 0;
            
            // Solo el mismo usuario, bibliotecario o admin pueden ver
            if ($id_usuario != $_SESSION['usuario_id'] && 
                !in_array($rol_usuario, ['bibliotecario', 'administrador'])) {
                echo json_encode(['exito' => false, 'mensaje' => 'Sin permisos']);
                exit;
            }
            
            echo json_encode($controller->listarMultasUsuario($id_usuario));
            break;
            
        case 'registrar_pago':
            if (!in_array($rol_usuario, ['bibliotecario', 'administrador'])) {
                echo json_encode(['exito' => false, 'mensaje' => 'Sin permisos']);
                exit;
            }
            
            $data = [
                'id_multa' => $_POST['id_multa'] ?? 0,
                'id_bibliotecario' => $_SESSION['usuario_id'],
                'observaciones' => $_POST['observaciones'] ?? ''
            ];
            
            echo json_encode($controller->registrarPago($data));
            break;
            
        case 'perdonar_multa':
            if ($rol_usuario !== 'administrador') {
                echo json_encode(['exito' => false, 'mensaje' => 'Solo administradores pueden perdonar multas']);
                exit;
            }   
            
            $data = [
                'id_multa' => $_POST['id_multa'] ?? 0,
                'id_administrador' => $_SESSION['usuario_id'],
                'motivo' => $_POST['motivo'] ?? ''
            ];
            
            echo json_encode($controller->perdonarMulta($data));
            break;
            
        case 'estadisticas':
            if (!in_array($rol_usuario, ['bibliotecario', 'administrador'])) {
                echo json_encode(['exito' => false, 'mensaje' => 'Sin permisos']);
                exit;
            }
            echo json_encode($controller->obtenerEstadisticas());
            break;
            
        default:
            echo json_encode(['exito' => false, 'mensaje' => 'Acción no válida']);
    }
    
} else {
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
}