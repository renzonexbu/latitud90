<template>
    <AdminLayout>
        <Head title="Estado de Cuenta Parcial" />

        <div class="py-12">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Header -->
                <ReportsHeader subtitle="Estado de Cuenta Parcial" />

                <!-- Filtros -->
                <div class="bg-white rounded-[20px] overflow-hidden">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Filtros</h3>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Programa
                                </label>
                                <select
                                    v-model="filters.programId"
                                    @change="applyFilters"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">
                                        Todos los programas
                                    </option>
                                    <option
                                        v-for="program in programs"
                                        :key="program.id"
                                        :value="program.id"
                                    >
                                        {{ program.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Participante
                                </label>
                                <select
                                    v-model="filters.participantId"
                                    @change="applyFilters"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">
                                        Todos los participantes
                                    </option>
                                    <option
                                        v-for="participant in participants"
                                        :key="participant.id"
                                        :value="participant.id"
                                    >
                                        {{ participant.first_name }}
                                        {{ participant.last_name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Fecha de Inscripción Desde
                                </label>
                                <input
                                    v-model="filters.dateFrom"
                                    @change="applyFilters"
                                    type="date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Fecha de Inscripción Hasta
                                </label>
                                <input
                                    v-model="filters.dateTo"
                                    @change="applyFilters"
                                    type="date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                        </div>

                        <div class="flex space-x-4">
                            <button
                                @click="openExportModal"
                                class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg"
                            >
                                Exportar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Lista de Estados de Cuenta -->
                <div v-if="filteredAccounts.length > 0">
                    <ReportsTable
                        :accounts="filteredAccounts"
                        @view-details="openDetailModal"
                    />
                    
                    <!-- Paginación -->
                    <div class="mt-6">
                        <ReportsPagination
                            :current-page="props.partialAccounts?.current_page || currentPage"
                            :total-accounts="props.partialAccounts?.total || 0"
                            :accounts-per-page="props.partialAccounts?.per_page || accountsPerPage"
                            @page-changed="handlePageChange"
                        />
                    </div>
                </div>

                <!-- Mensaje cuando no hay datos -->
                <div v-else class="bg-white rounded-[20px] overflow-hidden">
                    <div class="p-6 text-gray-900">
                        <div class="text-center py-8">
                            <p class="text-gray-500">
                                No hay datos para mostrar
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Detalles -->
        <div
            v-if="showDetailModal"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        >
            <div
                class="relative top-20 mx-auto p-5 border w-11/12 max-w-6xl shadow-lg rounded-md bg-white"
            >
                <div class="mt-3">
                    <!-- Header del Modal -->
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-900">
                            Estado de Cuenta Detallado -
                            {{ selectedAccount?.participant_name }}
                        </h3>
                        <button
                            @click="closeDetailModal"
                            class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
                        >
                            &times;
                        </button>
                    </div>

                    <div v-if="selectedAccount" class="space-y-6">
                        <!-- Información del Participante y Programa -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <h4
                                    class="text-lg font-semibold text-gray-900 mb-3"
                                >
                                    Información del Participante
                                </h4>
                                <div class="space-y-2">
                                    <p>
                                        <span class="font-medium">Nombre:</span>
                                        {{ selectedAccount.participant_name }}
                                    </p>
                                    <p>
                                        <span class="font-medium">Documento:</span>
                                        {{ selectedAccount.participant_document }}
                                    </p>
                                </div>
                            </div>
                            <div>
                                <h4
                                    class="text-lg font-semibold text-gray-900 mb-3"
                                >
                                    Información del Apoderado
                                </h4>
                                <div class="space-y-2">
                                    <p>
                                        <span class="font-medium">Nombre:</span>
                                        {{ selectedAccount.apoderado_name || 'N/A' }}
                                    </p>
                                    <p>
                                        <span class="font-medium">Email:</span>
                                        {{ selectedAccount.apoderado_email || 'N/A' }}
                                    </p>
                                    <p>
                                        <span class="font-medium">Teléfono:</span>
                                        {{ selectedAccount.apoderado_phone || 'N/A' }}
                                    </p>
                                </div>
                            </div>
                            <div>
                                <h4
                                    class="text-lg font-semibold text-gray-900 mb-3"
                                >
                                    Información del Programa
                                </h4>
                                <div class="space-y-2">
                                    <p>
                                        <span class="font-medium">Programa:</span>
                                        {{ selectedAccount.program_name }}
                                    </p>
                                    <p>
                                        <span class="font-medium">Fecha de Salida:</span>
                                        {{ formatDate(selectedAccount.program_departure_date) }}
                                    </p>
                                    <p>
                                        <span class="font-medium">Código de Inscripción:</span>
                                        {{ selectedAccount.enrollment_code }}
                                    </p>
                                    <p>
                                        <span class="font-medium">Ejecutivo de Ventas:</span>
                                        {{ selectedAccount.sales_executive_name || 'N/A' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Resumen Financiero -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4
                                class="text-lg font-semibold text-gray-900 mb-4"
                            >
                                Resumen Financiero
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Precio Total
                                    </p>
                                    <p class="text-xl font-bold text-gray-900">
                                        ${{
                                            selectedAccount.total_amount?.toLocaleString()
                                        }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Descuentos
                                    </p>
                                    <p class="text-xl font-bold text-red-600">
                                        -${{
                                            selectedAccount.total_discounts?.toLocaleString()
                                        }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Monto Neto
                                    </p>
                                    <p class="text-xl font-bold text-blue-600">
                                        ${{
                                            selectedAccount.net_amount?.toLocaleString()
                                        }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Total Pagado
                                    </p>
                                    <p class="text-xl font-bold text-green-600">
                                        ${{
                                            selectedAccount.total_paid?.toLocaleString()
                                        }}
                                    </p>
                                </div>
                            </div>

                            <!-- Barra de Progreso -->
                            <div class="mt-4">
                                <div
                                    class="flex justify-between text-sm text-gray-600 mb-1"
                                >
                                    <span>Progreso de Pago</span>
                                    <span
                                        >{{
                                            selectedAccount.progress_percentage
                                        }}%</span
                                    >
                                </div>
                                <div
                                    class="w-full bg-gray-200 rounded-full h-2"
                                >
                                    <div
                                        class="bg-green-600 h-2 rounded-full transition-all duration-300"
                                        :style="{
                                            width:
                                                selectedAccount.progress_percentage +
                                                '%',
                                        }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Saldo Pendiente -->
                            <div class="mt-4 text-center">
                                <p class="text-sm text-gray-600">
                                    Saldo Pendiente
                                </p>
                                <p
                                    class="text-2xl font-bold"
                                    :class="
                                        selectedAccount.pending_amount > 0
                                            ? 'text-red-600'
                                            : 'text-green-600'
                                    "
                                >
                                    ${{
                                        selectedAccount.pending_amount?.toLocaleString()
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- Descuentos Aplicados -->
                        <div
                            v-if="
                                selectedAccount.discounts_detail &&
                                selectedAccount.discounts_detail.length > 0
                            "
                        >
                            <h4
                                class="text-lg font-semibold text-gray-900 mb-3"
                            >
                                Descuentos Aplicados
                            </h4>
                            <div class="bg-yellow-50 p-4 rounded-lg">
                                <div
                                    v-for="discount in selectedAccount.discounts_detail"
                                    :key="discount.comment"
                                    class="flex justify-between items-center py-1"
                                >
                                    <span class="text-sm">{{
                                        discount.comment
                                    }}</span>
                                    <span class="font-medium">
                                        {{
                                            discount.type === "percentage"
                                                ? discount.value + "%"
                                                : "$" +
                                                  discount.value?.toLocaleString()
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Historial de Pagos -->
                        <div
                            v-if="
                                selectedAccount.payment_history &&
                                selectedAccount.payment_history.length > 0
                            "
                        >
                            <h4
                                class="text-lg font-semibold text-gray-900 mb-3"
                            >
                                Historial de Pagos
                            </h4>
                            <div class="overflow-x-auto">
                                <table
                                    class="min-w-full divide-y divide-gray-200"
                                >
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                            >
                                                Fecha
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                            >
                                                Monto
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                            >
                                                Método
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                            >
                                                Estado
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                            >
                                                Código
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="bg-white divide-y divide-gray-200"
                                    >
                                        <tr
                                            v-for="payment in selectedAccount.payment_history"
                                            :key="payment.id"
                                        >
                                            <td
                                                class="px-4 py-3 text-sm text-gray-900"
                                            >
                                                {{ formatDate(payment.date) }}
                                            </td>
                                            <td
                                                class="px-4 py-3 text-sm font-medium text-gray-900"
                                            >
                                                ${{
                                                    payment.amount?.toLocaleString()
                                                }}
                                            </td>
                                            <td
                                                class="px-4 py-3 text-sm text-gray-900"
                                            >
                                                {{ payment.method }}
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                <span
                                                    class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800"
                                                >
                                                    {{ payment.status }}
                                                </span>
                                            </td>
                                            <td
                                                class="px-4 py-3 text-sm text-gray-500"
                                            >
                                                {{
                                                    payment.authorization_code ||
                                                    payment.transaction_id ||
                                                    "N/A"
                                                }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Próximos Vencimientos -->
                        <div
                            v-if="
                                selectedAccount.upcoming_payments &&
                                selectedAccount.upcoming_payments.length > 0
                            "
                        >
                            <h4
                                class="text-lg font-semibold text-gray-900 mb-3"
                            >
                                Próximos Vencimientos
                            </h4>
                            <div class="overflow-x-auto">
                                <table
                                    class="min-w-full divide-y divide-gray-200"
                                >
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                            >
                                                Cuota
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                            >
                                                Fecha Vencimiento
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                            >
                                                Monto
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase"
                                            >
                                                Estado
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="bg-white divide-y divide-gray-200"
                                    >
                                        <tr
                                            v-for="payment in selectedAccount.upcoming_payments"
                                            :key="payment.id"
                                        >
                                            <td
                                                class="px-4 py-3 text-sm text-gray-900"
                                            >
                                                {{ payment.installment_number }}
                                            </td>
                                            <td
                                                class="px-4 py-3 text-sm text-gray-900"
                                            >
                                                {{
                                                    formatDate(payment.due_date)
                                                }}
                                            </td>
                                            <td
                                                class="px-4 py-3 text-sm font-medium text-gray-900"
                                            >
                                                ${{
                                                    payment.amount?.toLocaleString()
                                                }}
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                <span
                                                    class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800"
                                                >
                                                    {{ payment.status }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Botones del Modal -->
                    <div class="flex justify-end space-x-3 mt-6">
                        <button
                            @click="closeDetailModal"
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg"
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Exportación -->
        <div
            v-if="showExportModal"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
        >
            <div
                class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white"
            >
                <div class="mt-3">
                    <!-- Header del Modal -->
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-900">
                            Exportar Estado de Cuenta Parcial
                        </h3>
                        <button
                            @click="closeExportModal"
                            class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
                        >
                            &times;
                        </button>
                    </div>

                    <div class="space-y-6">
                        <!-- Selección de Campos -->
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">
                                Seleccionar Campos para Exportar
                            </h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Información del Participante -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h5 class="font-semibold text-gray-800 mb-3">Información del Participante</h5>
                                    <div class="space-y-2">
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.participant.name" class="mr-2">
                                            <span class="text-sm">Nombre</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.participant.email" class="mr-2">
                                            <span class="text-sm">Email</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.participant.document" class="mr-2">
                                            <span class="text-sm">Documento</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.participant.phone" class="mr-2">
                                            <span class="text-sm">Teléfono</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Información del Programa -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h5 class="font-semibold text-gray-800 mb-3">Información del Programa</h5>
                                    <div class="space-y-2">
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.program.name" class="mr-2">
                                            <span class="text-sm">Nombre del Programa</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.program.departureDate" class="mr-2">
                                            <span class="text-sm">Fecha de Salida</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.program.enrollmentCode" class="mr-2">
                                            <span class="text-sm">Código de Inscripción</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.program.salesExecutive" class="mr-2">
                                            <span class="text-sm">Ejecutivo de Ventas</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Información Financiera -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h5 class="font-semibold text-gray-800 mb-3">Información Financiera</h5>
                                    <div class="space-y-2">
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.financial.totalAmount" class="mr-2">
                                            <span class="text-sm">Precio Total</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.financial.discounts" class="mr-2">
                                            <span class="text-sm">Descuentos</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.financial.netAmount" class="mr-2">
                                            <span class="text-sm">Monto Neto</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.financial.totalPaid" class="mr-2">
                                            <span class="text-sm">Total Pagado</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.financial.pendingAmount" class="mr-2">
                                            <span class="text-sm">Saldo Pendiente</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.financial.progressPercentage" class="mr-2">
                                            <span class="text-sm">Progreso de Pago (%)</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información del Apoderado -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h5 class="font-semibold text-gray-800 mb-3">Información del Apoderado</h5>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" v-model="exportFields.apoderado.name" class="mr-2">
                                    <span class="text-sm">Nombre del Apoderado</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" v-model="exportFields.apoderado.email" class="mr-2">
                                    <span class="text-sm">Email del Apoderado</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" v-model="exportFields.apoderado.phone" class="mr-2">
                                    <span class="text-sm">Teléfono del Apoderado</span>
                                </label>
                            </div>
                        </div>

                        <!-- Opciones de Exportación -->
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h5 class="font-semibold text-blue-800 mb-3">Opciones de Exportación</h5>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Formato de Archivo
                                    </label>
                                    <select v-model="exportOptions.format" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                        <option value="xlsx">Excel (.xlsx)</option>
                                        <option value="csv">CSV (.csv)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Incluir Todos los Registros
                                    </label>
                                    <select v-model="exportOptions.includeAll" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                        <option value="current">Solo página actual</option>
                                        <option value="all">Todos los registros</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="flex justify-end space-x-3">
                            <button
                                @click="closeExportModal"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg"
                            >
                                Cancelar
                            </button>
                            <button
                                @click="exportReport"
                                :disabled="!hasSelectedFields"
                                class="bg-green-500 hover:bg-green-700 disabled:bg-gray-400 text-white font-bold py-2 px-4 rounded-lg"
                            >
                                Exportar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import { Head, router, Link } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import ReportsHeader from "@/Components/Reports/ReportsHeader.vue";
import ReportsTable from "@/Components/Reports/ReportsTable.vue";
import ReportsPagination from "@/Components/Reports/ReportsPagination.vue";

const props = defineProps({
    partialAccounts: {
        type: Object, // Changed to Object to match backend response
        default: () => ({}),
    },
    programs: {
        type: Array,
        default: () => [],
    },
    participants: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const filters = reactive({
    programId: props.filters.programId || "",
    participantId: props.filters.participantId || "",
    dateFrom: props.filters.dateFrom || "",
    dateTo: props.filters.dateTo || "",
});

// Estado del modal
const showDetailModal = ref(false);
const selectedAccount = ref(null);

// Estado de paginación
const currentPage = ref(1);
const accountsPerPage = ref(10);

// Estado del modal de exportación
const showExportModal = ref(false);
const exportFields = reactive({
    participant: {
        name: true,
        email: true,
        document: true,
        phone: true,
    },
    program: {
        name: true,
        departureDate: true,
        enrollmentCode: true,
        salesExecutive: true,
    },
    financial: {
        totalAmount: true,
        discounts: true,
        netAmount: true,
        totalPaid: true,
        pendingAmount: true,
        progressPercentage: true,
    },
    apoderado: {
        name: true,
        email: true,
        phone: true,
    },
});
const exportOptions = reactive({
    format: "xlsx",
    includeAll: "current",
});

// Establecer fechas por defecto (último mes)
onMounted(() => {
    if (!filters.dateFrom || !filters.dateTo) {
        const today = new Date();
        const lastMonth = new Date(
            today.getFullYear(),
            today.getMonth() - 1,
            today.getDate()
        );

        filters.dateTo = today.toISOString().split("T")[0];
        filters.dateFrom = lastMonth.toISOString().split("T")[0];
    }
});

// Filtrado en tiempo real
const filteredAccounts = computed(() => {
    const accounts = props.partialAccounts;
    
    // Si es un objeto de paginación (del backend), usar data directamente
    if (accounts && accounts.data && Array.isArray(accounts.data)) {
        return accounts.data;
    }
    
    // Si es un array simple, aplicar paginación del frontend
    if (Array.isArray(accounts)) {
        const startIndex = (currentPage.value - 1) * accountsPerPage.value;
        const endIndex = startIndex + accountsPerPage.value;
        return accounts.slice(startIndex, endIndex);
    }
    
    // Si no es ninguno de los anteriores, retornar array vacío
    console.warn('partialAccounts is not in expected format:', accounts);
    return [];
});

const applyFilters = () => {
    // Resetear a la primera página cuando se aplican filtros
    if (props.partialAccounts && props.partialAccounts.data) {
        filters.page = 1;
    } else {
        currentPage.value = 1;
    }
    
    // Aplicar filtros al backend
    router.get("/admin/reports/partial-account", filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const exportReport = () => {
    const params = new URLSearchParams(filters);
    const selectedFields = Object.keys(exportFields).reduce((acc, key) => {
        acc[key] = Object.keys(exportFields[key]).filter(field => exportFields[key][field]);
        return acc;
    }, {});
    params.append('fields', JSON.stringify(selectedFields));
    params.append('format', exportOptions.format);
    params.append('include_all', exportOptions.includeAll);

    // Log para debug
    console.log('Export Debug:', {
        selectedFields,
        format: exportOptions.format,
        includeAll: exportOptions.includeAll,
        url: `/admin/reports/export/partial-account?${params.toString()}`
    });

    window.open(
        `/admin/reports/export/partial-account?${params.toString()}`,
        "_blank"
    );
    closeExportModal();
};

const openDetailModal = (account) => {
    selectedAccount.value = account;
    showDetailModal.value = true;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedAccount.value = null;
};

const openExportModal = () => {
    showExportModal.value = true;
    // Resetear campos de exportación al abrir
    Object.keys(exportFields).forEach(key => {
        Object.keys(exportFields[key]).forEach(field => {
            exportFields[key][field] = true; // Por defecto todos seleccionados
        });
    });
    exportOptions.format = "xlsx";
    exportOptions.includeAll = "current";
};

const closeExportModal = () => {
    showExportModal.value = false;
};

const handlePageChange = (page) => {
    // Actualizar los filtros con la nueva página
    filters.page = page;
    
    // Aplicar filtros al backend con la nueva página
    router.get("/admin/reports/partial-account", filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const getStatusClass = (status) => {
    const classes = {
        paid: "bg-green-100 text-green-800",
        pending: "bg-yellow-100 text-yellow-800",
        overdue: "bg-red-100 text-red-800",
        partial: "bg-blue-100 text-blue-800",
    };
    return classes[status] || "bg-gray-100 text-gray-800";
};

const getStatusLabel = (status) => {
    const labels = {
        paid: "Pagado",
        pending: "Pendiente",
        overdue: "Vencido",
        partial: "Pago Parcial",
    };
    return labels[status] || status;
};

const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("es-CL");
};

const hasSelectedFields = computed(() => {
    return Object.values(exportFields).some(fields => Object.values(fields).some(field => field));
});

</script>
