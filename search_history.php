<?php
require 'auth.php';
require 'db.php';

// Fetch Settings for header
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$currency = $settings['currency_symbol'] ?? '$';
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';
$user_role = $_SESSION['role'] ?? 'admin';

// Fetch Portfolios for filter
$portfolios = [];
if ($user_role !== 'cobrador') {
    $portfolios = $pdo->query("SELECT * FROM portfolios ORDER BY name ASC")->fetchAll();
} else {
    $portfolios = $pdo->query("SELECT * FROM portfolios ORDER BY name ASC")->fetchAll();
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
        max-width: 900px;
        margin: 0 auto;
        padding: 3rem 1.5rem;
    }

    .header-section {
        text-align: center;
        margin-bottom: 3rem;
    }

    .icon-wrapper {
        width: 64px;
        height: 64px;
        background: #e0e7ff;
        color: var(--primary-color);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        font-size: 1.75rem;
        box-shadow: 0 0 0 8px #eef2ff;
    }

    .page-title {
        color: var(--text-primary);
        font-size: 2rem;
        margin-bottom: 0.75rem;
        font-weight: 800;
        letter-spacing: -0.025em;
    }

    .page-subtitle {
        color: var(--text-secondary);
        font-size: 1.1rem;
        max-width: 500px;
        margin: 0 auto;
        line-height: 1.5;
    }

    .search-container {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .input-group {
        position: relative;
    }

    .input-icon {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
        pointer-events: none;
        transition: color 0.2s;
    }

    .form-control {
        width: 100%;
        padding: 1rem 1rem 1rem 3rem;
        font-size: 1rem;
        border: 2px solid var(--border-color);
        border-radius: var(--radius-md);
        outline: none;
        background: var(--bg-tertiary);
        color: var(--text-primary);
        transition: all 0.2s;
        box-shadow: var(--shadow-sm);
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    .form-control:focus+.input-icon,
    .input-group:focus-within .input-icon {
        color: var(--primary-color);
    }

    /* Results */
    #results-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .client-card {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        padding: 1.5rem;
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        text-decoration: none;
        color: inherit;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .client-card:hover {
        transform: translateY(-2px);
        border-color: var(--primary-color);
        box-shadow: var(--shadow-md);
    }

    .client-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-color), var(--info-color));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .client-info h3 {
        margin: 0 0 0.25rem 0;
        color: var(--text-primary);
        font-size: 1.1rem;
        font-weight: 600;
    }

    .client-info p {
        margin: 0;
        color: var(--text-secondary);
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .client-info p i {
        color: #94a3b8;
    }

    .action-arrow {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--primary-color);
        font-weight: 600;
        font-size: 0.9rem;
        opacity: 0;
        transform: translateX(-10px);
        transition: all 0.2s;
    }

    .client-card:hover .action-arrow {
        opacity: 1;
        transform: translateX(0);
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: var(--text-secondary);
        background: var(--bg-primary);
        border-radius: var(--radius-lg);
        border: 2px dashed var(--border-color);
    }

    .empty-state i {
        font-size: 3rem;
        opacity: 0.2;
        margin-bottom: 1.5rem;
        color: var(--text-primary);
    }

    .empty-state h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1.25rem;
        color: var(--text-primary);
        font-weight: 700;
    }

    /* Loader */
    .loader {
        display: none;
        text-align: center;
        padding: 2rem;
    }

    .loader i {
        font-size: 2rem;
        color: var(--primary-color);
    }
</style>

<div class="container">
    <div class="header-section">
        <div class="icon-wrapper">
            <i class="fas fa-history"></i>
        </div>
        <h2 class="page-title">Historial de Clientes</h2>
        <p class="page-subtitle">Búsqueda rápida para acceder al historial crediticio completo de cualquier cliente.</p>
    </div>

    <div class="search-container">
        <!-- Filter Dropdown -->
        <div class="input-group" style="flex: 1; min-width: 200px;">
            <i class="fas fa-filter input-icon" style="left: 1rem;"></i>
            <select id="portfolio-filter" class="form-control">
                <option value="">Todas las Carteras</option>
                <?php foreach ($portfolios as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <i class="fas fa-chevron-down input-icon" style="right: 1rem; left: auto;"></i>
        </div>

        <!-- Search Input -->
        <div class="input-group" style="flex: 2; min-width: 300px;">
            <i class="fas fa-search input-icon" style="left: 1rem;"></i>
            <input type="text" id="history-search" class="form-control"
                placeholder="Buscar por nombre, cédula o teléfono..." autofocus>
        </div>
    </div>

    <div id="loading" class="loader">
        <i class="fas fa-circle-notch fa-spin"></i>
    </div>

    <div id="results-container">
        <!-- Default State -->
        <div class="empty-state">
            <i class="fas fa-bolt"></i>
            <h3>Empieza a escribir</h3>
            <p>Los resultados aparecerán automáticamente mientras escribes.</p>
        </div>
    </div>
</div>

<script>
    const searchInput = document.getElementById('history-search');
    const portfolioFilter = document.getElementById('portfolio-filter');
    const resultsContainer = document.getElementById('results-container');
    const loading = document.getElementById('loading');
    let debounceTimer;

    function triggerSearch() {
        const query = searchInput.value;
        const portfolioId = portfolioFilter.value;

        clearTimeout(debounceTimer);

        if (query.length < 2 && !portfolioId) {
            resultsContainer.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-bolt"></i>
                    <h3>Empieza a escribir</h3>
                    <p>Los resultados aparecerán automáticamente.</p>
                </div>
            `;
            return;
        }

        loading.style.display = 'block';
        resultsContainer.style.opacity = '0.5';

        // Instant search for portfolio filter, debounced for text
        if (portfolioId && query.length === 0) {
            performSearch(query, portfolioId);
        } else {
            debounceTimer = setTimeout(() => performSearch(query, portfolioId), 300);
        }
    }

    searchInput.addEventListener('input', triggerSearch);
    portfolioFilter.addEventListener('change', triggerSearch);

    async function performSearch(query, portfolioId) {
        try {
            let url = `global_search.php?q=${encodeURIComponent(query)}`;
            if (portfolioId) {
                url += `&portfolio_id=${encodeURIComponent(portfolioId)}`;
            }

            const response = await fetch(url);
            const data = await response.json();

            displayResults(data);
        } catch (error) {
            console.error(error);
            resultsContainer.innerHTML = `
                <div class="empty-state" style="border-color: #fca5a5; background: #fef2f2;">
                    <i class="fas fa-exclamation-triangle" style="color: #ef4444; opacity: 1;"></i>
                    <h3 style="color: #991b1b;">Error al buscar</h3>
                    <p style="color: #b91c1c;">Por favor intente nuevamente.</p>
                </div>
            `;
        } finally {
            loading.style.display = 'none';
            resultsContainer.style.opacity = '1';
        }
    }

    function displayResults(data) {
        if (!data.clients || data.clients.length === 0) {
            resultsContainer.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-user-slash"></i>
                    <h3>No se encontraron resultados</h3>
                    <p>Intenta con otros términos de búsqueda.</p>
                </div>
            `;
            return;
        }

        const html = data.clients.map(client => `
            <a href="client_history.php?id=${client.id}" class="client-card">
                <div class="client-avatar">
                    ${client.name.charAt(0).toUpperCase()}
                </div>
                
                <div class="client-info">
                    <h3>${client.name}</h3>
                    <p>
                        <span><i class="fas fa-id-card"></i> ${client.cedula || 'N/A'}</span>
                        <span style="opacity: 0.3;">|</span>
                        <span><i class="fas fa-phone"></i> ${client.phone || 'N/A'}</span>
                    </p>
                </div>

                <div class="action-arrow">
                    <span>Ver Historial</span>
                    <i class="fas fa-arrow-right"></i>
                </div>
            </a>
        `).join('');

        resultsContainer.innerHTML = html;
    }
</script>
</body>

</html>