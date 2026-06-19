<template>
    <div v-if="show && payment" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-7xl w-full max-h-[95vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-gray-50">
                <h2 class="text-2xl font-bold text-gray-900">Detalles de la Transacción #{{ payment.id }}</h2>
                <button
                    @click="$emit('close')"
                    class="text-gray-400 hover:text-gray-600 transition-colors p-2 hover:bg-gray-200 rounded-full"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div class="p-8">
                <!-- Grid principal con 2 columnas -->
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                    
                    <!-- Columna Izquierda -->
                    <div class="space-y-8">
                        <!-- Información del Pago -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                                Información del Pago
                            </h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">ID del Pago:</span>
                                    <span class="font-semibold text-gray-900">#{{ payment.id }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Orden:</span>
                                    <span class="font-semibold text-gray-900">{{ payment.is_installment ? payment.order?.order_number : (payment.buy_order || 'N/A') }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Nro. Documento:</span>
                                    <span class="font-semibold text-gray-900">{{ getDocumentNumber(payment) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Monto:</span>
                                    <span class="font-bold text-green-600 text-lg">${{ formatPrice(payment.amount) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Estado:</span>
                                    <span :class="getStatusClass(payment.status)" class="px-3 py-1 text-sm font-semibold rounded-full">
                                        {{ getStatusLabel(payment.status, payment) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Gateway:</span>
                                    <span class="font-semibold text-gray-900">{{ payment.payment_gateway?.name || 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Método de Pago:</span>
                                    <span class="font-semibold text-gray-900">{{ payment.is_installment ? `VirtualPos - Cuota ${payment.installment_number}` : (payment.payment_option?.label || 'N/A') }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Fecha de Transacción:</span>
                                    <span class="font-semibold text-gray-900">{{ getTransactionDate(payment) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Fecha de Creación:</span>
                                    <span class="font-semibold text-gray-900">{{ formatDate(payment.created_at) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-gray-600 font-medium">Email Enviado:</span>
                                    <span class="font-semibold" :class="payment.email_sent ? 'text-green-600' : 'text-red-600'">
                                        {{ payment.email_sent ? 'Sí' : 'No' }}
                                    </span>
                                </div>
                                <!-- Botón Reconfirmar Pago -->
                                <div v-if="canReconfirm(payment)" class="pt-3">
                                    <button
                                        @click="reconfirmPayment(payment)"
                                        :disabled="reconfirming"
                                        class="w-full flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                                    >
                                        <svg v-if="reconfirming" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        {{ reconfirming ? 'Reconfirmando...' : 'Reconfirmar Pago' }}
                                    </button>
                                    <p v-if="reconfirmMessage" :class="reconfirmSuccess ? 'text-green-600' : 'text-red-600'" class="text-sm mt-2 text-center">
                                        {{ reconfirmMessage }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Información del Participante -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Información del Participante
                            </h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Nombre:</span>
                                    <span class="font-semibold text-gray-900">{{ getParticipantName(payment) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-gray-600 font-medium">RUT / PASAPORTE:</span>
                                    <span class="font-semibold text-gray-900">{{ formatDocument(payment.order?.participant?.document_number, payment.order?.participant?.document_type) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Información del Programa -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Información del Programa
                            </h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Código:</span>
                                    <span class="font-semibold text-gray-900">{{ getProgramCode(payment) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Programa:</span>
                                    <span class="font-semibold text-gray-900 text-right max-w-xs">{{ getProgramName(payment) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-gray-600 font-medium">Institución:</span>
                                    <span class="font-semibold text-gray-900">{{ getInstitutionName(payment) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha -->
                    <div class="space-y-8">
                        <!-- Información del Pagador -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                Información del Pagador
                            </h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Nombre:</span>
                                    <span class="font-semibold text-gray-900">{{ getBuyerName(payment) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Email:</span>
                                    <span class="font-semibold text-gray-900">{{ getBuyerEmail(payment) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Teléfono:</span>
                                    <span class="font-semibold text-gray-900">{{ getBuyerPhone(payment) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">RUT/Documento:</span>
                                    <span class="font-semibold text-gray-900">{{ getBuyerDocument(payment) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">País:</span>
                                    <span class="font-semibold text-gray-900">{{ getBuyerCountry(payment) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Región:</span>
                                    <span class="font-semibold text-gray-900">{{ getBuyerRegion(payment) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-gray-600 font-medium">Ciudad:</span>
                                    <span class="font-semibold text-gray-900">{{ getBuyerCity(payment) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Información de Boleta BSale -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Boleta BSale
                            </h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Estado:</span>
                                    <span
                                        :class="getBsaleStatusClass(payment)"
                                        class="px-3 py-1 text-sm font-semibold rounded-full"
                                    >
                                        {{ getBsaleStatusLabel(payment) }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">N° Boleta:</span>
                                    <span class="font-semibold" :class="payment.bsale_number ? 'text-green-600' : 'text-gray-400'">
                                        {{ payment.bsale_number || 'Sin generar' }}
                                    </span>
                                </div>
                                <div v-if="payment.bsale_token" class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Ver Boleta:</span>
                                    <a
                                        :href="'https://app2.bsale.cl/view/' + payment.bsale_token"
                                        target="_blank"
                                        class="text-blue-600 hover:text-blue-800 font-semibold flex items-center"
                                    >
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                        Abrir en BSale
                                    </a>
                                </div>
                                <div v-if="payment.bsale_error" class="py-2">
                                    <span class="text-gray-600 font-medium block mb-2">Error:</span>
                                    <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                                        <p class="text-red-700 text-sm">{{ payment.bsale_error }}</p>
                                        <p v-if="payment.bsale_error_code" class="text-red-500 text-xs mt-1">
                                            Código: {{ payment.bsale_error_code }}
                                        </p>
                                    </div>
                                </div>
                                <!-- Botón Generar Boleta -->
                                <div v-if="canRetryBsale(payment)" class="pt-3">
                                    <button
                                        @click="retryBsale(payment)"
                                        :disabled="retryingBsale"
                                        class="w-full flex items-center justify-center px-4 py-2 bg-teal-600 hover:bg-teal-700 disabled:bg-teal-300 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                                    >
                                        <svg v-if="retryingBsale" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        {{ retryingBsale ? 'Generando...' : 'Generar Boleta' }}
                                    </button>
                                    <p v-if="bsaleRetryMessage" :class="bsaleRetrySuccess ? 'text-green-600' : 'text-red-600'" class="text-sm mt-2 text-center">
                                        {{ bsaleRetryMessage }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Información de Cuotas -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                                Información de Cuotas
                            </h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Cuota Actual:</span>
                                    <span class="font-semibold text-gray-900">{{ payment.is_installment ? payment.installment_number : (payment.order_detail?.installment_number || 'N/A') }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Total de Cuotas:</span>
                                    <span class="font-semibold text-gray-900">{{ getTotalInstallments(payment) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Monto Base:</span>
                                    <span class="font-semibold text-gray-900">${{ formatPrice(payment.is_installment ? payment.amount : payment.order_detail?.base_amount) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Descuento:</span>
                                    <span class="font-semibold text-gray-900">${{ formatPrice(payment.is_installment ? 0 : payment.order_detail?.discount_amount) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Fecha de Vencimiento:</span>
                                    <span class="font-semibold text-gray-900">{{ payment.is_installment ? formatDate(payment.due_date) : formatDate(payment.order_detail?.due_date) }}</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                    <span class="text-gray-600 font-medium">Pagado:</span>
                                    <span class="font-semibold" :class="(payment.is_installment ? (payment.status === 'completed') : payment.order_detail?.is_paid) ? 'text-green-600' : 'text-red-600'">
                                        {{ (payment.is_installment ? (payment.status === 'completed') : payment.order_detail?.is_paid) ? 'Sí' : 'No' }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-gray-600 font-medium">Fecha de Pago:</span>
                                    <span class="font-semibold text-gray-900">{{ payment.is_installment ? formatDate(payment.paid_at) : formatDate(payment.order_detail?.paid_at) }}</span>
                                </div>
                                <!-- Botón descarga evidencia aceptación T&C (descomentar cuando se solicite)
                                <div v-if="payment.order_detail?.id" class="pt-3">
                                    <a
                                        :href="`/admin/reports/download/terms-acceptance-pdf/${payment.order_detail.id}`"
                                        target="_blank"
                                        class="w-full flex items-center justify-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors duration-200"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Descargar Evidencia T&C
                                    </a>
                                </div>
                                -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Secciones adicionales en ancho completo -->
                <div class="mt-8 space-y-6">
                    <!-- Notas -->
                    <div v-if="getNotes(payment)" class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Notas
                        </h3>
                        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                            <p class="text-gray-700">{{ getNotes(payment) }}</p>
                        </div>
                    </div>

                    <!-- Respuesta del Gateway -->
                    <div v-if="payment.gateway_response && Object.keys(payment.gateway_response).length > 0" class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                            </svg>
                            Respuesta del Gateway
                        </h3>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <pre class="text-sm text-gray-700 whitespace-pre-wrap font-mono">{{ JSON.stringify(payment.gateway_response, null, 2) }}</pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 bg-gray-50">
                <button
                    @click="$emit('close')"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200"
                >
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    payment: {
        type: [Object, null],
        required: false,
        default: null
    },
    show: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close', 'payment-updated']);

const retryingBsale = ref(false);
const bsaleRetryMessage = ref('');
const bsaleRetrySuccess = ref(false);

const reconfirming = ref(false);
const reconfirmMessage = ref('');
const reconfirmSuccess = ref(false);

const canReconfirm = (payment) => {
    if (!payment) return false;
    // Permitimos reconfirmar pendientes Y fallidos: cuando Transbank/VirtualPOS responde
    // E-020 ("no encontrada") por timing en el momento del pago, el sistema marca el
    // payment como failed aunque el cobro sí se haya efectuado. Reconsultar después
    // suele devolver éxito y permite emitir la boleta sin tocar la BD a mano.
    const reconfirmableStatus = payment.status === 'pending' || payment.status === 'failed';
    return reconfirmableStatus && (payment.token || payment.external_payment_id);
};

const reconfirmPayment = async (payment) => {
    reconfirming.value = true;
    reconfirmMessage.value = '';
    reconfirmSuccess.value = false;

    try {
        // Usar axios global (configurado con withXSRFToken=true en bootstrap.js).
        // fetch + meta[csrf-token] fallaba con "CSRF token mismatch" cuando la sesión
        // expiraba con el modal abierto. axios refresca el cookie XSRF automáticamente.
        const { data } = await window.axios.post(`/admin/payments/${payment.id}/reconfirm`);

        if (data.success) {
            reconfirmSuccess.value = true;
            reconfirmMessage.value = data.message || 'Pago reconfirmado exitosamente';
            if (data.payment) {
                Object.assign(payment, data.payment);
            }
            emit('payment-updated', payment);
        } else {
            reconfirmMessage.value = data.message || 'No se pudo reconfirmar el pago';
        }
    } catch (error) {
        reconfirmMessage.value = error.response?.data?.message || 'Error de conexión al reconfirmar el pago';
    } finally {
        reconfirming.value = false;
    }
};

const canRetryBsale = (payment) => {
    if (!payment) return false;
    // Solo para pagos completados sin boleta generada
    const isCompleted = ['completed', 'approved'].includes(payment.status);
    const hasNoBoleta = !payment.bsale_document_id && !payment.bsale_number;
    return isCompleted && hasNoBoleta;
};

const retryBsale = async (payment) => {
    retryingBsale.value = true;
    bsaleRetryMessage.value = '';
    bsaleRetrySuccess.value = false;

    try {
        // Usar axios (mismo motivo que reconfirmPayment: evitar CSRF mismatch).
        const { data } = await window.axios.post(`/admin/payments/${payment.id}/retry-bsale`);

        if (data.success) {
            bsaleRetrySuccess.value = true;
            bsaleRetryMessage.value = `Boleta generada: N° ${data.bsale_number}`;
            // Actualizar datos del payment en el modal
            payment.bsale_document_id = data.bsale_document_id;
            payment.bsale_number = data.bsale_number;
            payment.bsale_token = data.bsale_token;
            emit('payment-updated', payment);
        } else {
            bsaleRetryMessage.value = data.message || 'Error al generar la boleta';
        }
    } catch (error) {
        bsaleRetryMessage.value = error.response?.data?.message || 'Error de conexión al generar la boleta';
    } finally {
        retryingBsale.value = false;
    }
};

const getParticipantName = (payment) => {
    // Si es un installment
    if (payment.is_installment && payment.order?.participant) {
        const p = payment.order.participant;
        return `${p.first_name} ${p.first_last_name}`;
    }
    // Usar el atributo del modelo Payment que ya está construido correctamente
    return payment.participant_name || 'N/A';
};

const getBuyerName = (payment) => {
    // Si es un installment, usar client_data de VirtualPos
    if (payment.is_installment && payment.client_data) {
        const name = payment.client_data.name || '';
        const surname = payment.client_data.surname || '';
        return `${name} ${surname}`.trim() || 'N/A';
    }
    return payment.order_detail?.name || 'N/A';
};

const getBuyerEmail = (payment) => {
    // Si es un installment, usar client_data de VirtualPos
    if (payment.is_installment && payment.client_data) {
        return payment.client_data.email || 'N/A';
    }
    return payment.order_detail?.email || 'N/A';
};

const getBuyerPhone = (payment) => {
    // Si es un installment, usar client_data de VirtualPos
    if (payment.is_installment && payment.client_data) {
        return payment.client_data.phone || 'N/A';
    }
    const code = payment.order_detail?.code_phone || '+56';
    const phone = payment.order_detail?.phone || '';
    return phone ? `${code} ${phone}` : 'N/A';
};

const getBuyerDocument = (payment) => {
    // Si es un installment, usar client_data de VirtualPos
    if (payment.is_installment && payment.client_data) {
        // client_data.rut ya viene formateado desde VirtualPos
        return payment.client_data.rut || payment.client_data.original_document || 'N/A';
    }
    return payment.order_detail?.document_number || 'N/A';
};

const getBuyerCountry = (payment) => {
    // Si es un installment, usar client_data de VirtualPos
    if (payment.is_installment && payment.client_data?.address) {
        const country = payment.client_data.address.country;
        // Convertir código de país a nombre
        return country === 'CL' ? 'Chile' : (country || 'N/A');
    }
    // Si es un objeto con name (relación cargada)
    if (payment.order_detail?.country?.name) {
        return payment.order_detail.country.name;
    }
    // Si es un string (código del país)
    if (typeof payment.order_detail?.country === 'string') {
        return payment.order_detail.country;
    }
    // Si es un número (ID del país)
    if (typeof payment.order_detail?.country === 'number') {
        return `ID: ${payment.order_detail.country}`;
    }
    return 'N/A';
};

const getBuyerRegion = (payment) => {
    // Si es un installment, no hay región en client_data de VirtualPos
    if (payment.is_installment) {
        return 'N/A';
    }
    // Si es un objeto con name (relación cargada)
    if (payment.order_detail?.region?.name) {
        return payment.order_detail.region.name;
    }
    // Si es un string o número (ID de la región)
    if (payment.order_detail?.region) {
        return typeof payment.order_detail.region === 'number'
            ? `ID: ${payment.order_detail.region}`
            : payment.order_detail.region;
    }
    return 'N/A';
};

const getBuyerCity = (payment) => {
    // Si es un installment, usar client_data de VirtualPos
    if (payment.is_installment && payment.client_data?.address) {
        return payment.client_data.address.city || 'N/A';
    }
    // Si es un objeto con name (relación cargada)
    if (payment.order_detail?.city?.name) {
        return payment.order_detail.city.name;
    }
    // Si es un string o número (ID de la ciudad)
    if (payment.order_detail?.city) {
        return typeof payment.order_detail.city === 'number'
            ? `ID: ${payment.order_detail.city}`
            : payment.order_detail.city;
    }
    return 'N/A';
};

const getTotalInstallments = (payment) => {
    // Si es un installment de suscripción, obtener desde installmentPlan
    if (payment.is_installment) {
        // Para installments de suscripción, siempre retornar 'N/A' ya que no tiene cuotas tradicionales
        return 'Suscripción';
    }
    // Obtener desde order que es donde está el total de cuotas
    if (payment.order?.total_installments) {
        return payment.order.total_installments;
    }
    // Fallback al order_detail si no está en order
    if (payment.order_detail?.installments_number) {
        return payment.order_detail.installments_number;
    }
    return 'N/A';
};

const getProgramCode = (payment) => {
    // Primero intentar con program_course (nueva arquitectura)
    if (payment.order?.program_course?.code) {
        return payment.order.program_course.code;
    }
    // Fallback a program (arquitectura antigua)
    if (payment.order?.program?.code) {
        return payment.order.program.code;
    }
    return 'N/A';
};

const getProgramName = (payment) => {
    // Primero intentar con program_course (nueva arquitectura)
    if (payment.order?.program_course?.name) {
        return payment.order.program_course.name;
    }
    // Fallback a program (arquitectura antigua)
    if (payment.order?.program?.name) {
        return payment.order.program.name;
    }
    return 'N/A';
};

const getInstitutionName = (payment) => {
    // Primero intentar con program_course (nueva arquitectura)
    if (payment.order?.program_course?.course?.institution?.name) {
        return payment.order.program_course.course.institution.name;
    }
    // Fallback a program (arquitectura antigua)
    if (payment.order?.program?.course?.institution?.name) {
        return payment.order.program.course.institution.name;
    }
    return 'N/A';
};

const getStatusClass = (status) => {
    const classes = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'completed': 'bg-green-100 text-green-800',
        'failed': 'bg-red-100 text-red-800',
        'authorized': 'bg-blue-100 text-blue-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status, payment = null) => {
    // Si es un installment, usar etiquetas específicas
    if (payment?.is_installment) {
        if (status === 'completed') {
            return 'Pagado';
        } else if (status === 'pending') {
            return 'Pendiente';
        } else if (status === 'failed') {
            return 'No Pagado';
        }
    }

    const labels = {
        'pending': 'Pendiente',
        'completed': 'Completado',
        'failed': 'Fallido',
        'authorized': 'Autorizado'
    };
    return labels[status] || status;
};

const formatPrice = (amount) => {
    return new Intl.NumberFormat('es-CL', { maximumFractionDigits: 0 }).format(amount || 0);
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    try {
        const parsedDate = new Date(date);
        if (isNaN(parsedDate.getTime())) return 'N/A';
        return parsedDate.toLocaleDateString('es-CL', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return 'N/A';
    }
};

const getTransactionDate = (payment) => {
    // Intentar con transaction_date primero
    if (payment.transaction_date) {
        return formatDate(payment.transaction_date);
    }
    // Luego con accounting_date
    if (payment.accounting_date) {
        return formatDate(payment.accounting_date);
    }
    // Para suscripciones, usar paid_at o due_date
    if (payment.paid_at) {
        return formatDate(payment.paid_at);
    }
    // Fallback a created_at
    if (payment.created_at) {
        return formatDate(payment.created_at);
    }
    return 'N/A';
};

/**
 * Formatea documentos según su tipo:
 * - RUT: Aplica formato 12.345.678-9
 * - PASAPORTE: Convierte a uppercase
 * - Fallback: Detecta automáticamente si es RUT por formato
 */
const formatDocument = (documentNumber, documentType) => {
    if (!documentNumber) return 'N/A';
    
    // Si tenemos el tipo de documento, usarlo para determinar el formato
    if (documentType && typeof documentType === 'object' && documentType.name) {
        if (documentType.name === 'RUT') {
            // Formatear como RUT
            const cleanNumber = documentNumber.toString().replace(/\./g, "").replace(/-/g, "");
            const isRut = /^\d{7,8}[\dK]$/.test(cleanNumber);
            
            if (isRut) {
                const body = cleanNumber.slice(0, -1);
                const dv = cleanNumber.slice(-1);
                const withDots = body.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                return `${withDots}-${dv.toUpperCase()}`;
            }
        } else if (documentType.name === 'PASAPORTE') {
            // Para pasaporte, mostrar en uppercase
            return documentNumber.toString().toUpperCase();
        }
    }
    
    // Fallback: detectar automáticamente si es RUT basándose en el formato
    const cleanNumber = documentNumber.toString().replace(/\./g, "").replace(/-/g, "");
    const isRut = /^\d{7,8}[\dK]$/.test(cleanNumber);
    
    if (isRut) {
        // Formatear como RUT
        const body = cleanNumber.slice(0, -1);
        const dv = cleanNumber.slice(-1);
        const withDots = body.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        return `${withDots}-${dv.toUpperCase()}`;
    } else {
        // Para otros documentos, mostrar en uppercase
        return documentNumber.toString().toUpperCase();
    }
};

const getNotes = (payment) => {
    if (payment.gateway_response?.notes) {
        return payment.gateway_response.notes;
    }
    return null;
};

/**
 * Obtiene el número de documento/boleta con la siguiente prioridad:
 * 1. payment_code (Nro. Boleta del Excel en importación masiva)
 * 2. bsale_number (número de boleta generado por BSale)
 * 3. order_id como fallback
 */
const getDocumentNumber = (payment) => {
    // 1. Primero verificar payment_code (Nro. Boleta del Excel)
    if (payment.payment_code) {
        return payment.payment_code;
    }
    // 2. Luego verificar bsale_number (boleta de BSale)
    if (payment.bsale_number) {
        return payment.bsale_number;
    }
    // 3. Fallback al order_id
    if (payment.order_id) {
        return `ORD-${payment.order_id}`;
    }
    return 'N/A';
};

/**
 * Obtiene la etiqueta de estado de la boleta BSale
 */
const getBsaleStatusLabel = (payment) => {
    // Si ya tiene boleta generada
    if (payment.bsale_number || payment.bsale_document_id) {
        return 'Generada';
    }

    // Si tiene error permanente
    if (payment.bsale_error_code) {
        return 'Error';
    }

    // Si el pago no está completado
    const completedStatuses = ['completed', 'approved'];
    if (!completedStatuses.includes(payment.status)) {
        return 'No Aplica';
    }

    // Verificar si es de año posterior
    const programDepartureDate = payment.order?.program_course?.departure_date
        || payment.order?.programCourse?.departure_date;

    if (programDepartureDate) {
        const departureYear = new Date(programDepartureDate).getFullYear();
        const currentYear = new Date().getFullYear();
        if (departureYear > currentYear) {
            return 'Año Posterior';
        }
    }

    return 'Pendiente';
};

/**
 * Obtiene la clase CSS para el estado de la boleta BSale
 */
const getBsaleStatusClass = (payment) => {
    // Si ya tiene boleta generada
    if (payment.bsale_number || payment.bsale_document_id) {
        return 'bg-green-100 text-green-800';
    }

    // Si tiene error permanente
    if (payment.bsale_error_code) {
        return 'bg-red-100 text-red-800';
    }

    // Si el pago no está completado
    const completedStatuses = ['completed', 'approved'];
    if (!completedStatuses.includes(payment.status)) {
        return 'bg-gray-100 text-gray-500';
    }

    // Verificar si es de año posterior
    const programDepartureDate = payment.order?.program_course?.departure_date
        || payment.order?.programCourse?.departure_date;

    if (programDepartureDate) {
        const departureYear = new Date(programDepartureDate).getFullYear();
        const currentYear = new Date().getFullYear();
        if (departureYear > currentYear) {
            return 'bg-gray-100 text-gray-500';
        }
    }

    return 'bg-yellow-100 text-yellow-800';
};
</script>
