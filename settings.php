<?php
require 'auth.php';
require 'db.php';

// Fetch current settings
$stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt->fetch();

if (!$settings) {
    $settings = [
        'company_name' => 'Mi Empresa',
        'currency_symbol' => '$',
        'logo_path' => '',
        'company_address' => '',
        'company_phone' => '',
        'receipt_footer' => ''
    ];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Sistema de Préstamos</title>
    <link rel="stylesheet" href="style.css?v=2.0">
</head>

<body>
    <div class="container">
        <header>
            <h1>Sistema de Préstamos</h1>
            <nav>
                <a href="index.php">Inicio</a>
                <a href="clients.php">Clientes</a>
                <a href="active_loans.php">Abonar</a>
                <a href="create_loan.php">Nuevo Préstamo</a>
                <a href="reports.php">Reportes</a>
                <a href="users.php">Usuarios</a>
                <a href="settings.php" class="active">Configuración</a>
                <a href="logout.php" style="color: #dc2626;">Cerrar Sesión</a>
            </nav>
        </header>

        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <h2>Configuración de la Empresa</h2>
            <form action="save_settings.php" method="POST" enctype="multipart/form-data" style="margin-top: 1.5rem;">

                <div class="form-group">
                    <label>Nombre de la Empresa</label>
                    <input type="text" name="company_name" value="<?= htmlspecialchars($settings['company_name']) ?>"
                        required>
                </div>

                <div class="form-group">
                    <label>Dirección</label>
                    <input type="text" name="company_address"
                        value="<?= htmlspecialchars($settings['company_address'] ?? '') ?>"
                        placeholder="Ej. Calle Principal #123">
                </div>

                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="company_phone"
                        value="<?= htmlspecialchars($settings['company_phone'] ?? '') ?>" placeholder="Ej. 555-0000">
                </div>

                <div class="form-group">
                    <label>Símbolo de Moneda</label>
                    <input type="text" name="currency_symbol"
                        value="<?= htmlspecialchars($settings['currency_symbol']) ?>" required placeholder="Ej. $ o €">
                </div>

                <div class="form-group">
                    <label>Mensaje Pie de Recibo</label>
                    <textarea name="receipt_footer" rows="3"
                        placeholder="Ej. ¡Gracias por su pago!"><?= htmlspecialchars($settings['receipt_footer'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>Logo de la Empresa</label>
                    <?php if (!empty($settings['logo_path'])): ?>
                        <div style="margin-bottom: 1rem;">
                            <img src="<?= htmlspecialchars($settings['logo_path']) ?>" alt="Logo Actual"
                                style="max-height: 100px; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="logo" accept="image/*">
                    <small style="color: var(--text-light);">Deja vacío para mantener el actual. Formatos: PNG,
                        JPG.</small>
                </div>

                <button type="submit" class="btn" style="width: 100%;">Guardar Cambios</button>
            </form>

            <!-- Import Existing Loans Section -->
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #e2e8f0;">
                <h3 style="color: #3b82f6;">📥 Importar Préstamos Existentes</h3>
                <p style="color: #64748b; margin-bottom: 1rem;">
                    Si tu empresa ya tiene préstamos activos, impórtalos al sistema para darles seguimiento.
                </p>
                <a href="import_loan.php" class="btn" style="background: #3b82f6; width: 100%; text-align: center;">
                    📥 Importar Préstamo Existente
                </a>
            </div>

            <!-- Reset System Section -->
            <?php if ($_SESSION['user_id'] == 1): ?>
                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #e2e8f0;">
                    <h3 style="color: #dc2626;">⚠️ Zona Peligrosa</h3>
                    <p style="color: #64748b; margin-bottom: 1rem;">Reinicia el sistema para un nuevo cliente. Esta acción
                        eliminará TODOS los datos.</p>
                    <a href="reset_system.php" class="btn" style="background: #dc2626; width: 100%; text-align: center;"
                        onclick="return confirm('¿Estás seguro? Esto te llevará a la página de reinicio del sistema.')">
                        🗑️ Reiniciar Sistema Completo
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>