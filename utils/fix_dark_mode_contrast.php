<?php
// Script para corregir el contraste de colores en modo oscuro

// 1. Actualizar style.css
$style_css = file_get_contents('style.css');
$style_css = str_replace('color: var(--text);', 'color: var(--text-primary);', $style_css);
$style_css = str_replace('color: var(--text-light);', 'color: var(--text-secondary);', $style_css);
file_put_contents('style.css', $style_css);
echo "✓ style.css actualizado\n";

// 2. Actualizar index.php - Reemplazar colores hardcoded por variables CSS
$index_php = file_get_contents('index.php');

// Reemplazar en los h3 de las tarjetas de estadísticas
$index_php = preg_replace(
    '/color:\s*#64748b;/i',
    'color: var(--text-secondary);',
    $index_php
);

// Reemplazar en los párrafos principales
$index_php = preg_replace(
    '/color:\s*#1e293b;/i',
    'color: var(--text-primary);',
    $index_php
);

// Actualizar el plugin de texto central del gráfico para usar colores dinámicos
$old_plugin = "ctx.fillStyle = '#1e293b';";
$new_plugin = "ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--text-primary').trim();";
$index_php = str_replace($old_plugin, $new_plugin, $index_php);

$old_subtext = "ctx.fillStyle = '#64748b';";
$new_subtext = "ctx.fillStyle = getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim();";
$index_php = str_replace($old_subtext, $new_subtext, $index_php);

file_put_contents('index.php', $index_php);
echo "✓ index.php actualizado con variables CSS dinámicas\n";

echo "\n✅ Corrección de contraste completada!\n";
echo "Los textos ahora se adaptarán automáticamente al modo oscuro.\n";
?>