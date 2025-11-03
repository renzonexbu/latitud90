# Módulo de Cuotas LAT90 (Installments Module)

## 📋 Descripción

Módulo independiente y desacoplado para gestionar el sistema de cuotas mensuales (mensualidades) de Latitud90.

Este módulo encapsula toda la lógica relacionada con:
- Creación de planes de cuotas
- Gestión de pagos mensuales
- Reestructuración de planes
- Cálculo y distribución de montos
- Generación de fechas de vencimiento

## 🎯 Características Principales

- ✅ **Completamente desacoplado** - Usa interfaces y repositorios
- ✅ **Fácilmente activable/desactivable** - Un solo switch en `.env`
- ✅ **Compatible con código legacy** - Convive con el sistema antiguo
- ✅ **Testeable** - Interfaces permiten mocking en tests
- ✅ **Extensible** - Fácil agregar nuevas funcionalidades
- ✅ **Documentado** - Código auto-documentado con tipos e interfaces

## 🚀 Instalación

El módulo ya está registrado en `config/app.php`. Solo necesitas configurar el archivo `.env`:

```env
# Sistema de cuotas habilitado
INSTALLMENTS_ENABLED=true

# Configuración de cuotas
INSTALLMENTS_MAX=24
INSTALLMENTS_DEFAULT=12
INSTALLMENTS_MIN_AMOUNT=50000

# Configuración de fechas
INSTALLMENTS_DUE_DAY=5
INSTALLMENTS_FIRST_OFFSET=30
INSTALLMENTS_USE_PROGRAM_DATE=true

# Sistema legacy
INSTALLMENTS_USE_LEGACY=true
INSTALLMENTS_SYNC_LEGACY=true

# Comandos programados
INSTALLMENTS_ENABLE_OVERDUE_CHECKER=true
INSTALLMENTS_ENABLE_PAYMENT_PROCESSOR=true
```

## 📖 Uso

### Usando el Facade (Recomendado)

```php
use Installments;

// Verificar si está habilitado
if (Installments::isEnabled()) {
    // Crear plan de cuotas
    $plan = Installments::createOrGetPlan(
        $programId,
        $rut,
        $paymentData,
        $formData
    );

    // Obtener siguiente cuota pendiente
    $nextInstallment = Installments::getNextPendingInstallment($plan->id);

    // Marcar como pagada
    Installments::markAsPaid($installmentId, [
        'order_id' => $orderId,
        'order_detail_id' => $orderDetailId,
        'payment_id' => $paymentId
    ]);

    // Reestructurar plan
    Installments::restructurePlan($planId, $newInstallments, $reason);

    // Recalcular después de descuento
    Installments::recalculateAfterDiscount($planId);
}
```

### Usando Inyección de Dependencias

```php
use App\Modules\Installments\Contracts\InstallmentServiceInterface;

class YourController extends Controller
{
    public function __construct(
        private InstallmentServiceInterface $installments
    ) {}

    public function someMethod()
    {
        if ($this->installments->isEnabled()) {
            $plan = $this->installments->createPlan([
                'program_id' => $programId,
                'participant_id' => $participantId,
                'total_amount' => $amount,
                'installments' => $count,
                'program' => $program
            ]);
        }
    }
}
```

### Usando el Calculator

```php
use App\Modules\Installments\Contracts\InstallmentCalculatorInterface;

class YourService
{
    public function __construct(
        private InstallmentCalculatorInterface $calculator
    ) {}

    public function calculate()
    {
        // Dividir monto en cuotas
        $amounts = $this->calculator->splitAmount(100000, 10);
        // Resultado: [10000, 10000, 10000, ...]

        // Generar fechas de vencimiento
        $dates = $this->calculator->generateMonthlyDueDates(
            Carbon::now(),
            10,
            5 // Día 5 de cada mes
        );
    }
}
```

## 🔧 Configuración

### Archivo de configuración

El archivo `app/Modules/Installments/Config/installments.php` contiene todas las opciones:

| Opción | Tipo | Por defecto | Descripción |
|--------|------|-------------|-------------|
| `enabled` | bool | `true` | Habilitar/deshabilitar sistema completo |
| `max_installments` | int | `24` | Máximo de cuotas permitidas |
| `default_installments` | int | `12` | Cuotas por defecto |
| `min_amount_for_installments` | int | `50000` | Monto mínimo para habilitar cuotas |
| `due_day_of_month` | int | `5` | Día del mes para vencimiento |
| `first_installment_days_offset` | int | `30` | Días para primera cuota |
| `use_program_final_date` | bool | `true` | Usar fecha final del programa |
| `use_legacy_system` | bool | `true` | Mantener sistema de orders_detail |
| `sync_with_legacy` | bool | `true` | Sincronizar con sistema legacy |
| `enable_overdue_checker` | bool | `true` | Habilitar comando de vencidas |
| `enable_payment_processor` | bool | `true` | Habilitar procesador de pagos |
| `max_installments_on_restructure` | int | `24` | Máximo al reestructurar |
| `allow_reduce_installments` | bool | `false` | Permitir reducir cuotas |
| `enable_events` | bool | `true` | Habilitar eventos |
| `enable_overdue_notifications` | bool | `false` | Notificar cuotas vencidas |
| `allow_multiple_active_plans` | bool | `false` | Múltiples planes por participante |
| `grace_days` | int | `0` | Días de gracia antes de marcar vencida |
| `rounding_mode` | string\|null | `null` | Modo de redondeo: up, down, nearest |

## 📁 Estructura del Módulo

```
app/Modules/Installments/
├── Config/
│   └── installments.php              # Configuración centralizada
├── Contracts/                         # Interfaces
│   ├── InstallmentServiceInterface.php
│   ├── InstallmentRepositoryInterface.php
│   ├── InstallmentPlanRepositoryInterface.php
│   └── InstallmentCalculatorInterface.php
├── Events/                            # Eventos del sistema
│   ├── InstallmentCreated.php
│   ├── InstallmentPaid.php
│   ├── InstallmentPlanCompleted.php
│   └── InstallmentPlanRestructured.php
├── Facades/                           # Facade para fácil acceso
│   └── Installments.php
├── Providers/                         # Service Provider
│   └── InstallmentServiceProvider.php
├── Repositories/                      # Capa de datos
│   ├── InstallmentRepository.php
│   └── InstallmentPlanRepository.php
├── Services/                          # Lógica de negocio
│   ├── InstallmentCalculator.php
│   ├── InstallmentManager.php
│   └── NullInstallmentManager.php
└── README.md                          # Esta documentación
```

## 🔀 Arquitectura

### Patrón Repository

Separa la lógica de acceso a datos de la lógica de negocio:

```
Controller/Service
    ↓
InstallmentServiceInterface (Contrato)
    ↓
InstallmentManager (Implementación)
    ↓
InstallmentRepositoryInterface (Contrato)
    ↓
InstallmentRepository (Implementación)
    ↓
Eloquent Model
```

### Null Object Pattern

Cuando `INSTALLMENTS_ENABLED=false`, el Service Provider registra `NullInstallmentManager` que implementa la misma interface pero retorna valores nulos/vacíos. Esto permite que el código funcione sin cambios.

## 🎛️ Cómo Desactivar el Sistema de Cuotas

### Opción 1: Desactivación Total (Recomendado)

En `.env`:
```env
INSTALLMENTS_ENABLED=false
```

Todo el sistema de cuotas se desactiva. El código sigue funcionando pero todas las operaciones de cuotas retornan null/false.

### Opción 2: Desactivación Gradual

Ir desactivando componentes uno por uno:

```env
# Desactivar comandos programados
INSTALLMENTS_ENABLE_OVERDUE_CHECKER=false
INSTALLMENTS_ENABLE_PAYMENT_PROCESSOR=false

# Desactivar eventos
INSTALLMENTS_ENABLE_EVENTS=false

# Desactivar sistema legacy
INSTALLMENTS_USE_LEGACY=false
INSTALLMENTS_SYNC_LEGACY=false
```

## 📊 Flujo de Trabajo

### 1. Crear Plan de Cuotas

```php
$plan = Installments::createOrGetPlan($programId, $rut, $paymentData, $formData);
// Crea InstallmentPlan + Installments individuales
// Si ya existe plan con cuotas pagadas, devuelve el existente
// Si existe plan sin cuotas pagadas pero diferente número, lo reemplaza
```

### 2. Procesar Pago

```php
$nextInstallment = Installments::getNextPendingInstallment($planId);
// Obtiene la siguiente cuota pendiente

Installments::markAsPaid($nextInstallment->id, [
    'order_id' => $order->id,
    'order_detail_id' => $orderDetail->id,
    'payment_id' => $payment->id
]);
// Marca cuota como pagada y verifica si el plan se completó
```

### 3. Reestructurar Plan

```php
Installments::restructurePlan($planId, 18, 'Solicitud del cliente');
// Valida que no se reduzca bajo cuotas pagadas
// Elimina cuotas pendientes
// Crea nuevas cuotas con montos recalculados
// Registra cambio en installment_restructure
```

### 4. Aplicar Descuento

```php
Installments::recalculateAfterDiscount($planId);
// Recalcula monto total con descuentos actualizados
// Mantiene cuotas pagadas intactas
// Redistribuye saldo pendiente en cuotas restantes
```

## 🧪 Testing

### Test Manual con Tinker

```bash
php artisan tinker

# Verificar estado
app('installments')->isEnabled()

# Calcular cuotas
app('installments')->calculateInstallments(100000, 10)

# Generar fechas
$program = \App\Models\Program::find(1);
app('installments')->generateDueDates($program, 10)
```

### Test Unitario (Ejemplo)

```php
use App\Modules\Installments\Contracts\InstallmentServiceInterface;
use Tests\TestCase;

class InstallmentTest extends TestCase
{
    public function test_can_create_installment_plan()
    {
        $service = app(InstallmentServiceInterface::class);

        $plan = $service->createPlan([
            'program_id' => 1,
            'participant_id' => 1,
            'total_amount' => 100000,
            'installments' => 10,
            // ...
        ]);

        $this->assertNotNull($plan);
        $this->assertEquals(10, $plan->total_installments);
    }
}
```

## 🔄 Migración desde Sistema Antiguo

El módulo está diseñado para convivir con el código antiguo:

1. **Fase 1**: Código nuevo y viejo funcionan en paralelo
2. **Fase 2**: Servicios migrados usan nuevo módulo con fallback al antiguo
3. **Fase 3**: Desactivar `INSTALLMENTS_USE_LEGACY=false` cuando esté listo
4. **Fase 4**: Eliminar código antiguo completamente

## 🐛 Troubleshooting

### El módulo no se carga

```bash
# Limpiar caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Verificar registro
php artisan tinker
app('installments') // Debe retornar InstallmentManager o NullInstallmentManager
```

### Errores de interface no encontrada

```bash
# Regenerar autoload
composer dump-autoload
```

### Las cuotas no se crean

1. Verificar `INSTALLMENTS_ENABLED=true` en `.env`
2. Revisar logs en `storage/logs/laravel.log`
3. Verificar que el programa tenga `enable_lat90_payment=true`

## 📚 Referencias

- Modelos: `app/Models/Installment.php`, `app/Models/InstallmentPlan.php`
- Servicios antiguos: `app/Services/Client/Payment/InstallmentService.php`
- Controlador: `app/Http/Controllers/Admin/InstallmentController.php`
- Migraciones: `database/migrations/*_create_installment*`

## 👥 Contribución

Para agregar nuevas funcionalidades:

1. Agregar método a `InstallmentServiceInterface`
2. Implementar en `InstallmentManager`
3. Implementar en `NullInstallmentManager` (retornar null/false)
4. Documentar el nuevo método
5. Agregar tests

## 📄 Licencia

Uso interno de Latitud90.

---

**Última actualización:** 2025-11-03
**Versión:** 1.0.0
