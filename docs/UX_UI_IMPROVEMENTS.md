# 🎨 Mejoras de UX/UI - Sistema de Préstamos

## ✨ Funcionalidades Implementadas

### 1. 🌙 **Modo Oscuro / Claro**
- **Toggle** en el header para cambiar entre temas
- **Guardado automático** en localStorage
- **Transiciones suaves** entre temas
- **Iconos dinámicos** (luna/sol)

**Uso:**
- Click en el botón "Tema" en el header
- O usa el atajo de teclado (próximamente)

---

### 2. 🎨 **Selector de Temas de Color**
5 esquemas de color disponibles:
- 🔵 **Azul** (predeterminado)
- 🟣 **Púrpura**
- 🟢 **Verde**
- 🟠 **Naranja**
- 🩷 **Rosa**

**Uso:**
- Click en los círculos de color en el header
- El tema se guarda automáticamente

---

### 3. 🔔 **Sistema de Notificaciones**
Notificaciones automáticas para:
- ⚠️ **Pagos próximos a vencer** (3 días antes)
- ❌ **Pagos vencidos**
- ✅ **Pagos completados** (opcional)

**Características:**
- Badge con contador de notificaciones
- Panel deslizable desde el header
- Actualización automática cada 30 segundos
- Toast notifications para eventos importantes

**Uso:**
- Click en el ícono de campana 🔔
- Las notificaciones se actualizan automáticamente

---

### 4. ⚡ **Búsqueda Global Rápida**
Busca en todo el sistema:
- 👥 Clientes (por nombre, cédula, teléfono)
- 💰 Préstamos (por ID, cliente)
- 📄 Pagos (próximamente)

**Características:**
- Modal overlay elegante
- Búsqueda en tiempo real (debounce 300ms)
- Resultados categorizados
- Navegación rápida a detalles

**Uso:**
- Click en botón "Buscar" en header
- O presiona **Ctrl + K** (Cmd + K en Mac)
- Escribe al menos 2 caracteres
- Presiona **ESC** para cerrar

---

## 📁 Archivos Creados

### JavaScript:
```
assets/js/
├── theme-manager.js      # Gestión de temas
├── notifications.js      # Sistema de notificaciones
└── global-search.js      # Búsqueda global
```

### CSS:
```
assets/css/
└── themes.css           # Estilos de temas y modo oscuro
```

### PHP:
```
├── global_search.php           # Endpoint de búsqueda
├── get_notifications.php       # Endpoint de notificaciones
└── components/
    └── enhanced_header.php     # Header mejorado
```

---

## 🚀 Cómo Implementar en tus Páginas

### Opción 1: Usar el Header Mejorado (Recomendado)

Reemplaza tu header actual con:

```php
<?php
require 'auth.php';
require 'db.php';

// Fetch Settings
$stmt_settings = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt_settings->fetch();
$company_name = $settings['company_name'] ?? 'Sistema de Préstamos';
$logo_path = $settings['logo_path'] ?? '';
$user_role = $_SESSION['role'] ?? 'admin';

// Include enhanced header
require 'components/enhanced_header.php';
?>

<!-- Tu contenido aquí -->
```

### Opción 2: Agregar Manualmente

Agrega en el `<head>`:
```html
<link rel="stylesheet" href="assets/css/themes.css?v=1.0">
```

Agrega antes del `</body>`:
```html
<script src="assets/js/theme-manager.js?v=1.0"></script>
<script src="assets/js/notifications.js?v=1.0"></script>
<script src="assets/js/global-search.js?v=1.0"></script>
```

---

## ⚙️ Configuración

### Variables CSS Personalizables

En `assets/css/themes.css` puedes modificar:

```css
:root {
    --bg-primary: #ffffff;
    --bg-secondary: #f8fafc;
    --text-primary: #1e293b;
    --accent-primary: #3b82f6;
    /* ... más variables */
}
```

### Frecuencia de Notificaciones

En `assets/js/notifications.js` línea ~80:
```javascript
// Cambiar 30000 (30 segundos) a tu preferencia
setInterval(() => this.loadNotifications(), 30000);
```

---

## 🎯 Atajos de Teclado

| Atajo | Acción |
|-------|--------|
| `Ctrl + K` | Abrir búsqueda global |
| `ESC` | Cerrar búsqueda/modales |

---

## 📱 Responsive Design

Todas las funcionalidades son **completamente responsive**:
- ✅ Móviles (< 768px)
- ✅ Tablets (768px - 1024px)
- ✅ Desktop (> 1024px)

---

## 🎨 Personalización de Temas

### Agregar Nuevo Color

1. En `assets/css/themes.css`:
```css
[data-color="tu-color"] {
    --accent-primary: #TU_COLOR;
    --accent-secondary: #TU_COLOR_OSCURO;
    --accent-light: #TU_COLOR_CLARO;
    --accent-lighter: #TU_COLOR_MUY_CLARO;
}

.color-option.tu-color { background: #TU_COLOR; }
```

2. En `components/enhanced_header.php`:
```html
<div class="color-option tu-color" 
     onclick="window.themeManager?.setColorTheme('tu-color')" 
     data-color="tu-color"></div>
```

---

## 🔔 Tipos de Notificaciones

### En el código:
```javascript
// Success
window.notificationManager.show('Operación exitosa', 'success');

// Error
window.notificationManager.show('Ocurrió un error', 'error');

// Warning
window.notificationManager.show('Advertencia importante', 'warning');

// Info
window.notificationManager.show('Información', 'info');

// Sin auto-cerrar (duration = 0)
window.notificationManager.show('Mensaje permanente', 'info', 0);
```

---

## 🐛 Troubleshooting

### Las notificaciones no aparecen
- Verifica que `get_notifications.php` sea accesible
- Revisa la consola del navegador (F12)
- Asegúrate de que la tabla `payments` tenga datos

### El tema no se guarda
- Verifica que localStorage esté habilitado
- Limpia la caché del navegador
- Revisa permisos de cookies

### La búsqueda no funciona
- Verifica que `global_search.php` exista
- Revisa la conexión a la base de datos
- Asegúrate de tener datos en `clients` y `loans`

---

## 📊 Próximas Mejoras Sugeridas

- [ ] PWA (Progressive Web App)
- [ ] Notificaciones push del navegador
- [ ] Búsqueda con filtros avanzados
- [ ] Historial de búsquedas
- [ ] Temas personalizados por usuario
- [ ] Atajos de teclado personalizables
- [ ] Modo de alto contraste (accesibilidad)

---

## 💡 Tips de Uso

1. **Modo Oscuro**: Ideal para trabajar de noche o en ambientes con poca luz
2. **Búsqueda Rápida**: Usa Ctrl+K constantemente para navegar más rápido
3. **Notificaciones**: Revisa el panel cada mañana para ver pagos del día
4. **Temas de Color**: Elige el que mejor se adapte a tu marca

---

## 🆘 Soporte

Si tienes problemas:
1. Revisa la consola del navegador (F12)
2. Verifica que todos los archivos estén en su lugar
3. Asegúrate de tener la última versión de los archivos

---

¡Disfruta de las nuevas funcionalidades! 🎉
