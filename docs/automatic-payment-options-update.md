# Sistema de Actualización Automática de Opciones de Pago

## Descripción

Este sistema actualiza automáticamente las opciones de pago y cuotas disponibles para los programas según el tiempo transcurrido hasta la fecha final de pago.

## Componentes

### 1. Servicio Principal (`UpdatePaymentOptionsService`)

- **Ubicación**: `app/Services/Commands/UpdatePaymentOptionsService.php`
- **Función**: Actualiza todas las opciones de pago de todos los programas activos
- **Lógica**: 
  - Calcula meses disponibles hasta la fecha final de pago
  - Filtra opciones de pago válidas según los meses disponibles
  - Deshabilita opciones que ya no son válidas
  - Actualiza el número máximo de cuotas permitidas

### 2. Comando de Consola

- **Ubicación**: `routes/console.php`
- **Comando**: `php artisan payment-options:update`
- **Función**: Ejecuta la actualización automática de opciones de pago

### 3. Programación Automática

- **Ubicación**: `app/Console/Kernel.php`
- **Frecuencia**: Diaria a las 6:00 AM
- **Comando**: `payment-options:update`

## Cómo Funciona

### Actualización Automática Diaria

1. **Cada día a las 6:00 AM** se ejecuta automáticamente el comando
2. **Se procesan todos los programas activos** con fecha de salida futura
3. **Se calculan los meses disponibles** hasta la fecha final de pago
4. **Se filtran las opciones de pago** según los meses disponibles:
   - Opciones sin cuotas (transferencia, pago internacional) siempre están disponibles
   - Opciones con cuotas solo están disponibles si no exceden los meses disponibles
5. **Se actualiza el número máximo de cuotas** permitidas para pago mensual

### Ejemplo de Funcionamiento

**Programa con fecha de salida: 15 de Diciembre 2025**
- **Hoy**: 15 de Octubre 2025
- **Meses disponibles**: 2 meses
- **Opciones disponibles**:
  - ✅ Transferencia (Khipu) - siempre disponible
  - ✅ Débito/Crédito sin cuotas - siempre disponible
  - ✅ Hasta 3 cuotas - disponible (≤ 2 meses)
  - ❌ Hasta 6 cuotas - no disponible (> 2 meses)
  - ❌ Hasta 9 cuotas - no disponible (> 2 meses)
  - ❌ Hasta 12 cuotas - no disponible (> 2 meses)

## Uso Manual

### Ejecutar Actualización Manual

```bash
# Actualización normal
php artisan payment-options:update

# Ver estadísticas sin hacer cambios
php artisan payment-options:update --dry-run
```

### Ver Logs

```bash
# Ver logs de la actualización automática
tail -f storage/logs/payment-options-update.log
```

## Configuración

### Cambiar Frecuencia de Ejecución

En `app/Console/Kernel.php`:

```php
// Actualizar opciones de pago y cuotas automáticamente cada día a las 6:00 AM
$schedule->command('payment-options:update')
    ->dailyAt('06:00')  // Cambiar a: hourly(), everyMinute(), etc.
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/payment-options-update.log'));
```

### Cambiar Hora de Ejecución

```php
->dailyAt('08:00')  // Cambiar a cualquier hora
->twiceDaily(6, 18) // Dos veces al día
->everyFourHours()   // Cada 4 horas
```

## Monitoreo

### Verificar Estado del Sistema

```bash
# Verificar que el comando funciona
php artisan payment-options:update

# Ver logs del sistema
tail -f storage/logs/laravel.log | grep "payment-options"
```

### Logs Importantes

- **Actualización exitosa**: `Opciones de pago actualizadas automáticamente`
- **Sin cambios necesarios**: `El programa no requiere actualización`
- **Errores**: `Error actualizando opciones de pago del programa`

## Ventajas del Sistema

1. **Automático**: No requiere intervención manual
2. **Eficiente**: Solo actualiza programas que lo necesitan
3. **Seguro**: No interrumpe el funcionamiento normal del sistema
4. **Auditable**: Registra todas las acciones en logs
5. **Flexible**: Se puede ejecutar manualmente cuando sea necesario

## Solución de Problemas

### Si no se ejecuta automáticamente

1. Verificar que el cron esté configurado:
   ```bash
   crontab -l
   # Debe incluir: * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
   ```

2. Verificar logs del sistema:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. Ejecutar manualmente para probar:
   ```bash
   php artisan payment-options:update
   ```

### Si hay errores

1. Verificar permisos de base de datos
2. Verificar que las tablas existan
3. Revisar logs detallados en `storage/logs/payment-options-update.log`
