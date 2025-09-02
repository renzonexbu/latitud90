<template>
    <div class="rounded-[20px] border border-gray-300 overflow-hidden">
        <!-- Table Header -->
        <div class="bg-turquesa rounded-t-[20px] px-6 py-4 flex items-center justify-between h-[75px]">
            <div class="text-white font-nexa-bold text-sm w-[300px]">
                Email
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[120px]">
                Estado
            </div>
            <div class="text-white font-nexa-bold text-sm text-center w-[200px]">
                Fecha de Suscripción
            </div>
            <div class="w-[80px] h-[20px] flex-shrink-0">
                <!-- Empty space for actions column -->
            </div>
        </div>

        <!-- Table Body -->
        <div class="flex flex-col">
            <div 
                v-for="(newsletter, index) in newsletters" 
                :key="newsletter.id"
                :class="[
                    'px-6 py-[18px] flex items-center justify-between',
                    index % 2 === 0 ? 'bg-white' : 'bg-gray-50'
                ]"
            >
                <div class="text-verde-oscuro font-nexa-bold text-sm w-[300px]">
                    {{ newsletter.email }}
                </div>
                <div class="w-[120px] flex justify-center">
                    <div 
                        :class="[
                            'rounded-xl px-2.5 py-1.5 flex items-center justify-center w-[100px]',
                            newsletter.is_active ? 'bg-[#4b8d7f]' : 'bg-gray-300'
                        ]"
                    >
                        <span class="font-nexa-xbold text-sm text-center text-white">
                            {{ newsletter.is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                </div>
                <div class="text-verde-oscuro font-nexa-bold text-sm text-center w-[200px]">
                    {{ formatDate(newsletter.subscribed_at) }}
                </div>
                <div class="w-[80px] h-[20px] flex-shrink-0 flex gap-2">
                    <button 
                        @click="editNewsletter(newsletter.id)"
                        class="p-1 hover:bg-gray-100 rounded transition-colors"
                        :title="`Editar ${newsletter.email}`"
                    >
                        <EditPencilIcon fill-color="#C7C7C7" />
                    </button>
                    <button 
                        @click="deleteNewsletter(newsletter.id)"
                        class="p-1 hover:bg-red-50 rounded transition-colors"
                        :title="`Eliminar ${newsletter.email}`"
                    >
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { EditPencilIcon } from "@/Components/Icons";

export default {
    name: "NewsletterTable",
    components: {
        EditPencilIcon,
    },
    props: {
        newsletters: {
            type: Array,
            default: () => []
        }
    },
    methods: {
        editNewsletter(newsletterId) {
            this.$emit('edit-newsletter', newsletterId);
        },

        deleteNewsletter(newsletterId) {
            this.$emit('delete-newsletter', newsletterId);
        },
        
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
    font-family: 'Nexa-Bold', sans-serif;
    font-weight: 700;
}

.font-nexa-xbold {
    font-family: 'Nexa-XBold', sans-serif;
    font-weight: 400;
}

.text-verde-oscuro {
    color: #1c4f4a;
}

.bg-turquesa {
    background-color: #007e93;
}
</style>
