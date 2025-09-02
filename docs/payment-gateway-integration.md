# Integración de Pasarelas de Pago

## Sistema de Detección de Pasarelas

El sistema utiliza un parámetro explícito `gateway` en las URLs de retorno para identificar de manera confiable el tipo de pasarela.

### URLs de Retorno Estándar

#### Transbank
```
/payment/callback/{orderDetailId}?gateway=transbank&token_ws=abc123
```

#### Khipu
```
/payment/callback/{orderDetailId}?gateway=khipu&payment_id=khipu_456
```

### Prioridad de Detección

1. **Parámetro explícito `gateway`** (más confiable)
   - `gateway=transbank` → Transbank
   - `gateway=khipu` → Khipu

2. **Parámetros específicos** (fallback)
   - `token_ws` → Transbank
   - `payment_id` → Khipu

3. **Análisis de URL** (legacy)
   - URL contiene "khipu" → Khipu
   - URL contiene "transbank" o "webpay" → Transbank

### Configuración en los Servicios

#### TransbankService
```php
// En ProcessPaymentController
$transbankCallbackUrl = route('payment.callback', [
    'orderDetailId' => $orderDetail->id, 
    'gateway' => 'transbank'
]);
```

#### KhipuService
```php
// En ProcessPaymentController
$khipuCallbackUrl = route('payment.callback', [
    'orderDetailId' => $orderDetail->id, 
    'gateway' => 'khipu'
]);
```

### Compatibilidad Legacy

El sistema mantiene compatibilidad con URLs antiguas:

- `/khipu/callback/{orderDetailId}` → redirige a `/payment/callback/{orderDetailId}?gateway=khipu`
- URLs sin parámetro `gateway` → detecta por parámetros específicos

### Ventajas del Sistema

1. **Claridad**: El parámetro `gateway` es explícito y no ambiguo
2. **Robustez**: Múltiples métodos de detección como fallback
3. **Compatibilidad**: Mantiene URLs legacy funcionando
4. **Escalabilidad**: Fácil agregar nuevas pasarelas
5. **Debugging**: Logs claros del tipo de pasarela detectada

### Ejemplos de URLs Completas

#### Transbank (Éxito)
```
https://tuapp.com/payment/callback/123?gateway=transbank&token_ws=abc123def456
```

#### Khipu (Éxito)
```
https://tuapp.com/payment/callback/123?gateway=khipu&payment_id=khipu_789xyz
```

#### Transbank (Legacy - aún funciona)
```
https://tuapp.com/payment/callback/123?token_ws=abc123def456
```

#### Khipu (Legacy - aún funciona)
```
https://tuapp.com/khipu/callback/123?payment_id=khipu_789xyz
```
