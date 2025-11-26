# Carpeta Utils

Esta carpeta contiene archivos de utilidad y migración que **NO son necesarios** para el funcionamiento normal del sistema.

## Contenido

### Scripts de Migración
- `migrate_late_fee.php` - Migración para agregar columna de mora
- `migrate_settings.php` - Migración de configuración v1
- `migrate_settings_v2.php` - Migración de configuración v2
- `migrate_users.php` - Migración de usuarios
- `update_db_role.php` - Migración para agregar roles de usuario

### Utilidades
- `debug_db.php` - Script de depuración de base de datos
- `reset_admin.php` - Resetear contraseña de admin
- `check_settings.php` - Verificar configuración del sistema

## Uso

Estos archivos solo deben ejecutarse cuando:
1. Se está migrando de una versión anterior
2. Se necesita depurar la base de datos
3. Se requiere resetear credenciales de administrador

**IMPORTANTE**: No eliminar estos archivos, pueden ser necesarios para futuras migraciones o mantenimiento.
