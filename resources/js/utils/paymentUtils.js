/**
 * Utilidades para cálculos de pago que replican exactamente la lógica del backend
 */

/**
 * Redondeo: solo .6+ hacia arriba (1-5 abajo, 6-9 arriba)
 * - Decimales de .0 a .59... → hacia abajo (floor)
 * - Decimales de .6 a .99... → hacia arriba (ceil)
 *
 * Equivalente a: floor(num + 0.4)
 *
 * Ejemplos:
 * - 149555.55 → 149555 (porque .55 < .6)
 * - 149555.65 → 149556 (porque .65 >= .6)
 * - 149555.50 → 149555 (porque .50 < .6)
 * - 33333.33 → 33333 (porque .33 < .6)
 */
function roundInstallment(num) {
    return Math.floor(num + 0.4);
}

/**
 * Divide un monto en N cuotas cuidando redondeo para que la suma sea exacta.
 * Sin decimales (pesos chilenos). Redondeo: solo .6+ hacia arriba.
 * La última cuota absorbe el residuo.
 * Replica exactamente la lógica de splitAmountInInstallments del backend
 */
export function splitAmountInInstallments(total, installments) {
    if (installments <= 0) {
        return [];
    }

    // Asegurar que trabajamos con enteros
    total = Math.round(total);

    // Monto base redondeado: solo .6+ hacia arriba
    const base = roundInstallment(total / installments);

    // Última cuota absorbe el residuo
    const allocated = base * (installments - 1);
    const lastAmount = total - allocated;

    // Primeras n-1 cuotas iguales, última absorbe residuo
    const amounts = new Array(installments - 1).fill(base);
    amounts.push(lastAmount);

    return amounts;
}

/**
 * Obtiene el monto de la primera cuota usando la lógica estandarizada
 */
export function getFirstInstallmentAmount(total, installments) {
    const amounts = splitAmountInInstallments(total, installments);
    return amounts[0] || 0;
}

/**
 * Calcula el monto mensual con redondeo correcto: solo .6+ hacia arriba
 */
export function calculateMonthlyAmount(total, installments) {
    return roundInstallment(Math.round(total) / installments);
}

/**
 * Formatea un precio en formato chileno
 */
export function formatPrice(amount) {
    const safe = Number(amount ?? 0);
    // No redondear para mostrar el monto exacto
    return new Intl.NumberFormat("es-CL", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    })
        .format(safe);
}

/**
 * Formatea un precio con decimales para mostrar en la UI
 */
export function formatPriceWithDecimals(amount) {
    const safe = Number(amount ?? 0);
    return new Intl.NumberFormat("es-CL", {
        style: "currency",
        currency: "CLP",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })
        .format(safe)
        .replace("CLP", "")
        .trim();
}
