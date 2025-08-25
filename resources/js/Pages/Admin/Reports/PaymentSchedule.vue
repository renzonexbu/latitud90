<template>
    <AdminLayout>
        <Head title="Cronograma de Recuperación de Cuotas" />

        <div class="py-12">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Header -->
                <ReportsHeader
                    title="Cronograma de Recuperación de Cuotas"
                    subtitle="Cuotas pactadas diariamente, vencimientos futuros y cuotas por cobrar en período determinado"
                />

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
                                        {{ program.code }} - {{ program.name }} -
                                        {{ program.destination }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Estado de Cuota
                                </label>
                                <select
                                    v-model="filters.status"
                                    @change="applyFilters"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">Todos los estados</option>
                                    <option value="pending">Pendiente</option>
                                    <option value="overdue">Vencida</option>
                                    <option value="paid">Pagada</option>
                                    <option value="upcoming">Próxima</option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Fecha Desde
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
                                    Fecha Hasta
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

                <!-- Resumen -->
                <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
                    <div
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                    >
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="p-2 bg-blue-100 rounded-lg mr-3">
                                    <svg
                                        class="w-6 h-6 text-blue-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        ></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Cuotas Pendientes
                                    </p>
                                    <p class="text-xl font-bold text-blue-600">
                                        {{ summary.pending || 0 }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                    >
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="p-2 bg-red-100 rounded-lg mr-3">
                                    <svg
                                        class="w-6 h-6 text-red-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        ></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Cuotas Vencidas
                                    </p>
                                    <p class="text-xl font-bold text-red-600">
                                        {{ summary.overdue || 0 }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                    >
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="p-2 bg-yellow-100 rounded-lg mr-3">
                                    <svg
                                        class="w-6 h-6 text-yellow-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"
                                        ></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Monto por Cobrar
                                    </p>
                                    <p
                                        class="text-xl font-bold text-yellow-600"
                                    >
                                        ${{
                                            formatCurrency(
                                                (summary.totalPending || 0) +
                                                    (summary.totalOverdue || 0)
                                            )
                                        }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        Pendientes: ${{
                                            formatCurrency(
                                                summary.totalPending || 0
                                            )
                                        }}
                                        | Vencidas: ${{
                                            formatCurrency(
                                                summary.totalOverdue || 0
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                    >
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="p-2 bg-green-100 rounded-lg mr-3">
                                    <svg
                                        class="w-6 h-6 text-green-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                        ></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Cuotas Pagadas
                                    </p>
                                    <p class="text-xl font-bold text-green-600">
                                        {{ summary.paid || 0 }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                    >
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="p-2 bg-orange-100 rounded-lg mr-3">
                                    <svg
                                        class="w-6 h-6 text-orange-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        ></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Monto Vencido
                                    </p>
                                    <p
                                        class="text-xl font-bold text-orange-600"
                                    >
                                        ${{
                                            formatCurrency(
                                                summary.totalOverdue || 0
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                    >
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="p-2 bg-purple-100 rounded-lg mr-3">
                                    <svg
                                        class="w-6 h-6 text-purple-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        ></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Total Cuotas
                                    </p>
                                    <p
                                        class="text-xl font-bold text-purple-600"
                                    >
                                        {{ summary.total || 0 }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        Todas las cuotas
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Cronograma de Cuotas -->
                <div>
                    <PaymentScheduleTable
                        :schedules="filteredSchedules"
                        @view-details="openDetailModal"
                    />

                    <!-- Paginación -->
                    <div class="mt-6">
                        <ReportsPagination
                            :current-page="
                                props.paymentSchedules?.current_page ||
                                currentPage
                            "
                            :total-accounts="props.paymentSchedules?.total || 0"
                            :accounts-per-page="
                                props.paymentSchedules?.per_page ||
                                schedulesPerPage
                            "
                            @page-changed="handlePageChange"
                        />
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
                            Detalles de Cuota -
                            {{ selectedSchedule?.participant_name }}
                        </h3>
                        <button
                            @click="closeDetailModal"
                            class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
                        >
                            &times;
                        </button>
                    </div>

                    <div v-if="selectedSchedule" class="space-y-6">
                        <!-- Información del Participante y Programa -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div>
                                <h4
                                    class="text-lg font-semibold text-gray-900 mb-3"
                                >
                                    Información del Participante
                                </h4>
                                <div class="space-y-2">
                                    <p>
                                        <span class="font-medium">Nombre:</span>
                                        {{ selectedSchedule.participant_name }}
                                    </p>
                                    <p>
                                        <span class="font-medium"
                                            >Documento:</span
                                        >
                                        {{ selectedSchedule.participant_document }}
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
                                        {{ selectedSchedule.apoderado_name || 'N/A' }}
                                    </p>
                                    <p>
                                        <span class="font-medium">Email:</span>
                                        {{ selectedSchedule.apoderado_email || 'N/A' }}
                                    </p>
                                    <p>
                                        <span class="font-medium">Teléfono:</span>
                                        {{ selectedSchedule.apoderado_phone || 'N/A' }}
                                    </p>
                                </div>
                            </div>
                            <div>
                                <h4
                                    class="text-lg font-semibold text-gray-900 mb-3"
                                >
                                    Información de la Cuota
                                </h4>
                                <div class="space-y-2">
                                    <p>
                                        <span class="font-medium"
                                            >Programa:</span
                                        >
                                        {{ selectedSchedule.program_name }}
                                    </p>
                                    <p>
                                        <span class="font-medium"
                                            >Fecha de Salida:</span
                                        >
                                        {{
                                            formatDate(
                                                selectedSchedule.program_departure_date
                                            )
                                        }}
                                    </p>
                                    <p>
                                        <span class="font-medium"
                                            >N° Cuota:</span
                                        >
                                        {{
                                            selectedSchedule.installment_number
                                        }}
                                    </p>
                                    <p>
                                        <span class="font-medium"
                                            >Fecha Vencimiento:</span
                                        >
                                        {{
                                            formatDate(
                                                selectedSchedule.due_date
                                            )
                                        }}
                                    </p>
                                    <p>
                                        <span class="font-medium">Estado:</span>
                                        <span
                                            :class="
                                                getStatusClass(
                                                    selectedSchedule.status
                                                )
                                            "
                                            class="px-2 py-1 text-xs font-semibold rounded-full"
                                        >
                                            {{
                                                getStatusLabel(
                                                    selectedSchedule.status
                                                )
                                            }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div>
                                <h4
                                    class="text-lg font-semibold text-gray-900 mb-3"
                                >
                                    Agente Comercial
                                </h4>
                                <div class="space-y-2">
                                    <p>
                                        <span class="font-medium">Nombre:</span>
                                        {{
                                            selectedSchedule.sales_executive_name ||
                                            "No asignado"
                                        }}
                                    </p>
                                    <p
                                        v-if="
                                            selectedSchedule.sales_executive_email
                                        "
                                    >
                                        <span class="font-medium">Email:</span>
                                        {{
                                            selectedSchedule.sales_executive_email
                                        }}
                                    </p>
                                    <p
                                        v-if="
                                            selectedSchedule.sales_executive_phone
                                        "
                                    >
                                        <span class="font-medium"
                                            >Teléfono:</span
                                        >
                                        {{
                                            selectedSchedule.sales_executive_phone
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Información Financiera -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4
                                class="text-lg font-semibold text-gray-900 mb-4"
                            >
                                Información Financiera
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Monto Base
                                    </p>
                                    <p class="text-xl font-bold text-gray-900">
                                        ${{
                                            formatCurrency(
                                                selectedSchedule.base_amount ||
                                                    0
                                            )
                                        }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Descuento
                                    </p>
                                    <p class="text-xl font-bold text-red-600">
                                        -${{
                                            formatCurrency(
                                                selectedSchedule.discount_amount ||
                                                    0
                                            )
                                        }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Monto Cuota
                                    </p>
                                    <p class="text-xl font-bold text-blue-600">
                                        ${{
                                            formatCurrency(
                                                selectedSchedule.amount || 0
                                            )
                                        }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Monto Pagado
                                    </p>
                                    <p class="text-xl font-bold text-green-600">
                                        ${{
                                            formatCurrency(
                                                selectedSchedule.paid_amount ||
                                                    0
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>

                            <!-- Días de Vencimiento -->
                            <div class="mt-4 text-center">
                                <p class="text-sm text-gray-600">
                                    Días de Vencimiento
                                </p>
                                <p
                                    class="text-2xl font-bold"
                                    :class="
                                        getDaysClass(
                                            selectedSchedule.days_overdue
                                        )
                                    "
                                >
                                    {{
                                        selectedSchedule.days_overdue || 0
                                    }}
                                    días
                                </p>
                            </div>

                            <!-- Información de la Orden -->
                            <div
                                class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4"
                            >
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        N° Orden
                                    </p>
                                    <p class="text-lg font-bold text-gray-900">
                                        {{ selectedSchedule.order_number }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Monto Total Orden
                                    </p>
                                    <p class="text-lg font-bold text-gray-900">
                                        ${{
                                            formatCurrency(
                                                selectedSchedule.order_final_amount ||
                                                    0
                                            )
                                        }}
                                    </p>
                                </div>
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
                            Exportar Cronograma de Cuotas
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
                                            <input type="checkbox" v-model="exportFields.program.salesExecutive" class="mr-2">
                                            <span class="text-sm">Ejecutivo de Ventas</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.program.salesExecutiveEmail" class="mr-2">
                                            <span class="text-sm">Email Ejecutivo</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.program.salesExecutivePhone" class="mr-2">
                                            <span class="text-sm">Teléfono Ejecutivo</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Información de la Cuota -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h5 class="font-semibold text-gray-800 mb-3">Información de la Cuota</h5>
                                    <div class="space-y-2">
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.installment.number" class="mr-2">
                                            <span class="text-sm">N° Cuota</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.installment.dueDate" class="mr-2">
                                            <span class="text-sm">Fecha Vencimiento</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.installment.amount" class="mr-2">
                                            <span class="text-sm">Monto Cuota</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.installment.baseAmount" class="mr-2">
                                            <span class="text-sm">Monto Base</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.installment.discountAmount" class="mr-2">
                                            <span class="text-sm">Descuento</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.installment.status" class="mr-2">
                                            <span class="text-sm">Estado</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.installment.daysOverdue" class="mr-2">
                                            <span class="text-sm">Días Vencimiento</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.installment.paidAmount" class="mr-2">
                                            <span class="text-sm">Monto Pagado</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.installment.pendingAmount" class="mr-2">
                                            <span class="text-sm">Monto Pendiente</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.installment.orderNumber" class="mr-2">
                                            <span class="text-sm">N° Orden</span>
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
import PaymentScheduleTable from "@/Components/Reports/PaymentScheduleTable.vue";
import ReportsPagination from "@/Components/Reports/ReportsPagination.vue";

const props = defineProps({
    paymentSchedules: {
        type: Object,
        default: () => ({}),
    },
    programs: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
});

const filters = reactive({
    programId: props.filters.programId || "",
    status: props.filters.status || "",
    dateFrom: props.filters.dateFrom || "",
    dateTo: props.filters.dateTo || "",
});

// Estado del modal
const showDetailModal = ref(false);
const selectedSchedule = ref(null);

// Estado de paginación
const currentPage = ref(1);
const schedulesPerPage = ref(10);

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
        salesExecutive: true,
        salesExecutiveEmail: true,
        salesExecutivePhone: true,
    },
    installment: {
        number: true,
        dueDate: true,
        amount: true,
        baseAmount: true,
        discountAmount: true,
        status: true,
        daysOverdue: true,
        paidAmount: true,
        pendingAmount: true,
        orderNumber: true,
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

// Establecer fechas por defecto solo si el usuario las especifica
onMounted(() => {
    // No establecer fechas por defecto automáticamente
    // Esto permite que se muestren todos los datos al inicio
});

// Filtrado en tiempo real
const filteredSchedules = computed(() => {
    const schedules = props.paymentSchedules;

    // Si es un objeto de paginación (del backend), usar data directamente
    if (schedules && schedules.data && Array.isArray(schedules.data)) {
        return schedules.data;
    }

    // Si es un array simple, aplicar paginación del frontend
    if (Array.isArray(schedules)) {
        const startIndex = (currentPage.value - 1) * schedulesPerPage.value;
        const endIndex = startIndex + schedulesPerPage.value;
        return schedules.slice(startIndex, endIndex);
    }

    // Si no es ninguno de los anteriores, retornar array vacío
    return [];
});

const applyFilters = () => {
    // Resetear a la primera página cuando se aplican filtros
    if (props.paymentSchedules && props.paymentSchedules.data) {
        filters.page = 1;
    } else {
        currentPage.value = 1;
    }

    // Aplicar filtros al backend
    router.get("/admin/reports/payment-schedule", filters, {
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
        url: `/admin/reports/export/payment-schedule?${params.toString()}`
    });

    window.open(
        `/admin/reports/export/payment-schedule?${params.toString()}`,
        "_blank"
    );
    closeExportModal();
};

const openDetailModal = (schedule) => {
    selectedSchedule.value = schedule;
    showDetailModal.value = true;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedSchedule.value = null;
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
    router.get("/admin/reports/payment-schedule", filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("es-CL");
};

const getStatusClass = (status) => {
    const classes = {
        pending: "bg-yellow-100 text-yellow-800",
        overdue: "bg-red-100 text-red-800",
        paid: "bg-green-100 text-green-800",
        upcoming: "bg-blue-100 text-blue-800",
    };
    return classes[status] || "bg-gray-100 text-gray-800";
};

const getStatusLabel = (status) => {
    const labels = {
        pending: "Pendiente",
        overdue: "Vencida",
        paid: "Pagada",
        upcoming: "Próxima",
    };
    return labels[status] || status;
};

const getDaysClass = (days) => {
    if (!days || days <= 0) return "text-gray-500";
    if (days <= 7) return "text-yellow-600";
    if (days <= 30) return "text-orange-600";
    return "text-red-600";
};



const formatCurrency = (value) => {
    // Convertir a número y redondear para evitar decimales
    const numericValue = Math.round(Number(value) || 0);
    // Formatear solo el número sin el símbolo de moneda, ya que lo agregamos manualmente
    return numericValue.toLocaleString("es-CL");
};

const formatRut = (rut) => {
    if (!rut) return "N/A";
    
    // Limpiar el RUT de puntos y guiones
    let rutLimpio = rut.toString().replace(/\./g, "").replace(/-/g, "");
    
    if (rutLimpio.length < 2) return rut;
    
    // Separar número y dígito verificador
    let dv = rutLimpio.slice(-1);
    let numero = rutLimpio.slice(0, -1);
    
    // Formatear número con puntos
    let numeroFormateado = numero.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    
    // Retornar RUT formateado
    return `${numeroFormateado}-${dv.toUpperCase()}`;
};

const hasSelectedFields = computed(() => {
    return Object.values(exportFields).some(fields => Object.values(fields).some(field => field));
});
</script>
