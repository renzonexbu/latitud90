<template>
    <div v-if="links.length > 3" class="flex flex-row items-center justify-end gap-6 relative">
        <!-- Total Items -->
        <div class="text-[#9ca3af] font-normal text-sm">
            Total {{ paginationInfo.total }} Elementos
        </div>
        
        <!-- Page Numbers -->
        <div class="flex flex-row gap-2 items-center justify-center">
            <!-- Botón Anterior -->
            <Link
                v-if="links[0].url"
                :href="links[0].url"
                :class="[
                    'flex w-8 h-8 items-center justify-center rounded-full cursor-pointer transition-colors',
                    'bg-white border border-[#e5e7eb] text-[#6b7280] hover:border-[#1c4f4a]'
                ]"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </Link>
            
            <!-- Números de página -->
            <Link
                v-for="(link, index) in pageLinks"
                :key="index"
                :href="link.url"
                v-html="link.label"
                :class="[
                    'flex w-8 h-8 items-center justify-center rounded-full cursor-pointer transition-colors text-sm font-medium',
                    link.active
                        ? 'bg-[#1c4f4a] text-white'
                        : link.url
                            ? 'bg-white border border-[#e5e7eb] text-[#6b7280] hover:border-[#1c4f4a]'
                            : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                ]"
            />
            
            <!-- Botón Siguiente -->
            <Link
                v-if="links[links.length - 1].url"
                :href="links[links.length - 1].url"
                :class="[
                    'flex w-8 h-8 items-center justify-center rounded-full cursor-pointer transition-colors',
                    'bg-white border border-[#e5e7eb] text-[#6b7280] hover:border-[#1c4f4a]'
                ]"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </Link>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    links: {
        type: Array,
        default: () => []
    }
});

const paginationInfo = computed(() => {
    // Extraer información de paginación de los links
    const total = props.links.length > 0 ? props.links[props.links.length - 2]?.label || 0 : 0;
    const currentPage = props.links.findIndex(link => link.active) + 1;
    const perPage = 15; // Ajusta según tu configuración
    
    return {
        from: (currentPage - 1) * perPage + 1,
        to: currentPage * perPage,
        total: total,
        currentPage: currentPage
    };
});

const pageLinks = computed(() => {
    // Filtrar solo los links que son números de página (excluir "Anterior" y "Siguiente")
    return props.links.filter((link, index) => {
        // Excluir el primer y último link (Anterior y Siguiente)
        return index > 0 && index < props.links.length - 1;
    });
});
</script>
