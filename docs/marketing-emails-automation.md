# Automatización de Emails de Marketing

## Descripción

Este sistema automatiza la recolección y gestión de emails de marketing desde las tablas `orders_detail` y `frequent_client` que tengan `marketing_accepted = true`.

## Componentes del Sistema

### 1. Tabla `marketing_mails`
- **email**: Email único del usuario (case-insensitive)
- **is_active**: Estado del email (true por defecto)
- **timestamps**: Fechas de creación y actualización

### 2. Servicio `MarketingMailsService`
- Detecta emails con `marketing_accepted = true`
- Almacena emails únicos en la tabla `marketing_mails`
- Evita duplicados usando comparación case-insensitive
- Procesa emails desde `orders_detail` y `frequent_client`

### 3. Comando de Consola `marketing:process-emails`
- Se ejecuta automáticamente 2 veces al día
- Procesa todos los emails pendientes
- Muestra estadísticas detalladas del proceso

### 4. Panel Administrativo
- Vista completa de todos los emails de marketing
- Estadísticas en tiempo real
- Gestión de estados (activar/desactivar)
- Búsqueda y filtros
- Procesamiento manual de emails

## Configuración del Cron Job

### Opción 1: Cron del Sistema (Recomendado)

Agregar al crontab del servidor:

```bash
# Editar crontab
crontab -e

# Agregar estas líneas:
# Ejecutar a las 6:00 AM y 6:00 PM todos los días
0 6,18 * * * cd /path/to/laravel && php artisan marketing:process-emails >> /dev/null 2>&1
```

### Opción 2: Cron de Laravel

En `app/Console/Kernel.php`, agregar:

```php
protected function schedule(Schedule $schedule): void
{
    // Procesar emails de marketing 2 veces al día
    $schedule->command('marketing:process-emails')
             ->twiceDaily(6, 18) // 6:00 AM y 6:00 PM
             ->appendOutputTo(storage_path('logs/marketing-emails.log'));
}
```

Luego configurar el cron del sistema para ejecutar Laravel:

```bash
* * * * * cd /path/to/laravel && php artisan schedule:run >> /dev/null 2>&1
```

## Uso del Comando

### Ejecución Manual

```bash
# Procesar emails de marketing
php artisan marketing:process-emails

# Ver ayuda del comando
php artisan marketing:process-emails --help
```

### Ejecución Programada

El comando se ejecuta automáticamente y:
1. Detecta emails con `marketing_accepted = true`
2. Los almacena en `marketing_mails` si no existen
3. Reactiva emails previamente desactivados
4. Evita duplicados usando comparación case-insensitive
5. Registra todo el proceso en logs

## Rutas del Panel Administrativo

- **GET** `/admin/marketing/emails` - Lista de emails
- **PATCH** `/admin/marketing/emails/{id}/toggle-status` - Cambiar estado
- **DELETE** `/admin/marketing/emails/{id}` - Eliminar email
- **POST** `/admin/marketing/emails/process` - Procesar emails manualmente
- **GET** `/admin/marketing/emails/stats` - Obtener estadísticas

## Características de Seguridad

- **Validación de emails**: Solo se almacenan emails válidos
- **Case-insensitive**: Evita duplicados como "user@email.com" y "USER@EMAIL.COM"
- **Transacciones**: Uso de transacciones de base de datos para consistencia
- **Logging**: Registro completo de todas las operaciones
- **Middleware de autenticación**: Solo usuarios autenticados pueden acceder

## Monitoreo y Logs

### Logs del Sistema
- Todas las operaciones se registran en `storage/logs/laravel.log`
- Incluye estadísticas de procesamiento
- Registra errores y excepciones

### Estadísticas Disponibles
- Total de emails en la tabla
- Emails activos vs inactivos
- Total de orders con marketing aceptado
- Total de clientes frecuentes con marketing aceptado

## Mantenimiento

### Limpieza de Emails
- Los emails se pueden desactivar desde el panel administrativo
- Los emails desactivados se pueden reactivar automáticamente si vuelven a aparecer
- Eliminación permanente disponible para administradores

### Optimización
- Índices en campos `email` e `is_active`
- Paginación en la vista administrativa
- Búsqueda con debounce para mejor rendimiento

## Troubleshooting

### Problemas Comunes

1. **Comando no se ejecuta**
   - Verificar configuración del cron
   - Revisar permisos del usuario del cron
   - Verificar logs de Laravel

2. **Emails duplicados**
   - Verificar que la migración se ejecutó correctamente
   - Revisar constraint UNIQUE en la tabla
   - Verificar lógica del servicio

3. **Errores de base de datos**
   - Verificar conexión a la base de datos
   - Revisar permisos de las tablas
   - Verificar estructura de las tablas relacionadas

### Comandos de Diagnóstico

```bash
# Verificar estado de la base de datos
php artisan tinker
>>> DB::connection()->getPdo();

# Verificar emails en la tabla
php artisan tinker
>>> App\Models\MarketingMail::count();

# Verificar emails activos
php artisan tinker
>>> App\Models\MarketingMail::active()->count();
```
