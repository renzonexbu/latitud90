<template>
    <div>
        <!-- Programs Grid - 3x2 layout -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <ProgramCard 
                v-for="program in paginatedPrograms" 
                :key="program.id" 
                :program="program"
                @click="handleProgramClick(program)"
            />
        </div>
        
        <!-- Pagination -->
        <ProgramsPagination 
            :current-page="currentPage"
            :total-programs="programs.length"
            :programs-per-page="6"
            @page-changed="handlePageChange"
        />
    </div>
</template>

<script>
import { router } from '@inertiajs/vue3';
import ProgramCard from './ProgramCard.vue';
import ProgramsPagination from './ProgramsPagination.vue';

export default {
    name: "ProgramsGrid",
    components: {
        ProgramCard,
        ProgramsPagination
    },
    data() {
        return {
            currentPage: 1,
            programsPerPage: 6,
            programs: [
                {
                    id: 1,
                    name: "Aventura en la Patagonia",
                    destination: "Patagonia Chilena",
                    price: 450000,
                    duration: 7,
                    participants: 12,
                    paymentPercentage: 75,
                    paidAmount: 337500,
                    totalAmount: 450000
                },
                {
                    id: 2,
                    name: "Exploración Torres del Paine",
                    destination: "Torres del Paine",
                    price: 380000,
                    duration: 5,
                    participants: 8,
                    paymentPercentage: 50,
                    paidAmount: 190000,
                    totalAmount: 380000
                },
                {
                    id: 3,
                    name: "Ruta de los Lagos",
                    destination: "Región de los Lagos",
                    price: 320000,
                    duration: 6,
                    participants: 15,
                    paymentPercentage: 25,
                    paidAmount: 80000,
                    totalAmount: 320000
                },
                {
                    id: 4,
                    name: "Desierto de Atacama",
                    destination: "San Pedro de Atacama",
                    price: 280000,
                    duration: 4,
                    participants: 10,
                    paymentPercentage: 90,
                    paidAmount: 252000,
                    totalAmount: 280000
                },
                {
                    id: 5,
                    name: "Isla de Pascua Misteriosa",
                    destination: "Isla de Pascua",
                    price: 520000,
                    duration: 8,
                    participants: 6,
                    paymentPercentage: 30,
                    paidAmount: 156000,
                    totalAmount: 520000
                },
                {
                    id: 6,
                    name: "Valle del Elqui",
                    destination: "Valle del Elqui",
                    price: 250000,
                    duration: 3,
                    participants: 20,
                    paymentPercentage: 100,
                    paidAmount: 250000,
                    totalAmount: 250000
                },
                {
                    id: 7,
                    name: "Chiloé Mágico",
                    destination: "Archipiélago de Chiloé",
                    price: 290000,
                    duration: 5,
                    participants: 14,
                    paymentPercentage: 60,
                    paidAmount: 174000,
                    totalAmount: 290000
                },
                {
                    id: 8,
                    name: "Araucanía Andina",
                    destination: "Araucanía",
                    price: 310000,
                    duration: 4,
                    participants: 16,
                    paymentPercentage: 40,
                    paidAmount: 124000,
                    totalAmount: 310000
                },
                {
                    id: 9,
                    name: "Rapa Nui Cultural",
                    destination: "Isla de Pascua",
                    price: 480000,
                    duration: 7,
                    participants: 8,
                    paymentPercentage: 15,
                    paidAmount: 72000,
                    totalAmount: 480000
                },
                {
                    id: 10,
                    name: "Glaciares del Sur",
                    destination: "Región de Magallanes",
                    price: 420000,
                    duration: 6,
                    participants: 10,
                    paymentPercentage: 80,
                    paidAmount: 336000,
                    totalAmount: 420000
                },
                {
                    id: 11,
                    name: "Vinos del Valle",
                    destination: "Valle del Maipo",
                    price: 180000,
                    duration: 2,
                    participants: 25,
                    paymentPercentage: 65,
                    paidAmount: 117000,
                    totalAmount: 180000
                },
                {
                    id: 12,
                    name: "Aventura en la Cordillera",
                    destination: "Cordillera de los Andes",
                    price: 350000,
                    duration: 5,
                    participants: 12,
                    paymentPercentage: 45,
                    paidAmount: 157500,
                    totalAmount: 350000
                }
            ]
        };
    },
    computed: {
        paginatedPrograms() {
            const startIndex = (this.currentPage - 1) * this.programsPerPage;
            const endIndex = startIndex + this.programsPerPage;
            return this.programs.slice(startIndex, endIndex);
        }
    },
    methods: {
        handlePageChange(page) {
            this.currentPage = page;
        },
        handleProgramClick(program) {
            // Solo programas 1 y 2 van al edit de los programas reales del seeder
            if (program.id === 1 || program.id === 2) {
                router.visit(route('admin.programs.edit', program.id));
            } else {
                // Los otros programas solo muestran un mensaje por ahora
                alert(`Programa "${program.name}" - Esta funcionalidad estará disponible próximamente`);
            }
        }
    }
};
</script> 