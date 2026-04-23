SET FOREIGN_KEY_CHECKS = 0;

-- Borrar tablas antiguas para asegurar estructura limpia
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS loans;
DROP TABLE IF EXISTS clients;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS portfolios;
DROP TABLE IF EXISTS settings;

-- Tabla de Carteras/Portfolios
CREATE TABLE IF NOT EXISTS portfolios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar cartera por defecto
INSERT IGNORE INTO portfolios (id, name) VALUES (1, 'General');

-- Tabla de Usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'superadmin', 'cobrador') DEFAULT 'admin',
    portfolio_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE SET NULL
);

-- Usuario SuperAdmin por defecto (password: admin)
INSERT IGNORE INTO users (id, username, password, role) 
VALUES (1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin');

-- Tabla de Clientes
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cedula VARCHAR(20) UNIQUE,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    address TEXT,
    portfolio_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE SET NULL
);

-- Tabla de Préstamos
CREATE TABLE IF NOT EXISTS loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    interest_rate DECIMAL(5, 2) NOT NULL,
    frequency ENUM('daily', 'weekly', 'biweekly', 'monthly') NOT NULL,
    duration_months INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    start_date DATE NOT NULL,
    status ENUM('active', 'paid') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- Tabla de Pagos
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    amount_due DECIMAL(10, 2) NOT NULL,
    due_date DATE NOT NULL,
    paid_amount DECIMAL(10, 2) DEFAULT 0,
    paid_date DATETIME,
    late_fee DECIMAL(10, 2) DEFAULT 0,
    paid_late_fee DECIMAL(10, 2) DEFAULT 0,
    status ENUM('pending', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE
);

-- Tabla de Configuración
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY DEFAULT 1,
    company_name VARCHAR(255) DEFAULT 'Mi Empresa',
    currency_symbol VARCHAR(10) DEFAULT '$',
    logo_path VARCHAR(255),
    interest_rate DECIMAL(5, 2) DEFAULT 15.00,
    company_address TEXT,
    company_phone VARCHAR(50),
    receipt_footer TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar configuración por defecto si no existe
INSERT IGNORE INTO settings (id, company_name, currency_symbol, interest_rate) 
VALUES (1, 'Mi Empresa', '$', 15.00);

SET FOREIGN_KEY_CHECKS = 1;
