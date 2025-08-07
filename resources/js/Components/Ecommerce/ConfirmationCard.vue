<template>
    <div
        class="bg-white rounded-xl border border-[#D3D3D3] p-6 flex flex-col justify-between h-[450px] w-[504px]"
    >
        <!-- Top Section -->
        <div class="flex flex-col gap-[18px]">
            <!-- Person Info -->
            <div class="flex flex-col gap-0">
                <div
                    class="text-[#5B5B5B] font-nexa text-xl leading-7 font-normal"
                >
                    Estás por pagar el viaje de
                </div>
                <div
                    class="text-[#007E93] font-nexa text-xl leading-7 font-bold"
                >
                    {{ participantName || "Usuario" }}
                </div>
            </div>

            <!-- Trip Card -->
            <div
                class="bg-[#007E93] rounded-lg p-[17px] flex flex-col gap-[15px] shadow-[0px_4px_11.6px_0px_rgba(163,163,163,0.11)]"
            >
                <div class="flex flex-col gap-[25px]">
                    <!-- Location and Date -->
                    <div class="flex justify-between items-start">
                        <div
                            class="text-white font-nexa text-[10px] font-normal leading-[33.053px] w-[171px]"
                            style="
                                font-feature-settings: 'liga' off, 'clig' off;
                            "
                        >
                            {{ program.destination }}
                        </div>
                        <div
                            class="text-white font-outfit text-[14px] font-medium leading-[33.053px]"
                            style="
                                font-feature-settings: 'liga' off, 'clig' off;
                            "
                        >
                            {{ formatDateRange(program.departure_date) }}
                        </div>
                    </div>

                    <!-- Trip Name and Change Button -->
                    <div class="flex justify-between items-center">
                        <div class="text-white font-nexa text-lg font-bold">
                            {{ program.name }}
                        </div>
                        <button
                            class="bg-[#1C4F4A] rounded-full px-[10px] py-1 hover:bg-[#0f3a35] transition-colors duration-300"
                            @click="changeProgram"
                        >
                            <div
                                class="text-white font-nexa text-[10px] leading-[14px] font-bold"
                            >
                                Cambiar programa
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Section -->
        <div class="flex flex-col gap-[10px] mt-4">
            <!-- Total Value -->
            <div class="flex justify-between items-center">
                <div
                    class="text-[#5B5B5B] font-nexa text-xs leading-[13px] font-bold"
                >
                    Valor total
                </div>
                <div
                    class="text-[#C7C7C7] font-nexa text-base leading-[22px] font-normal"
                >
                    ${{ formatCurrency(program.trip_price) }}
                </div>
            </div>

            <!-- Payment Amount -->
            <div class="border-t border-[#D3D3D3] pb-2">
                <div class="flex justify-between items-center">
                    <div
                        class="text-[#434343] font-outfit text-xl leading-[61px] font-medium"
                    >
                        Pagarás
                    </div>
                    <div
                        class="text-[#434343] font-nexa text-2xl leading-7 font-bold"
                    >
                        ${{ formatCurrency(program.trip_price) }}
                    </div>
                </div>

                <!-- Terms Acceptance -->
                <div class="flex flex-col gap-[8px] mt-4">
                    <div class="flex items-center gap-[8px]">
                        <input
                            type="checkbox"
                            class="custom-checkbox w-[12px] h-[12px] rounded-[1.5px]"
                            v-model="termsAccepted"
                        />
                        <div
                            class="text-[#434343] font-nexa text-[10px] leading-[16px] font-normal"
                        >
                            <span class="font-nexa">Acepto los</span>
                            <a
                                href="/terminos-y-condiciones"
                                target="_blank"
                                class="font-nexa font-bold underline hover:text-[#007E93] transition-colors"
                            >
                                términos y condiciones de compra*
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Button -->
    <div class="flex justify-center mt-6">
        <button
            class="flex w-[404px] px-5 py-[11px] justify-center items-center gap-3 rounded-[49px] bg-[#FBBD51] transition-colors duration-300 hover:bg-[#e6a93d]"
            @click="handlePayment"
        >
            <span
                class="text-white font-urbanist text-base font-semibold leading-[18px]"
                style="font-feature-settings: 'liga' off, 'clig' off"
            >
                Ir a pagar ${{ formatCurrency(program.trip_price) }}
            </span>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 25 24"
                fill="none"
                class="flex-shrink-0"
            >
                <path
                    d="M2.75 12C2.75 17.385 7.115 21.75 12.5 21.75C17.885 21.75 22.25 17.385 22.25 12C22.25 6.615 17.885 2.25 12.5 2.25C7.115 2.25 2.75 6.615 2.75 12ZM12.97 7.97C13.1106 7.82955 13.3012 7.75066 13.5 7.75066C13.6988 7.75066 13.8894 7.82955 14.03 7.97L17.53 11.47C17.6705 11.6106 17.7493 11.8012 17.7493 12C17.7493 12.1988 17.6705 12.3894 17.53 12.53L14.03 16.03C13.9613 16.1037 13.8785 16.1628 13.7865 16.2038C13.6945 16.2448 13.5952 16.2668 13.4945 16.2686C13.3938 16.2704 13.2938 16.2518 13.2004 16.2141C13.107 16.1764 13.0222 16.1203 12.951 16.049C12.8797 15.9778 12.8236 15.893 12.7859 15.7996C12.7482 15.7062 12.7296 15.6062 12.7314 15.5055C12.7332 15.4048 12.7552 15.3055 12.7962 15.2135C12.8372 15.1215 12.8963 15.0387 12.97 14.97L15.19 12.75H8C7.80109 12.75 7.61032 12.671 7.46967 12.5303C7.32902 12.3897 7.25 12.1989 7.25 12C7.25 11.8011 7.32902 11.6103 7.46967 11.4697C7.61032 11.329 7.80109 11.25 8 11.25H15.19L12.97 9.03C12.8295 8.88937 12.7507 8.69875 12.7507 8.5C12.7507 8.30125 12.8295 8.11063 12.97 7.97Z"
                    fill="white"
                />
            </svg>
        </button>
    </div>
</template>

<script>
import { router } from "@inertiajs/vue3";

export default {
    name: "ConfirmationCard",
    props: {
        program: {
            type: Object,
            required: true,
        },
        formData: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            termsAccepted: false,
        };
    },
    computed: {
        participantName() {
            // Usar los datos del participante desde las props
            return this.formData.name || "Usuario";
        },
    },
    methods: {
        formatCurrency(amount) {
            if (!amount) return "0";
            return new Intl.NumberFormat("es-CL").format(amount);
        },
        formatDateRange(dateString) {
            if (!dateString) return "";

            const date = new Date(dateString);
            const day = date.getDate();
            const month = date.getMonth() + 1;
            const year = date.getFullYear();

            return `${day} de ${this.getMonthName(month)} ${year}`;
        },
        getMonthName(month) {
            const months = [
                "Enero",
                "Febrero",
                "Marzo",
                "Abril",
                "Mayo",
                "Junio",
                "Julio",
                "Agosto",
                "Septiembre",
                "Octubre",
                "Noviembre",
                "Diciembre",
            ];
            return months[month - 1];
        },
        handlePayment() {
            // Aquí puedes agregar la lógica para procesar el pago
            console.log("Procesando pago...");
            // Por ejemplo, redirigir a la pasarela de pago
            // router.visit('/payment-gateway');
        },
        changeProgram() {
            // Redirigir al paso 1 (listado de programas) con el RUT del participante
            const rut = this.formData.document_number;
            if (rut) {
                router.visit(`/programs?rut=${rut}`);
            } else {
                // Si no hay RUT, ir a la página principal de programas
                router.visit("/programs");
            }
        },
    },
};
</script>

<style scoped>
.custom-checkbox {
    accent-color: #fbbd51;
}

.custom-checkbox:checked {
    background-color: #fbbd51;
    border-color: #fbbd51;
}

/* Estilos adicionales para mayor compatibilidad */
.custom-checkbox:checked::before {
    background-color: #fbbd51;
}

/* Para navegadores que no soportan accent-color */
.custom-checkbox:checked {
    background-color: #fbbd51 !important;
    border-color: #fbbd51 !important;
}
</style>
