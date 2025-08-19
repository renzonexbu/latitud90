/**
 * Utilidades para cálculos de pago que replican exactamente la lógica del backend
 */

/**
 * Divide un monto en N cuotas cuidando redondeo para que la suma sea exacta.
 * Replica exactamente la lógica de splitAmountInInstallments del backend
 */
export function splitAmountInInstallments(total, installments) {
    // Replicar exactamente la lógica del backend
    const base = Math.floor((total / installments) * 100) / 100; // 2 decimales hacia abajo
    const amounts = new Array(installments).fill(base);
    const allocated = base * installments;
    let remainder = Math.round((total - allocated) * 100) / 100;

    // Distribuir centavos restantes sumando 0.01 a las primeras cuotas
    let i = 0;
    while (remainder > 0 && i < installments) {
        amounts[i] = Math.round((amounts[i] + 0.01) * 100) / 100;
        remainder = Math.round((remainder - 0.01) * 100) / 100;
        i++;
    }
    
    return amounts;
}

/**
 * Obtiene el monto de la primera cuota usando la lógica estandarizada
 */
export function getFirstInstallmentAmount(total, installments) {
    const amounts = splitAmountInInstallments(total, installments);
    return amounts[0];
}

/**
 * Formatea un precio en formato chileno
 */
export function formatPrice(amount) {
    const safe = Number(amount ?? 0);
    // Redondear hacia abajo como el backend
    const rounded = Math.floor(safe);
    return new Intl.NumberFormat("es-CL", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    })
        .format(rounded);
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
