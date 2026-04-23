<?php
require 'auth.php';
require 'db.php';

// Check if user is cobrador and redirect to active_loans
if (isset($_SESSION['role']) && $_SESSION['role'] === 'cobrador') {
    header("Location: active_loans.php");
    exit;
}

$message = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = $_POST['company_name'];
    $currency_symbol = $_POST['currency_symbol'];
    $company_address = $_POST['company_address'];
    $company_phone = $_POST['company_phone'];
    $receipt_footer = $_POST['receipt_footer'];

    // Handle Logo Upload
    $logo_path = $_POST['current_logo'];
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["logo"]["name"]);
        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
            $logo_path = $target_file;
        }
    }

    $stmt = $pdo->prepare("UPDATE settings SET company_name = ?, currency_symbol = ?, logo_path = ?, company_address = ?, company_phone = ?, receipt_footer = ? WHERE id = 1");
    $stmt->execute([$company_name, $currency_symbol, $logo_path, $company_address, $company_phone, $receipt_footer]);
    $message = "Configuración actualizada correctamente.";
}

// Fetch Current Settings
$stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt->fetch();
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';
$user_role = $_SESSION['role'] ?? 'admin';

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

    /* Cards */
    .card {
        background: var(--primary-surface);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        padding: 2rem;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .card h2 {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--text-primary);
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        border-bottom: 2px solid var(--border-color);
        padding-bottom: 1rem;
    }

    h3 {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Grid Layout */
    .settings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 2rem;
    }

    /* Forms */
    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-secondary);
    }

    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        font-size: 0.95rem;
        transition: all 0.2s;
        background: var(--bg-tertiary);
        color: var(--text-primary);
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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

    .btn-outline {
        background: transparent;
        border: 2px solid var(--primary-color);
        color: var(--primary-color);
    }

    .btn-outline:hover {
        background: var(--primary-color);
        color: white;
    }

    .btn-danger-block {
        background: #ef4444;
        color: white;
        font-weight: 700;
    }

    .btn-danger-block:hover {
        background: #dc2626;
    }

    /* Color Pickers */
    .color-option {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        border: 3px solid transparent;
        transition: transform 0.2s;
    }

    .color-option:hover {
        transform: scale(1.1);
    }

    .color-option.active {
        border-color: var(--text-primary);
    }

    /* Switch Toggle */
    .switch {
        position: relative;
        display: inline-block;
        width: 48px;
        height: 24px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e1;
        transition: .3s;
        border-radius: 34px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
    }

    input:checked+.slider {
        background-color: var(--success-color);
    }

    input:checked+.slider:before {
        transform: translateX(24px);
    }

    /* Logo Preview */
    .logo-preview {
        padding: 1rem;
        background: var(--secondary-surface);
        border: 1px dashed var(--border-color);
        border-radius: var(--radius-md);
        text-align: center;
        margin-bottom: 0.5rem;
    }

    .logo-preview img {
        height: 60px;
        object-fit: contain;
    }
</style>

<div class="container">
    <div class="card" style="margin-bottom: 2rem;">
        <h2 style="margin-bottom: 0; border: none;"><i class="fas fa-sliders-h"></i> Configuración del Sistema</h2>
    </div>

    <?php if ($message): ?>
        <div
            style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem; border: 1px solid #bbf7d0;">
            <i class="fas fa-check-circle" style="font-size: 1.25rem;"></i>
            <span style="font-weight: 600;"><?= $message ?></span>
        </div>
    <?php endif; ?>

    <div class="settings-grid">

        <!-- General Settings -->
        <div class="card">
            <h3><i class="fas fa-building" style="color: var(--primary-color);"></i> Datos de la Empresa</h3>

            <form method="POST" enctype="multipart/form-data"
                style="display: flex; flex-direction: column; height: 100%;">
                <div class="form-group">
                    <label>Nombre de la Empresa</label>
                    <input type="text" name="company_name" class="form-control"
                        value="<?= htmlspecialchars($settings['company_name']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Símbolo de Moneda</label>
                    <input type="text" name="currency_symbol" class="form-control"
                        value="<?= htmlspecialchars($settings['currency_symbol']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Dirección</label>
                    <input type="text" name="company_address" class="form-control"
                        value="<?= htmlspecialchars($settings['company_address'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="company_phone" class="form-control"
                        value="<?= htmlspecialchars($settings['company_phone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Logo</label>
                    <?php if (!empty($settings['logo_path'])): ?>
                        <div class="logo-preview">
                            <img src="<?= htmlspecialchars($settings['logo_path']) ?>" alt="Current Logo">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    <input type="hidden" name="current_logo" value="<?= htmlspecialchars($settings['logo_path']) ?>">
                </div>

                <div class="form-group">
                    <label>Pie de Recibo</label>
                    <textarea name="receipt_footer" class="form-control"
                        rows="2"><?= htmlspecialchars($settings['receipt_footer'] ?? '') ?></textarea>
                </div>

                <div style="margin-top: auto;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>

        <!-- Right Column: Personalization & System -->
        <div style="display: flex; flex-direction: column; gap: 2rem;">

            <!-- Personalization -->
            <div class="card">
                <h3><i class="fas fa-paint-brush" style="color: #8b5cf6;"></i> Personalización</h3>

                <div class="form-group">
                    <label>Tema</label>
                    <button type="button" onclick="toggleTheme()" class="btn btn-outline">
                        <i class="fas fa-adjust"></i> Alternar Claro / Oscuro
                    </button>
                    <script>
                        function toggleTheme() {
                            if (window.themeManager) {
                                window.themeManager.toggleTheme();
                            }
                        }
                    </script>
                </div>

                <div class="form-group">
                    <label>Color de Acento</label>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <div class="color-option" style="background: #3b82f6;" onclick="setThemeColor('blue')"></div>
                        <div class="color-option" style="background: #8b5cf6;" onclick="setThemeColor('purple')"></div>
                        <div class="color-option" style="background: #10b981;" onclick="setThemeColor('green')"></div>
                        <div class="color-option" style="background: #f59e0b;" onclick="setThemeColor('orange')"></div>
                        <div class="color-option" style="background: #ec4899;" onclick="setThemeColor('pink')"></div>
                    </div>
                    <script>
                        function setThemeColor(color) {
                            if (window.themeManager) {
                                window.themeManager.setColorTheme(color);
                            }
                        }
                    </script>
                </div>

                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px dashed var(--border-color);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <span style="display: block; font-weight: 600; color: var(--text-primary);">Notificaciones
                                de Pago</span>
                            <span style="font-size: 0.8rem; color: var(--text-secondary);">Alertas automáticas</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" checked onchange="toggleNotifications(this)">
                            <span class="slider round"></span>
                        </label>
                    </div>
                    <button type="button" class="btn btn-outline"
                        style="margin-top: 1rem; width: 100%; border-style: dashed;"
                        onclick="window.notificationManager.show('¡Prueba exitosa!', 'success')">
                        <i class="fas fa-paper-plane"></i> Probar Notificación
                    </button>
                </div>
            </div>

            <!-- Portfolio Tools -->
            <div class="card">
                <h3><i class="fas fa-briefcase" style="color: #10b981;"></i> Gestión de Cartera</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1rem;">
                    Herramientas de migración y configuración inicial.
                </p>
                <a href="initialize_portfolio.php" class="btn"
                    style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;">
                    <i class="fas fa-file-import"></i> Inicializar Cartera
                </a>
            </div>

            <!-- Danger Zone -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                <div class="card" style="border: 1px solid #fecaca; background: #fff1f2;">
                    <h3 style="color: #991b1b;"><i class="fas fa-exclamation-triangle"></i> Zona de Peligro</h3>
                    <p style="color: #991b1b; font-size: 0.9rem; margin-bottom: 1rem;">
                        Estas acciones son irreversibles y pueden causar pérdida de datos.
                    </p>
                    <a href="reset_system.php" onclick="return confirm('¿ESTÁS SEGURO? Esto borrará TODOS los datos.')"
                        class="btn btn-danger-block">
                        <i class="fas fa-trash"></i> Reiniciar Sistema (Reset Factory)
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
    function toggleNotifications(checkbox) {
        if (checkbox.checked) {
            window.notificationManager.startPolling();
            window.notificationManager.show('Notificaciones activadas', 'success');
        } else {
            window.notificationManager.show('Notificaciones desactivadas', 'warning');
        }
    }
</script>
</body>

</html>