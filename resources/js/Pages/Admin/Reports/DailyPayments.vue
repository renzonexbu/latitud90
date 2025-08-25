<template>
  <AdminLayout>
    <Head title="Reporte de Pagos Diarios" />

    <div class="py-12">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Header -->
                <ReportsHeader
                    title="Reporte de Pagos Diarios"
                    subtitle="Pagos realizados diariamente con información detallada de participantes, programas y montos"
                />

                <!-- Filtros -->
                <div class="bg-white rounded-[20px] overflow-hidden">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Filtros</h3>

                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
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
                                    Ejecutivo Comercial
                                </label>
                                <select
                                    v-model="filters.salesExecutiveId"
                                    @change="applyFilters"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">
                                        Todos los ejecutivos
                                    </option>
                                    <option
                                        v-for="executive in salesExecutives"
                                        :key="executive.id"
                                        :value="executive.id"
                                    >
                                        {{ executive.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Forma de Financiamiento
                                </label>
                                <select
                                    v-model="filters.financingType"
                                    @change="applyFilters"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">
                                        Todas las formas
                                    </option>
                                    <option
                                        v-for="(label, value) in financingTypes"
                                        :key="value"
                                        :value="value"
                                    >
                                        {{ label }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Método de Pago
                                </label>
                                <select
                                    v-model="filters.paymentMethodId"
                                    @change="applyFilters"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="">
                                        Todos los métodos
                                    </option>
                                                                         <option
                                         v-for="method in paymentMethods"
                                         :key="method.id"
                                         :value="method.id"
                                     >
                                         {{ method.label }}
                                     </option>
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
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        ></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Total Órdenes
                                    </p>
                                    <p class="text-xl font-bold text-blue-600">
                                        {{ summary.total_orders || 0 }}
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
                                        Total Pagos
                                    </p>
                                    <p class="text-xl font-bold text-green-600">
                                        {{ summary.total_payments || 0 }}
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
                                        Monto Total Pagado
                                    </p>
                                    <p class="text-xl font-bold text-yellow-600">
                                        ${{ formatCurrency(summary.total_amount_paid || 0) }}
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
                                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                        ></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Promedio Pago
                                    </p>
                                    <p class="text-xl font-bold text-purple-600">
                                        ${{ formatCurrency(summary.average_payment || 0) }}
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
                                        Pagos Hoy
                                    </p>
                                    <p class="text-xl font-bold text-orange-600">
                                        {{ summary.payments_today || 0 }}
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
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        ></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Total Registros
                                    </p>
                                    <p class="text-xl font-bold text-red-600">
                                        {{ summary.total_records || 0 }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Pagos Diarios -->
                <div>
                    <DailyPaymentsTable
                        :payments="filteredPayments"
                        @view-details="openDetailModal"
                    />

                    <!-- Paginación -->
                    <div class="mt-6">
                        <ReportsPagination
                            :current-page="
                                props.dailyPayments?.current_page ||
                                currentPage
                            "
                            :total-items="props.dailyPayments?.total || 0"
                            :items-per-page="
                                props.dailyPayments?.per_page ||
                                paymentsPerPage
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
                            Detalles de Pago -
                            {{ selectedPayment?.participant_name }}
                        </h3>
                        <button
                            @click="closeDetailModal"
                            class="text-gray-400 hover:text-gray-600 text-2xl font-bold"
                        >
                            &times;
                        </button>
                    </div>

                    <div v-if="selectedPayment" class="space-y-6">
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
                                        {{ selectedPayment.participant_name }}
                                    </p>
                                    <p>
                                        <span class="font-medium"
                                            >Documento:</span
                                        >
                                        {{ selectedPayment.participant_document }}
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
                                        {{ selectedPayment.emergency_contact_name || 'No especificado' }}
                                    </p>
                                    <p>
                                        <span class="font-medium">Email:</span>
                                        {{ selectedPayment.emergency_contact_email || 'No especificado' }}
                                    </p>
                                    <p>
                                        <span class="font-medium"
                                            >Teléfono:</span
                                        >
                                        {{ selectedPayment.emergency_contact_phone || 'No especificado' }}
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
                                        <span class="font-medium"
                                            >Programa:</span
                                        >
                                        {{ selectedPayment.program_name }}
                                    </p>
                                    <p>
                                        <span class="font-medium"
                                            >Destino:</span
                                        >
                                        {{ selectedPayment.program_destination }}
                                    </p>
                                    <p>
                                        <span class="font-medium"
                                            >Fecha de Salida:</span
                                        >
                                        {{
                                            formatDate(
                                                selectedPayment.program_departure_date
                                            )
                                        }}
                                    </p>
                                    <p>
                                        <span class="font-medium"
                                            >Precio Programa:</span
                                        >
                                        ${{ formatCurrency(selectedPayment.program_price) }}
                                    </p>
                                </div>
                            </div>
                            <div>
                                <h4
                                    class="text-lg font-semibold text-gray-900 mb-3"
                                >
                                    Ejecutivo Comercial
                                </h4>
                                <div class="space-y-2">
                                    <p>
                                        <span class="font-medium">Nombre:</span>
                                        {{
                                            selectedPayment.sales_executive_name ||
                                            "No asignado"
                                        }}
                                    </p>
                                    <p
                                        v-if="
                                            selectedPayment.sales_executive_email
                                        "
                                    >
                                        <span class="font-medium">Email:</span>
                                        {{
                                            selectedPayment.sales_executive_email
                                        }}
                                    </p>
                                    <p
                                        v-if="
                                            selectedPayment.sales_executive_phone
                                        "
                                    >
                                        <span class="font-medium"
                                            >Teléfono:</span
                                        >
                                        {{
                                            selectedPayment.sales_executive_phone
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Información del Pago -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4
                                class="text-lg font-semibold text-gray-900 mb-4"
                            >
                                Información del Pago
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        N° Orden
                                    </p>
                                    <p class="text-xl font-bold text-gray-900">
                                        {{ selectedPayment.order_number }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Cuota
                                    </p>
                                    <p class="text-xl font-bold text-purple-600">
                                        {{ selectedPayment.installment_number }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Fecha Pago
                                    </p>
                                    <p class="text-xl font-bold text-gray-900">
                                        {{ formatDate(selectedPayment.payment_date) }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Método Pago
                                    </p>
                                    <p class="text-xl font-bold text-gray-900">
                                        {{ selectedPayment.payment_method_name }}
                                    </p>
                                </div>
                            </div>

                            <!-- Información adicional del pago -->
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Forma Financiamiento
                                    </p>
                                    <p class="text-lg font-bold text-blue-600">
                                        {{ selectedPayment.financing_type_label }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Fecha Vencimiento Cuota
                                    </p>
                                    <p class="text-lg font-bold text-orange-600">
                                        {{ formatDate(selectedPayment.installment_due_date) }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Código Autorización
                                    </p>
                                    <p class="text-lg font-bold text-gray-900">
                                        {{ selectedPayment.authorization_code || 'N/A' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Montos -->
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Monto Abonado
                                    </p>
                                    <p class="text-xl font-bold text-green-600">
                                        ${{ formatCurrency(selectedPayment.payment_amount) }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Monto Cuota
                                    </p>
                                    <p class="text-xl font-bold text-blue-600">
                                        ${{ formatCurrency(selectedPayment.installment_amount) }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Monto Liberado
                                    </p>
                                    <p class="text-xl font-bold text-purple-600">
                                        ${{ formatCurrency(selectedPayment.released_amount) }}
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-600">
                                        Saldo por Pagar
                                    </p>
                                    <p
                                        class="text-xl font-bold"
                                        :class="getBalanceClass(selectedPayment.remaining_balance)"
                                    >
                                        ${{ formatCurrency(selectedPayment.remaining_balance) }}
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
                            Exportar Pagos Diarios
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
                                            <input type="checkbox" v-model="exportFields.program.destination" class="mr-2">
                                            <span class="text-sm">Destino</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.program.departureDate" class="mr-2">
                                            <span class="text-sm">Fecha de Salida</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.program.price" class="mr-2">
                                            <span class="text-sm">Precio Programa</span>
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

                                <!-- Información del Pago -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h5 class="font-semibold text-gray-800 mb-3">Información del Pago</h5>
                                    <div class="space-y-2">
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.payment.orderNumber" class="mr-2">
                                            <span class="text-sm">N° Orden</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.payment.financingType" class="mr-2">
                                            <span class="text-sm">Forma Financiamiento</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.payment.amount" class="mr-2">
                                            <span class="text-sm">Monto Abonado</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.payment.releasedAmount" class="mr-2">
                                            <span class="text-sm">Monto Liberado</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.payment.externalContribution" class="mr-2">
                                            <span class="text-sm">Aporte Externo</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.payment.remainingBalance" class="mr-2">
                                            <span class="text-sm">Saldo por Pagar</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.payment.paymentDate" class="mr-2">
                                            <span class="text-sm">Fecha Pago</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.payment.paymentMethod" class="mr-2">
                                            <span class="text-sm">Método Pago</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" v-model="exportFields.payment.status" class="mr-2">
                                            <span class="text-sm">Estado Pago</span>
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
import { Head, router } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import ReportsHeader from "@/Components/Reports/ReportsHeader.vue";
import DailyPaymentsTable from "@/Components/Reports/DailyPaymentsTable.vue";
import ReportsPagination from "@/Components/Reports/ReportsPagination.vue";

const props = defineProps({
    dailyPayments: {
        type: Object,
        default: () => ({}),
    },
  programs: {
    type: Array,
        default: () => [],
  },
    salesExecutives: {
    type: Array,
        default: () => [],
    },
    financingTypes: {
        type: Object,
        default: () => ({}),
    },
    paymentMethods: {
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
    salesExecutiveId: props.filters.salesExecutiveId || "",
    financingType: props.filters.financingType || "",
    paymentMethodId: props.filters.paymentMethodId || "",
    dateFrom: props.filters.dateFrom || "",
    dateTo: props.filters.dateTo || "",
});

// Estado del modal
const showDetailModal = ref(false);
const selectedPayment = ref(null);

// Estado de paginación
const currentPage = ref(1);
const paymentsPerPage = ref(10);

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
        destination: true,
        departureDate: true,
        price: true,
        salesExecutive: true,
        salesExecutiveEmail: true,
        salesExecutivePhone: true,
    },
    payment: {
        orderNumber: true,
        financingType: true,
        amount: true,
        releasedAmount: true,
        externalContribution: true,
        remainingBalance: true,
        paymentDate: true,
        paymentMethod: true,
        status: true,
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

// Establecer fechas por defecto (últimos 5 días)
onMounted(() => {
    if (!filters.dateFrom && !filters.dateTo) {
        const endDate = new Date();
        const startDate = new Date();
        startDate.setDate(startDate.getDate() - 4); // Últimos 5 días incluyendo el actual
        
        filters.dateFrom = startDate.toISOString().split('T')[0];
        filters.dateTo = endDate.toISOString().split('T')[0];
        
        // Aplicar filtros por defecto
        applyFilters();
    }
});

// Filtrado en tiempo real
const filteredPayments = computed(() => {
    const payments = props.dailyPayments;

    // Si es un objeto de paginación (del backend), usar data directamente
    if (payments && payments.data && Array.isArray(payments.data)) {
        return payments.data;
    }

    // Si es un array simple, aplicar paginación del frontend
    if (Array.isArray(payments)) {
        const startIndex = (currentPage.value - 1) * paymentsPerPage.value;
        const endIndex = startIndex + paymentsPerPage.value;
        return payments.slice(startIndex, endIndex);
    }

    // Si no es ninguno de los anteriores, retornar array vacío
    return [];
});

const applyFilters = () => {
    // Resetear a la primera página cuando se aplican filtros
    if (props.dailyPayments && props.dailyPayments.data) {
        filters.page = 1;
    } else {
        currentPage.value = 1;
    }

    // Aplicar filtros al backend
    router.get("/admin/reports/daily-payments", filters, {
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

    window.open(
        `/admin/reports/export/daily-payments?${params.toString()}`,
        "_blank"
    );
    closeExportModal();
};

const openDetailModal = (payment) => {
    selectedPayment.value = payment;
    showDetailModal.value = true;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedPayment.value = null;
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
    router.get("/admin/reports/daily-payments", filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("es-CL");
};

const formatCurrency = (value) => {
    const numericValue = Math.round(Number(value) || 0);
    return numericValue.toLocaleString("es-CL");
};

const getBalanceClass = (balance) => {
    if (balance <= 0) return "text-green-600";
    if (balance <= 100000) return "text-yellow-600";
    return "text-red-600";
};

const hasSelectedFields = computed(() => {
    return Object.values(exportFields).some(fields => Object.values(fields).some(field => field));
});
</script>
