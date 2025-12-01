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

<div class="container">
    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <h2 style="margin-bottom: 2rem; border-bottom: 2px solid var(--border-color); padding-bottom: 1rem;">
            <i class="fas fa-sliders-h"></i> Configuración del Sistema
        </h2>

        <?php if ($message): ?>
            <div
                style="background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem;">
                <i class="fas fa-check-circle" style="font-size: 1.25rem;"></i>
                <span style="font-weight: 600;"><?= $message ?></span>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">

            <!-- General Settings Column -->
            <div>
                <h3 style="color: var(--text-primary); margin-bottom: 1.5rem; font-size: 1.25rem;">
                    <i class="fas fa-building" style="color: var(--accent-primary);"></i> Datos de la Empresa
                </h3>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label style="font-weight: 600; color: var(--text-secondary);">Nombre de la Empresa</label>
                        <input type="text" name="company_name"
                            value="<?= htmlspecialchars($settings['company_name']) ?>" required
                            style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-secondary); color: var(--text-primary);">
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600; color: var(--text-secondary);">Símbolo de Moneda</label>
                        <input type="text" name="currency_symbol"
                            value="<?= htmlspecialchars($settings['currency_symbol']) ?>" required
                            style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-secondary); color: var(--text-primary);">
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600; color: var(--text-secondary);">Dirección</label>
                        <input type="text" name="company_address"
                            value="<?= htmlspecialchars($settings['company_address'] ?? '') ?>"
                            style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-secondary); color: var(--text-primary);">
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600; color: var(--text-secondary);">Teléfono</label>
                        <input type="text" name="company_phone"
                            value="<?= htmlspecialchars($settings['company_phone'] ?? '') ?>"
                            style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-secondary); color: var(--text-primary);">
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600; color: var(--text-secondary);">Pie de Recibo</label>
                        <textarea name="receipt_footer" rows="3"
                            style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-secondary); color: var(--text-primary);"><?= htmlspecialchars($settings['receipt_footer'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600; color: var(--text-secondary);">Logo</label>
                        <?php if (!empty($settings['logo_path'])): ?>
                            <div
                                style="margin-bottom: 1rem; padding: 1rem; background: var(--bg-secondary); border-radius: 8px; text-align: center;">
                                <img src="<?= htmlspecialchars($settings['logo_path']) ?>" alt="Current Logo"
                                    style="height: 60px; object-fit: contain;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="logo" accept="image/*"
                            style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-secondary); color: var(--text-primary);">
                        <input type="hidden" name="current_logo"
                            value="<?= htmlspecialchars($settings['logo_path']) ?>">
                    </div>

                    <button type="submit" class="btn"
                        style="width: 100%; margin-top: 1rem; background: var(--accent-primary); color: white; border: none; padding: 1rem; border-radius: 8px; font-weight: 700; cursor: pointer;">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </form>
            </div>

            <!-- Appearance & System Settings Column -->
            <div>
                <!-- Appearance Section -->
                <div
                    style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 16px; margin-bottom: 2rem; border: 1px solid var(--border-color);">
                    <h3 style="color: var(--text-primary); margin-bottom: 1.5rem; font-size: 1.25rem;">
                        <i class="fas fa-paint-brush" style="color: var(--accent-primary);"></i> Personalización
                    </h3>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label
                            style="font-weight: 600; color: var(--text-secondary); display: block; margin-bottom: 0.75rem;">Modo
                            de Pantalla</label>
                        <div style="display: flex; gap: 1rem;">
                            <button type="button" onclick="window.themeManager.toggleTheme()" class="theme-btn"
                                style="flex: 1; padding: 0.75rem; border: 2px solid var(--border-color); border-radius: 8px; background: var(--bg-primary); color: var(--text-primary); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                <i class="fas fa-adjust"></i> Alternar
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label
                            style="font-weight: 600; color: var(--text-secondary); display: block; margin-bottom: 0.75rem;">Color
                            de Acento</label>
                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            <div class="color-option blue" onclick="window.themeManager.setColorTheme('blue')"
                                data-color="blue"
                                style="width: 40px; height: 40px; border-radius: 50%; cursor: pointer; background: #3b82f6; border: 3px solid transparent;">
                            </div>
                            <div class="color-option purple" onclick="window.themeManager.setColorTheme('purple')"
                                data-color="purple"
                                style="width: 40px; height: 40px; border-radius: 50%; cursor: pointer; background: #8b5cf6; border: 3px solid transparent;">
                            </div>
                            <div class="color-option green" onclick="window.themeManager.setColorTheme('green')"
                                data-color="green"
                                style="width: 40px; height: 40px; border-radius: 50%; cursor: pointer; background: #10b981; border: 3px solid transparent;">
                            </div>
                            <div class="color-option orange" onclick="window.themeManager.setColorTheme('orange')"
                                data-color="orange"
                                style="width: 40px; height: 40px; border-radius: 50%; cursor: pointer; background: #f59e0b; border: 3px solid transparent;">
                            </div>
                            <div class="color-option pink" onclick="window.themeManager.setColorTheme('pink')"
                                data-color="pink"
                                style="width: 40px; height: 40px; border-radius: 50%; cursor: pointer; background: #ec4899; border: 3px solid transparent;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notifications Section -->
                <div
                    style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 16px; margin-bottom: 2rem; border: 1px solid var(--border-color);">
                    <h3 style="color: var(--text-primary); margin-bottom: 1.5rem; font-size: 1.25rem;">
                        <i class="fas fa-bell" style="color: var(--accent-primary);"></i> Notificaciones
                    </h3>

                    <div
                        style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                        <div>
                            <p style="margin: 0; font-weight: 600; color: var(--text-primary);">Alertas de Pagos</p>
                            <p style="margin: 0; font-size: 0.85rem; color: var(--text-secondary);">Mostrar alertas de
                                pagos próximos</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" checked onchange="toggleNotifications(this)">
                            <span class="slider round"></span>
                        </label>
                    </div>

                    <button type="button" onclick="window.notificationManager.show('¡Notificación de prueba!', 'info')"
                        style="width: 100%; padding: 0.75rem; border: 2px solid var(--accent-primary); border-radius: 8px; background: transparent; color: var(--accent-primary); font-weight: 600; cursor: pointer;">
                        <i class="fas fa-paper-plane"></i> Enviar Prueba
                    </button>
                </div>

                <!-- Portfolio Management Section -->
                <div
                    style="background: var(--bg-secondary); padding: 1.5rem; border-radius: 16px; margin-bottom: 2rem; border: 1px solid var(--border-color);">
                    <h3 style="color: var(--text-primary); margin-bottom: 1.5rem; font-size: 1.25rem;">
                        <i class="fas fa-briefcase" style="color: var(--accent-primary);"></i> Gestión de Cartera
                    </h3>

                    <p style="color: var(--text-secondary); margin-bottom: 1rem; font-size: 0.9rem;">
                        Herramientas para gestionar la cartera inicial y migración de datos.
                    </p>

                    <a href="initialize_portfolio.php"
                        style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%; padding: 0.75rem; background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); color: white; border-radius: 8px; text-decoration: none; font-weight: 600; transition: transform 0.2s;">
                        <i class="fas fa-file-import"></i> Inicializar Cartera Existente
                    </a>
                </div>

                <!-- Danger Zone -->
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                    <div style="background: #fee2e2; padding: 1.5rem; border-radius: 16px; border: 1px solid #fecaca;">
                        <h3 style="color: #b91c1c; margin-bottom: 0.5rem; font-size: 1.25rem;">
                            <i class="fas fa-exclamation-triangle"></i> Zona de Peligro
                        </h3>
                        <p style="color: #7f1d1d; margin-bottom: 1.5rem; font-size: 0.9rem;">Estas acciones son
                            irreversibles.</p>

                        <a href="reset_system.php" onclick="return confirm('¿ESTÁS SEGURO? Esto borrará TODOS los datos.')"
                            style="display: block; width: 100%; padding: 1rem; background: #dc2626; color: white; text-align: center; border-radius: 8px; text-decoration: none; font-weight: 700;">
                            <i class="fas fa-trash"></i> Reiniciar Sistema
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    /* Switch Toggle Styles */
    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
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
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
    }

    input:checked+.slider {
        background-color: var(--accent-primary);
    }

    input:focus+.slider {
        box-shadow: 0 0 1px var(--accent-primary);
    }

    input:checked+.slider:before {
        -webkit-transform: translateX(26px);
        -ms-transform: translateX(26px);
        transform: translateX(26px);
    }

    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }
</style>

<script>
    function toggleNotifications(checkbox) {
        if (checkbox.checked) {
            window.notificationManager.startPolling();
            window.notificationManager.show('Notificaciones activadas', 'success');
        } else {
            // Stop polling logic would go here if implemented in NotificationManager
            window.notificationManager.show('Notificaciones desactivadas', 'warning');
        }
    }
</script>

</body>

</html>