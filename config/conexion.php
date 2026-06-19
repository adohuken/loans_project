<?php
class Conexion {
    private $host = "localhost";
    private $db_name = "sistema_prestamos"; // ¡CAMBIAR ESTO!
    private $username = "root"; // ¡CAMBIAR ESTO!
    private $password = ""; // ¡CAMBIAR ESTO!
    public $conn;

    public function obtenerConexion() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Error de conexión: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>