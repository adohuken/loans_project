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

<div class="container">
    <div class="grid grid-2-3">
        <div class="card">
            <h2><i class="fas fa-user-plus"></i> Nuevo Cliente</h2>
            <form action="save_client.php" method="POST">
                <div class="form-group">
                    <label>Nombre Completo</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Cédula / DNI</label>
                    <input type="text" name="cedula">
                </div>
                <div class="form-group">
                    <label>Cartera (Opcional)</label>
                    <select name="portfolio_id"
                        style="width: 100%; padding: 0.875rem 1rem; border: 2px solid #e2e8f0; border-radius: 12px; background: white;">
                        <option value="">-- Seleccionar Cartera --</option>
                        <?php foreach ($portfolios as $portfolio): ?>
                            <option value="<?= $portfolio['id'] ?>"><?= htmlspecialchars($portfolio['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="phone">
                </div>
                <div class="form-group">
                    <label>Dirección</label>
                    <input type="text" name="address">
                </div>
                <button type="submit" class="btn"><i class="fas fa-save"></i> Guardar Cliente</button>
            </form>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                <h2 style="margin: 0;"><i class="fas fa-users"></i> Lista de Clientes</h2>
            </div>

            <!-- Search Form -->
            <form method="GET" action="clients.php" style="background: #f8fafc; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; border: 1px solid #e2e8f0;">
                <div style="flex: 1; min-width: 200px;">
                    <input type="text" name="search" placeholder="Buscar por nombre, cédula o teléfono..." value="<?= htmlspecialchars($search) ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                </div>
                <div style="min-width: 200px;">
                    <select name="portfolio_filter" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                        <option value="">Todas las Carteras</option>
                        <?php foreach ($portfolios as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $portfolio_filter == $p['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <?php if ($search || $portfolio_filter): ?>
                    <a href="clients.php" class="btn btn-secondary" style="padding: 0.75rem 1.5rem;">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                <?php endif; ?>
            </form>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Cédula</th>
                            <th>Cartera</th>
                            <th>Teléfono</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($clients) > 0): ?>
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td>#<?= $client['id'] ?></td>
                                    <td><?= htmlspecialchars($client['name']) ?></td>
                                    <td><?= htmlspecialchars($client['cedula']) ?></td>
                                    <td>
                                        <?php if ($client['portfolio_name']): ?>
                                            <span class="badge badge-pending"
                                                style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">
                                                <i class="fas fa-briefcase" style="font-size: 0.75rem;"></i>
                                                <?= htmlspecialchars($client['portfolio_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #94a3b8;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($client['phone']) ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="client_history.php?id=<?= $client['id'] ?>"
                                                class="btn btn-sm btn-secondary" title="Ver Historial">
                                                <i class="fas fa-history"></i>
                                            </a>
                                            <a href="edit_client.php?id=<?= $client['id'] ?>" 
                                               class="btn btn-sm btn-primary" title="Editar Cliente">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="clients.php?delete=<?= $client['id'] ?>" class="btn btn-sm btn-danger"
                                                onclick="return confirm('¿Eliminar cliente?')" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem; color: #64748b;">
                                    <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 1rem; display: block; opacity: 0.5;"></i>
                                    No se encontraron clientes con los criterios de búsqueda.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>