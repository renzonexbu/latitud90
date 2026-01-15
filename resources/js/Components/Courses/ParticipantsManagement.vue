<template>
    <div v-if="programCourseId && programCourseId > 0" class="participants-management">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-[#007e93] font-nexa-bold text-[16px] leading-[20px]">
                    Participantes Insertados
                </h3>
                <p class="text-[#5b5b5b] font-nexa-regular text-[12px] leading-[16px] mt-1">
                    {{ filteredParticipants.length }} participante(s) {{ showOnlyDeletable ? 'sin pagos' : 'en total' }}
                </p>
            </div>
            <div class="flex gap-2">
                <button
                    type="button"
                    @click="showOnlyDeletable = !showOnlyDeletable"
                    class="px-3 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
                >
                    {{ showOnlyDeletable ? 'Mostrar todos' : 'Solo sin pagos' }}
                </button>
                <button
                    type="button"
                    @click="refreshParticipants"
                    :disabled="loading"
                    class="px-3 py-2 text-sm bg-turquesa text-white rounded-lg hover:bg-verde-oscuro transition-colors disabled:opacity-50"
                >
                    {{ loading ? 'Cargando...' : 'Refrescar' }}
                </button>
            </div>
        </div>

        <!-- Bulk Actions Bar -->
        <div v-if="filteredParticipants.some(p => p.can_delete)" class="mb-4 flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
            <input
                type="checkbox"
                :checked="allDeletableSelected"
                @change="toggleSelectAll"
                class="w-4 h-4 text-turquesa border-gray-300 rounded focus:ring-turquesa"
            />
            <span class="text-sm font-nexa-regular text-gray-700">
                {{ selectedParticipants.length > 0 ? `${selectedParticipants.length} seleccionado(s)` : 'Seleccionar todos los eliminables' }}
            </span>
            <button
                v-if="selectedParticipants.length > 0"
                type="button"
                @click="confirmBulkDelete"
                class="ml-auto px-4 py-2 text-sm font-nexa-bold text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors"
            >
                Eliminar seleccionados ({{ selectedParticipants.length }})
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-turquesa"></div>
            <p class="text-gray-500 mt-2">Cargando participantes...</p>
        </div>

        <!-- Participants Table -->
        <div v-else-if="filteredParticipants.length > 0" class="overflow-x-auto bg-white rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-nexa-bold text-gray-700 uppercase tracking-wider w-12">
                            <span class="sr-only">Seleccionar</span>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-nexa-bold text-gray-700 uppercase tracking-wider">
                            Nombre
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-nexa-bold text-gray-700 uppercase tracking-wider">
                            RUT
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-nexa-bold text-gray-700 uppercase tracking-wider">
                            Email
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-nexa-bold text-gray-700 uppercase tracking-wider">
                            Código Inscripción
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-nexa-bold text-gray-700 uppercase tracking-wider">
                            Pagos
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-nexa-bold text-gray-700 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="participant in filteredParticipants" :key="participant.participant_program_id" class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm">
                            <input
                                v-if="participant.can_delete"
                                type="checkbox"
                                :checked="selectedParticipants.includes(participant.participant_program_id)"
                                @change="toggleSelection(participant.participant_program_id)"
                                class="w-4 h-4 text-turquesa border-gray-300 rounded focus:ring-turquesa"
                            />
                        </td>
                        <td class="px-4 py-3 text-sm font-nexa-regular text-gray-900">
                            {{ participant.full_name }}
                        </td>
                        <td class="px-4 py-3 text-sm font-nexa-regular text-gray-700">
                            {{ participant.rut }}
                        </td>
                        <td class="px-4 py-3 text-sm font-nexa-regular text-gray-700">
                            {{ participant.email }}
                        </td>
                        <td class="px-4 py-3 text-sm font-nexa-regular text-gray-700">
                            {{ participant.enrollment_code }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span
                                v-if="participant.has_payments"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-nexa-bold bg-green-100 text-green-800"
                            >
                                ${{ formatNumber(participant.total_paid) }}
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-nexa-regular bg-gray-100 text-gray-600"
                            >
                                Sin pagos
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-right">
                            <button
                                v-if="participant.can_delete"
                                @click="confirmDelete(participant)"
                                type="button"
                                class="text-red-600 hover:text-red-800 font-nexa-bold text-sm transition-colors"
                            >
                                Eliminar
                            </button>
                            <span
                                v-else
                                class="text-gray-400 text-xs font-nexa-regular italic"
                                title="No se puede eliminar porque tiene pagos registrados"
                            >
                                Con transacciones
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-8 bg-gray-50 rounded-lg border border-gray-200">
            <p class="text-gray-500 font-nexa-regular">No hay participantes insertados en este programa</p>
        </div>

        <!-- Confirmation Modal -->
        <div
            v-if="participantToDelete || bulkDeleteConfirm"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            @click.self="cancelDelete"
        >
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
                <h3 class="text-lg font-nexa-bold text-gray-900 mb-2">
                    Confirmar Eliminación
                </h3>
                <p v-if="participantToDelete" class="text-sm font-nexa-regular text-gray-600 mb-4">
                    ¿Estás seguro de que deseas eliminar a <strong>{{ participantToDelete.full_name }}</strong> de este programa?
                </p>
                <p v-else-if="bulkDeleteConfirm" class="text-sm font-nexa-regular text-gray-600 mb-4">
                    ¿Estás seguro de que deseas eliminar <strong>{{ selectedParticipants.length }} participante(s)</strong> de este programa?
                </p>
                <p class="text-xs font-nexa-regular text-red-600 mb-6">
                    Esta acción no se puede deshacer.
                </p>
                <div class="flex gap-3 justify-end">
                    <button
                        type="button"
                        @click="cancelDelete"
                        class="px-4 py-2 text-sm font-nexa-bold text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        @click="bulkDeleteConfirm ? deleteBulkParticipants() : deleteParticipant()"
                        :disabled="deleting"
                        class="px-4 py-2 text-sm font-nexa-bold text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50"
                    >
                        {{ deleting ? 'Eliminando...' : 'Eliminar' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    programCourseId: {
        type: Number,
        default: null
    }
});

const participants = ref([]);
const loading = ref(false);
const participantToDelete = ref(null);
const deleting = ref(false);
const selectedParticipants = ref([]);
const showOnlyDeletable = ref(false);
const bulkDeleteConfirm = ref(false);

// Computed properties
const filteredParticipants = computed(() => {
    if (showOnlyDeletable.value) {
        return participants.value.filter(p => p.can_delete);
    }
    return participants.value;
});

const allDeletableSelected = computed(() => {
    const deletableParticipants = filteredParticipants.value.filter(p => p.can_delete);
    if (deletableParticipants.length === 0) return false;
    return deletableParticipants.every(p => selectedParticipants.value.includes(p.participant_program_id));
});

const fetchParticipants = async () => {
    if (!props.programCourseId) {
        console.warn('No programCourseId provided');
        return;
    }

    console.log('[ParticipantsManagement] Fetching participants for programCourseId:', props.programCourseId);

    loading.value = true;
    try {
        const response = await axios.get('/admin/courses/participants', {
            params: {
                program_course_id: props.programCourseId
            }
        });
        console.log('[ParticipantsManagement] Response:', response.data);
        participants.value = response.data.participants || [];
    } catch (error) {
        console.error('[ParticipantsManagement] Error fetching participants:', error);
        // Silently handle error - don't show alert during initial load
        participants.value = [];
    } finally {
        loading.value = false;
    }
};

const refreshParticipants = () => {
    selectedParticipants.value = [];
    fetchParticipants();
};

const confirmDelete = (participant) => {
    participantToDelete.value = participant;
};

const cancelDelete = () => {
    participantToDelete.value = null;
    bulkDeleteConfirm.value = false;
};

const toggleSelection = (participantProgramId) => {
    const index = selectedParticipants.value.indexOf(participantProgramId);
    if (index > -1) {
        selectedParticipants.value.splice(index, 1);
    } else {
        selectedParticipants.value.push(participantProgramId);
    }
};

const toggleSelectAll = () => {
    const deletableParticipants = filteredParticipants.value.filter(p => p.can_delete);

    if (allDeletableSelected.value) {
        // Deselect all
        deletableParticipants.forEach(p => {
            const index = selectedParticipants.value.indexOf(p.participant_program_id);
            if (index > -1) {
                selectedParticipants.value.splice(index, 1);
            }
        });
    } else {
        // Select all deletable
        deletableParticipants.forEach(p => {
            if (!selectedParticipants.value.includes(p.participant_program_id)) {
                selectedParticipants.value.push(p.participant_program_id);
            }
        });
    }
};

const confirmBulkDelete = () => {
    if (selectedParticipants.value.length === 0) return;
    bulkDeleteConfirm.value = true;
};

const deleteParticipant = async () => {
    if (!participantToDelete.value) return;

    deleting.value = true;
    try {
        const response = await axios.delete('/admin/courses/participants/remove', {
            data: {
                participant_program_id: participantToDelete.value.participant_program_id
            }
        });

        if (response.data.success) {
            alert(response.data.message);
            // Remove from local array
            participants.value = participants.value.filter(
                p => p.participant_program_id !== participantToDelete.value.participant_program_id
            );
            participantToDelete.value = null;
        } else {
            alert(response.data.message);
        }
    } catch (error) {
        console.error('Error deleting participant:', error);
        alert(error.response?.data?.message || 'Error al eliminar participante');
    } finally {
        deleting.value = false;
    }
};

const deleteBulkParticipants = async () => {
    if (selectedParticipants.value.length === 0) return;

    deleting.value = true;
    const participantsToDelete = [...selectedParticipants.value];
    let successCount = 0;
    let errorCount = 0;

    try {
        // Delete participants one by one
        for (const participantProgramId of participantsToDelete) {
            try {
                const response = await axios.delete('/admin/courses/participants/remove', {
                    data: {
                        participant_program_id: participantProgramId
                    }
                });

                if (response.data.success) {
                    successCount++;
                    // Remove from local array
                    participants.value = participants.value.filter(
                        p => p.participant_program_id !== participantProgramId
                    );
                    // Remove from selected array
                    const index = selectedParticipants.value.indexOf(participantProgramId);
                    if (index > -1) {
                        selectedParticipants.value.splice(index, 1);
                    }
                } else {
                    errorCount++;
                }
            } catch (error) {
                console.error('Error deleting participant:', participantProgramId, error);
                errorCount++;
            }
        }

        // Show result message
        if (successCount > 0 && errorCount === 0) {
            alert(`${successCount} participante(s) eliminado(s) exitosamente`);
        } else if (successCount > 0 && errorCount > 0) {
            alert(`${successCount} participante(s) eliminado(s), ${errorCount} error(es)`);
        } else {
            alert(`Error al eliminar participantes`);
        }

        bulkDeleteConfirm.value = false;
    } catch (error) {
        console.error('Error in bulk delete:', error);
        alert('Error al eliminar participantes');
    } finally {
        deleting.value = false;
    }
};

const formatNumber = (value) => {
    if (!value) return '0';
    return Number(value).toLocaleString('es-CL');
};

// Watch for programCourseId changes
watch(() => props.programCourseId, (newValue) => {
    console.log('[ParticipantsManagement] programCourseId changed:', newValue);
    if (newValue && newValue > 0) {
        fetchParticipants();
    }
}, { immediate: true });

// Also fetch on mount
onMounted(() => {
    console.log('[ParticipantsManagement] Component mounted with programCourseId:', props.programCourseId);
    if (props.programCourseId && props.programCourseId > 0) {
        fetchParticipants();
    }
});
</script>

<style scoped>
.participants-management {
    margin-top: 1.5rem;
}
</style>
