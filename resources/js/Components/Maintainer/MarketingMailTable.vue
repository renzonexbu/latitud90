<template>
    <div class="bg-white rounded-[20px] overflow-hidden">
        <!-- Table Container -->
        <div class="flex flex-col gap-0">
            <!-- Table Header -->
            <div
                class="bg-turquesa rounded-t-[20px] px-5 py-[11px] flex items-center justify-between h-[61.51px]"
            >
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[300px]"
                >
                    Email
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]"
                >
                    Estado
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[200px]"
                >
                    Fecha de Creación
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]"
                >
                    Acciones
                </div>
            </div>

            <!-- Table Body -->
            <div class="flex flex-col h-[574px] overflow-hidden">
                <div
                    v-for="(email, index) in marketingMails"
                    :key="email.id"
                    :class="[
                        'px-5 py-[14px] flex items-center justify-between',
                        index % 2 === 0 ? 'bg-white' : 'bg-[#f9f9f9]',
                    ]"
                >
                    <!-- Email -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[300px]"
                    >
                        {{ email.email }}
                    </div>

                    <!-- Estado -->
                    <div class="flex justify-center items-center w-[140px]">
                        <div
                            :class="[
                                'rounded-[12px] px-[10px] py-[6px] text-white font-nexa-xbold text-[12px] leading-[13px] text-center flex items-center justify-center',
                                email.is_active ? 'bg-[#4b8d7f]' : 'bg-[#d54b44]',
                            ]"
                        >
                            {{ email.is_active ? 'Activo' : 'Inactivo' }}
                        </div>
                    </div>

                    <!-- Fecha de Creación -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[200px]"
                    >
                        {{ formatDate(email.created_at) }}
                    </div>

                    <!-- Acciones -->
                    <div class="flex gap-4 items-center justify-center w-[140px]">
                        <!-- Botón Activar/Desactivar -->
                        <button
                            @click="$emit('toggle-status', email.id)"
                            :class="[
                                'rounded-[12px] px-[10px] py-[6px] text-white font-nexa-xbold text-[12px] leading-[13px] text-center flex items-center justify-center transition-colors',
                                email.is_active 
                                    ? 'bg-[#d54b44] hover:bg-[#b83a33]' 
                                    : 'bg-[#4b8d7f] hover:bg-[#3a7266]'
                            ]"
                        >
                            {{ email.is_active ? 'Desactivar' : 'Activar' }}
                        </button>

                        <!-- Botón Eliminar -->
                        <button
                            @click="$emit('delete-email', email.id)"
                            class="rounded-[12px] px-[10px] py-[6px] bg-[#d54b44] hover:bg-[#b83a33] text-white font-nexa-xbold text-[12px] leading-[13px] text-center flex items-center justify-center transition-colors"
                        >
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "MarketingMailTable",
    props: {
        marketingMails: {
            type: Array,
            default: () => [],
        },
    },
    methods: {
        formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString('es-CL', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    }
};
</script>

<style scoped>
/* Custom font classes - add these to your Tailwind config or use existing ones */
.font-nexa-bold {
    font-family: "Nexa-Bold", sans-serif;
    font-weight: 700;
}

.font-nexa-xbold {
    font-family: "Nexa-XBold", sans-serif;
    font-weight: 400;
}

.text-verde-oscuro {
    color: #1c4f4a;
}

.bg-turquesa {
    background-color: #007e93;
}
</style>
