<?php
// Script para limpiar duplicados en index.php

$file = 'index.php';
$lines = file($file);

// Encontrar y eliminar el bloque duplicado
// El bloque duplicado empieza después del primer cierre de </div> del stats grid
// y termina antes de la tabla de préstamos recientes

$output = [];
$skip_mode = false;
$skip_count = 0;

foreach ($lines as $i => $line) {
    // Detectar el inicio del bloque duplicado
    // Buscamos la segunda aparición de "Clientes Totales" que no está dentro del grid principal
    if (strpos($line, 'Clientes Totales') !== false && $skip_count > 0) {
        $skip_mode = true;
    }

    // Contar apariciones de "Clientes Totales" para saber cuál es la duplicada
    if (strpos($line, 'Clientes Totales') !== false) {
        $skip_count++;
    }

    // Detectar el fin del bloque duplicado (cuando llegamos a "Préstamos Recientes")
    if (strpos($line, 'Préstamos Recientes') !== false) {
        $skip_mode = false;
    }

    // Solo agregar la línea si no estamos en modo skip
    if (!$skip_mode || $skip_count <= 1) {
        $output[] = $line;
    }
}

// Guardar el archivo limpio
file_put_contents($file, implode('', $output));
echo "✓ Duplicados eliminados de index.php\n";
echo "Total de líneas procesadas: " . count($lines) . "\n";
echo "Líneas finales: " . count($output) . "\n";
?>