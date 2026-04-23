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
    body {
        background-color: var(--bg-secondary);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        color: var(--text-primary);
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }

    .card {
        background: var(--primary-surface);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        padding: 2rem;
    }

    h2 {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .backup-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 2rem;
    }

    .backup-section {
        background: var(--secondary-surface);
        padding: 2rem;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
        transition: all 0.2s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .backup-section:hover {
        border-color: var(--primary-color);
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }

    .section-title {
        color: var(--text-primary);
        margin-bottom: 1rem;
        font-weight: 700;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .section-desc {
        color: var(--text-secondary);
        margin-bottom: 2rem;
        font-size: 0.95rem;
        line-height: 1.5;
        flex-grow: 1;
    }

    /* Buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.875rem 1.5rem;
        border-radius: var(--radius-md);
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        width: 100%;
        text-decoration: none;
    }

    .btn-primary {
        background: var(--primary-color);
        color: white;
        box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2);
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
    }

    .btn-danger {
        background: #fef2f2;
        color: var(--danger-color);
        border: 1px solid #fee2e2;
    }

    .btn-danger:hover {
        background: #fee2e2;
    }

    /* File Input */
    .file-input-wrapper {
        margin-bottom: 1.5rem;
    }

    input[type="file"] {
        background: var(--bg-tertiary);
        padding: 0.75rem;
        border: 1px dashed var(--border-color);
        border-radius: var(--radius-md);
        width: 100%;
        font-size: 0.9rem;
        color: var(--text-secondary);
        cursor: pointer;
    }

    input[type="file"]:hover {
        border-color: var(--primary-color);
        background: var(--bg-secondary);
    }

    /* Alert */
    .alert-success {
        background: #ecfdf5;
        color: #047857;
        padding: 1rem;
        border-radius: var(--radius-md);
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        border: 1px solid #a7f3d0;
    }
</style>

<div class="container">
    <div class="card">
        <h2>
            <i class="fas fa-database" style="color: var(--primary-color);"></i> Backup y Restauración
        </h2>

        <?php if ($message): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle" style="font-size: 1.25rem;"></i>
                <span style="font-weight: 600;"><?= $message ?></span>
            </div>
        <?php endif; ?>

        <div class="backup-grid">
            <!-- Backup Section -->
            <div class="backup-section">
                <h3 class="section-title">
                    <div style="background: #eff6ff; padding: 8px; border-radius: 8px; color: var(--primary-color);">
                        <i class="fas fa-download"></i>
                    </div>
                    Crear Copia de Seguridad
                </h3>
                <p class="section-desc">
                    Genera y descarga un archivo SQL con toda la información actual de la base de datos.
                    Guardalo en un lugar seguro.
                </p>
                <form method="POST">
                    <button type="submit" name="backup" class="btn btn-primary">
                        <i class="fas fa-download"></i> Descargar Backup SQL
                    </button>
                </form>
            </div>

            <!-- Restore Section -->
            <div class="backup-section" style="border-color: var(--danger-color); background: rgba(239, 68, 68, 0.05);">
                <h3 class="section-title">
                    <div style="background: #fee2e2; padding: 8px; border-radius: 8px; color: var(--danger-color);">
                        <i class="fas fa-upload"></i>
                    </div>
                    Restaurar Base de Datos
                </h3>
                <p class="section-desc" style="color: #991b1b;">
                    Sube un archivo .sql para restaurar el sistema.
                    <strong>Advertencia:</strong> Esto reemplazará todos los datos actuales.
                </p>
                <form method="POST" enctype="multipart/form-data">
                    <div class="file-input-wrapper">
                        <input type="file" name="backup_file" accept=".sql" required>
                    </div>
                    <button type="submit" name="restore" class="btn btn-danger"
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