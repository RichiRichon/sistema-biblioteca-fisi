<?php
/**
 * Importación Masiva de Estudiantes y Docentes
 * Sistema de Gestión Bibliotecaria FISI
 */

set_time_limit(600); // 10 minutos máximo
ini_set('memory_limit', '256M');

require_once __DIR__ . '/config/database.php';

// Configuración
$archivo_estudiantes = __DIR__ . '/uploads/DataAlumnos.csv';
$archivo_docentes = __DIR__ . '/uploads/DataDocentes.csv';

// Estadísticas
$stats = [
    'estudiantes_procesados' => 0,
    'estudiantes_creados' => 0,
    'estudiantes_errores' => 0,
    'docentes_procesados' => 0,
    'docentes_creados' => 0,
    'docentes_errores' => 0,
    'errores_detalle' => []
];

try {
    $db = Database::getInstance();
    $pdo = $db->getConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #34495e; margin-top: 30px; }
        .success { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .warning { color: #f39c12; font-weight: bold; }
        .info { color: #3498db; }
        .progress { background: #ecf0f1; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .stat-box { display: inline-block; background: #3498db; color: white; padding: 15px 25px; margin: 10px; border-radius: 5px; }
        .error-list { background: #ffe6e6; padding: 15px; border-left: 4px solid #e74c3c; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #34495e; color: white; padding: 12px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f8f9fa; }
        .timestamp { color: #7f8c8d; font-size: 0.9em; }
    </style>";
    
    echo "<div class='container'>";
    echo "<h1>📚 Importación Masiva de Usuarios - Sistema Bibliotecario FISI</h1>";
    echo "<p class='timestamp'>Iniciado: " . date('Y-m-d H:i:s') . "</p>";
    
    // ========================================
    // IMPORTAR ESTUDIANTES
    // ========================================
    
    echo "<h2>👨‍🎓 IMPORTANDO ESTUDIANTES</h2>";
    
    if (!file_exists($archivo_estudiantes)) {
        throw new Exception("Archivo de estudiantes no encontrado: {$archivo_estudiantes}");
    }
    
    echo "<div class='progress'>";
    echo "<p class='info'>📂 Archivo: " . basename($archivo_estudiantes) . "</p>";
    echo "<p class='info'>📊 Procesando...</p>";
    echo "</div>";
    
    $handle = fopen($archivo_estudiantes, 'r');
    
    // Detectar BOM UTF-8 y eliminarlo
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($handle);
    }
    
    // Leer encabezados
    $headers = fgetcsv($handle, 1000, ';');
    
    // Preparar statement
    $stmt_estudiante = $pdo->prepare("
        INSERT INTO usuarios 
        (nombre_usuario, correo, clave_hash, nombres, apellidos, rol, carnet_biblioteca, dni, estado, fecha_creacion) 
        VALUES (?, ?, ?, ?, ?, 'estudiante', ?, ?, 'activo', NOW())
    ");
    
    $linea = 1; // Ya leímos el header
    
    while (($data = fgetcsv($handle, 1000, ';')) !== FALSE) {
        $linea++;
        $stats['estudiantes_procesados']++;
        
        try {
            // Extraer datos según orden confirmado
            // CÓDIGO ALUMNO | PATERNO | MATERNO | NOMBRES | CORREO INSTITUCIONAL | PROGRAMA | DOC. IDENTIDAD
            $codigo = isset($data[0]) ? trim($data[0]) : '';      // Columna 0: CÓDIGO ALUMNO
            $paterno = isset($data[1]) ? trim($data[1]) : '';     // Columna 1: PATERNO
            $materno = isset($data[2]) ? trim($data[2]) : '';     // Columna 2: MATERNO
            $nombres = isset($data[3]) ? trim($data[3]) : '';     // Columna 3: NOMBRES
            $correo = isset($data[4]) ? trim(strtolower($data[4])) : ''; // Columna 4: CORREO INSTITUCIONAL
            // Columna 5: PROGRAMA (no lo usamos)
            $dni = isset($data[6]) ? trim($data[6]) : '';         // Columna 6: DOC. IDENTIDAD
            
            // Validar datos esenciales
            if (empty($codigo) || empty($nombres) || empty($paterno) || empty($correo)) {
                $stats['estudiantes_errores']++;
                $stats['errores_detalle'][] = "Línea {$linea}: Datos incompletos - Código: {$codigo}";
                continue;
            }
            
            // Construir apellidos
            $apellidos = trim($paterno . ' ' . $materno);
            
            // Generar nombre de usuario (usar código)
            $nombre_usuario = $codigo;
            
            // Generar contraseña (código del estudiante)
            $password = $codigo;
            $clave_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Generar carnet de biblioteca
            $carnet = 'EST-' . $codigo;
            
            // Insertar en BD
            $stmt_estudiante->execute([
                $nombre_usuario,
                $correo,
                $clave_hash,
                $nombres,
                $apellidos,
                $carnet,
                $dni
            ]);
            
            $stats['estudiantes_creados']++;
            
            // Mostrar progreso cada 100 registros
            if ($stats['estudiantes_procesados'] % 100 == 0) {
                echo "<p class='success'>✓ Procesados: {$stats['estudiantes_procesados']} | Creados: {$stats['estudiantes_creados']}</p>";
                flush();
                ob_flush();
            }
            
        } catch (PDOException $e) {
            $stats['estudiantes_errores']++;
            
            // Registrar error específico
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $stats['errores_detalle'][] = "Línea {$linea}: Estudiante duplicado - Código: {$codigo}";
            } else {
                $stats['errores_detalle'][] = "Línea {$linea}: Error BD - {$e->getMessage()}";
            }
        }
    }
    
    fclose($handle);
    
    echo "<div class='progress'>";
    echo "<p class='success'>✓ Estudiantes completados</p>";
    echo "<p>Procesados: <strong>{$stats['estudiantes_procesados']}</strong></p>";
    echo "<p>Creados exitosamente: <strong class='success'>{$stats['estudiantes_creados']}</strong></p>";
    echo "<p>Errores: <strong class='error'>{$stats['estudiantes_errores']}</strong></p>";
    echo "</div>";
    
    // ========================================
    // IMPORTAR DOCENTES
    // ========================================
    
    echo "<h2>👨‍🏫 IMPORTANDO DOCENTES</h2>";
    
    if (!file_exists($archivo_docentes)) {
        throw new Exception("Archivo de docentes no encontrado: {$archivo_docentes}");
    }
    
    echo "<div class='progress'>";
    echo "<p class='info'>📂 Archivo: " . basename($archivo_docentes) . "</p>";
    echo "<p class='info'>📊 Procesando...</p>";
    echo "</div>";
    
    $handle = fopen($archivo_docentes, 'r');
    
    // Detectar BOM UTF-8 y eliminarlo
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($handle);
    }
    
    // Leer encabezados
    $headers = fgetcsv($handle, 1000, ';');
    
    // Preparar statement
    $stmt_docente = $pdo->prepare("
        INSERT INTO usuarios 
        (nombre_usuario, correo, clave_hash, nombres, apellidos, rol, carnet_biblioteca, dni, estado, fecha_creacion) 
        VALUES (?, ?, ?, ?, ?, 'docente', ?, ?, 'activo', NOW())
    ");
    
    $linea = 1;
    
    while (($data = fgetcsv($handle, 1000, ';')) !== FALSE) {
        $linea++;
        $stats['docentes_procesados']++;
        
        try {
            // Extraer datos según orden confirmado
            // Apellido Paterno | Apellido Materno | Nombres | Código | DNI | Categoría | Correo Institucional
            $paterno = isset($data[0]) ? trim($data[0]) : '';     // Columna 0: Apellido Paterno
            $materno = isset($data[1]) ? trim($data[1]) : '';     // Columna 1: Apellido Materno
            $nombres = isset($data[2]) ? trim($data[2]) : '';     // Columna 2: Nombres
            $codigo = isset($data[3]) ? trim($data[3]) : '';      // Columna 3: Código
            $dni = isset($data[4]) ? trim($data[4]) : '';         // Columna 4: DNI
            // Columna 5: Categoría (no lo usamos)
            $correo = isset($data[6]) ? trim(strtolower($data[6])) : ''; // Columna 6: Correo Institucional
            
            // Validar datos esenciales
            if (empty($codigo) || empty($nombres) || empty($paterno) || empty($correo)) {
                $stats['docentes_errores']++;
                $stats['errores_detalle'][] = "Línea {$linea} (Docentes): Datos incompletos - Código: {$codigo}";
                continue;
            }
            
            // Construir apellidos
            $apellidos = trim($paterno . ' ' . $materno);
            
            // Generar nombre de usuario (usar código)
            $nombre_usuario = $codigo;
            
            // Generar contraseña (código del docente)
            $password = $codigo;
            $clave_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Generar carnet de biblioteca
            $carnet = 'DOC-' . $codigo;
            
            // Insertar en BD
            $stmt_docente->execute([
                $nombre_usuario,
                $correo,
                $clave_hash,
                $nombres,
                $apellidos,
                $carnet,
                $dni
            ]);
            
            $stats['docentes_creados']++;
            
        } catch (PDOException $e) {
            $stats['docentes_errores']++;
            
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $stats['errores_detalle'][] = "Línea {$linea} (Docentes): Docente duplicado - Código: {$codigo}";
            } else {
                $stats['errores_detalle'][] = "Línea {$linea} (Docentes): Error BD - {$e->getMessage()}";
            }
        }
    }
    
    fclose($handle);
    
    echo "<div class='progress'>";
    echo "<p class='success'>✓ Docentes completados</p>";
    echo "<p>Procesados: <strong>{$stats['docentes_procesados']}</strong></p>";
    echo "<p>Creados exitosamente: <strong class='success'>{$stats['docentes_creados']}</strong></p>";
    echo "<p>Errores: <strong class='error'>{$stats['docentes_errores']}</strong></p>";
    echo "</div>";
    
    // ========================================
    // RESUMEN FINAL
    // ========================================
    
    echo "<h2>📊 RESUMEN DE IMPORTACIÓN</h2>";
    
    $total_procesados = $stats['estudiantes_procesados'] + $stats['docentes_procesados'];
    $total_creados = $stats['estudiantes_creados'] + $stats['docentes_creados'];
    $total_errores = $stats['estudiantes_errores'] + $stats['docentes_errores'];
    
    echo "<div class='stat-box'>
        <h3>{$total_procesados}</h3>
        <p>Registros Procesados</p>
    </div>";
    
    echo "<div class='stat-box' style='background: #27ae60;'>
        <h3>{$total_creados}</h3>
        <p>Usuarios Creados</p>
    </div>";
    
    echo "<div class='stat-box' style='background: #e74c3c;'>
        <h3>{$total_errores}</h3>
        <p>Errores</p>
    </div>";
    
    // Mostrar tabla de resumen
    echo "<table>";
    echo "<tr><th>Categoría</th><th>Procesados</th><th>Creados</th><th>Errores</th><th>Tasa de Éxito</th></tr>";
    
    $tasa_estudiantes = $stats['estudiantes_procesados'] > 0 
        ? round(($stats['estudiantes_creados'] / $stats['estudiantes_procesados']) * 100, 2) 
        : 0;
    
    echo "<tr>
        <td><strong>Estudiantes</strong></td>
        <td>{$stats['estudiantes_procesados']}</td>
        <td class='success'>{$stats['estudiantes_creados']}</td>
        <td class='error'>{$stats['estudiantes_errores']}</td>
        <td>{$tasa_estudiantes}%</td>
    </tr>";
    
    $tasa_docentes = $stats['docentes_procesados'] > 0 
        ? round(($stats['docentes_creados'] / $stats['docentes_procesados']) * 100, 2) 
        : 0;
    
    echo "<tr>
        <td><strong>Docentes</strong></td>
        <td>{$stats['docentes_procesados']}</td>
        <td class='success'>{$stats['docentes_creados']}</td>
        <td class='error'>{$stats['docentes_errores']}</td>
        <td>{$tasa_docentes}%</td>
    </tr>";
    
    $tasa_total = $total_procesados > 0 
        ? round(($total_creados / $total_procesados) * 100, 2) 
        : 0;
    
    echo "<tr style='background: #ecf0f1; font-weight: bold;'>
        <td>TOTAL</td>
        <td>{$total_procesados}</td>
        <td class='success'>{$total_creados}</td>
        <td class='error'>{$total_errores}</td>
        <td>{$tasa_total}%</td>
    </tr>";
    echo "</table>";
    
    // Mostrar errores detallados si los hay
    if (!empty($stats['errores_detalle'])) {
        echo "<h2>⚠️ Errores Detallados</h2>";
        echo "<div class='error-list'>";
        echo "<p><strong>Total de errores: " . count($stats['errores_detalle']) . "</strong></p>";
        echo "<ul>";
        // Mostrar solo los primeros 50 errores
        $errores_mostrar = array_slice($stats['errores_detalle'], 0, 50);
        foreach ($errores_mostrar as $error) {
            echo "<li>{$error}</li>";
        }
        if (count($stats['errores_detalle']) > 50) {
            echo "<li><em>... y " . (count($stats['errores_detalle']) - 50) . " errores más</em></li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    // Verificación en BD
    echo "<h2>✅ Verificación en Base de Datos</h2>";
    
    $stmt = $pdo->query("SELECT rol, COUNT(*) as total FROM usuarios GROUP BY rol ORDER BY rol");
    $usuarios_por_rol = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr><th>Rol</th><th>Total de Usuarios</th></tr>";
    foreach ($usuarios_por_rol as $row) {
        echo "<tr><td><strong>" . ucfirst($row['rol']) . "</strong></td><td>{$row['total']}</td></tr>";
    }
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $total_bd = $stmt->fetchColumn();
    echo "<tr style='background: #ecf0f1; font-weight: bold;'><td>TOTAL</td><td>{$total_bd}</td></tr>";
    echo "</table>";
    
    echo "<div class='progress' style='background: #d4edda; border: 2px solid #28a745;'>";
    echo "<h3 class='success'>✓ IMPORTACIÓN COMPLETADA EXITOSAMENTE</h3>";
    echo "<p class='timestamp'>Finalizado: " . date('Y-m-d H:i:s') . "</p>";
    echo "<p><strong>IMPORTANTE:</strong> Todos los usuarios deben cambiar su contraseña en el primer inicio de sesión.</p>";
    echo "<p><strong>Contraseña temporal:</strong> El código de estudiante/docente</p>";
    echo "</div>";
    
    echo "</div>"; // Cerrar container
    
} catch (Exception $e) {
    echo "<div class='error-list'>";
    echo "<h2>❌ ERROR CRÍTICO</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
