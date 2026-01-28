<template>
    <AdminLayout>
        <Head title="Registrar Pago Offline" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold text-gray-900">
                                Registrar Pago Offline
                            </h2>
                            <Link
                                :href="route('admin.payments.index')"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200"
                            >
                                Volver
                            </Link>
                        </div>

                        <!-- Alerta de error general -->
                        <div
                            v-if="errors.error"
                            ref="errorAlert"
                            class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg"
                        >
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg
                                        class="h-5 w-5 text-red-500"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">
                                        Error al procesar el pago
                                    </h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        {{ errors.error }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form @submit.prevent="submit" class="space-y-8">
                            <!-- SECCIÓN 1: DATOS DEL PAGADOR -->
                            <div class="border-b border-gray-200 pb-6">
                                <h3
                                    class="text-lg font-semibold text-gray-900 mb-6"
                                >
                                    Datos del Pagador
                                </h3>

                                <!-- Tipo de Documento - Full Width -->
                                <div class="mb-6">
                                    <label
                                        class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal mb-3 block"
                                    >
                                        Tipo de documento *
                                    </label>
                                    <div class="flex gap-6">
                                        <label
                                            class="flex items-center gap-3 cursor-pointer group"
                                        >
                                            <div class="relative">
                                                <input
                                                    type="radio"
                                                    name="documentType"
                                                    :value="
                                                        getDocumentTypeId('RUT')
                                                    "
                                                    v-model="
                                                        buyerForm.documentType
                                                    "
                                                    class="sr-only peer"
                                                />
                                                <div
                                                    class="w-5 h-5 border-2 border-[#5B5B5B] rounded-full peer-checked:border-[#FBBD51] peer-checked:bg-[#FBBD51] transition-all duration-200 flex items-center justify-center"
                                                >
                                                    <div
                                                        class="w-2 h-2 bg-white rounded-full opacity-0 peer-checked:opacity-100 transition-opacity duration-200"
                                                    ></div>
                                                </div>
                                            </div>
                                            <span
                                                class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal group-hover:text-[#FBBD51] transition-colors duration-200"
                                            >
                                                RUT
                                            </span>
                                        </label>
                                        <label
                                            class="flex items-center gap-3 cursor-pointer group"
                                        >
                                            <div class="relative">
                                                <input
                                                    type="radio"
                                                    name="documentType"
                                                    :value="
                                                        getDocumentTypeId(
                                                            'Pasaporte'
                                                        )
                                                    "
                                                    v-model="
                                                        buyerForm.documentType
                                                    "
                                                    class="sr-only peer"
                                                />
                                                <div
                                                    class="w-5 h-5 border-2 border-[#5B5B5B] rounded-full peer-checked:border-[#FBBD51] peer-checked:bg-[#FBBD51] transition-all duration-200 flex items-center justify-center"
                                                >
                                                    <div
                                                        class="w-2 h-2 bg-white rounded-full opacity-0 peer-checked:opacity-100 transition-opacity duration-200"
                                                    ></div>
                                                </div>
                                            </div>
                                            <span
                                                class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal group-hover:text-[#FBBD51] transition-colors duration-200"
                                            >
                                                Pasaporte
                                            </span>
                                        </label>
                                        <label
                                            class="flex items-center gap-3 cursor-pointer group"
                                        >
                                            <div class="relative">
                                                <input
                                                    type="radio"
                                                    name="documentType"
                                                    :value="
                                                        getDocumentTypeId('DNI')
                                                    "
                                                    v-model="
                                                        buyerForm.documentType
                                                    "
                                                    class="sr-only peer"
                                                />
                                                <div
                                                    class="w-5 h-5 border-2 border-[#5B5B5B] rounded-full peer-checked:border-[#FBBD51] peer-checked:bg-[#FBBD51] transition-all duration-200 flex items-center justify-center"
                                                >
                                                    <div
                                                        class="w-2 h-2 bg-white rounded-full opacity-0 peer-checked:opacity-100 transition-opacity duration-200"
                                                    ></div>
                                                </div>
                                            </div>
                                            <span
                                                class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal group-hover:text-[#FBBD51] transition-colors duration-200"
                                            >
                                                DNI
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <div
                                    class="grid grid-cols-1 md:grid-cols-2 gap-6"
                                >
                                    <!-- Left Column - Document Number and Personal Information -->
                                    <div class="flex flex-col gap-[18px]">
                                        <!-- Número de Documento -->
                                        <div class="flex flex-col gap-[12px]">
                                            <label
                                                class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal"
                                            >
                                                {{ getDocumentLabel() }} *
                                            </label>
                                            <div class="relative">
                                                <input
                                                    type="text"
                                                    :placeholder="
                                                        getDocumentPlaceholder()
                                                    "
                                                    :class="[
                                                        'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                        isRutDocument &&
                                                        rutValidation.isValid ===
                                                            false
                                                            ? 'border-red-500'
                                                            : '',
                                                        isRutDocument &&
                                                        rutValidation.isValid ===
                                                            true
                                                            ? 'border-green-500'
                                                            : 'border-[#5B5B5B]',
                                                    ]"
                                                    v-model="
                                                        buyerForm.documentNumber
                                                    "
                                                    @input="handleDocumentInput"
                                                    @blur="handleDocumentBlur"
                                                />
                                                <div
                                                    v-if="isLoadingFrequentClient"
                                                    class="absolute right-3 top-1/2 transform -translate-y-1/2"
                                                >
                                                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                                                </div>
                                            </div>
                                            <div
                                                v-if="
                                                    isRutDocument &&
                                                    rutValidation.message
                                                "
                                                class="text-xs mt-1 validation-message"
                                                :class="[
                                                    rutValidation.isValid ===
                                                    true
                                                        ? 'text-green-500'
                                                        : 'text-red-500',
                                                ]"
                                            >
                                                {{ rutValidation.message }}
                                            </div>
                                        </div>

                                        <!-- Nombre completo -->
                                        <div class="flex flex-col gap-[12px]">
                                            <label
                                                class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal"
                                            >
                                                Nombre completo *
                                            </label>
                                            <input
                                                type="text"
                                                placeholder="Nombre y apellido"
                                                class="w-full h-[46px] bg-white rounded-lg border border-[#5B5B5B] px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
                                                v-model="buyerForm.fullName"
                                            />
                                        </div>

                                        <!-- Correo electrónico -->
                                        <div class="flex flex-col gap-[12px]">
                                            <label
                                                class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal"
                                            >
                                                Correo electrónico
                                            </label>
                                            <input
                                                type="email"
                                                placeholder="Escriba su correo electrónico"
                                                class="w-full h-[46px] bg-white rounded-lg border border-[#5B5B5B] px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
                                                v-model="buyerForm.email"
                                            />
                                        </div>

                                            </div>
                                </div>
                            </div>

                            <!-- SECCIÓN 2: INFORMACIÓN DEL PAGO -->
                            <div>
                                <h3
                                    class="text-lg font-semibold text-gray-900 mb-6"
                                >
                                    Información del Pago
                                </h3>

                                <!-- Buscador de Participante Inscrito -->
                                <div class="mb-6" data-search-container>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Buscar Participante Inscrito *
                                    </label>
                                    <p class="text-sm text-gray-500 mb-3">
                                        Busque por RUT, nombre, apellido o código de programa
                                    </p>
                                    <div class="relative">
                                        <input
                                            type="text"
                                            v-model="searchQuery"
                                            @input="debounceSearch"
                                            @focus="showDropdown = true"
                                            @keydown.escape="showDropdown = false"
                                            @keydown.down.prevent="navigateDropdown(1)"
                                            @keydown.up.prevent="navigateDropdown(-1)"
                                            @keydown.enter.prevent="selectHighlighted"
                                            placeholder="Ej: 12.345.678-9, Juan Pérez, V0008..."
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                            :class="{
                                                'border-red-500': errors.program_id || errors.participant_id,
                                                'border-green-500': selectedEnrollment
                                            }"
                                        />
                                        <div v-if="isSearching" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                            <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-[#007e93]"></div>
                                        </div>
                                        <div v-else-if="selectedEnrollment" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                            <button
                                                type="button"
                                                @click="clearSelection"
                                                class="text-gray-400 hover:text-gray-600"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Dropdown de resultados -->
                                        <div
                                            v-if="showDropdown && searchResults.length > 0"
                                            class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-80 overflow-y-auto"
                                        >
                                            <div
                                                v-for="(result, index) in searchResults"
                                                :key="`${result.participant_id}-${result.program_course_id}`"
                                                @click="selectEnrollment(result)"
                                                @mouseenter="highlightedIndex = index"
                                                class="px-4 py-3 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors"
                                                :class="{
                                                    'bg-[#007e93] text-white': highlightedIndex === index,
                                                    'hover:bg-gray-50': highlightedIndex !== index
                                                }"
                                            >
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <div class="font-semibold" :class="{ 'text-white': highlightedIndex === index, 'text-gray-900': highlightedIndex !== index }">
                                                            {{ result.full_name }}
                                                        </div>
                                                        <div class="text-sm" :class="{ 'text-gray-200': highlightedIndex === index, 'text-gray-600': highlightedIndex !== index }">
                                                            {{ result.document_number }}
                                                        </div>
                                                    </div>
                                                    <div class="text-right">
                                                        <div class="flex items-center justify-end gap-2">
                                                            <div class="text-sm font-medium" :class="{ 'text-gray-200': highlightedIndex === index, 'text-[#007e93]': highlightedIndex !== index }">
                                                                {{ result.program_code }}
                                                            </div>
                                                            <span
                                                                :class="[
                                                                    'inline-flex px-1.5 py-0.5 text-[9px] font-semibold rounded-full',
                                                                    result.program_active
                                                                        ? 'bg-green-100 text-green-800'
                                                                        : 'bg-red-100 text-red-800'
                                                                ]"
                                                            >
                                                                {{ result.program_active ? 'Activo' : 'Inactivo' }}
                                                            </span>
                                                        </div>
                                                        <div class="text-xs" :class="{ 'text-gray-300': highlightedIndex === index, 'text-gray-500': highlightedIndex !== index }">
                                                            {{ result.program_name }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Mensaje de no resultados -->
                                        <div
                                            v-if="showDropdown && searchQuery.length >= 2 && searchResults.length === 0 && !isSearching"
                                            class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg p-4 text-center text-gray-500"
                                        >
                                            No se encontraron participantes inscritos
                                        </div>
                                    </div>
                                    <span v-if="errors.program_id" class="text-red-500 text-sm mt-1 block">{{ errors.program_id }}</span>
                                    <span v-if="errors.participant_id" class="text-red-500 text-sm mt-1 block">{{ errors.participant_id }}</span>
                                </div>

                                <!-- Participante Seleccionado -->
                                <div
                                    v-if="selectedEnrollment"
                                    class="mb-6 p-4 bg-green-50 rounded-lg border border-green-200"
                                >
                                    <h4 class="text-md font-semibold text-green-800 mb-3 flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Participante Seleccionado
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <span class="text-sm text-gray-600">Nombre:</span>
                                            <p class="font-semibold text-gray-800">{{ selectedEnrollment.full_name }}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">RUT/Documento:</span>
                                            <p class="font-semibold text-gray-800">{{ selectedEnrollment.document_number }}</p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600">Programa:</span>
                                            <p class="font-semibold text-[#007e93] flex items-center gap-2">
                                                {{ selectedEnrollment.program_code }} - {{ selectedEnrollment.program_name }}
                                                <span
                                                    :class="[
                                                        'inline-flex px-2 py-0.5 text-[10px] font-semibold rounded-full',
                                                        selectedEnrollment.program_active
                                                            ? 'bg-green-100 text-green-800'
                                                            : 'bg-red-100 text-red-800'
                                                    ]"
                                                >
                                                    {{ selectedEnrollment.program_active ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Información del Estado de Pagos del Participante -->
                                <div
                                    v-if="isLoadingParticipantStatus"
                                    class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200"
                                >
                                    <div class="flex items-center justify-center">
                                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                                        <span class="ml-2 text-blue-600">Cargando estado de pagos...</span>
                                    </div>
                                </div>
                                
                                <div
                                    v-else-if="participantPaymentStatus"
                                    class="mb-6 p-4 bg-gray-50 rounded-lg"
                                >
                                    <h4
                                        class="text-md font-semibold text-gray-800 mb-3"
                                    >
                                        Estado de Pagos del Participante
                                    </h4>
                                    <div
                                        class="grid grid-cols-1 md:grid-cols-4 gap-4"
                                    >
                                        <div>
                                            <span class="text-sm text-gray-600"
                                                >Monto Total:</span
                                            >
                                            <p
                                                class="font-semibold text-gray-800"
                                            >
                                                ${{
                                                    formatPrice(
                                                        participantPaymentStatus
                                                            .payment_info
                                                            .total_amount
                                                    )
                                                }}
                                            </p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600"
                                                >Monto Pagado:</span
                                            >
                                            <p
                                                class="font-semibold text-green-600"
                                            >
                                                ${{
                                                    formatPrice(
                                                        participantPaymentStatus
                                                            .payment_info
                                                            .paid_amount
                                                    )
                                                }}
                                            </p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600"
                                                >Saldo Pendiente:</span
                                            >
                                            <p
                                                class="font-semibold text-red-600"
                                            >
                                                ${{
                                                    formatPrice(
                                                        participantPaymentStatus
                                                            .payment_info
                                                            .balance
                                                    )
                                                }}
                                            </p>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-600"
                                                >Cuotas Pagadas:</span
                                            >
                                            <p
                                                class="font-semibold text-blue-600"
                                            >
                                                {{
                                                    participantPaymentStatus
                                                        .payment_info
                                                        .installments_summary
                                                }}
                                                cuotas
                                            </p>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <span class="text-sm text-gray-600"
                                            >Progreso de Pago:</span
                                        >
                                        <div
                                            class="w-full bg-gray-200 rounded-full h-2.5 mt-1"
                                        >
                                            <div
                                                class="bg-green-600 h-2.5 rounded-full transition-all duration-300"
                                                :style="{
                                                    width:
                                                        participantPaymentStatus
                                                            .payment_info
                                                            .payment_percentage +
                                                        '%',
                                                }"
                                            ></div>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1">
                                            {{
                                                participantPaymentStatus
                                                    .payment_info
                                                    .payment_percentage
                                            }}% completado
                                        </p>
                                    </div>
                                    <div class="mt-3">
                                        <span class="text-sm text-gray-600"
                                            >Estado:</span
                                        >
                                        <span
                                            class="ml-2 px-2 py-1 text-xs font-medium rounded-full"
                                            :class="
                                                getPaymentStatusClass(
                                                    participantPaymentStatus
                                                        .payment_info
                                                        .payment_status
                                                )
                                            "
                                        >
                                            {{
                                                getPaymentStatusLabel(
                                                    participantPaymentStatus
                                                        .payment_info
                                                        .payment_status
                                                )
                                            }}
                                        </span>
                                        <div class="mt-1 text-sm text-gray-600">
                                            {{
                                                getDetailedPaymentStatus(
                                                    participantPaymentStatus.payment_info
                                                )
                                            }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Información del Pago Offline -->
                                <div
                                    class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200"
                                >
                                    <h4
                                        class="text-md font-semibold text-blue-800 mb-3"
                                    >
                                        Datos del Pago Offline
                                    </h4>

                                    <!-- Monto del Pago -->
                                    <div class="mb-4">
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-2"
                                        >
                                            Monto (CLP) *
                                        </label>
                                        <input
                                            v-model="form.amount"
                                            type="number"
                                            min="0"
                                            step="1"
                                            placeholder="0"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                            :class="{
                                                'border-red-500': errors.amount,
                                            }"
                                        />
                                        <span
                                            v-if="errors.amount"
                                            class="text-red-500 text-sm mt-1"
                                            >{{ errors.amount }}</span
                                        >
                                    </div>

                                    <!-- Tipo de Pago Offline -->
                                    <div class="mb-4">
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-2"
                                        >
                                            Tipo de Pago Offline *
                                        </label>
                                        <select
                                            v-model="
                                                form.presential_payment_type
                                            "
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                            :class="{
                                                'border-red-500':
                                                    errors.presential_payment_type,
                                            }"
                                        >
                                            <option value="">
                                                Seleccione el tipo de pago
                                            </option>
                                            <option
                                                v-for="option in paymentTypeOptions"
                                                :key="option.report_code"
                                                :value="option.report_code"
                                            >
                                                {{ option.label }}
                                            </option>
                                        </select>
                                        <span
                                            v-if="
                                                errors.presential_payment_type
                                            "
                                            class="text-red-500 text-sm mt-1"
                                            >{{
                                                errors.presential_payment_type
                                            }}</span
                                        >
                                    </div>

                                    <!-- Código de Pago/Boleta/Factura -->
                                    <div class="mb-4">
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-2"
                                        >
                                            Código de Pago/Boleta/Factura *
                                        </label>
                                        <input
                                            v-model="form.payment_code"
                                            type="text"
                                            placeholder="Ej: B001-2024, F2024-001, P2024-001"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                            :class="{
                                                'border-red-500':
                                                    errors.payment_code,
                                            }"
                                        />
                                        <p class="text-sm text-gray-500 mt-1">
                                            Ingrese el código de la boleta,
                                            factura o comprobante de pago
                                            presencial
                                        </p>
                                        <span
                                            v-if="errors.payment_code"
                                            class="text-red-500 text-sm mt-1"
                                            >{{ errors.payment_code }}</span
                                        >
                                    </div>

                                    <!-- Fecha de Transacción y Código de Autorización -->
                                    <div
                                        class="grid grid-cols-1 md:grid-cols-2 gap-6"
                                    >
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2"
                                            >
                                                Fecha de Transacción *
                                            </label>
                                            <input
                                                v-model="form.transaction_date"
                                                type="datetime-local"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                                :class="{
                                                    'border-red-500':
                                                        errors.transaction_date,
                                                }"
                                            />
                                            <span
                                                v-if="errors.transaction_date"
                                                class="text-red-500 text-sm mt-1"
                                                >{{
                                                    errors.transaction_date
                                                }}</span
                                            >
                                        </div>

                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2"
                                            >
                                                Código de Autorización
                                            </label>
                                            <input
                                                v-model="
                                                    form.authorization_code
                                                "
                                                type="text"
                                                placeholder="Código de autorización (opcional)"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                                :class="{
                                                    'border-red-500':
                                                        errors.authorization_code,
                                                }"
                                            />
                                            <span
                                                v-if="errors.authorization_code"
                                                class="text-red-500 text-sm mt-1"
                                                >{{
                                                    errors.authorization_code
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </div>

                                <!-- Notas -->
                                <div class="mb-6">
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                    >
                                        Notas
                                    </label>
                                    <textarea
                                        v-model="form.notes"
                                        rows="3"
                                        placeholder="Notas adicionales sobre el pago offline..."
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#007e93] focus:border-transparent"
                                        :class="{
                                            'border-red-500': errors.notes,
                                        }"
                                    ></textarea>
                                    <span
                                        v-if="errors.notes"
                                        class="text-red-500 text-sm mt-1"
                                        >{{ errors.notes }}</span
                                    >
                                </div>
                            </div>

                            <!-- Botones -->
                            <div
                                class="flex justify-end space-x-3 pt-6 border-t border-gray-200"
                            >
                                <Link
                                    :href="route('admin.payments.index')"
                                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200"
                                >
                                    Cancelar
                                </Link>
                                <button
                                    type="button"
                                    @click="showConfirmationModal"
                                    :disabled="
                                        form.processing || !isBuyerFormValid
                                    "
                                    class="bg-[#007e93] hover:bg-[#006b7a] disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200"
                                >
                                    {{
                                        form.processing
                                            ? "Registrando..."
                                            : "Registrar Pago"
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Confirmación -->
        <div
            v-if="isConfirmModalOpen"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Overlay -->
                <div
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                    @click="closeConfirmationModal"
                ></div>

                <!-- Centrar modal -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Confirmar Registro de Pago
                                </h3>
                                <div class="mt-4">
                                    <p class="text-sm text-gray-500 mb-4">
                                        Por favor, revise los datos del pago antes de confirmar:
                                    </p>

                                    <!-- Resumen del pago -->
                                    <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">Participante:</span>
                                            <span class="text-sm font-semibold text-gray-900">{{ selectedEnrollment?.full_name }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">Programa:</span>
                                            <span class="text-sm font-semibold text-[#007e93]">{{ selectedEnrollment?.program_code }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">Monto:</span>
                                            <span class="text-sm font-bold text-green-600">${{ formatPrice(parseFloat(form.amount) || 0) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">Tipo de Pago:</span>
                                            <span class="text-sm font-semibold text-gray-900">{{ getPaymentTypeLabel(form.presential_payment_type) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">Código de Pago:</span>
                                            <span class="text-sm font-semibold text-gray-900">{{ form.payment_code }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">Fecha:</span>
                                            <span class="text-sm font-semibold text-gray-900">{{ formatTransactionDate(form.transaction_date) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">Pagador:</span>
                                            <span class="text-sm font-semibold text-gray-900">{{ buyerForm.fullName }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">RUT/Doc:</span>
                                            <span class="text-sm font-semibold text-gray-900">{{ buyerForm.documentNumber }}</span>
                                        </div>
                                    </div>

                                    <!-- Aviso de generación de boleta BSale -->
                                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                        <div class="flex items-start">
                                            <svg class="h-5 w-5 text-yellow-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                            <div class="ml-3">
                                                <h4 class="text-sm font-medium text-yellow-800">
                                                    Generación de Boleta Electrónica
                                                </h4>
                                                <p class="text-sm text-yellow-700 mt-1">
                                                    Al confirmar, se generará automáticamente una <strong>boleta electrónica en BSale</strong> para este pago (si aplica según el año del programa). El número de boleta se guardará en el sistema.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            type="button"
                            @click="confirmAndSubmit"
                            :disabled="isSubmitting"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-[#007e93] text-base font-medium text-white hover:bg-[#006b7a] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#007e93] sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="isSubmitting" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Procesando...
                            </span>
                            <span v-else>Confirmar y Registrar</span>
                        </button>
                        <button
                            type="button"
                            @click="closeConfirmationModal"
                            :disabled="isSubmitting"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                        >
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

const props = defineProps({
    programs: {
        type: Array,
        default: () => [],
    },
    documentTypes: {
        type: Array,
        default: () => [],
    },
    paymentTypeOptions: {
        type: Array,
        default: () => [],
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

// Formulario de datos del pagador (simplificado)
const buyerForm = reactive({
    fullName: "",
    documentType: "",
    documentNumber: "",
    email: "",
});

// Formulario de información del pago
const form = useForm({
    program_id: "",
    participant_id: "",
    amount: "",
    presential_payment_type: "", // Nuevo campo para tipo de pago presencial
    payment_code: "", // Código de boleta/factura
    transaction_date: new Date()
        .toLocaleString("sv-SE", { timeZone: "America/Santiago" })
        .slice(0, 16),
    authorization_code: "",
    notes: "",
});

const availableParticipants = ref([]);
const participantPaymentStatus = ref(null);
const isLoadingParticipantStatus = ref(false);
const isLoadingFrequentClient = ref(false);
const errorAlert = ref(null);

// Variables para el modal de confirmación
const isConfirmModalOpen = ref(false);
const isSubmitting = ref(false);

// Variables para el buscador de participantes inscritos
const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);
const showDropdown = ref(false);
const highlightedIndex = ref(0);
const selectedEnrollment = ref(null);
let searchTimeout = null;

const rutValidation = reactive({
    isValid: null,
    message: "",
});

// Computed properties
const isRutDocument = computed(() => {
    if (!buyerForm.documentType) return false;
    const selectedDocType = props.documentTypes.find(
        (doc) => doc.id == buyerForm.documentType
    );
    return selectedDocType && selectedDocType.name.toLowerCase() === "rut";
});

const isBuyerFormValid = computed(() => {
    const validations = {
        fullName: buyerForm.fullName.trim() !== "",
        documentType: buyerForm.documentType !== "",
        documentNumber: buyerForm.documentNumber.trim() !== "",
        // Email es opcional para pagos presenciales
    };

    const basicValidation = Object.values(validations).every((v) => v === true);
    const rutOk = isRutDocument.value ? rutValidation.isValid === true : true;

    // Validar también los campos del formulario de pago
    const paymentValidations = {
        program_id: form.program_id !== "",
        participant_id: form.participant_id !== "",
        amount: form.amount !== "" && parseFloat(form.amount) > 0,
        presential_payment_type: form.presential_payment_type !== "",
        payment_code: form.payment_code.trim() !== "",
        transaction_date: form.transaction_date !== "",
    };

    const paymentValidation = Object.values(paymentValidations).every(
        (v) => v === true
    );

    return basicValidation && rutOk && paymentValidation;
});

// Helper function para obtener el token CSRF
const getCsrfToken = () => {
    const token = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
    
    if (!token) {
        console.error("Token CSRF no encontrado en el documento");
    }
    
    return token;
};

// Methods
const getDocumentLabel = () => {
    if (!buyerForm.documentType) return "Número de documento";

    const selectedDocType = props.documentTypes.find(
        (doc) => doc.id == buyerForm.documentType
    );

    return selectedDocType ? selectedDocType.name : "Número de documento";
};

const getDocumentPlaceholder = () => {
    if (!buyerForm.documentType) return "Ingresa tu número de documento";

    const selectedDocType = props.documentTypes.find(
        (doc) => doc.id == buyerForm.documentType
    );

    if (!selectedDocType) return "Ingresa tu número de documento";

    switch (selectedDocType.name.toLowerCase()) {
        case "rut":
            return "Ej: 12.345.678-9";
        case "pasaporte":
            return "Ej: A12345678";
        default:
            return "Ingresa tu número de documento";
    }
};

const getDocumentTypeId = (name) => {
    const docType = props.documentTypes.find(
        (doc) => doc.name.toLowerCase() === name.toLowerCase()
    );
    return docType ? docType.id : "";
};

const handleDocumentInput = () => {
    if (isRutDocument.value) {
        formatRut();
    }
};

const handleDocumentBlur = () => {
    // Validar documento si es RUT
    if (isRutDocument.value) {
        validateDocument();
    }

    // Buscar cliente frecuente si hay tipo de documento y número
    if (buyerForm.documentType && buyerForm.documentNumber.trim()) {
        searchFrequentClient();
    }
};

const searchFrequentClient = async () => {
    if (isLoadingFrequentClient.value) return;
    
    try {
        isLoadingFrequentClient.value = true;
        const csrfToken = getCsrfToken();

        if (!csrfToken) {
            return;
        }

        const response = await fetch("/frequent-clients/find-by-document", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify({
                document_id: buyerForm.documentType,
                document: buyerForm.documentNumber.trim(),
            }),
        });

        if (!response.ok) {
            console.error(`Error en la petición: ${response.status} ${response.statusText}`);
            return;
        }

        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            console.error("La respuesta no es JSON válido");
            return;
        }

        const result = await response.json();

        if (result.success && result.data) {
            // Autocompletar el formulario con los datos del cliente frecuente
            autocompleteForm(result.data);
        }
    } catch (error) {
        console.error("Error buscando cliente frecuente:", error);
    } finally {
        isLoadingFrequentClient.value = false;
    }
};

const autocompleteForm = (clientData) => {
    // Autocompletar los campos del formulario simplificado
    buyerForm.fullName = clientData.full_name;
    buyerForm.email = clientData.email;
};

const validateDocument = () => {
    if (isRutDocument.value) {
        validateRut();
    }
};

const formatRut = () => {
    // Remover todos los caracteres no numéricos excepto K
    let rut = buyerForm.documentNumber.replace(/[^0-9kK]/g, "");

    if (rut.length > 0) {
        rut = rut.toUpperCase();

        // Si tiene más de 1 carácter, separar cuerpo y dígito verificador
        if (rut.length > 1) {
            const body = rut.slice(0, -1);
            const dv = rut.slice(-1);

            // Formatear el cuerpo con puntos
            let formattedBody = "";
            for (let i = body.length - 1, j = 0; i >= 0; i--, j++) {
                if (j > 0 && j % 3 === 0) {
                    formattedBody = "." + formattedBody;
                }
                formattedBody = body[i] + formattedBody;
            }

            // Combinar cuerpo formateado con dígito verificador
            buyerForm.documentNumber = `${formattedBody}-${dv}`;
        } else {
            buyerForm.documentNumber = rut;
        }
    }

    // Validar el RUT después de formatearlo
    validateRut();
};

const validateRut = () => {
    const rut = buyerForm.documentNumber.replace(/\./g, "").replace(/-/g, "");

    if (rut.length === 0) {
        rutValidation.isValid = null;
        rutValidation.message = "";
        return;
    }

    // Validar formato básico
    if (!/^[0-9]+[0-9kK]$/.test(rut)) {
        rutValidation.isValid = false;
        rutValidation.message = "Formato de RUT inválido";
        return;
    }

    // Separar cuerpo y dígito verificador
    const body = rut.slice(0, -1);
    const dv = rut.slice(-1).toUpperCase();

    // Validar que el cuerpo tenga al menos 7 dígitos
    if (body.length < 7) {
        rutValidation.isValid = false;
        rutValidation.message = "RUT debe tener al menos 7 dígitos";
        return;
    }

    // Calcular dígito verificador
    const dvCalculado = calculateDv(body);

    // Comparar dígitos verificadores
    rutValidation.isValid = dv === dvCalculado;
    rutValidation.message = rutValidation.isValid
        ? "RUT válido"
        : "RUT inválido";
};

const calculateDv = (body) => {
    let sum = 0;
    let factor = 2;
    for (let i = body.length - 1; i >= 0; i--) {
        sum += body[i] * factor;
        factor = factor === 7 ? 2 : factor + 1;
    }
    const dv = 11 - (sum % 11);
    return dv === 10 ? "K" : dv === 11 ? "0" : dv.toString();
};

// Métodos del formulario de pago
const loadParticipants = () => {
    if (!form.program_id) {
        availableParticipants.value = [];
        form.participant_id = "";
        return;
    }

    const program = props.programs.find((p) => p.id == form.program_id);
    if (program && program.course && program.course.participants) {
        availableParticipants.value = program.course.participants;
    } else {
        availableParticipants.value = [];
    }
    form.participant_id = "";
};

const loadParticipantPaymentStatus = async () => {
    if (!form.program_id || !form.participant_id) {
        participantPaymentStatus.value = null;
        return;
    }

    if (isLoadingParticipantStatus.value) return;

    try {
        isLoadingParticipantStatus.value = true;
        const csrfToken = getCsrfToken();

        if (!csrfToken) {
            participantPaymentStatus.value = null;
            return;
        }

        const response = await fetch("/admin/payments/participant-status", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify({
                program_id: form.program_id,
                participant_id: form.participant_id,
            }),
        });

        if (!response.ok) {
            console.error(
                `Error al cargar el estado de pago del participante: ${response.status} ${response.statusText}`
            );
            participantPaymentStatus.value = null;
            return;
        }

        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
            console.error("La respuesta no es JSON válido");
            participantPaymentStatus.value = null;
            return;
        }

        const result = await response.json();
        if (result.success) {
            participantPaymentStatus.value = result.data;
        } else {
            console.error(
                "Error al cargar el estado de pago:",
                result.message
            );
            participantPaymentStatus.value = null;
        }
    } catch (error) {
        console.error(
            "Error en la solicitud de estado de pago del participante:",
            error
        );
        participantPaymentStatus.value = null;
    } finally {
        isLoadingParticipantStatus.value = false;
    }
};

const formatPrice = (amount) => {
    return amount.toLocaleString("es-CL", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });
};

const getPaymentStatusClass = (status) => {
    switch (status) {
        case "no_enrolled":
            return "bg-gray-100 text-gray-800";
        case "no_payments":
            return "bg-red-100 text-red-800";
        case "partial_payments":
            return "bg-yellow-100 text-yellow-800";
        case "fully_paid":
            return "bg-green-100 text-green-800";
        default:
            return "bg-gray-100 text-gray-800";
    }
};

const getPaymentStatusLabel = (status) => {
    switch (status) {
        case "no_enrolled":
            return "No inscrito";
        case "no_payments":
            return "Sin pagos ejecutados";
        case "partial_payments":
            return "Pagos parciales";
        case "fully_paid":
            return "Completamente pagado";
        default:
            return status;
    }
};

const getDetailedPaymentStatus = (paymentInfo) => {
    if (!paymentInfo) return "";

    switch (paymentInfo.payment_status) {
        case "no_enrolled":
            return "No inscrito en el programa";
        case "no_payments":
            return "Inscrito pero sin pagos ejecutados";
        case "partial_payments":
            return `Pagos parciales (${paymentInfo.installments_summary} cuotas pagadas)`;
        case "fully_paid":
            return `Completamente pagado (${paymentInfo.installments_summary} cuotas pagadas)`;
        default:
            return paymentInfo.payment_status;
    }
};

// Formatear nombre del participante (Primer Nombre Primer Apellido en Title Case)
const formatParticipantName = (participant) => {
    const firstName = participant.first_name
        ? participant.first_name.charAt(0).toUpperCase() +
          participant.first_name.slice(1).toLowerCase()
        : "";
    const lastName = participant.first_last_name
        ? participant.first_last_name.charAt(0).toUpperCase() +
          participant.first_last_name.slice(1).toLowerCase()
        : "";

    // Si no hay apellido, solo mostrar nombre
    if (!lastName) {
        return firstName;
    }

    return `${firstName} ${lastName}`.trim();
};

// Formatear documento del participante (RUT formateado si es RUT)
const formatParticipantDocument = (participant) => {
    if (!participant.document_number) return "";

    // Verificar si es RUT (formato chileno)
    if (
        /^[0-9]{7,8}[0-9kK]$/.test(
            participant.document_number.replace(/[.-]/g, "")
        )
    ) {
        // Formatear RUT con puntos y guión
        const rut = participant.document_number.replace(/[.-]/g, "");
        const body = rut.slice(0, -1);
        const dv = rut.slice(-1).toUpperCase();

        let formattedBody = "";
        for (let i = body.length - 1, j = 0; i >= 0; i--, j++) {
            if (j > 0 && j % 3 === 0) {
                formattedBody = "." + formattedBody;
            }
            formattedBody = body[i] + formattedBody;
        }

        return `${formattedBody}-${dv}`;
    }

    // Si no es RUT, devolver tal como está
    return participant.document_number;
};

// ========================================
// Funciones del buscador de participantes
// ========================================

const debounceSearch = () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    // Si ya hay una selección y el usuario está editando, limpiarla
    if (selectedEnrollment.value) {
        clearSelection();
    }

    searchTimeout = setTimeout(() => {
        performSearch();
    }, 300);
};

const performSearch = async () => {
    const query = searchQuery.value.trim();

    if (query.length < 2) {
        searchResults.value = [];
        showDropdown.value = false;
        return;
    }

    try {
        isSearching.value = true;
        showDropdown.value = true;
        highlightedIndex.value = 0;

        const response = await fetch(`/admin/payments/presential/search-participants?search=${encodeURIComponent(query)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            console.error('Error en la búsqueda:', response.status);
            searchResults.value = [];
            return;
        }

        const result = await response.json();

        if (result.success) {
            searchResults.value = result.data;
        } else {
            searchResults.value = [];
        }
    } catch (error) {
        console.error('Error buscando participantes:', error);
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
};

const navigateDropdown = (direction) => {
    if (searchResults.value.length === 0) return;

    highlightedIndex.value += direction;

    if (highlightedIndex.value < 0) {
        highlightedIndex.value = searchResults.value.length - 1;
    } else if (highlightedIndex.value >= searchResults.value.length) {
        highlightedIndex.value = 0;
    }
};

const selectHighlighted = () => {
    if (searchResults.value.length > 0 && highlightedIndex.value >= 0) {
        selectEnrollment(searchResults.value[highlightedIndex.value]);
    }
};

const selectEnrollment = (enrollment) => {
    selectedEnrollment.value = enrollment;
    form.program_id = enrollment.program_course_id;
    form.participant_id = enrollment.participant_id;
    searchQuery.value = enrollment.label;
    showDropdown.value = false;
    searchResults.value = [];

    // Cargar estado de pagos del participante
    loadParticipantPaymentStatus();
};

const clearSelection = () => {
    selectedEnrollment.value = null;
    form.program_id = '';
    form.participant_id = '';
    searchQuery.value = '';
    searchResults.value = [];
    participantPaymentStatus.value = null;
};

// Cerrar dropdown al hacer clic fuera
const handleClickOutside = (event) => {
    const searchContainer = event.target.closest('[data-search-container]');
    if (!searchContainer) {
        showDropdown.value = false;
    }
};

// ========================================
// Funciones del modal de confirmación
// ========================================

const showConfirmationModal = () => {
    isConfirmModalOpen.value = true;
};

const closeConfirmationModal = () => {
    if (!isSubmitting.value) {
        isConfirmModalOpen.value = false;
    }
};

const getPaymentTypeLabel = (code) => {
    const option = props.paymentTypeOptions.find(opt => opt.report_code === code);
    return option ? option.label : code;
};

const formatTransactionDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleString('es-CL', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const confirmAndSubmit = () => {
    isSubmitting.value = true;
    submit();
};

const submit = () => {
    // Combinar los datos del pagador con los datos del pago
    const combinedData = {
        ...form.data(),
        status: "completed", // Siempre completado para pagos presenciales
        buyer_full_name: buyerForm.fullName,
        buyer_document_type: buyerForm.documentType,
        buyer_document_number: buyerForm.documentNumber,
        buyer_email: buyerForm.email,
    };

    // Crear un nuevo formulario con los datos combinados
    const submitForm = useForm(combinedData);
    submitForm.post(route("admin.payments.presential.store"), {
        preserveScroll: false,
        onFinish: () => {
            isSubmitting.value = false;
            isConfirmModalOpen.value = false;

            // Si hay errores, hacer scroll hacia el componente de error
            if (Object.keys(submitForm.errors).length > 0) {
                nextTick(() => {
                    setTimeout(() => {
                        // Hacer scroll al elemento de error si existe
                        if (errorAlert.value) {
                            errorAlert.value.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        } else {
                            // Fallback: scroll al top de la página
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        }
                    }, 150);
                });
            }
        }
    });
};

// Lifecycle
onMounted(() => {
    // Establecer RUT como tipo de documento por defecto
    buyerForm.documentType = getDocumentTypeId("RUT");

    // Agregar listener para cerrar dropdown al hacer clic fuera
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    // Limpiar listener
    document.removeEventListener('click', handleClickOutside);

    // Limpiar timeout de búsqueda
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
});
</script>

<style scoped>
/* Estilos para los radio buttons personalizados */
input[type="radio"]:checked + div {
    border-color: #fbbd51;
    background-color: #fbbd51;
}

/* Estilos para el checkbox personalizado */
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
