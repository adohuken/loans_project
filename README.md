# Sistema de Gestión de Préstamos

Sistema completo de gestión de préstamos con interfaz moderna, control de usuarios por roles y gestión de carteras.

## 🚀 Características Principales

- ✅ **Gestión de Clientes**: Registro completo con cédula, teléfono y dirección
- ✅ **Préstamos Flexibles**: Frecuencias diarias, semanales, quincenales y mensuales
- ✅ **Calendario de Pagos**: Generación automática de cuotas
- ✅ **Pagos Parciales**: Permite abonos parciales a las cuotas
- ✅ **Mora Automática**: Cálculo y registro de moras
- ✅ **Carteras/Portfolios**: Organización de clientes por carteras
- ✅ **Roles de Usuario**: SuperAdmin, Admin y Cobrador
- ✅ **Reportes Financieros**: Estadísticas detalladas por cartera
- ✅ **Backup y Restauración**: Sistema completo de respaldo
- ✅ **Recibos PDF**: Generación automática de recibos
- ✅ **Diseño Moderno**: Interfaz glassmorphism con gradientes

## 📋 Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- Extensiones PHP: PDO, PDO_MySQL

## 🔧 Instalación

1. **Clonar o copiar el proyecto** en tu directorio web (ej: `htdocs/loans_project`)

2. **Crear la base de datos**:
   ```bash
   mysql -u root -p < database.sql
   ```

3. **Configurar la conexión** en `db.php`:
   ```php
   $host = 'localhost';
   $dbname = 'loans_db';
   $username = 'root';
   $password = '';
   ```

4. **Acceder al sistema**:
   - URL: `http://localhost/loans_project/login.php`
   - Usuario: `admin`
   - Contraseña: `admin`

## 👥 Roles de Usuario

### SuperAdmin
- Acceso completo al sistema
- Gestión de usuarios
- Backup y restauración
- Reinicio del sistema

### Admin
- Gestión de clientes y préstamos
- Reportes financieros
- Configuración del sistema
- Gestión de carteras

### Cobrador
- Solo ve clientes de su cartera asignada
- Registro de pagos
- Consulta de préstamos activos

## 📁 Estructura del Proyecto

```
loans_project/
├── index.php              # Dashboard principal
├── login.php              # Página de inicio de sesión
├── auth.php               # Autenticación de sesiones
├── db.php                 # Conexión a base de datos
├── clients.php            # Gestión de clientes
├── create_loan.php        # Crear nuevo préstamo
├── active_loans.php       # Préstamos activos
├── loan_details.php       # Detalles de préstamo
├── process_payment.php    # Procesar pagos
├── receipt.php            # Generar recibos
├── reports.php            # Reportes financieros
├── portfolios.php         # Gestión de carteras
├── users.php              # Gestión de usuarios
├── settings.php           # Configuración del sistema
├── backup.php             # Backup y restauración
├── reset_system.php       # Reinicio del sistema
├── style.css              # Estilos principales
└── database.sql           # Esquema de base de datos
```

## 💡 Uso del Sistema

### Crear un Préstamo

1. Ir a **Clientes** y registrar un nuevo cliente (o seleccionar uno existente)
2. Ir a **Nuevo Préstamo**
3. Seleccionar cliente, monto, tasa de interés y frecuencia
4. El sistema genera automáticamente el calendario de pagos

### Registrar un Pago

1. Ir a **Abonar** (Active Loans)
2. Buscar el préstamo del cliente
3. Click en **Ver Detalles**
4. Click en **Pagar** en la cuota correspondiente
5. Ingresar el monto (puede ser parcial)
6. El sistema calcula automáticamente la mora si aplica

### Gestionar Carteras

1. Ir a **Carteras**
2. Crear nuevas carteras (ej: "Ruta Norte", "Cobrador Juan")
3. Asignar clientes a carteras desde **Clientes**
4. Asignar usuarios cobradores a carteras desde **Usuarios**

### Ver Reportes

1. Ir a **Reportes**
2. Filtrar por fecha y/o cartera
3. Ver estadísticas detalladas:
   - Total prestado
   - Total recaudado
   - Saldo pendiente
   - Mora registrada
   - Estadísticas por cartera

## 🎨 Personalización

### Cambiar Logo y Nombre de Empresa

1. Ir a **Configuración**
2. Cambiar nombre de empresa
3. Subir logo (formatos: JPG, PNG, GIF)
4. Cambiar símbolo de moneda

### Crear Usuarios

1. Ir a **Usuarios** (solo SuperAdmin)
2. Click en **Crear Nuevo Usuario**
3. Seleccionar rol:
   - **Admin**: Acceso completo excepto gestión de usuarios
   - **Cobrador**: Solo su cartera asignada
   - **SuperAdmin**: Acceso total

## 🔒 Seguridad

- Contraseñas hasheadas con `password_hash()`
- Protección contra SQL injection con PDO prepared statements
- Validación de sesiones en todas las páginas
- Control de acceso por roles
- Cobradores solo ven su cartera asignada

## 🔄 Backup y Restauración

### Crear Backup

1. Ir a **Backup** (solo SuperAdmin)
2. Click en **Descargar Backup SQL**
3. Se descarga un archivo `.sql` con todos los datos

### Restaurar Backup

1. Ir a **Backup**
2. Seleccionar archivo `.sql`
3. Click en **Restaurar Sistema**
4. ⚠️ Esto sobrescribe todos los datos actuales

## 🔄 Reiniciar Sistema

Para empezar de cero con una nueva empresa:

1. Ir a **Configuración** (solo SuperAdmin)
2. Scroll hasta "Zona de Peligro"
3. Click en **Reiniciar Sistema**
4. Escribir "REINICIAR" para confirmar
5. El sistema elimina todos los datos excepto el usuario admin

## 📊 Cálculo de Intereses

El sistema usa **interés mensual simple**:

```
Interés Total = Monto × (Tasa/100) × Meses
Total a Pagar = Monto + Interés Total
Cuota = Total a Pagar / Número de Cuotas
```

**Ejemplo:**
- Monto: $10,000
- Tasa: 15% mensual
- Plazo: 3 meses
- Frecuencia: Semanal (12 cuotas)

```
Interés = 10,000 × 0.15 × 3 = $4,500
Total = 10,000 + 4,500 = $14,500
Cuota Semanal = 14,500 / 12 = $1,208.33
```

## 🐛 Solución de Problemas

### Error de conexión a base de datos
- Verificar credenciales en `db.php`
- Verificar que MySQL esté corriendo
- Verificar que la base de datos `loans_db` exista

### No aparece el logo
- Verificar que la carpeta `uploads/` tenga permisos de escritura
- Verificar que la ruta del logo sea correcta

### Usuario cobrador no ve préstamos
- Verificar que el cobrador tenga una cartera asignada
- Verificar que los clientes estén asignados a esa cartera

## 📝 Licencia

Este proyecto es de código abierto y está disponible para uso personal y comercial.

## 🤝 Soporte

Para reportar bugs o solicitar características, contacta al desarrollador.

---

**Versión:** 3.0  
**Última actualización:** Noviembre 2025
