<?php
// prueba_conexion.php
// Archivo para demostrar que la BD está funcionando con datos reales

try {
    // Configuración de conexión
    $host = 'localhost';
    $nombre_bd = 'biblioteca_fisi';
    $usuario = 'root';
    $clave = '';
    
    // Crear conexión PDO
    $pdo = new PDO("mysql:host=$host;dbname=$nombre_bd;charset=utf8mb4", 
                   $usuario, $clave);
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<!DOCTYPE html><html><head><title>Sistema Bibliotecario FISI - Prueba</title>";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .contenedor { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .exito { color: #27ae60; font-weight: bold; }
        .info { background: #ecf0f1; padding: 10px; border-left: 4px solid #3498db; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #3498db; color: white; }
        .caja-estadistica { display: inline-block; background: #3498db; color: white; padding: 10px 15px; margin: 5px; border-radius: 5px; }
    </style></head><body><div class='contenedor'>";
    
    echo "<h1>🏛️ Sistema Web de Gestión Bibliotecaria FISI</h1>";
    echo "<p class='exito'>✅ Conexión a Base de Datos: EXITOSA</p>";
    
    // Mostrar tablas creadas
    $consulta = "SHOW TABLES";
    $stmt = $pdo->prepare($consulta);
    $stmt->execute();
    $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div class='info'><strong>📊 Infraestructura:</strong> " . count($tablas) . " tablas creadas correctamente</div>";
    
    // Estadísticas generales
    echo "<h2>📈 Estadísticas del Sistema</h2>";
    
    // Contar libros y ejemplares
    $estadisticas = [];
    $consultas = [
        'total_libros' => "SELECT COUNT(*) FROM libros WHERE estado = 'activo'",
        'total_ejemplares' => "SELECT COUNT(*) FROM ejemplares_libros WHERE estado = 'disponible'", 
        'total_categorias' => "SELECT COUNT(*) FROM categorias",
        'total_usuarios' => "SELECT COUNT(*) FROM usuarios"
    ];
    
    foreach ($consultas as $clave => $consulta) {
        $stmt = $pdo->prepare($consulta);
        $stmt->execute();
        $estadisticas[$clave] = $stmt->fetchColumn();
    }
    
    echo "<div style='text-align: center;'>";
    echo "<span class='caja-estadistica'>📚 {$estadisticas['total_libros']} Libros Únicos</span>";
    echo "<span class='caja-estadistica'>📖 {$estadisticas['total_ejemplares']} Ejemplares Disponibles</span>";
    echo "<span class='caja-estadistica'>🗂️ {$estadisticas['total_categorias']} Categorías</span>";
    echo "<span class='caja-estadistica'>👤 {$estadisticas['total_usuarios']} Usuarios del Sistema</span>";
    echo "</div>";
    
    // Mostrar libros importados del catálogo real
    echo "<h2>📋 Catálogo de Libros (Datos Reales de FISI)</h2>";
    echo "<p><em>Muestra de libros importados del catálogo oficial proporcionado por la jefa de biblioteca:</em></p>";
    
    $consulta = "
        SELECT 
            l.titulo,
            l.autor, 
            l.clasificacion_base as clasificacion,
            l.año_publicacion as año,
            COUNT(e.id_ejemplar) as ejemplares,
            SUM(CASE WHEN e.estado = 'disponible' THEN 1 ELSE 0 END) as disponibles
        FROM libros l 
        LEFT JOIN ejemplares_libros e ON l.id_libro = e.id_libro
        WHERE l.estado = 'activo'
        GROUP BY l.id_libro, l.titulo, l.autor, l.clasificacion_base, l.año_publicacion
        ORDER BY COUNT(e.id_ejemplar) DESC
    ";
    
    $stmt = $pdo->prepare($consulta);
    $stmt->execute();
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($libros) {
        echo "<table>";
        echo "<tr><th>Título</th><th>Autor</th><th>Clasificación</th><th>Año</th><th>Ejemplares</th><th>Disponibles</th></tr>";
        foreach($libros as $libro) {
            $año = $libro['año'] ? $libro['año'] : 'N/A';
            echo "<tr>";
            echo "<td><strong>{$libro['titulo']}</strong></td>";
            echo "<td>{$libro['autor']}</td>";
            echo "<td><code>{$libro['clasificacion']}</code></td>";
            echo "<td>{$año}</td>";
            echo "<td>{$libro['ejemplares']}</td>";
            echo "<td><span style='color: #27ae60;'>{$libro['disponibles']}</span></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Mostrar categorías basadas en clasificación real
    echo "<h2>🗂️ Categorías por Clasificación Bibliotecaria</h2>";
    $consulta = "SELECT nombre, descripcion, codigo FROM categorias ORDER BY codigo";
    $stmt = $pdo->prepare($consulta);
    $stmt->execute();
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Código</th><th>Categoría</th><th>Descripción</th></tr>";
    foreach($categorias as $cat) {
        echo "<tr>";
        echo "<td><code>{$cat['codigo']}</code></td>";
        echo "<td><strong>{$cat['nombre']}</strong></td>";
        echo "<td>{$cat['descripcion']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Información técnica
    echo "<hr><h2>⚙️ Información Técnica</h2>";
    echo "<div class='info'>";
    echo "<strong>Arquitectura:</strong> 3 Capas (Presentación → Lógica → Datos)<br>";
    echo "<strong>Tecnologías:</strong> PHP 8+ | MySQL 8+ | Apache (XAMPP) | Bootstrap 5<br>";
    echo "<strong>Base de Datos:</strong> biblioteca_fisi<br>";
    echo "<strong>Datos:</strong> Basados en catálogo real de 3,373 registros<br>";
    echo "<strong>Estado:</strong> ✅ Listo para implementar Historias de Usuario<br>";
    echo "<strong>Próximo hito:</strong> 🎯 Implementar HU-03 (Autenticación) para el 02/10/2025";
    echo "</div>";
    
    // Verificación de integridad
    echo "<h3>🔍 Verificación de Integridad</h3>";
    $verificaciones = [
        'Libros sin ejemplares' => "SELECT COUNT(*) FROM libros l LEFT JOIN ejemplares_libros e ON l.id_libro = e.id_libro WHERE e.id_libro IS NULL",
        'Ejemplares huérfanos' => "SELECT COUNT(*) FROM ejemplares_libros e LEFT JOIN libros l ON e.id_libro = l.id_libro WHERE l.id_libro IS NULL",
        'Usuarios administrativos' => "SELECT COUNT(*) FROM usuarios WHERE rol IN ('administrador', 'bibliotecario')"
    ];
    
    echo "<ul>";
    foreach($verificaciones as $nombre_verificacion => $consulta) {
        $stmt = $pdo->prepare($consulta);
        $stmt->execute();
        $resultado = $stmt->fetchColumn();
        $estado = ($nombre_verificacion == 'Usuarios administrativos' && $resultado > 0) ? '✅' : 
                 ($resultado == 0 ? '✅' : '⚠️');
        echo "<li><strong>$nombre_verificacion:</strong> $resultado $estado</li>";
    }
    echo "</ul>";
    
    echo "</div></body></html>";
    
} catch(PDOException $e) {
    echo "<h1 style='color: red;'>❌ Error de Conexión</h1>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Solución:</strong> Verificar que XAMPP esté iniciado y la BD 'biblioteca_fisi' esté creada.</p>";
    echo "<p><strong>Pasos:</strong></p>";
    echo "<ol>";
    echo "<li>Abrir XAMPP Control Panel</li>";
    echo "<li>Iniciar Apache y MySQL (deben estar en verde)</li>";
    echo "<li>Ir a http://localhost/phpmyadmin</li>";
    echo "<li>Crear base de datos 'biblioteca_fisi'</li>";
    echo "<li>Ejecutar el script SQL completo</li>";
    echo "</ol>";
}
?>