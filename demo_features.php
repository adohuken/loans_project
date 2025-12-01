<?php
require 'auth.php';
require 'db.php';

// Fetch Settings
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';
$user_role = $_SESSION['role'] ?? 'admin';

// Include enhanced header
require 'components/enhanced_header.php';
?>

<div class="container" style="padding: 2rem;">
    <div class="card" style="padding: 3rem; text-align: center;">
        <h1
            style="font-size: 3rem; margin-bottom: 1rem; background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            🎉 ¡Nuevas Funcionalidades!
        </h1>

        <p style="font-size: 1.25rem; color: var(--text-secondary); margin-bottom: 3rem;">
            Tu sistema de préstamos ahora tiene mejoras increíbles de UX/UI
        </p>

        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; margin-top: 3rem;">
            <!-- Feature 1 -->
            <div style="background: var(--bg-secondary); padding: 2rem; border-radius: 20px; border: 2px solid var(--border-color); transition: transform 0.3s;"
                onmouseover="this.style.transform='translateY(-8px)'" onmouseout="this.style.transform='translateY(0)'">
                <div
                    style="width: 80px; height: 80px; margin: 0 auto 1.5rem; background: linear-gradient(135deg, #3b82f6, #8b5cf6); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem;">
                    🌙
                </div>
                <h3 style="color: var(--text-primary); margin-bottom: 1rem;">Modo Oscuro</h3>
                <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                    Cambia entre tema claro y oscuro con un solo click. Perfecto para trabajar de noche.
                </p>
                <button onclick="window.themeManager?.toggleTheme()"
                    style="background: var(--accent-primary); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600;">
                    Probar Ahora
                </button>
            </div>

            <!-- Feature 2 -->
            <div style="background: var(--bg-secondary); padding: 2rem; border-radius: 20px; border: 2px solid var(--border-color); transition: transform 0.3s;"
                onmouseover="this.style.transform='translateY(-8px)'" onmouseout="this.style.transform='translateY(0)'">
                <div
                    style="width: 80px; height: 80px; margin: 0 auto 1.5rem; background: linear-gradient(135deg, #10b981, #059669); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem;">
                    🎨
                </div>
                <h3 style="color: var(--text-primary); margin-bottom: 1rem;">Temas de Color</h3>
                <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                    5 esquemas de color para personalizar tu experiencia. Elige tu favorito.
                </p>
                <div style="display: flex; justify-content: center; gap: 0.5rem;">
                    <div style="width: 40px; height: 40px; background: #3b82f6; border-radius: 50%; cursor: pointer;"
                        onclick="window.themeManager?.setColorTheme('blue')"></div>
                    <div style="width: 40px; height: 40px; background: #8b5cf6; border-radius: 50%; cursor: pointer;"
                        onclick="window.themeManager?.setColorTheme('purple')"></div>
                    <div style="width: 40px; height: 40px; background: #10b981; border-radius: 50%; cursor: pointer;"
                        onclick="window.themeManager?.setColorTheme('green')"></div>
                </div>
            </div>

            <!-- Feature 3 -->
            <div style="background: var(--bg-secondary); padding: 2rem; border-radius: 20px; border: 2px solid var(--border-color); transition: transform 0.3s;"
                onmouseover="this.style.transform='translateY(-8px)'" onmouseout="this.style.transform='translateY(0)'">
                <div
                    style="width: 80px; height: 80px; margin: 0 auto 1.5rem; background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem;">
                    🔔
                </div>
                <h3 style="color: var(--text-primary); margin-bottom: 1rem;">Notificaciones</h3>
                <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                    Recibe alertas de pagos próximos y vencidos. Nunca pierdas un pago.
                </p>
                <button onclick="toggleNotifications()"
                    style="background: var(--accent-primary); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600;">
                    Ver Notificaciones
                </button>
            </div>

            <!-- Feature 4 -->
            <div style="background: var(--bg-secondary); padding: 2rem; border-radius: 20px; border: 2px solid var(--border-color); transition: transform 0.3s;"
                onmouseover="this.style.transform='translateY(-8px)'" onmouseout="this.style.transform='translateY(0)'">
                <div
                    style="width: 80px; height: 80px; margin: 0 auto 1.5rem; background: linear-gradient(135deg, #ec4899, #db2777); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem;">
                    ⚡
                </div>
                <h3 style="color: var(--text-primary); margin-bottom: 1rem;">Búsqueda Rápida</h3>
                <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
                    Encuentra clientes y préstamos al instante. Usa Ctrl+K para abrir.
                </p>
                <button onclick="window.globalSearch?.openSearch()"
                    style="background: var(--accent-primary); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600;">
                    Probar (Ctrl+K)
                </button>
            </div>
        </div>

        <!-- Demo Notifications -->
        <div
            style="margin-top: 4rem; padding: 2rem; background: var(--accent-lighter); border-radius: 20px; border: 2px solid var(--accent-primary);">
            <h3 style="color: var(--text-primary); margin-bottom: 2rem;">
                <i class="fas fa-rocket"></i> Prueba las Notificaciones
            </h3>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <button onclick="window.notificationManager?.show('¡Operación exitosa!', 'success')"
                    style="background: #10b981; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600;">
                    <i class="fas fa-check-circle"></i> Success
                </button>
                <button onclick="window.notificationManager?.show('Algo salió mal', 'error')"
                    style="background: #ef4444; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600;">
                    <i class="fas fa-exclamation-circle"></i> Error
                </button>
                <button onclick="window.notificationManager?.show('Ten cuidado con esto', 'warning')"
                    style="background: #f59e0b; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600;">
                    <i class="fas fa-exclamation-triangle"></i> Warning
                </button>
                <button onclick="window.notificationManager?.show('Información importante', 'info')"
                    style="background: #3b82f6; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600;">
                    <i class="fas fa-info-circle"></i> Info
                </button>
            </div>
        </div>

        <!-- Keyboard Shortcuts -->
        <div
            style="margin-top: 3rem; padding: 2rem; background: var(--bg-secondary); border-radius: 20px; border: 2px solid var(--border-color);">
            <h3 style="color: var(--text-primary); margin-bottom: 1.5rem;">
                <i class="fas fa-keyboard"></i> Atajos de Teclado
            </h3>
            <div style="display: flex; gap: 2rem; justify-content: center; flex-wrap: wrap;">
                <div style="text-align: center;">
                    <kbd
                        style="background: var(--bg-tertiary); padding: 0.5rem 1rem; border-radius: 8px; font-size: 1.1rem; font-weight: 600; color: var(--text-primary); box-shadow: 0 2px 4px var(--shadow);">Ctrl
                        + K</kbd>
                    <p style="margin-top: 0.5rem; color: var(--text-secondary); font-size: 0.9rem;">Búsqueda Global</p>
                </div>
                <div style="text-align: center;">
                    <kbd
                        style="background: var(--bg-tertiary); padding: 0.5rem 1rem; border-radius: 8px; font-size: 1.1rem; font-weight: 600; color: var(--text-primary); box-shadow: 0 2px 4px var(--shadow);">ESC</kbd>
                    <p style="margin-top: 0.5rem; color: var(--text-secondary); font-size: 0.9rem;">Cerrar Modales</p>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div style="margin-top: 3rem;">
            <a href="index.php"
                style="display: inline-flex; align-items: center; gap: 0.75rem; background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); color: white; padding: 1rem 2rem; border-radius: 12px; text-decoration: none; font-weight: 700; box-shadow: 0 8px 16px -4px var(--shadow); transition: transform 0.3s;"
                onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                <i class="fas fa-arrow-left"></i>
                Volver al Inicio
            </a>
        </div>
    </div>
</div>

</body>

</html>