<?php
// archivo: biblioteca-fisi/ver_csv.php
$archivo = __DIR__ . '/uploads/catalogo_libros.csv';

if (!file_exists($archivo)) {
    die("Archivo no encontrado");
}

echo "<h3>Primeras 20 líneas del CSV:</h3>";
echo "<pre>";

$handle = fopen($archivo, 'r');
for ($i = 0; $i < 20; $i++) {
    $linea = fgets($handle);
    echo "Línea $i: " . htmlspecialchars($linea) . "\n";
}
fclose($handle);

echo "</pre>";

echo "<h3>Análisis con fgetcsv():</h3>";
echo "<pre>";

$handle = fopen($archivo, 'r');
// Saltar encabezados
fgets($handle);
fgets($handle);

for ($i = 0; $i < 10; $i++) {
    $data = fgetcsv($handle, 1000, ',');
    echo "Registro $i:\n";
    print_r($data);
    echo "\n---\n";
}
fclose($handle);
?>