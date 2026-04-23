<?php
require 'auth.php';
require 'db.php';

// Fetch Settings
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';
$user_role = $_SESSION['role'] ?? 'admin';

// Check if user is cobrador and redirect to active_loans
if (isset($_SESSION['role']) && $_SESSION['role'] === 'cobrador') {
    header("Location: active_loans.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: clients.php");
    exit;
}

// Fetch Portfolios for Dropdown
$portfolios = $pdo->query("SELECT * FROM portfolios ORDER BY name ASC")->fetchAll();

// Handle Search
$search = $_GET['search'] ?? '';
$portfolio_filter = $_GET['portfolio_filter'] ?? '';

$query = "
    SELECT c.*, p.name as portfolio_name 
    FROM clients c 
    LEFT JOIN portfolios p ON c.portfolio_id = p.id 
    WHERE 1=1
";

$params = [];

if ($search) {
    $query .= " AND (c.name LIKE ? OR c.cedula LIKE ? OR c.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($portfolio_filter) {
    $query .= " AND c.portfolio_id = ?";
    $params[] = $portfolio_filter;
}

$query .= " ORDER BY c.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$clients = $stmt->fetchAll();

// Include enhanced header
require 'components/enhanced_header.php';
?>

<style>
    body {
        background-color: var(--bg-secondary);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        color: var(--text-primary);
        margin: 0;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
    }

    .page-header {
        margin-bottom: 2rem;
    }

    .main-grid {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 2rem;
        align-items: start;
    }

    @media (max-width: 1024px) {
        .main-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Cards */
    .card {
        background: var(--primary-surface);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border-color);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
    }

    .card-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border-color);
        background: var(--primary-surface);
    }

    .card-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 0;
    }

    .card-body {
        padding: 1.5rem;
    }

    /* Forms */
    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
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

    .btn-submit {
        width: 100%;
        padding: 0.875rem;
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: var(--radius-md);
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-submit:hover {
        background: var(--primary-dark);
    }

    /* Search Bar */
    .search-bar {
        background: var(--secondary-surface);
        padding: 1rem;
        border-radius: var(--radius-md);
        border: 1px solid var(--border-color);
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }

    /* Table */
    .table-container {
        overflow-x: auto;
    }

    .modern-table {
        width: 100%;
        border-collapse: collapse;
    }

    .modern-table th {
        background: var(--secondary-surface);
        padding: 1rem;
        text-align: left;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-secondary);
        font-weight: 700;
        border-bottom: 1px solid var(--border-color);
    }

    .modern-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
        vertical-align: middle;
        font-size: 0.9rem;
    }

    .modern-table tr:hover td {
        background: var(--secondary-surface);
    }

    .client-avatar {
        width: 32px;
        height: 32px;
        background: #eff6ff;
        color: var(--primary-color);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.875rem;
        margin-right: 0.75rem;
    }

    .flex-center {
        display: flex;
        align-items: center;
    }

    /* Badges & Buttons */
    .badge-modern {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        background: #f1f5f9;
        color: #475569;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }

    .btn-icon {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        color: var(--text-secondary);
        transition: all 0.2s;
        border: 1px solid transparent;
    }

    .btn-icon:hover {
        background: var(--secondary-surface);
        color: var(--primary-color);
        border-color: var(--border-color);
    }

    .btn-icon.danger:hover {
        background: #fef2f2;
        color: var(--danger-color);
        border-color: #fee2e2;
    }
</style>

<div class="container">

    <div class="main-grid">
        <!-- New Client Form -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-plus" style="color: var(--primary-color)"></i> Nuevo
                    Cliente</h3>
            </div>
            <div class="card-body">
                <form action="save_client.php" method="POST">
                    <div class="form-group">
                        <label>Nombre Completo</label>
                        <input type="text" name="name" class="form-control" placeholder="Ej: Juan Pérez" required>
                    </div>
                    <div class="form-group">
                        <label>Cédula / DNI</label>
                        <input type="text" name="cedula" class="form-control" placeholder="Identificación">
                    </div>
                    <div class="form-group">
                        <label>Cartera</label>
                        <select name="portfolio_id" class="form-control">
                            <option value="">-- Sin asignar --</option>
                            <?php foreach ($portfolios as $portfolio): ?>
                                <option value="<?= $portfolio['id'] ?>"><?= htmlspecialchars($portfolio['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="phone" class="form-control" placeholder="Celular">
                    </div>
                    <div class="form-group">
                        <label>Dirección</label>
                        <input type="text" name="address" class="form-control" placeholder="Domicilio">
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Guardar Cliente
                    </button>
                </form>
            </div>
        </div>

        <!-- Client List -->
        <div class="card">
            <div class="card-header"
                style="display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem;">
                <h3 class="card-title"><i class="fas fa-users" style="color: #64748b"></i> Base de Clientes</h3>
                <span class="badge-modern"><?= count($clients) ?> Clientes</span>
            </div>

            <div class="card-body" style="padding-top: 0;">
                <!-- Search Toolbar -->
                <form method="GET" action="clients.php" class="search-bar">
                    <div style="flex: 1; position: relative;">
                        <i class="fas fa-search"
                            style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                        <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>"
                            placeholder="Buscar por nombre, cédula o teléfono..."
                            style="padding-left: 2.5rem; border: none; background: var(--bg-tertiary); color: var(--text-primary);">
                    </div>
                    <div style="min-width: 200px;">
                        <select name="portfolio_filter" class="form-control" style="border: none; background: var(--bg-tertiary); color: var(--text-primary);">
                            <option value="">Todas las Carteras</option>
                            <?php foreach ($portfolios as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $portfolio_filter == $p['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-submit" style="width: auto; padding: 0 1.5rem;">Buscar</button>
                    <?php if ($search || $portfolio_filter): ?>
                        <a href="clients.php" class="btn-submit"
                            style="width: auto; padding: 0 1rem; background: var(--secondary-surface); color: var(--text-secondary);">
                            <i class="fas fa-times"></i>
                        </a>
                    <?php endif; ?>
                </form>

                <div class="table-container">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th width="60">ID</th>
                                <th>Cliente</th>
                                <th>Identificación</th>
                                <th>Cartera</th>
                                <th>Contacto</th>
                                <th style="text-align: right;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($clients) > 0): ?>
                                <?php foreach ($clients as $client): ?>
                                    <tr>
                                        <td><span
                                                style="color: var(--text-secondary); font-family: monospace;">#<?= $client['id'] ?></span>
                                        </td>
                                        <td>
                                            <div class="flex-center">
                                                <div class="client-avatar">
                                                    <?= strtoupper(substr($client['name'], 0, 1)) ?>
                                                </div>
                                                <div style="font-weight: 500;"><?= htmlspecialchars($client['name']) ?></div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($client['cedula'] ?? '-') ?></td>
                                        <td>
                                            <?php if ($client['portfolio_name']): ?>
                                                <span class="badge-modern" style="background: #e0f2fe; color: #0284c7;">
                                                    <i class="fas fa-briefcase" style="font-size: 0.7rem;"></i>
                                                    <?= htmlspecialchars($client['portfolio_name']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-modern">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($client['phone'] ?? '-') ?></td>
                                        <td style="text-align: right;">
                                            <a href="client_history.php?id=<?= $client['id'] ?>" class="btn-icon"
                                                title="Ver Historial">
                                                <i class="fas fa-chart-line"></i>
                                            </a>
                                            <a href="edit_client.php?id=<?= $client['id'] ?>" class="btn-icon" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="clients.php?delete=<?= $client['id'] ?>" class="btn-icon danger"
                                                onclick="return confirm('¿Eliminar cliente permanentemente?')" title="Eliminar">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 4rem 2rem;">
                                        <div style="color: var(--text-secondary);">
                                            <i class="fas fa-search"
                                                style="font-size: 2.5rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                                            <p>No se encontraron clientes.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>

</html>