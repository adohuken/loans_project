<?php
require 'db.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    
    -- Password hash for 'admin123'
    INSERT IGNORE INTO users (id, username, password) VALUES (1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
    ";

    $pdo->exec($sql);
    echo "Tabla 'users' creada y usuario admin configurado. <a href='login.php'>Ir al Login</a>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>