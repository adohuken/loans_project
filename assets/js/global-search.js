// Global Search System
class GlobalSearch {
    constructor() {
        this.searchInput = null;
        this.resultsContainer = null;
        this.debounceTimer = null;
        this.init();
    }

    init() {
        this.createSearchBar();
        this.setupKeyboardShortcut();
    }

    createSearchBar() {
        const searchBtn = document.getElementById('global-search-btn');
        if (searchBtn) {
            searchBtn.addEventListener('click', () => this.openSearch());
        }
    }

    openSearch() {
        // Create modal overlay
        const overlay = document.createElement('div');
        overlay.id = 'search-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding-top: 10vh;
            animation: fadeIn 0.2s ease;
        `;

        // Create search container
        const container = document.createElement('div');
        container.style.cssText = `
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: slideDown 0.3s ease;
        `;

        container.innerHTML = `
            <div style="padding: 1.5rem; border-bottom: 2px solid #e2e8f0;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <i class="fas fa-search" style="color: #3b82f6; font-size: 1.5rem;"></i>
                    <input 
                        type="text" 
                        id="global-search-input" 
                        placeholder="Buscar clientes, préstamos, pagos..."
                        style="flex: 1; border: none; outline: none; font-size: 1.2rem; color: #1e293b;"
                        autofocus
                    />
                    <button onclick="document.getElementById('search-overlay').remove()" style="background: none; border: none; color: #64748b; cursor: pointer; font-size: 1.5rem;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <p style="margin: 0.5rem 0 0 0; color: #64748b; font-size: 0.875rem;">
                    <kbd style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">Ctrl</kbd> + 
                    <kbd style="background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">K</kbd> 
                    para abrir
                </p>
            </div>
            <div id="search-results" style="max-height: 400px; overflow-y: auto; padding: 1rem;">
                <div style="text-align: center; padding: 2rem; color: #64748b;">
                    <i class="fas fa-search" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                    <p>Escribe para buscar...</p>
                </div>
            </div>
        `;

        overlay.appendChild(container);
        document.body.appendChild(overlay);

        // Close on overlay click
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.remove();
            }
        });

        // Setup search
        this.searchInput = document.getElementById('global-search-input');
        this.resultsContainer = document.getElementById('search-results');

        this.searchInput.addEventListener('input', (e) => {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => this.performSearch(e.target.value), 300);
        });

        // Add animations
        const styleEl = document.createElement('style');
        styleEl.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes slideDown {
                from { transform: translateY(-50px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
        `;
        document.head.appendChild(styleEl);
    }

    async performSearch(query) {
        if (query.length < 2) {
            this.resultsContainer.innerHTML = `
                <div style="text-align: center; padding: 2rem; color: #64748b;">
                    <i class="fas fa-search" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                    <p>Escribe al menos 2 caracteres...</p>
                </div>
            `;
            return;
        }

        this.resultsContainer.innerHTML = `
            <div style="text-align: center; padding: 2rem;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #3b82f6;"></i>
                <p style="color: #64748b; margin-top: 1rem;">Buscando...</p>
            </div>
        `;

        try {
            const response = await fetch(`global_search.php?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            this.displayResults(data);
        } catch (error) {
            console.error('Search error:', error);
            this.resultsContainer.innerHTML = `
                <div style="text-align: center; padding: 2rem; color: #ef4444;">
                    <i class="fas fa-exclamation-circle" style="font-size: 2rem;"></i>
                    <p>Error al buscar. Intenta de nuevo.</p>
                </div>
            `;
        }
    }

    displayResults(data) {
        if (!data.clients?.length && !data.loans?.length) {
            this.resultsContainer.innerHTML = `
                <div style="text-align: center; padding: 2rem; color: #64748b;">
                    <i class="fas fa-search-minus" style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                    <p>No se encontraron resultados</p>
                </div>
            `;
            return;
        }

        let html = '';

        // Clients
        if (data.clients?.length > 0) {
            html += `
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="color: #64748b; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.75rem;">
                        <i class="fas fa-users"></i> Clientes (${data.clients.length})
                    </h4>
            `;

            data.clients.forEach(client => {
                html += `
                    <a href="client_history.php?id=${client.id}" style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border-radius: 12px; text-decoration: none; color: inherit; transition: background 0.2s; margin-bottom: 0.5rem;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #8b5cf6); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                            ${client.name.charAt(0).toUpperCase()}
                        </div>
                        <div style="flex: 1;">
                            <p style="margin: 0; font-weight: 600; color: #1e293b;">${client.name}</p>
                            <p style="margin: 0; font-size: 0.875rem; color: #64748b;">Cédula: ${client.cedula || 'N/A'}</p>
                        </div>
                        <i class="fas fa-chevron-right" style="color: #cbd5e1;"></i>
                    </a>
                `;
            });

            html += '</div>';
        }

        // Loans
        if (data.loans?.length > 0) {
            html += `
                <div>
                    <h4 style="color: #64748b; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.75rem;">
                        <i class="fas fa-hand-holding-usd"></i> Préstamos (${data.loans.length})
                    </h4>
            `;

            data.loans.forEach(loan => {
                const statusColor = loan.status === 'active' ? '#f59e0b' : '#10b981';
                const statusText = loan.status === 'active' ? 'Activo' : 'Pagado';

                html += `
                    <a href="loan_details.php?id=${loan.id}" style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border-radius: 12px; text-decoration: none; color: inherit; transition: background 0.2s; margin-bottom: 0.5rem; border-left: 3px solid ${statusColor};" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                        <div style="flex: 1;">
                            <p style="margin: 0; font-weight: 600; color: #1e293b;">Préstamo #${loan.id} - ${loan.client_name}</p>
                            <p style="margin: 0; font-size: 0.875rem; color: #64748b;">
                                $${parseFloat(loan.amount).toLocaleString()} • 
                                <span style="color: ${statusColor}; font-weight: 600;">${statusText}</span>
                            </p>
                        </div>
                        <i class="fas fa-chevron-right" style="color: #cbd5e1;"></i>
                    </a>
                `;
            });

            html += '</div>';
        }

        this.resultsContainer.innerHTML = html;
    }

    setupKeyboardShortcut() {
        document.addEventListener('keydown', (e) => {
            // Ctrl+K or Cmd+K
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                this.openSearch();
            }

            // ESC to close
            if (e.key === 'Escape') {
                const overlay = document.getElementById('search-overlay');
                if (overlay) overlay.remove();
            }
        });
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    window.globalSearch = new GlobalSearch();
});
