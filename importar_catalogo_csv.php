<?php
/**
 * Script de Importación del Catálogo Completo - Versión CSV
 * 
 * PREPARACIÓN:
 * 1. Convertir el Excel a CSV:
 *    - Abrir CATALOGO_DE_LIBROS_FISI_RC.xlsx en Excel
 *    - Archivo > Guardar como > Tipo: CSV (delimitado por comas) (*.csv)
 *    - Guardar como: catalogo_libros.csv
 * 2. Subir catalogo_libros.csv a: /biblioteca-fisi/uploads/
 * 3. Ejecutar: http://localhost/biblioteca-fisi/importar_catalogo_csv.php
 */

// Aumentar límites
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir configuración
require_once __DIR__ . '/config/database.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importación de Catálogo CSV - Sistema Bibliotecario FISI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; }
        .log-container {
            max-height: 400px;
            overflow-y: auto;
            background: #1e1e1e;
            color: #d4d4d4;
            border-radius: 5px;
            padding: 15px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 13px;
            line-height: 1.5;
        }
        .log-success { color: #4ec9b0; }
        .log-warning { color: #dcdcaa; }
        .log-error { color: #f48771; }
        .log-info { color: #9cdcfe; }
        .progress-bar-animated {
            animation: progress-bar-stripes 1s linear infinite;
        }
    </style>
</head>
<body>
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-lg">
                    <div class="card-header bg-gradient bg-primary text-white py-3">
                        <h3 class="mb-0">📚 Importación del Catálogo Completo FISI</h3>
                        <small>Sistema de Gestión Bibliotecaria</small>
                    </div>
                    <div class="card-body p-4">

<?php
$archivo_csv = __DIR__ . '/uploads/catalogo_libros.csv';

// Verificar archivo
if (!file_exists($archivo_csv)) {
    echo '<div class="alert alert-danger">';
    echo '<h5>❌ Archivo no encontrado</h5>';
    echo '<p><strong>Pasos para solucionar:</strong></p>';
    echo '<ol>';
    echo '<li>Abre el archivo Excel: <code>CATALOGO_DE_LIBROS_FISI_RC.xlsx</code></li>';
    echo '<li>En Excel: <strong>Archivo</strong> → <strong>Guardar como</strong></li>';
    echo '<li>Tipo de archivo: <strong>CSV UTF-8 (delimitado por comas) (*.csv)</strong></li>';
    echo '<li>Nombre: <code>catalogo_libros.csv</code></li>';
    echo '<li>Guardar en: <code>biblioteca-fisi/uploads/</code></li>';
    echo '<li>Actualiza esta página</li>';
    echo '</ol>';
    echo '<p class="mb-0"><small>Buscando en: ' . $archivo_csv . '</small></p>';
    echo '</div>';
    exit;
}

// Información del archivo
$tamano_mb = round(filesize($archivo_csv) / 1024 / 1024, 2);
echo '<div class="alert alert-info mb-3">';
echo '<h6 class="alert-heading">📄 Archivo detectado</h6>';
echo '<p class="mb-1"><strong>Ubicación:</strong> ' . basename($archivo_csv) . '</p>';
echo '<p class="mb-0"><strong>Tamaño:</strong> ' . $tamano_mb . ' MB</p>';
echo '</div>';

// Iniciar log
echo '<div class="log-container mb-3" id="log">';
flush(); ob_flush();

try {
    // Conectar BD
    $pdo = Database::getInstance()->getConexion();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    log_msg('Conexión establecida con la base de datos', 'success');
    
    // Leer CSV
    log_msg('Abriendo archivo CSV...', 'info');
    $handle = fopen($archivo_csv, 'r');
    
    if (!$handle) {
        throw new Exception('No se pudo abrir el archivo CSV');
    }
    
    // Detectar encoding
    stream_filter_append($handle, 'convert.iconv.UTF-8/UTF-8');
    
    // Saltar primeras 2 filas (encabezados)
    fgets($handle);
    fgets($handle);
    
    // Estadísticas
    $stats = [
        'procesados' => 0,
        'libros_nuevos' => 0,
        'libros_existentes' => 0,
        'ejemplares_nuevos' => 0,
        'ejemplares_existentes' => 0,
        'categorias_nuevas' => 0,
        'errores' => 0
    ];
    
    // Cache
    $libros_cache = [];
    $categorias_cache = [];
    
    log_msg('Iniciando procesamiento de registros...', 'info');
    
    // Procesar línea por línea
    $linea = 0;
    while (($data = fgetcsv($handle, 1000, ';')) !== FALSE) { // ✅ Cambiado a punto y coma
        $linea++;
        $stats['procesados']++;
        
        // Extraer datos (ajustar índices según tu CSV con punto y coma)
        $vacio = isset($data[0]) ? trim($data[0]) : '';
        $numero = isset($data[1]) ? trim($data[1]) : '';
        $titulo = isset($data[2]) ? trim($data[2]) : '';
        $autor = isset($data[3]) ? trim($data[3]) : '';
        $clasificacion = isset($data[4]) ? trim($data[4]) : '';
        $anio = isset($data[5]) ? trim($data[5]) : null;
        
        // Validar datos mínimos (saltar líneas vacías o encabezados)
        if (empty($titulo) || empty($clasificacion) || empty($numero) || !is_numeric($numero)) {
            continue;
        }
        
        // Limpiar datos - IMPORTANTE: eliminar saltos de línea y comillas
        $titulo = str_replace(["\n", "\r", '"', "  "], [' ', ' ', '', ' '], $titulo);
        $titulo = trim($titulo);
        
        $autor = str_replace(["\n", "\r", '"'], [' ', ' ', ''], $autor);
        $autor = trim($autor);
        
        // Validar año
        if ($anio && ($anio < 1900 || $anio > 2030)) {
            $anio = null;
        }
        
        try {
            // 1. PROCESAR CATEGORÍA
            $codigo_cat = strtoupper(substr($clasificacion, 0, 2));
            
            if (!isset($categorias_cache[$codigo_cat])) {
                $stmt = $pdo->prepare("SELECT id_categoria FROM categorias WHERE codigo = ?");
                $stmt->execute([$codigo_cat]);
                $cat = $stmt->fetch();
                
                if (!$cat) {
                    $nombre_cat = getNombreCategoria($codigo_cat);
                    $stmt = $pdo->prepare("
                        INSERT INTO categorias (nombre, codigo, descripcion) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([
                        $nombre_cat,
                        $codigo_cat,
                        "Libros con clasificación {$codigo_cat}"
                    ]);
                    $categorias_cache[$codigo_cat] = $pdo->lastInsertId();
                    $stats['categorias_nuevas']++;
                    log_msg("✓ Nueva categoría: {$nombre_cat} ({$codigo_cat})", 'success');
                } else {
                    $categorias_cache[$codigo_cat] = $cat['id_categoria'];
                }
            }
            
            $categoria_id = $categorias_cache[$codigo_cat];
            
            // 2. IDENTIFICAR CLASIFICACIÓN BASE
            $clasificacion_base = preg_replace('/\s*ej\.\s*\d+$/i', '', $clasificacion);
            $clasificacion_base = trim($clasificacion_base);
            
            // 3. BUSCAR O CREAR EL LIBRO (TÍTULO ÚNICO)
            $libro_id = null;
            $cache_key = md5(mb_strtolower($titulo) . mb_strtolower($autor));
            
            if (!isset($libros_cache[$cache_key])) {
                $stmt = $pdo->prepare("
                    SELECT id_libro FROM libros 
                    WHERE LOWER(titulo) = LOWER(?) AND LOWER(autor) = LOWER(?)
                ");
                $stmt->execute([$titulo, $autor]);
                $libro = $stmt->fetch();
                
                if (!$libro) {
                    $stmt = $pdo->prepare("
                        INSERT INTO libros 
                        (titulo, autor, clasificacion, año_publicacion, estado) 
                        VALUES (?, ?, ?, ?, 'activo')
                    ");
                    $stmt->execute([$titulo, $autor, $clasificacion_base, $anio]);
                    $libros_cache[$cache_key] = $pdo->lastInsertId();
                    $stats['libros_nuevos']++;
                    
                    // Asociar con categoría
                    $libro_id_nuevo = $libros_cache[$cache_key];
                    $stmt = $pdo->prepare("
                        INSERT INTO libro_categorias (id_libro, id_categoria) VALUES (?, ?)
                    ");
                    $stmt->execute([$libro_id_nuevo, $categoria_id]);
                } else {
                    $libros_cache[$cache_key] = $libro['id_libro'];
                    $stats['libros_existentes']++;
                }
            } else {
                $stats['libros_existentes']++;
            }
            
            $libro_id = $libros_cache[$cache_key];
            
            // 4. CREAR EJEMPLAR
            $stmt = $pdo->prepare("
                SELECT id_ejemplar FROM ejemplares_libros 
                WHERE id_libro = ? AND codigo_ejemplar = ?
            ");
            $stmt->execute([$libro_id, $clasificacion]);
            
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("
                    INSERT INTO ejemplares_libros 
                    (id_libro, codigo_ejemplar, estado) 
                    VALUES (?, ?, 'disponible')
                ");
                $stmt->execute([$libro_id, $clasificacion]);
                $stats['ejemplares_nuevos']++;
            } else {
                $stats['ejemplares_existentes']++;
            }
            
            // Log de progreso cada 100 registros
            if ($stats['procesados'] % 100 == 0) {
                log_msg("Procesados: {$stats['procesados']} registros | Libros nuevos: {$stats['libros_nuevos']}", 'info');
                flush(); ob_flush();
            }
            
        } catch (PDOException $e) {
            $stats['errores']++;
            if ($stats['errores'] <= 5) { // Solo mostrar primeros 5 errores
                log_msg("Error en línea {$linea}: " . $e->getMessage(), 'error');
            }
        }
    }
    
    fclose($handle);
    
    log_msg('', 'info');
    log_msg('=== IMPORTACIÓN COMPLETADA ===', 'success');
    log_msg("Total procesados: {$stats['procesados']}", 'info');
    log_msg("Libros nuevos: {$stats['libros_nuevos']}", 'success');
    log_msg("Ejemplares nuevos: {$stats['ejemplares_nuevos']}", 'success');
    log_msg("Categorías nuevas: {$stats['categorias_nuevas']}", 'success');
    
    if ($stats['errores'] > 0) {
        log_msg("Errores: {$stats['errores']}", 'warning');
    }
    
    echo '</div>'; // Cerrar log
    
    // Mostrar estadísticas
    ?>
    <div class="row g-3 mt-2">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h2 class="display-4"><?= $stats['libros_nuevos'] ?></h2>
                    <p class="mb-0">Libros Nuevos</p>
                    <small><?= $stats['libros_existentes'] ?> ya existían</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h2 class="display-4"><?= $stats['ejemplares_nuevos'] ?></h2>
                    <p class="mb-0">Ejemplares Nuevos</p>
                    <small><?= $stats['ejemplares_existentes'] ?> ya existían</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h2 class="display-4"><?= $stats['categorias_nuevas'] ?></h2>
                    <p class="mb-0">Categorías Nuevas</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card <?= $stats['errores'] > 0 ? 'bg-warning' : 'bg-secondary' ?> text-white">
                <div class="card-body text-center">
                    <h2 class="display-4"><?= $stats['errores'] ?></h2>
                    <p class="mb-0">Errores</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="alert alert-success mt-4">
        <h5 class="alert-heading">✅ ¡Importación exitosa!</h5>
        <p>El catálogo completo ha sido cargado en la base de datos.</p>
        <hr>
        <p class="mb-0">Total de registros procesados: <strong><?= $stats['procesados'] ?></strong></p>
    </div>
    
    <div class="text-center mt-3">
        <a href="views/admin_dashboard.php" class="btn btn-primary btn-lg">
            Ir al Panel de Administración →
        </a>
    </div>
    
    <?php
    
} catch (Exception $e) {
    echo '</div>'; // Cerrar log
    echo '<div class="alert alert-danger mt-3">';
    echo '<h5>❌ Error crítico</h5>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}

function log_msg($msg, $type = 'info') {
    $icons = ['success' => '✓', 'error' => '✗', 'warning' => '⚠', 'info' => '→'];
    $icon = $icons[$type] ?? '•';
    echo "<div class='log-{$type}'>[" . date('H:i:s') . "] {$icon} " . htmlspecialchars($msg) . "</div>\n";
}

function getNombreCategoria($codigo) {
    $map = [
        'QA' => 'Matemáticas', 'TK' => 'Ing. Eléctrica/Electrónica',
        'QC' => 'Física', 'HF' => 'Comercio y Negocios',
        'HD' => 'Gestión y Administración', 'T5' => 'Tecnología General',
        'TJ' => 'Ing. Mecánica', 'TA' => 'Ing. Civil',
        'T3' => 'Tecnología Industrial', 'Q3' => 'Ciencias Naturales',
        'TR' => 'Fotografía', 'HB' => 'Teoría Económica',
        'LB' => 'Educación', 'HG' => 'Finanzas',
        'TS' => 'Manufactura', 'BC' => 'Lógica y Filosofía',
        'BF' => 'Psicología'
    ];
    return $map[$codigo] ?? "Categoría {$codigo}";
}
?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        const log = document.getElementById('log');
        if (log) log.scrollTop = log.scrollHeight;
    </script>
</body>
</html>
