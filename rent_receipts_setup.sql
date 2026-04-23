-- Script para crear la tabla de recibos de renta
-- Ejecutar esto en la base de datos de producción (InfinityFree u otro)

CREATE TABLE IF NOT EXISTS rent_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_name VARCHAR(255) NOT NULL,
    tenant_id_number VARCHAR(50),
    amount DECIMAL(10, 2) NOT NULL,
    payment_date DATE NOT NULL,
    concept TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
