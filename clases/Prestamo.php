<?php
require_once __DIR__ . '/../config/conexion.php'; 

class Prestamo {
    private $conn;
    private $tabla_prestamos = "prestamos";
    private $tabla_abonos = "abonos";
    private $tabla_clientes = "clientes";

    public function __construct($db) {
        $this->conn = $db;
    }

    // --- LÓGICA DE CÁLCULO Y ASIGNACIÓN ---

    public function calcularMontoTotal($monto_inicial, $tasa_mensual, $plazo_meses) {
        $tasa_decimal = $tasa_mensual / 100;
        $interes_total = $monto_inicial * $tasa_decimal * $plazo_meses;
        $monto_total = $monto_inicial + $interes_total;
        return round($monto_total, 2);
    }

    public function asignarPrestamo($datos) {
        $monto_total = $this->calcularMontoTotal($datos['monto_inicial'], $datos['tasa_interes'], $datos['plazo_meses']);

        try {
            $this->conn->beginTransaction();

            // 1. INSERCIÓN DEL PRÉSTAMO
            $query_prestamo = "INSERT INTO " . $this->tabla_prestamos . " 
                            (id_cliente, monto_inicial, tasa_interes, plazo_meses, monto_total, frecuencia_pago, fecha_inicio)
                            VALUES (:id_c, :mi, :ti, :pm, :mt, :fp, :fi)";
            
            $stmt_prestamo = $this->conn->prepare($query_prestamo);
            $stmt_prestamo->execute([
                ':id_c' => $datos['id_cliente'],
                ':mi' => $datos['monto_inicial'],
                ':ti' => $datos['tasa_interes'],
                ':pm' => $datos['plazo_meses'],
                ':mt' => $monto_total,
                ':fp' => $datos['frecuencia_pago'],
                ':fi' => $datos['fecha_inicio']
            ]);
            
            $id_prestamo = $this->conn->lastInsertId();

            // 2. GENERACIÓN Y INSERCIÓN DEL CALENDARIO
            $this->generarCalendario($id_prestamo, $monto_total, $datos['plazo_meses'], $datos['frecuencia_pago'], $datos['fecha_inicio']);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            // Mostrar error solo en desarrollo
            // echo "Error al asignar préstamo: " . $e->getMessage(); 
            return false;
        }
    }

    private function generarCalendario($id_prestamo, $monto_total, $plazo_meses, $frecuencia, $fecha_inicio) {
        $mapa_intervalo = ['Diario' => 'P1D', 'Semanal' => 'P7D', 'Quincenal' => 'P15D', 'Mensual' => 'P1M'];
        $mapa_pagos_por_mes = ['Diario' => 30, 'Semanal' => 4, 'Quincenal' => 2, 'Mensual' => 1];

        $total_pagos = $plazo_meses * $mapa_pagos_por_mes[$frecuencia];
        $monto_por_abono = round($monto_total / $total_pagos, 2);
        $fecha_actual = new DateTime($fecha_inicio);
        $intervalo = $mapa_intervalo[$frecuencia];

        $query_abono = "INSERT INTO " . $this->tabla_abonos . " (id_prestamo, monto_esperado, fecha_vencimiento)
                        VALUES (:idp, :me, :fv)";
        $stmt_abono = $this->conn->prepare($query_abono);

        for ($i = 0; $i < $total_pagos; $i++) {
            $fecha_vencimiento = $fecha_actual->format('Y-m-d');
            
            $stmt_abono->execute([
                ':idp' => $id_prestamo,
                ':me' => $monto_por_abono,
                ':fv' => $fecha_vencimiento
            ]);
            
            // Avanzar solo si no es el primer pago (el primer pago usa la fecha de inicio)
            if ($i < $total_pagos - 1) {
                $fecha_actual->add(new DateInterval($intervalo));
            }
        }
    }

    // --- LÓGICA DE ABONOS ---

    public function obtenerPrestamosActivos() {
        $query = "SELECT p.id_prestamo, c.nombre FROM prestamos p 
                  JOIN clientes c ON p.id_cliente = c.id_cliente 
                  WHERE p.estado = 'Activo' ORDER BY c.nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function registrarAbono($id_prestamo, $monto_pagado) {
        try {
            $this->conn->beginTransaction();
            
            // 1. ENCONTRAR EL PRÓXIMO ABONO PENDIENTE
            $query_select = "SELECT id_abono, monto_esperado FROM abonos 
                             WHERE id_prestamo = :idp AND estado_abono = 'Pendiente' 
                             ORDER BY fecha_vencimiento ASC LIMIT 1";
            $stmt_select = $this->conn->prepare($query_select);
            $stmt_select->execute([':idp' => $id_prestamo]);
            $abono = $stmt_select->fetch(PDO::FETCH_ASSOC);

            if (!$abono) {
                throw new Exception("No hay abonos pendientes para este préstamo.");
            }

            $id_abono = $abono['id_abono'];
            $monto_esperado = $abono['monto_esperado'];
            $fecha_pago = date('Y-m-d H:i:s');
            
            $nuevo_estado = ($monto_pagado >= $monto_esperado) ? 'Pagado' : 'Pendiente';

            // 2. ACTUALIZAR EL ABONO
            $query_update = "UPDATE " . $this->tabla_abonos . " SET 
                             monto_pagado = :mp, 
                             fecha_pago = :fp, 
                             estado_abono = :estado 
                             WHERE id_abono = :ida";

            $stmt_update = $this->conn->prepare($query_update);
            $stmt_update->execute([
                ':mp' => $monto_pagado, 
                ':fp' => $fecha_pago,
                ':estado' => $nuevo_estado,
                ':ida' => $id_abono
            ]);

            // 3. OBTENER DETALLES COMPLETOS PARA EL RECIBO
            $query_recibo = "SELECT 
                                c.nombre as cliente_nombre, c.identificacion,
                                p.id_prestamo, p.monto_inicial, p.tasa_interes, p.plazo_meses,
                                a.id_abono, a.monto_esperado, a.monto_pagado,
                                a.fecha_vencimiento, a.fecha_pago
                             FROM abonos a
                             JOIN prestamos p ON a.id_prestamo = p.id_prestamo
                             JOIN clientes c ON p.id_cliente = c.id_cliente
                             WHERE a.id_abono = :ida";
            
            $stmt_recibo = $this->conn->prepare($query_recibo);
            $stmt_recibo->execute([':ida' => $id_abono]);
            $this->conn->commit();
            
            return $stmt_recibo->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            $this->conn->rollBack();
            // echo "Error al registrar abono: " . $e->getMessage(); 
            return false;
        }
    }

    // --- LÓGICA DE DASHBOARD ---

    public function obtenerMetricasDashboard() {
        $metricas = [
            'total_clientes' => 0, 'prestamos_activos' => 0,
            'monto_prestado_total' => 0.00, 'monto_abonado_hoy' => 0.00,
        ];

        // Total de Clientes
        $query_clientes = "SELECT COUNT(*) AS total FROM " . $this->tabla_clientes;
        $metricas['total_clientes'] = $this->conn->query($query_clientes)->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // Préstamos Activos
        $query_activos = "SELECT COUNT(*) AS total FROM " . $this->tabla_prestamos . " WHERE estado = 'Activo'";
        $metricas['prestamos_activos'] = $this->conn->query($query_activos)->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // Monto Total Prestado
        $query_monto_prestado = "SELECT SUM(monto_inicial) AS total FROM " . $this->tabla_prestamos;
        $monto_prestado = $this->conn->query($query_monto_prestado)->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        $metricas['monto_prestado_total'] = number_format($monto_prestado, 2);

        // Monto Abonado Hoy
        $hoy = date('Y-m-d');
        $query_abonado_hoy = "SELECT SUM(monto_pagado) AS total FROM " . $this->tabla_abonos . " WHERE DATE(fecha_pago) = :hoy AND estado_abono = 'Pagado'";
        $stmt_abonado_hoy = $this->conn->prepare($query_abonado_hoy);
        $stmt_abonado_hoy->execute([':hoy' => $hoy]);
        $monto_hoy = $stmt_abonado_hoy->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        $metricas['monto_abonado_hoy'] = number_format($monto_hoy, 2);

        return $metricas;
    }
    
    public function obtenerProximosAbonos($limite = 5) {
        $query = "SELECT 
                    a.fecha_vencimiento, a.monto_esperado, c.nombre, p.id_prestamo
                  FROM abonos a
                  JOIN prestamos p ON a.id_prestamo = p.id_prestamo
                  JOIN clientes c ON p.id_cliente = c.id_cliente
                  WHERE a.estado_abono = 'Pendiente'
                  ORDER BY a.fecha_vencimiento ASC
                  LIMIT :limite";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
?>