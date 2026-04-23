<?php
// Enhanced Header Component with UX/UI improvements
// Include this file in your pages: require 'components/enhanced_header.php';

$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Default values to prevent undefined variable warnings
$company_name = $company_name ?? 'Sistema de Préstamos';
$user_role = $user_role ?? ($_SESSION['role'] ?? 'guest');
$logo_path = $logo_path ?? '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css?v=3.0">
    <link rel="stylesheet" href="assets/css/themes.css?v=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Apply theme immediately to prevent flash
        (function () {
            const theme = localStorage.getItem('theme') || 'light';
            const color = localStorage.getItem('colorTheme') || 'blue';
            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.setAttribute('data-color', color);
        })();
    </script>
</head>

<body>
    <header style="flex-direction: column; gap: 1rem; padding: 1.5rem;">
        <!-- Top Row: Logo and Controls -->
        <div
            style="display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: wrap; gap: 1rem;">
            <!-- Logo and Company Name -->
            <div style="display: flex; align-items: center; gap: 1rem;">
                <?php if (!empty($logo_path)): ?>
                    <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo"
                        style="height: 50px; width: 50px; object-fit: cover; border-radius: 50%;">
                <?php endif; ?>
                <h1
                    style="margin: 0; font-size: 1.75rem; background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    <?= htmlspecialchars($company_name) ?>
                </h1>
            </div>

            <!-- Right Controls -->
            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <!-- Global Search Button -->
                <button id="global-search-btn" class="search-btn" title="Búsqueda rápida">
                    <i class="fas fa-search"></i>
                    <span>Buscar</span>
                    <kbd>Ctrl+K</kbd>
                </button>

                <!-- Color Theme Picker (Only on Settings Page) -->
                <?php if ($current_page === 'settings'): ?>
                    <div class="color-picker" title="Cambiar tema de color">
                        <div class="color-option blue" onclick="window.themeManager?.setColorTheme('blue')"
                            data-color="blue"></div>
                        <div class="color-option purple" onclick="window.themeManager?.setColorTheme('purple')"
                            data-color="purple"></div>
                        <div class="color-option green" onclick="window.themeManager?.setColorTheme('green')"
                            data-color="green"></div>
                        <div class="color-option orange" onclick="window.themeManager?.setColorTheme('orange')"
                            data-color="orange"></div>
                        <div class="color-option pink" onclick="window.themeManager?.setColorTheme('pink')"
                            data-color="pink"></div>
                    </div>
                <?php endif; ?>

                <!-- Dark Mode Toggle -->
                <button id="theme-toggle" class="theme-toggle-btn" title="Cambiar tema claro/oscuro">
                    <i id="theme-toggle-icon" class="fas fa-moon"></i>
                    <span>Tema</span>
                </button>

                <!-- Notifications Bell -->
                <button onclick="toggleNotifications()" class="theme-toggle-btn notification-bell"
                    style="position: relative;" title="Notificaciones">
                    <i class="fas fa-bell"></i>
                    <span id="notification-badge" class="notification-badge" style="display: none;">0</span>
                </button>

                <!-- User Menu -->
                <div
                    style="display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 1rem; background: var(--accent-lighter); border-radius: 12px; border: 2px solid var(--accent-primary);">
                    <div
                        style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.1rem;">
                        <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div>
                        <p style="margin: 0; font-weight: 700; color: var(--text-primary); font-size: 0.9rem;">
                            <?= htmlspecialchars($_SESSION['username'] ?? 'Usuario') ?>
                        </p>
                        <p
                            style="margin: 0; font-size: 0.75rem; color: var(--text-secondary); text-transform: capitalize;">
                            <?= htmlspecialchars($user_role) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav
            style="display: flex; justify-content: center; flex-wrap: wrap; gap: 0.5rem; padding-top: 1rem; border-top: 2px solid var(--border-color);">
            <?php if ($user_role !== 'cobrador'): ?>
                <a href="index.php" class="<?= $current_page === 'index' ? 'active' : '' ?>">
                    <i class="fas fa-home"></i> Inicio
                </a>
            <?php endif; ?>

            <?php if ($user_role !== 'cobrador'): ?>
                <a href="clients.php" class="<?= $current_page === 'clients' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> Clientes
                </a>
                <a href="search_history.php" class="<?= $current_page === 'search_history' ? 'active' : '' ?>">
                    <i class="fas fa-history"></i> Historial
                </a>
            <?php endif; ?>

            <a href="active_loans.php" class="<?= $current_page === 'active_loans' ? 'active' : '' ?>">
                <i class="fas fa-hand-holding-usd"></i> Abonar
            </a>

            <a href="rent_receipts.php" class="<?= $current_page === 'rent_receipts' || $current_page === 'create_rent_receipt' || $current_page === 'view_rent_receipt' ? 'active' : '' ?>">
                <i class="fas fa-file-invoice"></i> Renta
            </a>

            <?php if ($user_role !== 'cobrador'): ?>
                <a href="create_loan.php" class="<?= $current_page === 'create_loan' ? 'active' : '' ?>">
                    <i class="fas fa-plus-circle"></i> Nuevo Préstamo
                </a>
                <a href="reports.php" class="<?= $current_page === 'reports' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i> Reportes
                </a>
                <a href="portfolios.php" class="<?= $current_page === 'portfolios' ? 'active' : '' ?>">
                    <i class="fas fa-briefcase"></i> Carteras
                </a>
            <?php endif; ?>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                <a href="users.php" class="<?= $current_page === 'users' ? 'active' : '' ?>">
                    <i class="fas fa-user-shield"></i> Usuarios
                </a>
            <?php endif; ?>

            <?php if ($user_role !== 'cobrador'): ?>
                <a href="settings.php" class="<?= $current_page === 'settings' ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i> Configuración
                </a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin'): ?>
                    <a href="backup.php" class="<?= $current_page === 'backup' ? 'active' : '' ?>">
                        <i class="fas fa-database"></i> Backup
                    </a>
                <?php endif; ?>
            <?php endif; ?>

            <a href="logout.php" style="color: #ef4444; margin-left: auto;">
                <i class="fas fa-sign-out-alt"></i> Salir
            </a>
        </nav>
    </header>

    <!-- Notification Panel (Hidden by default) -->
    <div id="notification-panel"
        style="display: none; position: fixed; top: 80px; right: 20px; width: 400px; max-width: 90vw; background: var(--bg-primary); border-radius: 16px; box-shadow: 0 20px 40px -10px var(--shadow); z-index: 9998; max-height: 500px; overflow-y: auto; border: 2px solid var(--border-color);">
        <div
            style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: var(--bg-secondary);">
            <h3 style="margin: 0; font-weight: 800; color: var(--text-primary); font-size: 1.1rem;">
                <i class="fas fa-bell" style="color: var(--primary-color);"></i> Notificaciones
            </h3>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <button onclick="markAllRead()"
                    style="background: none; border: none; color: var(--primary-color); font-size: 0.8rem; font-weight: 600; cursor: pointer;">
                    Marcar todo leído
                </button>
                <button onclick="toggleNotifications()"
                    style="background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 1.2rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div id="notification-list" style="padding: 0;">
            <div style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i>
                <p>Cargando notificaciones...</p>
            </div>
        </div>
    </div>

    <!-- Load Scripts -->
    <script src="assets/js/theme-manager.js?v=1.0"></script>
    <script src="assets/js/notifications.js?v=1.0"></script>
    <script src="assets/js/global-search.js?v=1.0"></script>

    <script>
        // Toggle Notifications Panel
        function toggleNotifications() {
            const panel = document.getElementById('notification-panel');
            if (panel.style.display === 'none') {
                panel.style.display = 'block';
                loadNotificationPanel();
            } else {
                panel.style.display = 'none';
            }
        }

        // Mark all as read
        async function markAllRead() {
            try {
                await fetch('mark_notification_read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ all: true })
                });
                loadNotificationPanel(); // Reload to update UI
                // Update global badge via NotificationManager if available
                if (window.notificationManager) {
                    window.notificationManager.loadNotifications();
                }
            } catch (error) {
                console.error('Error marking all read:', error);
            }
        }

        // Load notifications into panel
        async function loadNotificationPanel() {
            const list = document.getElementById('notification-list');
            try {
                const response = await fetch('get_notifications.php');
                const data = await response.json();

                if (data.notifications.length === 0) {
                    list.innerHTML = `
                        <div style="text-align: center; padding: 3rem 2rem; color: var(--text-secondary);">
                            <i class="fas fa-check-circle" style="font-size: 3rem; opacity: 0.2; margin-bottom: 1rem;"></i>
                            <p style="margin: 0;">No tienes notificaciones nuevas</p>
                        </div>
                    `;
                    return;
                }

                list.innerHTML = data.notifications.map(n => `
                    <div style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); transition: background 0.2s; ${n.read ? 'opacity: 0.6;' : 'background: var(--primary-surface); border-left: 4px solid var(--primary-color);'}" 
                         onmouseover="this.style.background='var(--bg-secondary)'" 
                         onmouseout="this.style.background='${n.read ? 'transparent' : 'var(--primary-surface)'}'">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                            <strong style="color: var(--text-primary); font-size: 0.95rem;">${n.title}</strong>
                            <small style="color: var(--text-secondary); font-size: 0.75rem;">${formatDate(n.date)}</small>
                        </div>
                        <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem; line-height: 1.4;">${n.message}</p>
                    </div>
                `).join('');

                // Update badge
                const badge = document.getElementById('notification-badge');
                if (badge) {
                    if (data.unread_count > 0) {
                        badge.style.display = 'flex';
                        badge.innerText = data.unread_count > 99 ? '99+' : data.unread_count;
                    } else {
                        badge.style.display = 'none';
                    }
                }

            } catch (error) {
                console.error('Error loading notifications:', error);
                list.innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: #ef4444;">
                        <i class="fas fa-exclamation-circle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>Error al cargar notificaciones</p>
                    </div>
                `;
            }
        }

        // Check for notifications periodically
        setInterval(() => {
            // Update badge quietly in background
            fetch('get_notifications.php')
                .then(r => r.json())
                .then(data => {
                    const badge = document.getElementById('notification-badge');
                    if (badge) {
                        if (data.unread_count > 0) {
                            badge.style.display = 'flex';
                            badge.innerText = data.unread_count > 99 ? '99+' : data.unread_count;
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(e => console.error('Error polling notifications:', e));
        }, 30000);

        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString + 'T00:00:00'); // append time to avoid timezone issues
            return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
        }

        // Close panel when clicking outside
        document.addEventListener('click', function (event) {
            const panel = document.getElementById('notification-panel');
            const toggleBtn = document.querySelector('button[onclick="toggleNotifications()"]');

            if (panel.style.display === 'block' &&
                !panel.contains(event.target) &&
                !toggleBtn.contains(event.target)) {
                panel.style.display = 'none';
            }
        });
    </script>
</body>

</html>