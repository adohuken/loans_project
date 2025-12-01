<?php
require 'auth.php';
require 'db.php';

// Fetch Settings
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';
$user_role = $_SESSION['role'] ?? 'admin';

// 🔒 SECURITY CHECK: Only SuperAdmin can access backup
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: index.php");
    exit;
}

$message = '';

// Handle Backup
if (isset($_POST['backup'])) {
    $tables = [];
    $result = $pdo->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $return = "";
    foreach ($tables as $table) {
        $result = $pdo->query("SELECT * FROM $table");
        $num_fields = $result->columnCount();

        $return .= "DROP TABLE IF EXISTS $table;";
        $row2 = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_NUM);
        $return .= "\n\n" . $row2[1] . ";\n\n";

        for ($i = 0; $i < $num_fields; $i++) {
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $return .= "INSERT INTO $table VALUES(";
                for ($j = 0; $j < $num_fields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    if (isset($row[$j])) {
                        $return .= '"' . $row[$j] . '"';
                    } else {
                        $return .= '""';
                    }
                    if ($j < ($num_fields - 1)) {
                        $return .= ',';
                    }
                }
                $return .= ");\n";
            }
        }
        $return .= "\n\n\n";
    }

    $backup_file = 'backup_' . date("Y-m-d-H-i-s") . '.sql';
    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: Binary");
    header("Content-disposition: attachment; filename=\"" . $backup_file . "\"");
    echo $return;
    exit;
}

// Handle Restore
if (isset($_POST['restore'])) {
    if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] == 0) {
        $filename = $_FILES['backup_file']['tmp_name'];
        $handle = fopen($filename, "r+");
        $contents = fread($handle, filesize($filename));
        $sql = explode(';', $contents);

        // Disable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        foreach ($sql as $query) {
            $query = trim($query);
            if (!empty($query)) {
                try {
                    $pdo->query($query);
                } catch (Exception $e) {
                    // Ignore errors for now or log them
                }
            }
        }

        // Re-enable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        fclose($handle);
        $message = "Base de datos restaurada exitosamente.";
    } else {
        $message = "Error al subir el archivo.";
    }
}

// Include enhanced header
require 'components/enhanced_header.php';
?>

<style>
    .backup-section {
        padding: 1.5rem;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        background: var(--bg-secondary);
        transition: all 0.3s ease;
    }

    .backup-section:hover {
        border-color: var(--accent-primary);
        box-shadow: 0 4px 12px var(--shadow);
    }

    .section-title {
        color: var(--text-primary);
        margin-bottom: 0.5rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .section-desc {
        color: var(--text-secondary);
        margin-bottom: 1.5rem;
        font-size: 0.95rem;
    }

    .file-input-wrapper {
        margin-bottom: 1rem;
    }

    input[type="file"] {
        background: var(--bg-tertiary);
        color: var(--text-primary);
        border: 1px solid var(--border-color);
        padding: 0.75rem;
        border-radius: 8px;
        width: 100%;
        cursor: pointer;
    }

    input[type="file"]::file-selector-button {
        background: var(--accent-lighter);
        color: var(--accent-primary);
        border: 1px solid var(--accent-primary);
        padding: 0.5rem 1rem;
        border-radius: 6px;
        cursor: pointer;
        margin-right: 1rem;
        font-weight: 600;
        transition: all 0.2s;
    }

    input[type="file"]::file-selector-button:hover {
        background: var(--accent-primary);
        color: white;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .btn-danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }
</style>

<div class="container">
    <div class="card">
        <h2 style="color: var(--text-primary); margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem;">
            <i class="fas fa-database" style="color: var(--accent-primary);"></i> Backup y Restauración
        </h2>

        <?php if ($message): ?>
            <div
                style="background: var(--accent-light); color: var(--accent-secondary); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid var(--accent-primary); display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-check-circle"></i> <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="grid"
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <!-- Backup Section -->
            <div class="backup-section">
                <h3 class="section-title"><i class="fas fa-download"></i> Crear Copia de Seguridad</h3>
                <p class="section-desc">Descarga una copia completa de la base de datos en formato SQL para resguardar
                    tu información.</p>
                <form method="POST">
                    <button type="submit" name="backup" class="btn-primary">
                        <i class="fas fa-download"></i> Descargar Backup SQL
                    </button>
                </form>
            </div>

            <!-- Restore Section -->
            <div class="backup-section">
                <h3 class="section-title"><i class="fas fa-upload"></i> Restaurar Base de Datos</h3>
                <p class="section-desc">Sube un archivo .sql previamente descargado para restaurar el sistema al estado
                    de esa copia.</p>
                <form method="POST" enctype="multipart/form-data">
                    <div class="file-input-wrapper">
                        <input type="file" name="backup_file" accept=".sql" required>
                    </div>
                    <button type="submit" name="restore" class="btn-danger"
                        onclick="return confirm('⚠️ ¡ADVERTENCIA CRÍTICA!\n\nEsta acción BORRARÁ TODOS los datos actuales y los reemplazará con los del archivo de respaldo.\n\n¿Estás absolutamente seguro de continuar?')">
                        <i class="fas fa-trash-restore"></i> Restaurar Sistema
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>

</html>