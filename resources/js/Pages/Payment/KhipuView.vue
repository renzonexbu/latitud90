<template>
    <div class="min-h-screen bg-gray-50 flex items-center justify-center p-6">
        <div
            class="w-full max-w-lg bg-white rounded-xl border border-gray-200 shadow-sm p-6"
        >
            <!-- Spinner / Checking -->
            <div
                v-if="stage === 'checking'"
                class="flex flex-col items-center text-center gap-4"
            >
                <svg
                    class="animate-spin h-8 w-8 text-[#007E93]"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
                <h2 class="text-[#007E93] font-outfit text-xl font-semibold">
                    Verificando tu pago…
                </h2>
                <p class="text-[#5B5B5B] text-sm">
                    Estamos consultando el estado de tu transacción con Khipu.
                    Intento {{ attempt }} de {{ maxAttempts }}.
                </p>
            </div>

            <!-- Pending / Fallback -->
            <div v-else class="flex flex-col items-center text-center gap-4">
                <h2 class="text-[#B45309] font-outfit text-xl font-semibold">
                    No pudimos verificar tu pago
                </h2>
                <p class="text-[#5B5B5B] text-sm">
                    No fue posible confirmar tu pago en este momento. Te redirigiremos al inicio.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";

const props = defineProps({
    orderDetailId: { type: Number, required: true },
    paymentId: { type: String, default: "" },
    paymentData: { type: Object, default: null },
    rut: { type: String, default: "" },
});

const attempt = ref(0);
const maxAttempts = ref(5);
const stage = ref("checking"); // 'checking' | 'pending'

const programsUrl = computed(() => {
    const r = props.rut ? `?rut=${encodeURIComponent(props.rut)}` : "";
    return `/programs${r}`;
});

onMounted(async () => {
    // Si no tenemos paymentId en props, intentar resolverlo
    if (!props.paymentId) {
        try {
            const res = await fetch(`/khipu/last/${props.orderDetailId}`, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });
            if (res.ok) {
                const data = await res.json();
                if (data && data.payment_id) {
                    // parche mínimo: crear un nuevo objeto props-like
                    // Nota: en <script setup> props es readonly; guardamos localmente
                    localPaymentId.value = data.payment_id;
                }
            }
        } catch (_) {}
    }
    await poll();
    // Si no se confirmó, redirigir automáticamente al inicio
    if (stage.value === 'pending') {
        setTimeout(() => { window.location.href = programsUrl.value; }, 1200);
    }
});

const localPaymentId = ref("");

async function poll() {
    while (attempt.value < maxAttempts.value) {
        attempt.value += 1;
        try {
            console.log('[KhipuView] confirm attempt start', { attempt: attempt.value, orderDetailId: props.orderDetailId, paymentId: (props.paymentId || localPaymentId.value) });
            const res = await fetch("/khipu/confirm", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: JSON.stringify({
                    orderDetailId: props.orderDetailId,
                    payment_id: props.paymentId || localPaymentId.value,
                }),
            });

            // Intentar parsear JSON de forma segura
            let data = null;
            const contentType = res.headers.get("Content-Type") || "";
            if (contentType.includes("application/json")) {
                data = await res.json();
            } else {
                const text = await res.text();
                try {
                    data = JSON.parse(text);
                } catch (_) {
                    data = null;
                }
                console.log('[KhipuView] confirm non-json response', { attempt: attempt.value, status: res.status, text });
            }
            console.log('[KhipuView] confirm response', { attempt: attempt.value, status: res.status, ok: res.ok, data });
            if (data && data.success && data.redirect) {
                window.location.href = data.redirect;
                return;
            }
        } catch (e) {
            // Ignorar y reintentar
            console.log('[KhipuView] confirm error', { attempt: attempt.value, error: String(e) });
        }
        // Esperar 2s antes del siguiente intento
        await new Promise((r) => setTimeout(r, 2000));
    }
    stage.value = "pending";
}

function retry() {
    attempt.value = 0;
    stage.value = "checking";
    poll();
}
</script>

<style scoped></style>

<!-- Duplicate markup removed -->
