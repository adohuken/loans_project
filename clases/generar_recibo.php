<?php
// Incluir FPDF (Asegúrate de que la ruta sea correcta)
require('fpdf/fpdf.php');
require_once 'config/conexion.php';

// 1. Obtener Datos del Abono
if (!isset($_GET['id_abono'])) {
    die("Error: ID de abono no especificado.");
}

$id_abono = (int)$_GET['id_abono'];

$database = new Conexion();
$db = $database->obtenerConexion();

// Query para obtener todos los detalles necesarios para el recibo
$query = "SELECT 
            c.nombre as cliente_nombre, c.identificacion,
            p.id_prestamo, p.monto_inicial, p.tasa_interes, p.plazo_meses,
            a.id_abono, a.monto_pagado, a.fecha_pago,
            a.monto_esperado, a.fecha_vencimiento
          FROM abonos a
          JOIN prestamos p ON a.id_prestamo = p.id_prestamo
          JOIN clientes c ON p.id_cliente = c.id_cliente
          WHERE a.id_abono = :ida";

$stmt = $db->prepare($query);
$stmt->execute([':ida' => $id_abono]);
$datos = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$datos) {
    die("Error: Datos de abono no encontrados.");
}

// 2. Generación del PDF con FPDF
class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial','B',15);
        $this->Cell(0,10,utf8_decode('RECIBO DE ABONO'),0,1,'C');
        $this->SetFillColor(200, 220, 255);
        $this->SetFont('Arial','I',10);
        $this->Cell(0, 7, utf8_decode("Generado el: " . date('Y-m-d H:i:s')), 0, 1, 'R');
        $this->Ln(5);
    }
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,utf8_decode('Gracias por su pago.').' | Página '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// ----------------------------------------------------------------------
// INFORMACIÓN GENERAL
// ----------------------------------------------------------------------
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0, 10, utf8_decode('INFORMACIÓN DEL PAGO'), 1, 1, 'L', true);

$pdf->SetFont('Arial','',10);
$pdf->Cell(60, 8, utf8_decode('N° de Recibo:'), 0);
$pdf->Cell(0, 8, $datos['id_abono'], 0, 1);

$pdf->Cell(60, 8, utf8_decode('Fecha de Pago Registrada:'), 0);
$pdf->Cell(0, 8, $datos['fecha_pago'], 0, 1);

$pdf->Cell(60, 8, utf8_decode('Préstamo N°:'), 0);
$pdf->Cell(0, 8, $datos['id_prestamo'], 0, 1);

$pdf->Ln(5);

// ----------------------------------------------------------------------
// DATOS DEL CLIENTE
// ----------------------------------------------------------------------
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0, 10, utf8_decode('DATOS DEL CLIENTE'), 1, 1, 'L', true);

$pdf->SetFont('Arial','',10);
$pdf->Cell(60, 8, 'Cliente:', 0);
$pdf->Cell(0, 8, utf8_decode($datos['cliente_nombre']), 0, 1);

$pdf->Cell(60, 8, utf8_decode('Identificación:'), 0);
$pdf->Cell(0, 8, $datos['identificacion'], 0, 1);

$pdf->Ln(5);

// ----------------------------------------------------------------------
// DETALLE DEL MONTO
// ----------------------------------------------------------------------
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0, 10, utf8_decode('DETALLE DEL ABONO'), 1, 1, 'L', true);

$pdf->SetFont('Arial','',10);

$pdf->Cell(60, 8, utf8_decode('Cuota Vencía el:'), 0);
$pdf->Cell(0, 8, $datos['fecha_vencimiento'], 0, 1);

$pdf->Cell(60, 8, utf8_decode('Monto Esperado:'), 0);
$pdf->Cell(0, 8, '$ ' . number_format($datos['monto_esperado'], 2), 0, 1);

// Monto Pagado (Resaltado)
$pdf->SetFont('Arial','B',14);
$pdf->SetFillColor(190, 255, 190);
$pdf->Cell(60, 12, utf8_decode('MONTO PAGADO:'), 1, 0, 'L', true);
$pdf->Cell(0, 12, '$ ' . number_format($datos['monto_pagado'], 2), 1, 1, 'C', true);

$pdf->Ln(15);
$pdf->SetFont('Arial','I',10);
$pdf->Cell(0, 5, utf8_decode('____________________________________'), 0, 1, 'C');
$pdf->Cell(0, 5, utf8_decode('Firma o Sello del Recibidor'), 0, 1, 'C');

// 3. Salida del PDF (Mostrar en el navegador)
$pdf->Output('I', 'Recibo_Abono_' . $datos['id_abono'] . '.pdf');
?>