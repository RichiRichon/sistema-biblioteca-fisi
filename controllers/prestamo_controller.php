<?php
/**
 * Controlador de Préstamos
 * HU-02: Préstamos y Devoluciones - FASE 1
 * Sistema de Gestión Bibliotecaria FISI
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');

class PrestamoController {
    private $pdo;
    
    public function __construct() {
        $db = Database::getInstance();
        $this->pdo = $db->getConexion();
    }
    
    /**
     * Registrar un nuevo préstamo
     */
    public function registrarPrestamo($data) {
        try {
            $this->pdo->beginTransaction();
            
            // 1. Validar datos requeridos
            $errores = $this->validarDatosPrestamo($data);
            if (!empty($errores)) {
                throw new Exception(implode(', ', $errores));
            }
            
            $id_usuario = (int)$data['id_usuario'];
            $id_ejemplar = (int)$data['id_ejemplar'];
            $id_bibliotecario = (int)$data['id_bibliotecario'];
            $observaciones = $data['observaciones'] ?? '';
            
            // 2. Obtener información del usuario
            $usuario = $this->obtenerUsuario($id_usuario);
            if (!$usuario) {
                throw new Exception('Usuario no encontrado');
            }
            
            // 3. Validar que el usuario pueda solicitar préstamos
            $this->validarEstadoUsuario($usuario);
            
            // 4. Validar disponibilidad del ejemplar
            $this->validarDisponibilidadEjemplar($id_ejemplar);
            
            // 5. Validar límite de préstamos del usuario
            $this->validarLimitePrestamos($id_usuario, $usuario['rol']);
            
            // 6. Calcular fecha de devolución según rol
            $dias_prestamo = $this->obtenerDiasPrestamo($usuario['rol']);
            $fecha_devolucion = date('Y-m-d H:i:s', strtotime("+{$dias_prestamo} days"));
            
            // 7. Insertar préstamo
            $stmt = $this->pdo->prepare("
                INSERT INTO prestamos 
                (id_usuario, id_ejemplar, id_bibliotecario, fecha_prestamo, fecha_devolucion_esperada, estado, observaciones)
                VALUES (?, ?, ?, NOW(), ?, 'activo', ?)
            ");
            
            $stmt->execute([
                $id_usuario,
                $id_ejemplar,
                $id_bibliotecario,
                $fecha_devolucion,
                $observaciones
            ]);
            
            $id_prestamo = $this->pdo->lastInsertId();
            
            // 8. Actualizar estado del ejemplar
            $this->actualizarEstadoEjemplar($id_ejemplar, 'prestado');
            
            // 9. Registrar en historial (solo si la tabla existe)
            try {
                $this->registrarHistorial($id_prestamo, 'creado', $id_bibliotecario, 'Préstamo registrado');
            } catch (Exception $e) {
                // Si falla el historial, continuamos
                error_log("Advertencia: No se pudo registrar en historial - " . $e->getMessage());
            }
            
            $this->pdo->commit();
            
            // 10. Obtener información completa del préstamo
            $prestamo_info = $this->obtenerInformacionPrestamo($id_prestamo);
            
            return [
                'exito' => true,
                'mensaje' => 'Préstamo registrado exitosamente',
                'id_prestamo' => $id_prestamo,
                'fecha_devolucion' => $fecha_devolucion,
                'dias_prestamo' => $dias_prestamo,
                'prestamo' => $prestamo_info
            ];
            
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'exito' => false,
                'mensaje' => 'Error al registrar préstamo: ' . $e->getMessage()
            ];
        }
    }
    
    private function validarDatosPrestamo($data) {
        $errores = [];
        if (empty($data['id_usuario'])) $errores[] = 'El ID de usuario es requerido';
        if (empty($data['id_ejemplar'])) $errores[] = 'El ID del ejemplar es requerido';
        if (empty($data['id_bibliotecario'])) $errores[] = 'El ID del bibliotecario es requerido';
        return $errores;
    }
    
    private function obtenerUsuario($id_usuario) {
        $stmt = $this->pdo->prepare("
            SELECT id_usuario, nombre_usuario, nombres, apellidos, correo, rol, estado
            FROM usuarios WHERE id_usuario = ?
        ");
        $stmt->execute([$id_usuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function validarEstadoUsuario($usuario) {
        if ($usuario['estado'] !== 'activo') {
            throw new Exception('El usuario no está activo');
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as multas_pendientes
                FROM multas
                WHERE id_usuario = ? AND estado = 'pendiente'
            ");
            $stmt->execute([$usuario['id_usuario']]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado['multas_pendientes'] > 0) {
                throw new Exception('El usuario tiene multas pendientes');
            }
        } catch (PDOException $e) {
            error_log("Advertencia: Tabla multas - " . $e->getMessage());
        }
    }
    
    private function validarDisponibilidadEjemplar($id_ejemplar) {
        $stmt = $this->pdo->prepare("
            SELECT e.estado, l.titulo, l.autor, e.codigo_ejemplar
            FROM ejemplares_libros e
            INNER JOIN libros l ON e.id_libro = l.id_libro
            WHERE e.id_ejemplar = ?
        ");
        $stmt->execute([$id_ejemplar]);
        $ejemplar = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ejemplar) throw new Exception('Ejemplar no encontrado');
        if ($ejemplar['estado'] !== 'disponible') {
            throw new Exception('El ejemplar no está disponible');
        }
        return $ejemplar;
    }
    
    private function validarLimitePrestamos($id_usuario, $rol) {
        $limites = ['estudiante' => 3, 'docente' => 5, 'bibliotecario' => 5, 'administrador' => 10];
        $limite = $limites[$rol] ?? 3;
        
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total_activos FROM prestamos
            WHERE id_usuario = ? AND estado = 'activo'
        ");
        $stmt->execute([$id_usuario]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado['total_activos'] >= $limite) {
            throw new Exception("Límite de {$limite} préstamos alcanzado para {$rol}");
        }
    }
    
    private function obtenerDiasPrestamo($rol) {
        $dias = ['estudiante' => 3, 'docente' => 7, 'bibliotecario' => 7, 'administrador' => 14];
        return $dias[$rol] ?? 3;
    }
    
    private function actualizarEstadoEjemplar($id_ejemplar, $nuevo_estado) {
        $stmt = $this->pdo->prepare("UPDATE ejemplares_libros SET estado = ? WHERE id_ejemplar = ?");
        $stmt->execute([$nuevo_estado, $id_ejemplar]);
    }
    
    private function registrarHistorial($id_prestamo, $accion, $realizado_por, $observaciones) {
        $stmt = $this->pdo->prepare("
            INSERT INTO historial_prestamos (id_prestamo, accion, realizado_por, observaciones)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$id_prestamo, $accion, $realizado_por, $observaciones]);
    }
    
    private function obtenerInformacionPrestamo($id_prestamo) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, u.nombres as usuario_nombres, u.apellidos as usuario_apellidos,
                   l.titulo as libro_titulo, l.autor as libro_autor, e.codigo_ejemplar
            FROM prestamos p
            INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
            INNER JOIN ejemplares_libros e ON p.id_ejemplar = e.id_ejemplar
            INNER JOIN libros l ON e.id_libro = l.id_libro
            WHERE p.id_prestamo = ?
        ");
        $stmt->execute([$id_prestamo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function buscarUsuario($termino) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id_usuario, nombre_usuario, nombres, apellidos, correo, rol, estado
                FROM usuarios
                WHERE (nombre_usuario LIKE ? OR nombres LIKE ? OR apellidos LIKE ? OR correo LIKE ?)
                AND rol IN ('estudiante', 'docente') AND estado = 'activo'
                LIMIT 20
            ");
            $like = "%{$termino}%";
            $stmt->execute([$like, $like, $like, $like]);
            return ['exito' => true, 'usuarios' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ['exito' => false, 'mensaje' => 'Error al buscar: ' . $e->getMessage()];
        }
    }
    
    public function buscarLibroDisponible($termino) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT l.id_libro, l.titulo, l.autor, l.clasificacion,
                       e.id_ejemplar, e.codigo_ejemplar, c.nombre as categoria
                FROM libros l
                INNER JOIN ejemplares_libros e ON l.id_libro = e.id_libro
                LEFT JOIN libro_categorias lc ON l.id_libro = lc.id_libro
                LEFT JOIN categorias c ON lc.id_categoria = c.id_categoria
                WHERE l.estado = 'activo' AND e.estado = 'disponible'
                AND (l.titulo LIKE ? OR l.autor LIKE ? OR l.clasificacion LIKE ?)
                LIMIT 20
            ");
            $like = "%{$termino}%";
            $stmt->execute([$like, $like, $like]);
            return ['exito' => true, 'libros' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (Exception $e) {
            return ['exito' => false, 'mensaje' => 'Error al buscar: ' . $e->getMessage()];
        }
    }
}

// MANEJO DE PETICIONES
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_SESSION['usuario_id'])) {
        echo json_encode(['exito' => false, 'mensaje' => 'Sesión no válida']);
        exit;
    }
    
    $rol_usuario = $_SESSION['rol'] ?? '';
    $controller = new PrestamoController();
    $accion = $_REQUEST['accion'] ?? '';
    
    switch ($accion) {
        case 'registrar':
            if (!in_array($rol_usuario, ['bibliotecario', 'administrador'])) {
                echo json_encode(['exito' => false, 'mensaje' => 'Sin permisos']);
                exit;
            }
            $data = [
                'id_usuario' => $_POST['id_usuario'] ?? '',
                'id_ejemplar' => $_POST['id_ejemplar'] ?? '',
                'id_bibliotecario' => $_SESSION['usuario_id'],
                'observaciones' => $_POST['observaciones'] ?? ''
            ];
            echo json_encode($controller->registrarPrestamo($data));
            break;
            
        case 'buscar_usuario':
            echo json_encode($controller->buscarUsuario($_GET['termino'] ?? ''));
            break;
            
        case 'buscar_libro':
            echo json_encode($controller->buscarLibroDisponible($_GET['termino'] ?? ''));
            break;
            
        default:
            echo json_encode(['exito' => false, 'mensaje' => 'Acción no válida']);
    }
} else {
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido']);
}