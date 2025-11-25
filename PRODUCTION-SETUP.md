# 🚀 Configuración de Producción para cPanel Linux

## ⚡ SOLUCIÓN RÁPIDA AL ERROR 419

El error 419 (CSRF Token Mismatch) ocurre porque las sesiones y cookies no se están creando en producción.

### 🎯 Cambios Críticos en el .env

```bash
# 1. Cambiar el driver de sesión (MÁS IMPORTANTE)
SESSION_DRIVER=file

# 2. Configurar el dominio (reemplaza con tu dominio real)
SESSION_DOMAIN=.tudominio.com

# 3. Habilitar cookies seguras (solo si usas HTTPS)
SESSION_SECURE_COOKIE=true

# 4. Agregar dominios confiables para Sanctum
SANCTUM_STATEFUL_DOMAINS=tudominio.com,www.tudominio.com

# 5. Configurar la URL de la aplicación
APP_URL=https://tudominio.com
```

### 📋 Pasos de Implementación

#### 1. Editar .env en el servidor

Accede a cPanel → File Manager → Edita el archivo `.env` en la raíz de tu aplicación.

#### 2. Verificar permisos

```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
mkdir -p storage/framework/sessions
chmod 775 storage/framework/sessions
```

#### 3. Limpiar caché

```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
```

#### 4. Probar

- Abre el sitio en modo incógnito
- Abre DevTools (F12) → Application → Cookies
- Verifica que existe la cookie `laravel_session`

### 🔍 Diagnóstico Rápido

Crea un archivo `public/test.php`:

```php
<?php
session_start();
if (!isset($_SESSION['test'])) $_SESSION['test'] = 0;
$_SESSION['test']++;
echo "Sesiones funcionando: " . $_SESSION['test'];
?>
```

Accede a `https://tudominio.com/test.php` y refresca varias veces.
Si el número aumenta, las sesiones funcionan. **Elimina el archivo después.**

### ⚠️ Problemas Comunes

| Problema | Solución |
|----------|----------|
| Error 500 después de cambios | Ejecuta `php artisan config:clear` |
| Cookie no se crea | Verifica `SESSION_DOMAIN` (debe empezar con `.`) |
| Error persiste en HTTPS | Cambia `SESSION_SECURE_COOKIE=true` |
| Error persiste en HTTP | Cambia `SESSION_SECURE_COOKIE=false` |
| Permiso denegado | Ejecuta `chmod -R 775 storage/` |

### 📊 Valores por Ambiente

| Variable | Desarrollo | Producción HTTP | Producción HTTPS |
|----------|-----------|-----------------|------------------|
| `SESSION_DRIVER` | database | **file** | **file** |
| `SESSION_DOMAIN` | null | **.tudominio.com** | **.tudominio.com** |
| `SESSION_SECURE_COOKIE` | false | **false** | **true** |
| `APP_DEBUG` | true | **false** | **false** |
| `APP_ENV` | local | **production** | **production** |

### 🎯 Checklist Final

- [ ] Edité el .env con los valores correctos
- [ ] Cambié `SESSION_DRIVER` a `file`
- [ ] Configuré `SESSION_DOMAIN` con mi dominio (con punto al inicio)
- [ ] Configuré `SESSION_SECURE_COOKIE` según mi protocolo (HTTP/HTTPS)
- [ ] Agregué `SANCTUM_STATEFUL_DOMAINS`
- [ ] Verifiqué permisos de `storage/` (775)
- [ ] Ejecuté `php artisan config:cache`
- [ ] Probé el login y funciona

### 📞 Si Aún No Funciona

Verifica estos archivos:

1. **app/Http/Kernel.php** - El middleware `StartSession` debe estar ANTES de `VerifyCsrfToken`
2. **config/session.php** - Debe leer correctamente las variables del .env
3. **app/Http/Middleware/TrustProxies.php** - Debe tener `protected $proxies = '*';`

### 🔧 Archivos Modificados

- `config/cors.php` - Cambiado `supports_credentials` a `true`
- `.env.production` - Plantilla con la configuración correcta

---

**Última actualización:** 2025-11-21
