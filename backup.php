<?php
require 'auth.php';
require 'db.php';

// Fetch Settings
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$currency = $settings['currency_symbol'] ?? '$';
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';

// Get database statistics
$total_clients = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$total_loans = $pdo->query("SELECT COUNT(*) FROM loans")->fetchColumn();
$total_payments = $pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup y Restauración - <?= htmlspecialchars($company_name) ?></title>
    <link rel="stylesheet" href="style.css?v=2.0">
</head>

<body>
    <div class="container">
        <header>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <?php if (!empty($logo_path)): ?>
                    <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo"
                        style="height: 50px; width: auto; object-fit: contain;">
                <?php endif; ?>
                <h1><?= htmlspecialchars($company_name) ?></h1>
            </div>
            <nav>
                <a href="index.php">Inicio</a>
                <a href="clients.php">Clientes</a>
                <a href="active_loans.php">Abonar</a>
                <a href="create_loan.php">Nuevo Préstamo</a>
                <a href="users.php">Usuarios</a>
                <a href="settings.php">Configuración</a>
                <a href="backup.php" class="active">Backup</a>
                <a href="logout.php" style="color: #dc2626;">Cerrar Sesión</a>
            </nav>
        </header>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="card">
            <h2>🔄 Backup y Restauración del Sistema</h2>
            <p style="color: #64748b; margin-bottom: 2rem;">
                Administra copias de seguridad de toda la información del sistema. Puedes exportar todos los datos
                actuales
                o importar un backup previamente guardado.
            </p>

            <!-- Database Statistics -->
            <div class="grid" style="margin-bottom: 2rem;">
                <div class="card" style="border-left: 4px solid #3b82f6;">
                    <h3 style="font-size: 0.9rem; color: #64748b;">Clientes</h3>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #1e293b;"><?= $total_clients ?></p>
                </div>
                <div class="card" style="border-left: 4px solid #10b981;">
                    <h3 style="font-size: 0.9rem; color: #64748b;">Préstamos</h3>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #1e293b;"><?= $total_loans ?></p>
                </div>
                <div class="card" style="border-left: 4px solid #8b5cf6;">
                    <h3 style="font-size: 0.9rem; color: #64748b;">Pagos</h3>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #1e293b;"><?= $total_payments ?></p>
                </div>
                <div class="card" style="border-left: 4px solid #f59e0b;">
                    <h3 style="font-size: 0.9rem; color: #64748b;">Usuarios</h3>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #1e293b;"><?= $total_users ?></p>
                </div>
            </div>

            <!-- Export Section -->
            <div class="card"
                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-bottom: 1.5rem;">
                <h3 style="color: white; margin-bottom: 0.5rem;">📥 Exportar Backup</h3>
                <p style="opacity: 0.9; margin-bottom: 1rem;">
                    Descarga una copia completa de todos los datos del sistema en formato JSON.
                </p>
                <a href="export_backup.php" class="btn"
                    style="background: white; color: #667eea; display: inline-block;">
                    ⬇️ Descargar Backup Completo
                </a>
            </div>

            <!-- Import Section -->
            <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                <h3 style="color: white; margin-bottom: 0.5rem;">📤 Importar Backup</h3>
                <p style="opacity: 0.9; margin-bottom: 1rem;">
                    Restaura todos los datos desde un archivo de backup previamente exportado.
                </p>

                <div style="background: rgba(255,255,255,0.2); padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <strong style="display: block; margin-bottom: 0.5rem;">⚠️ ADVERTENCIA:</strong>
                    <ul style="margin: 0; padding-left: 1.5rem; opacity: 0.95;">
                        <li>Esta acción eliminará TODOS los datos actuales del sistema</li>
                        <li>Se reemplazarán con los datos del archivo de backup</li>
                        <li>Esta operación NO se puede deshacer</li>
                        <li>Asegúrate de tener un backup actual antes de proceder</li>
                    </ul>
                </div>

                <form action="import_backup.php" method="POST" enctype="multipart/form-data"
                    onsubmit="return confirm('⚠️ ADVERTENCIA: Esto eliminará TODOS los datos actuales y los reemplazará con el backup. ¿Estás seguro de continuar?');">
                    <div style="margin-bottom: 1rem;">
                        <input type="file" name="backup_file" accept=".json" required
                            style="background: white; color: #333; padding: 0.5rem; border-radius: 4px; width: 100%;">
                    </div>
                    <button type="submit" class="btn" style="background: #dc2626; color: white;">
                        🔄 Restaurar desde Backup
                    </button>
                </form>
            </div>
        </div>

        <!-- Instructions -->
        <div class="card">
            <h3>📋 Instrucciones</h3>
            <div style="display: grid; gap: 1rem; margin-top: 1rem;">
                <div>
                    <h4 style="color: #3b82f6; margin-bottom: 0.5rem;">Para Exportar:</h4>
                    <ol style="margin: 0; padding-left: 1.5rem; color: #64748b;">
                        <li>Haz clic en "Descargar Backup Completo"</li>
                        <li>Se descargará un archivo JSON con toda la información</li>
                        <li>Guarda este archivo en un lugar seguro</li>
                    </ol>
                </div>
                <div>
                    <h4 style="color: #f59e0b; margin-bottom: 0.5rem;">Para Importar:</h4>
                    <ol style="margin: 0; padding-left: 1.5rem; color: #64748b;">
                        <li>Haz clic en "Seleccionar archivo" y elige un archivo de backup (.json)</li>
                        <li>Haz clic en "Restaurar desde Backup"</li>
                        <li>Confirma la acción en el mensaje de advertencia</li>
                        <li>Espera a que se complete la restauración</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</body>

</html>