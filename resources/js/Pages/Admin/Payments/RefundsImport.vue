<template>
    <AdminLayout>
        <Head title="Importar Devoluciones desde Excel" />

        <!-- Processing Overlay -->
        <div v-if="form.processing" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
            <div class="bg-white rounded-xl p-8 max-w-md w-full mx-4 shadow-2xl">
                <div class="flex flex-col items-center">
                    <!-- Spinner -->
                    <div class="relative">
                        <div class="w-16 h-16 border-4 border-[#e74c3c]/20 rounded-full"></div>
                        <div class="w-16 h-16 border-4 border-[#e74c3c] border-t-transparent rounded-full animate-spin absolute top-0 left-0"></div>
                    </div>

                    <!-- Title -->
                    <h3 class="mt-6 text-xl font-bold text-gray-900">
                        {{ isConfirming ? 'Insertando devoluciones en la base de datos...' : 'Procesando archivo...' }}
                    </h3>

                    <!-- Message -->
                    <p class="mt-2 text-gray-600 text-center">
                        {{ isConfirming ? 'Por favor espera mientras se insertan las devoluciones.' : 'Esto puede tardar varios minutos dependiendo del tamaño del archivo.' }}
                    </p>

                    <!-- File info -->
                    <div v-if="form.file" class="mt-4 px-4 py-2 bg-gray-100 rounded-lg">
                        <p class="text-sm text-gray-700">
                            <span class="font-bold">{{ form.file.name }}</span>
                            <span class="text-gray-500 ml-2">({{ formatFileSize(form.file.size) }})</span>
                        </p>
                    </div>

                    <!-- Warning -->
                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-xs text-yellow-800 text-center">
                            Por favor, no cierres esta ventana ni navegues a otra página mientras se procesa el archivo.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Modal (after import) -->
        <div v-if="showResultsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl w-full max-w-7xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col">
                <!-- Modal Header -->
                <div class="bg-[#e74c3c] text-white px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-2xl font-bold">Resultado de la Importación</h3>
                        <p class="text-sm mt-1 opacity-90">
                            Detalle de las devoluciones procesadas
                        </p>
                    </div>
                    <button @click="closeResults" class="text-white hover:text-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Stats Summary -->
                <div v-if="importResults" class="px-6 py-4 bg-gray-50 border-b">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                            <div class="text-2xl font-bold text-blue-600">{{ importResults.processed }}</div>
                            <div class="text-sm text-gray-600">Total procesadas</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                            <div class="text-2xl font-bold text-green-600">{{ importResults.successful }}</div>
                            <div class="text-sm text-gray-600">Insertadas correctamente</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                            <div class="text-2xl font-bold text-red-600">{{ importResults.errors }}</div>
                            <div class="text-sm text-gray-600">Con errores</div>
                        </div>
                    </div>
                </div>

                <!-- Results Content -->
                <div class="flex-1 overflow-y-auto px-6 py-4">
                    <div v-if="importResults && importResults.details" class="space-y-4">
                        <!-- Success Records -->
                        <div v-if="importSuccessDetails.length > 0" class="bg-white rounded-lg border border-gray-200">
                            <div class="bg-green-50 px-4 py-3 border-b border-green-200">
                                <h4 class="font-bold text-green-800 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Devoluciones Insertadas ({{ importSuccessDetails.length }})
                                </h4>
                            </div>
                            <div class="p-4 max-h-60 overflow-y-auto">
                                <div class="space-y-2">
                                    <div v-for="(detail, index) in importSuccessDetails" :key="'isuccess-' + index"
                                        class="p-3 bg-green-50 rounded border border-green-200 text-sm">
                                        <div class="font-bold text-green-900">
                                            Fila {{ detail.row }}: {{ detail.participant_name }}
                                            — ${{ formatNumber(detail.refund_amount) }}
                                        </div>
                                        <div v-if="detail.warning" class="mt-1 text-yellow-700 text-xs">
                                            {{ detail.warning_message }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Failed Records -->
                        <div v-if="importFailedDetails.length > 0" class="bg-white rounded-lg border border-gray-200">
                            <div class="bg-red-50 px-4 py-3 border-b border-red-200">
                                <h4 class="font-bold text-red-800 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Devoluciones con Error ({{ importFailedDetails.length }})
                                </h4>
                            </div>
                            <div class="p-4 max-h-60 overflow-y-auto">
                                <div class="space-y-2">
                                    <div v-for="(detail, index) in importFailedDetails" :key="'ifailed-' + index"
                                        class="p-3 bg-red-50 rounded border border-red-200 text-sm">
                                        <div class="font-bold text-red-900 mb-1">Fila {{ detail.row }}</div>
                                        <div class="text-red-800">{{ detail.error }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t flex justify-end">
                    <button @click="closeResults"
                        class="px-6 py-2 bg-[#e74c3c] hover:bg-[#c0392b] text-white font-bold rounded-lg transition-colors">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <div v-if="showPreviewModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl w-full max-w-7xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col">
                <!-- Modal Header -->
                <div class="bg-[#e74c3c] text-white px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-2xl font-bold">Vista Previa de Devoluciones</h3>
                        <p class="text-sm mt-1 opacity-90">
                            Revisa los datos antes de confirmar la inserción en la base de datos
                        </p>
                    </div>
                    <button @click="closePreview" class="text-white hover:text-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Stats Summary -->
                <div v-if="previewData" class="px-6 py-4 bg-gray-50 border-b">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                            <div class="text-2xl font-bold text-green-600">{{ previewData.results.successful }}</div>
                            <div class="text-sm text-gray-600">Devoluciones válidas</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                            <div class="text-2xl font-bold text-yellow-600">{{ previewData.results.warnings }}</div>
                            <div class="text-sm text-gray-600">Con advertencias</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                            <div class="text-2xl font-bold text-red-600">{{ previewData.results.errors }}</div>
                            <div class="text-sm text-gray-600">Con errores</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                            <div class="text-2xl font-bold text-blue-600">{{ previewData.previewed_rows }}</div>
                            <div class="text-sm text-gray-600">Filas procesadas</div>
                        </div>
                    </div>
                </div>

                <!-- Preview Content -->
                <div class="flex-1 overflow-y-auto px-6 py-4">
                    <div v-if="previewData && previewData.results.details" class="space-y-4">
                        <!-- Success Records -->
                        <div v-if="successfulDetails.length > 0" class="bg-white rounded-lg border border-gray-200">
                            <div class="bg-green-50 px-4 py-3 border-b border-green-200">
                                <h4 class="font-bold text-green-800 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Devoluciones Válidas ({{ successfulDetails.length }})
                                </h4>
                            </div>
                            <div class="p-4 max-h-96 overflow-y-auto">
                                <div class="space-y-2">
                                    <div v-for="(detail, index) in successfulDetails.slice(0, 50)" :key="'success-' + index"
                                        class="p-3 bg-green-50 rounded border border-green-200 text-sm">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <div class="font-bold text-green-900 mb-1">
                                                    Fila {{ detail.row }}: {{ detail.data?.participant_name }}
                                                </div>
                                                <div class="text-green-800 grid grid-cols-2 gap-x-4 gap-y-1">
                                                    <div><span class="font-semibold">RUT:</span> {{ detail.data?.participant_rut }}</div>
                                                    <div><span class="font-semibold">Programa:</span> {{ detail.data?.program_name }}</div>
                                                    <div><span class="font-semibold">Monto Devolución:</span> ${{ formatNumber(detail.data?.refund_amount) }}</div>
                                                    <div><span class="font-semibold">Tipo:</span> {{ detail.data?.refund_type || 'N/A' }}</div>
                                                    <div><span class="font-semibold">Aplicar a:</span> {{ detail.data?.aplicar_a || 'N/A' }}</div>
                                                    <div><span class="font-semibold">Documento:</span> {{ detail.data?.document_number || '-' }}</div>
                                                    <div><span class="font-semibold">Monto Pagado:</span> ${{ formatNumber(detail.data?.paid_amount) }}</div>
                                                    <div><span class="font-semibold">Nuevo Saldo:</span> ${{ formatNumber(detail.data?.new_balance) }}</div>
                                                </div>
                                                <div v-if="detail.warning" class="mt-2 text-yellow-700 text-xs">
                                                    ⚠️ {{ detail.warning_message }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="successfulDetails.length > 50" class="text-sm text-gray-600 text-center py-2">
                                        ... y {{ successfulDetails.length - 50 }} registros más
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Failed Records -->
                        <div v-if="failedDetails.length > 0" class="bg-white rounded-lg border border-gray-200">
                            <div class="bg-red-50 px-4 py-3 border-b border-red-200">
                                <h4 class="font-bold text-red-800 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Registros con Errores ({{ failedDetails.length }})
                                </h4>
                            </div>
                            <div class="p-4 max-h-96 overflow-y-auto">
                                <div class="space-y-2">
                                    <div v-for="(detail, index) in failedDetails" :key="'failed-' + index"
                                        class="p-3 bg-red-50 rounded border border-red-200 text-sm">
                                        <div class="font-bold text-red-900 mb-1">Fila {{ detail.row }}</div>
                                        <div class="text-red-800">{{ detail.error }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t flex justify-between items-center">
                    <div class="text-sm text-gray-600">
                        <span v-if="previewData && previewData.results.successful > 0" class="text-green-600 font-bold">
                            {{ previewData.results.successful }} devoluciones válidas
                        </span>
                        <span v-if="previewData && previewData.results.errors > 0" class="ml-3 text-red-600 font-bold">
                            {{ previewData.results.errors }} con errores
                        </span>
                    </div>
                    <div class="flex gap-3">
                        <button @click="closePreview"
                            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 font-bold transition-colors">
                            Cancelar
                        </button>
                        <button @click="confirmImport"
                            :disabled="!previewData || previewData.results.successful === 0 || form.processing"
                            class="px-6 py-2 bg-[#e74c3c] hover:bg-[#c0392b] text-white font-bold rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                            <svg v-if="form.processing" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Confirmar e Insertar Devoluciones
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-8">
                        <!-- Header -->
                        <div class="flex justify-between items-center mb-8">
                            <div>
                                <h2 class="text-3xl font-bold text-gray-900">Importar Devoluciones desde Excel</h2>
                                <p class="text-gray-600 mt-2">Importe múltiples devoluciones (NC y RA) desde un archivo Excel</p>
                            </div>
                            <div class="flex gap-3">
                                <Link
                                    :href="route('admin.payments.refunds.menu')"
                                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200"
                                >
                                    Volver al Menú
                                </Link>
                                <Link
                                    :href="route('admin.payments.index')"
                                    class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors duration-200"
                                >
                                    Cancelar
                                </Link>
                            </div>
                        </div>

                        <!-- Instrucciones del formato -->
                        <div class="mb-6 bg-gray-50 rounded-lg border border-gray-200 p-4">
                            <h3 class="text-sm font-bold text-gray-800 mb-3">Formato del archivo Excel</h3>

                            <div class="mb-3">
                                <p class="text-xs font-semibold text-gray-700 mb-1">Columnas requeridas:</p>
                                <div class="overflow-x-auto">
                                    <table class="text-xs w-full border border-gray-300">
                                        <thead>
                                            <tr class="bg-gray-200">
                                                <th class="px-2 py-1 text-left border-r border-gray-300">Columna</th>
                                                <th class="px-2 py-1 text-left border-r border-gray-300">Descripción</th>
                                                <th class="px-2 py-1 text-left">Ejemplo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="border-t border-gray-300">
                                                <td class="px-2 py-1 font-semibold border-r border-gray-300">RUT</td>
                                                <td class="px-2 py-1 border-r border-gray-300">RUT del participante</td>
                                                <td class="px-2 py-1">12.345.678-9</td>
                                            </tr>
                                            <tr class="border-t border-gray-300 bg-white">
                                                <td class="px-2 py-1 font-semibold border-r border-gray-300">Nro. Negocio</td>
                                                <td class="px-2 py-1 border-r border-gray-300">Código del programa</td>
                                                <td class="px-2 py-1">V0116</td>
                                            </tr>
                                            <tr class="border-t border-gray-300">
                                                <td class="px-2 py-1 font-semibold border-r border-gray-300">Monto</td>
                                                <td class="px-2 py-1 border-r border-gray-300">Monto de la devolución (positivo)</td>
                                                <td class="px-2 py-1">150000</td>
                                            </tr>
                                            <tr class="border-t border-gray-300 bg-white">
                                                <td class="px-2 py-1 font-semibold border-r border-gray-300">Fecha</td>
                                                <td class="px-2 py-1 border-r border-gray-300">Fecha de la transacción</td>
                                                <td class="px-2 py-1">20/02/2026</td>
                                            </tr>
                                            <tr class="border-t border-gray-300">
                                                <td class="px-2 py-1 font-semibold border-r border-gray-300">Tipo Reembolso</td>
                                                <td class="px-2 py-1 border-r border-gray-300">NC (Nota de Crédito) o RA (Reverso Administrativo)</td>
                                                <td class="px-2 py-1">NC</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div>
                                <p class="text-xs font-semibold text-gray-700 mb-1">Columnas opcionales:</p>
                                <div class="overflow-x-auto">
                                    <table class="text-xs w-full border border-gray-300">
                                        <thead>
                                            <tr class="bg-gray-200">
                                                <th class="px-2 py-1 text-left border-r border-gray-300">Columna</th>
                                                <th class="px-2 py-1 text-left border-r border-gray-300">Descripción</th>
                                                <th class="px-2 py-1 text-left">Default</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="border-t border-gray-300">
                                                <td class="px-2 py-1 font-semibold border-r border-gray-300">Aplicar A</td>
                                                <td class="px-2 py-1 border-r border-gray-300">Abonos o Aportes (solo para NC)</td>
                                                <td class="px-2 py-1">Abonos</td>
                                            </tr>
                                            <tr class="border-t border-gray-300 bg-white">
                                                <td class="px-2 py-1 font-semibold border-r border-gray-300">Cod. SII</td>
                                                <td class="px-2 py-1 border-r border-gray-300">Código SII</td>
                                                <td class="px-2 py-1">-</td>
                                            </tr>
                                            <tr class="border-t border-gray-300">
                                                <td class="px-2 py-1 font-semibold border-r border-gray-300">N. Documento</td>
                                                <td class="px-2 py-1 border-r border-gray-300">Número de documento</td>
                                                <td class="px-2 py-1">-</td>
                                            </tr>
                                            <tr class="border-t border-gray-300 bg-white">
                                                <td class="px-2 py-1 font-semibold border-r border-gray-300">Nombre del Cliente</td>
                                                <td class="px-2 py-1 border-r border-gray-300">Nombre del pagador</td>
                                                <td class="px-2 py-1">Nombre del participante</td>
                                            </tr>
                                            <tr class="border-t border-gray-300">
                                                <td class="px-2 py-1 font-semibold border-r border-gray-300">Notas</td>
                                                <td class="px-2 py-1 border-r border-gray-300">Observaciones</td>
                                                <td class="px-2 py-1">-</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Formulario de importación -->
                        <form @submit.prevent="submitPreview" class="space-y-6">
                            <!-- Selección de archivo -->
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-[#e74c3c] transition-colors">
                                <div class="space-y-4">
                                    <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                    </div>

                                    <div>
                                        <label for="excel_file" class="cursor-pointer">
                                            <span class="text-lg font-medium text-gray-700">Seleccionar archivo Excel</span>
                                            <input
                                                id="excel_file"
                                                type="file"
                                                accept=".xlsx,.xls"
                                                @change="handleFileSelect"
                                                class="hidden"
                                                ref="fileInput"
                                            />
                                        </label>
                                        <p class="text-sm text-gray-500 mt-2">
                                            Formatos soportados: .xlsx, .xls
                                        </p>
                                    </div>

                                    <div v-if="selectedFile" class="mt-4 p-3 bg-green-50 rounded-lg border border-green-200">
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="text-sm font-medium text-green-800">
                                                Archivo seleccionado: {{ selectedFile.name }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-green-600 mt-1">
                                            Tamaño: {{ formatFileSize(selectedFile.size) }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                                <Link
                                    :href="route('admin.payments.refunds.menu')"
                                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200"
                                >
                                    Cancelar
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="!selectedFile || form.processing"
                                    class="bg-[#e74c3c] hover:bg-[#c0392b] disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center"
                                >
                                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ form.processing ? 'Procesando...' : 'Ver Vista Previa' }}
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const page = usePage();
const selectedFile = ref(null);
const fileInput = ref(null);
const showPreviewModal = ref(false);
const previewData = ref(null);
const isConfirming = ref(false);
const showResultsModal = ref(false);
const importResults = ref(null);

const form = useForm({
    file: null
});

// Computed properties to filter details
const successfulDetails = computed(() => {
    if (!previewData.value || !previewData.value.results.details) return [];
    return previewData.value.results.details.filter(detail => detail.success === true);
});

const failedDetails = computed(() => {
    if (!previewData.value || !previewData.value.results.details) return [];
    return previewData.value.results.details.filter(detail => detail.success === false);
});

const importSuccessDetails = computed(() => {
    if (!importResults.value || !importResults.value.details) return [];
    return importResults.value.details.filter(detail => detail.success === true);
});

const importFailedDetails = computed(() => {
    if (!importResults.value || !importResults.value.details) return [];
    return importResults.value.details.filter(detail => detail.success === false);
});

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        // Validar tipo de archivo
        const allowedTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
            'application/vnd.ms-excel' // .xls
        ];

        if (!allowedTypes.includes(file.type)) {
            alert('Por favor seleccione un archivo Excel válido (.xlsx o .xls)');
            return;
        }

        // Validar tamaño (máximo 10MB)
        if (file.size > 10 * 1024 * 1024) {
            alert('El archivo es demasiado grande. El tamaño máximo permitido es 10MB.');
            return;
        }

        selectedFile.value = file;
        form.file = file;
    }
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const formatNumber = (value) => {
    if (!value) return '0';
    return new Intl.NumberFormat('es-CL').format(value);
};

const submitPreview = async () => {
    if (!selectedFile.value) {
        alert('Por favor seleccione un archivo Excel');
        return;
    }

    form.processing = true;

    try {
        const formData = new FormData();
        formData.append('file', selectedFile.value);

        const response = await axios.post(route('admin.payments.refunds.import-preview'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        if (response.data.success) {
            previewData.value = response.data;
            showPreviewModal.value = true;
        } else {
            alert('Error al previsualizar: ' + (response.data.error || 'Ocurrió un error al procesar el archivo.'));
        }
    } catch (error) {
        console.error('Error al previsualizar:', error);
        const errorMessage = error.response?.data?.error || error.message || 'Ocurrió un error al procesar el archivo.';
        alert('Error al previsualizar: ' + errorMessage);
    } finally {
        form.processing = false;
    }
};

const closePreview = () => {
    showPreviewModal.value = false;
};

const confirmImport = async () => {
    isConfirming.value = true;
    form.processing = true;

    try {
        const formData = new FormData();
        formData.append('file', selectedFile.value);

        const response = await axios.post(route('admin.payments.refunds.import-store'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        showPreviewModal.value = false;

        if (response.data.success) {
            importResults.value = response.data.results;
            showResultsModal.value = true;
        } else {
            alert('Error en la importación: ' + (response.data.error || 'Error desconocido'));
        }
    } catch (error) {
        showPreviewModal.value = false;
        console.error('Error en la importación:', error);
        const errorMessage = error.response?.data?.error || error.message || 'Ocurrió un error al procesar la importación.';
        alert('Error en la importación: ' + errorMessage);
    } finally {
        isConfirming.value = false;
        form.processing = false;
    }
};

const closeResults = () => {
    showResultsModal.value = false;
    importResults.value = null;

    // Limpiar el archivo seleccionado
    selectedFile.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    previewData.value = null;
};
</script>

<style scoped>
/* Estilos personalizados para el input de archivo */
input[type="file"]:focus + label {
    outline: 2px solid #e74c3c;
    outline-offset: 2px;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
