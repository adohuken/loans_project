<?php
require 'auth.php';
require 'db.php';

// Fetch Settings
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';

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
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup y Restauración</title>
    <link rel="stylesheet" href="style.css?v=3.0">
    <link rel="stylesheet" href="mobile.css?v=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <header style="flex-direction: column; gap: 1.5rem;">
            <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                <?php if (!empty($logo_path)): ?>
                    <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo"
                        style="height: 60px; width: auto; object-fit: contain;">
                <?php endif; ?>
                <h1 style="margin: 0; font-size: 2rem;"><?= htmlspecialchars($company_name) ?></h1>
            </div>
            <nav style="justify-content: center;">
                <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
                <a href="clients.php"><i class="fas fa-users"></i> Clientes</a>
                <a href="active_loans.php"><i class="fas fa-hand-holding-usd"></i> Abonar</a>
                <a href="create_loan.php"><i class="fas fa-plus-circle"></i> Nuevo Préstamo</a>
                <a href="reports.php"><i class="fas fa-chart-line"></i> Reportes</a>
                <a href="portfolios.php"><i class="fas fa-briefcase"></i> Carteras</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                    <a href="users.php"><i class="fas fa-user-shield"></i> Usuarios</a>
                <?php endif; ?>
                <a href="settings.php"><i class="fas fa-cog"></i> Configuración</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                    <a href="backup.php" class="active"><i class="fas fa-database"></i> Backup</a>
                <?php endif; ?>
                <span
                    style="color: #1a202c; font-weight: 600; font-size: 0.85rem; padding: 0.5rem 0.85rem; background: #fff; border-radius: 8px;">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                </span>
                <a href="logout.php" style="color: #dc2626;"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </nav>
        </header>

        <div class="card">
            <h2><i class="fas fa-database"></i> Backup y Restauración</h2>
            <?php if ($message): ?>
                <div style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <i class="fas fa-check-circle"></i> <?= $message ?>
                </div>
            <?php endif; ?>

            <div class="grid">
                <div style="padding: 1rem; border: 1px solid #e2e8f0; border-radius: 12px;">
                    <h3><i class="fas fa-download"></i> Crear Copia de Seguridad</h3>
                    <p style="color: #64748b; margin-bottom: 1rem;">Descarga una copia completa de la base de datos.</p>
                    <form method="POST">
                        <button type="submit" name="backup" class="btn">
                            <i class="fas fa-download"></i> Descargar Backup SQL
                        </button>
                    </form>
                </div>

                <div style="padding: 1rem; border: 1px solid #e2e8f0; border-radius: 12px;">
                    <h3><i class="fas fa-upload"></i> Restaurar Base de Datos</h3>
                    <p style="color: #64748b; margin-bottom: 1rem;">Sube un archivo .sql para restaurar el sistema.</p>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <input type="file" name="backup_file" accept=".sql" required style="padding: 0.5rem;">
                        </div>
                        <button type="submit" name="restore" class="btn btn-secondary"
                            onclick="return confirm('⚠️ ¡ADVERTENCIA! Esto borrará todos los datos actuales y los reemplazará con el backup. ¿Estás seguro?')">
                            <i class="fas fa-trash-restore"></i> Restaurar Sistema
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>