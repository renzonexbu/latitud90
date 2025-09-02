<template>
    <div class="bg-[#f7efee] rounded-[12px] p-[12px_10px] md:p-[18px_15px] h-auto md:h-[180px] mx-2 md:mx-4 w-full max-w-full md:max-w-[320px]">
        <div class="flex flex-row gap-[8px] md:gap-[12px] items-start">
            <!-- Warning SVG -->
            <svg
                class="w-[15px] h-[15px] md:w-[17.5px] md:h-[17.5px] flex-shrink-0"
                width="19"
                height="18"
                viewBox="0 0 19 18"
                fill="none"
            >
                <path
                    d="M9.13141 6.19792V10.2083M9.13141 12.5759V12.2114M4.74183 7.84729C6.65808 4.07385 7.61475 2.1875 9.13141 2.1875C10.6481 2.1875 11.6055 4.07385 13.521 7.84729L13.7594 8.31688C15.3505 11.4523 16.1467 13.02 15.427 14.1662C14.7081 15.3125 12.9289 15.3125 9.36985 15.3125H8.89298C5.33464 15.3125 3.55475 15.3125 2.83579 14.1662C2.11683 13.02 2.91235 11.4523 4.50339 8.31688L4.74183 7.84729Z"
                    stroke="#D54B44"
                    stroke-width="1.09375"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>

            <!-- Warning Message -->
            <div class="flex flex-col gap-[2px] md:gap-[4px] flex-1 min-w-0">
                <div class="text-[#d54a42] font-nexa text-[12px] md:text-[14px] leading-[14px] md:leading-[16px] w-full">
                    <span class="font-nexa font-normal">
                        Al elegir el Pago Mensual de Cuota LAT90, se
                    </span>
                    <span class="font-nexa font-black">
                        desactiva la opción de pago total
                    </span>
                    <span class="font-nexa font-normal">
                        con tarjeta en 3, 6, 9 o 12 cuotas.
                        <br />
                        <br />
                        Si luego quieres abonar el total, deberás seleccionar todas las cuotas
                        de LAT90 y
                    </span>
                    <span class="font-nexa font-extrabold">
                        podrás pagar con tarjeta (1 cuota), débito o transferencia.
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "MonthlyWarningMessage"
}
</script>
