<?php
require 'db.php';

echo "<h2>Diagnóstico de Tabla 'loans'</h2>";

try {
    echo "<h3>Columnas actuales:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

    $stmt = $pdo->query("SHOW COLUMNS FROM loans");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $found_created_at = false;

    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";

        if ($col['Field'] === 'created_at') {
            $found_created_at = true;
        }
    }
    echo "</table>";

    if (!$found_created_at) {
        echo "<h3 style='color: red;'>❌ ALERTA: La columna 'created_at' NO existe. Intentando agregarla ahora...</h3>";

        try {
            $pdo->exec("ALTER TABLE loans ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
            echo "<h3 style='color: green;'>✅ Columna 'created_at' agregada exitosamente.</h3>";
        } catch (Exception $e) {
            echo "<h3 style='color: red;'>❌ Error al agregar columna: " . $e->getMessage() . "</h3>";
        }
    } else {
        echo "<h3 style='color: green;'>✅ La columna 'created_at' YA existe.</h3>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>