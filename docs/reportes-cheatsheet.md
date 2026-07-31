# Reportes financieros — cheatsheet para debugging

Guía para el próximo dev que reciba un ticket "el reporte X no coincide con Y".
Cubre Estado de Cuenta Parcial, Consolidado, Softland (movimientos y auxiliares)
y admin/courses. Fecha última revisión: 2026-07.

---

## 1. Fuente única de verdad

**Siempre que necesites "cuánto pagó" un participante en un programa, usá:**

```php
\App\Services\Admin\ParticipantFinancialService::calculate($participantId, $programCourseId)
```

Devuelve `total_paid`, `abono`, `aporte`, `credito_temporal`, `nota_credito`,
`reverso_admin`, `net_amount`, `pending_amount`, `progress_percentage`, etc.

Este servicio suma directo desde `payments` con status `approved`/`completed`,
agrupado por `payment_options.report_code`. **NO** depende de `installment_plan.status`
ni de `installments.status`. Por eso es robusto ante cancelaciones de PAT.

> Regla: si tu reporte re-implementa el cálculo de "cuánto pagó" con queries
> propias, revisá que no caiga en los bugs de abajo antes de commitear.

---

## 2. Los 3 bugs recurrentes de reportería

### Bug A — "Cuotas PAT desaparecen al cancelar la suscripción"

**Síntoma:** el abono del reporte muestra menos que la realidad. La pantalla y
el Consolidado dicen $601.111, el export dice $116.077. Diferencia = suma de
cuotas PAT ya cobradas.

**Causa:** el reporte suma cuotas PAT desde
`installment_plan->installments()->where('status','paid')->sum('amount')`. Cuando
el usuario cancela la PAT, el `installment_plan` queda `cancelled` (nuestro fix
`CancelSubscriptionService` + `SubscriptionRecalculationService` lo cancela
correctamente) y el reporte pierde de vista esas cuotas.

**Fix:** sumar desde `payments` con `payment_source='subscription'`:

```php
$subscriptionPayments = (float) Payment::whereIn('order_id', $orderIds)
    ->whereIn('status', ['approved', 'completed'])
    ->where('payment_source', 'subscription')
    ->sum('amount');
```

**Aplicado en:**
- `CourseDataService::calculatePaidAmount` (commit `81cbd70`)
- `ExportService::generatePartialAccountXlsx` (commit `2231851`)

**No afectados (verificado):**
- `GetSubscriptionsService:50` — busca el plan sin filtrar por status, así que
  el plan cancelado con cuotas `paid` intactas sigue contando bien.
- Métodos que usan `->count()` para "Cuotas Pagadas" (columna del reporte) —
  cuentan, no suman dinero.

---

### Bug B — "Pagos de importación masiva no aparecen"

**Síntoma:** un pago que se ve en admin/payments no está en el reporte.

**Causa:** el pago quedó con `status='approved'` (el masivo lo carga así), pero
el reporte filtra solo `status='completed'`.

**Fix:** cambiar todos los `where('status','completed')` por
`whereIn('status',['approved','completed'])` en las queries de reportes.

**Aplicado en:**
- `SoftlandB2DataService`, `SoftlandDataService`, `SoftlandAuxiliaresService` (commit `55d520c`)
- Cambios adicionales en cada reporte según su query específica.

**Cuándo pasa:** cualquier reporte nuevo o cualquier flujo que crea payments.

---

### Bug C — "Falta AC (anticipos años futuros) en el reporte"

**Síntoma:** clientes que solo pagaron anticipos no aparecen en algún reporte.

**Causa:** el filtro es `where('document_type','B2')` en vez de
`whereIn('document_type',['B2','AC'])`.

**Fix:** siempre incluir ambos tipos en filtros de reportes financieros.

**Aplicado en:**
- `SoftlandAuxiliaresService` (commit `1f4171d`) — 2 filtros.

**Regla:** salvo que el reporte sea explícitamente "solo boletas" o "solo
anticipos", los filtros deben incluir `['B2','AC']`.

---

## 3. Bug de datos — cancelación de PAT que no cancela installment_plan

**Síntoma:** después de cancelar una PAT, el participante no puede pagar con
otras modalidades en el checkout (solo ve la opción PAT).

**Causa:** `ProgramDetailService.php:164-183` marca `paymentPlanLocked=true` si
encuentra `installment_plan.status='active'` con cuotas pagadas. Si algún flujo
de cancelación no cancela el installment_plan, el checkout queda bloqueado.

**Fix:** cualquier lugar donde se ponga `program_subscriptions.status='CANCELADA'`
debe también cancelar el installment_plan y sus cuotas pendientes.

**Aplicado en:**
- `CancelSubscriptionService` (ya lo hacía)
- `SubscriptionRecalculationService::cancelSubscription` (commit `c106deb`)

**Cómo detectar otros flujos:**

```bash
grep -rn "'status' => 'CANCELADA'" app --include="*.php"
```

Cada resultado debe ir acompañado de un bloque como:

```php
$installmentPlan = \App\Models\InstallmentPlan::where('participant_id', $subscription->participant_id)
    ->where('program_id', $subscription->program_id)
    ->first();
if ($installmentPlan) {
    $installmentPlan->update(['status' => 'cancelled']);
    $installmentPlan->installments()
        ->where('status', 'pending')
        ->update(['status' => 'cancelled']);
}
```

**SQL para arreglar datos ya afectados en prod:**

```sql
UPDATE installment_plans ip
SET ip.status = 'cancelled', ip.updated_at = NOW()
WHERE ip.status = 'active'
  AND EXISTS (
    SELECT 1 FROM program_subscriptions ps
    WHERE ps.participant_id = ip.participant_id
      AND ps.program_id = ip.program_id
      AND ps.status = 'CANCELADA'
  );

UPDATE installments i
JOIN installment_plans ip ON ip.id = i.installment_plan_id
SET i.status = 'cancelled'
WHERE ip.status = 'cancelled' AND i.status = 'pending';
```

---

## 4. Softland — reglas contables

### Familia de cuentas por método de pago

```
VIRTUAL   (VP, KP, PAT, VPI) → 1-1-02-014
TRANSBANK (TC, WP)           → 1-1-02-009
BANCO     (TE, DP)           → 1-1-01-039
AC (anticipos, HABER)        → 2-1-04-051
```

Ver `SoftlandDataService::getAccountCodeByPaymentMethod()`.

### Regla especial: `presential_debit_credit`

Tiene `report_code = VP` pero **no** debe ir en `$officeCodes`. Se procesa por
VirtualPos → cuenta `1-1-02-014` (no TRANSBANK). Ver `generateMovements()`.

### Regla especial: PAT y VPI → VP en el reporte

`getPaymentMethodCode()` mapea PAT y VPI a VP siempre. Contabilidad no
distingue subvariantes.

### N° de Documento según medio de pago (Carmen 2026-07-17)

`getAccountingDocumentNumber()`:
- **VIRTUAL** → primeros 8 chars del `uuid` del gateway response
- **TRANSBANK** → `authorization_code`
- **BANCO** → fecha del pago en formato `DDMMAA` (6 dígitos)

### Glosa AC (HABER)

Formato: `{programCode}/{nombre_completo_participante}/{forma_pago}`.
Usar `$programCourse->code` directamente, **no** `Program::find(...)` — el
Program padre a veces no tiene `code` y sale "SIN-CODIGO".

Ver `formatACCreditDescription()`.

### Agrupación de boletas B2

`SoftlandB2DataService` agrupa por `bsale_number` antes de emitir asientos.
Cuando una boleta cubre varios participantes (aportes compartidos), se emite
un solo asiento por boleta con el monto sumado. Ver commit `3647dac`.

---

## 5. Estado de Cuenta Parcial — reglas específicas

### Detección de PAT

Se considera que un participante paga por PAT si:
1. Hay `program_subscriptions.status='ACTIVA'` con `installment_plan` vinculado, o
2. Hay `installment_plan` con `status != 'cancelled'` para su `order_id`.

Ver `createInstallmentDebitMovement`, `ExecutivesPartialAccountService`,
`ExportService::generatePartialAccountXlsx`.

### Cuotas pagadas mostradas

Regla: si NO hay ningún `payment` con `status IN (approved,completed) AND amount>0`,
mostrar `0/0` sin importar planes que existan. Un `installment_plan.status='active'`
sin cuotas cobradas es un intento fallido de PAT — no cuenta.

Ver commit `1317bbc`, `c7d5373`.

### Participantes de baja

- Con devolución parcial: precio y abono se muestran = monto retenido (todos
  los payments confirmados sumados; NC/RA con monto negativo restan
  automáticamente). Saldo = 0.
- Con devolución total: retenido = 0, todo en 0. Saldo = 0.

Ver commit `cdac238`.

### Saldo

`saldo = (abono + scholarship + aporte + released) - price`

- Positivo = excedente a favor del pagador (color azul)
- Negativo = deuda (color rojo, entre paréntesis)

---

## 6. Antes de deployar

Cuando toques cualquier reporte financiero, corré este checklist:

- [ ] ¿Filtrás por `status IN (approved,completed)` y no solo `completed`?
- [ ] ¿Incluís `document_type IN (B2,AC)` y no solo `B2`?
- [ ] Si sumás cuotas PAT: ¿desde `payments.payment_source='subscription'` o desde `installments.status='paid'`? (usá el primero salvo justificación explícita)
- [ ] ¿El total de pantalla coincide con el total del export para el mismo participante en el mismo período?
- [ ] ¿El Consolidado muestra las mismas líneas que el Estado de Cuenta Parcial cuenta como abono?

---

## 7. Historial de commits relevantes (más reciente primero)

```
2231851  Fix export Estado Cta Parcial: cuotas PAT desde payments, no installments
c106deb  Cancel PAT vía recálculo: también cancela installment_plan
1f4171d  Softland Auxiliares: incluir pagos AC (anticipos años futuros)
d701d21  Softland: pagos Link TD/TC (VP) van a VIRTUAL POS, no a TRANSBANK
90f6d48  Softland DEBE AC: cuenta/tipo/nro doc según medio de pago
3bb39c9  Softland HABER AC cuotas PAT: usar ProgramCourse->code
35eae75  Softland HABER AC: glosa con nombre participante + tipo doc AC
bd5bbf7  Softland pagos offline banco: cuenta 1-1-01-039, nro doc DDMMYY
db5549a  Softland AC: tipo de documento siempre via getPaymentMethodCode
1facaec  Softland AC: N° de documento usa transaction_id (uuid)
07819d0  Softland AC: cuenta correcta 2-1-04-051 y glosa sin RUT
5f38aaa  Softland: código plan de cuentas por método de pago + N° auth en AC
d1aa509  Reportes ejecutivos: liberado por %, excedente en Consolidated
7181ebb  Fixes PAT: total_amount inflado, InstallmentPlan.programCourse
3647dac  Excel masivo captura Tipo Doc + RUT del pagador; Softland B2 agrupa por boleta
a4bf8ea  Fix Estado Cuenta Parcial pantalla: incluir CT/NC/RA en columna Abono
a0c63f4  Fix Softland masivo: RUT cliente y fecha de emisión
55d520c  Fix Softland: incluir pagos status='approved' (importación masiva)
81cbd70  Fix admin/courses recaudado: sumar PAT desde payments, no installments
1317bbc  Fix Estado Cuenta Parcial: criterio principal es 'pago exitoso'
1d4790e  Fix Estado Cuenta Parcial Excel: abono no contabilizaba cuotas PAT pagadas
411ebd4  Corrige cálculo de monto recaudado en reporte Programas por Medio de Pago
```

---

## 8. Archivos clave

| Archivo | Rol |
|---|---|
| `app/Services/Admin/ParticipantFinancialService.php` | Fuente única de verdad para "cuánto pagó" |
| `app/Services/Admin/Reports/Executives/ExportService.php` | Export Excel Estado de Cuenta Parcial + Consolidado |
| `app/Services/Admin/Reports/Executives/ExecutivesPartialAccountService.php` | Estado de Cuenta Parcial en pantalla |
| `app/Services/Admin/Reports/Executives/ExecutivesConsolidatedService.php` | Consolidado de Pagos |
| `app/Services/Admin/Reports/Softland/SoftlandDataService.php` | Movimientos contables Softland (B2 + AC) |
| `app/Services/Admin/Reports/Softland/SoftlandAuxiliaresService.php` | Archivo Auxiliares Softland (clientes) |
| `app/Services/Admin/Reports/Softland/SoftlandB2DataService.php` | Movimiento de boletas B2 agrupado |
| `app/Services/Admin/Courses/CourseDataService.php` | Tabla admin/courses (recaudado/total) |
| `app/Services/VirtualPos/CancelSubscriptionService.php` | Cancelación PAT (ya cancela installment_plan) |
| `app/Services/Subscription/SubscriptionRecalculationService.php` | Cancelación por recálculo (fix c106deb) |
