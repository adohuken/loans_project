# Sistema de Préstamos

Este sistema permite gestionar clientes, préstamos y pagos con cálculo de intereses y generación de recibos.

## Requisitos
- PHP instalado (versión 7.4 o superior recomendada).
- Extensión `pdo_sqlite` habilitada en PHP.

## Instalación y Ejecución

1.  Descomprima o ubique la carpeta del proyecto.
2.  Abra una terminal en la carpeta del proyecto.
3.  Ejecute el servidor interno de PHP:
    ```bash
    php -S localhost:8000
    ```
4.  Abra su navegador web e ingrese a: `http://localhost:8000`

## Uso

1.  **Clientes**: Registre sus clientes en la sección "Clientes".
2.  **Préstamos**: Cree nuevos préstamos asignando monto, interés mensual, duración y frecuencia de pago.
    - El sistema calculará automáticamente el total con intereses y dividirá los pagos.
3.  **Pagos**: Desde el "Inicio" o "Detalles del Préstamo", registre los pagos de las cuotas.
4.  **Recibos**: Al registrar un pago, podrá imprimir o guardar el recibo en PDF.

## Base de Datos
El sistema utiliza SQLite. El archivo de base de datos `loans.db` se creará automáticamente la primera vez que ejecute la aplicación.
