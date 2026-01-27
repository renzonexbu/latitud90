<template>
    <AdminLayout>
        <Head title="Importar Pagos Offline" />

        <!-- Sistema de Alertas -->
        <AlertWrapper ref="alertWrapper" />

        <!-- Processing Overlay -->
        <div v-if="form.processing || isConfirming" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
            <div class="bg-white rounded-xl p-8 max-w-md w-full mx-4 shadow-2xl">
                <div class="flex flex-col items-center">
                    <!-- Spinner -->
                    <div class="relative">
                        <div class="w-16 h-16 border-4 border-turquesa/20 rounded-full"></div>
                        <div class="w-16 h-16 border-4 border-turquesa border-t-transparent rounded-full animate-spin absolute top-0 left-0"></div>
                    </div>

                    <!-- Title -->
                    <h3 class="mt-6 text-xl font-nexa-bold text-verde-oscuro">
                        {{ progressData ? progressData.message : (isConfirming ? 'Insertando pagos en la base de datos...' : 'Procesando archivo...') }}
                    </h3>

                    <!-- Progress Bar (if we have progress data) -->
                    <div v-if="progressData && progressData.percentage > 0" class="w-full mt-4">
                        <div class="flex justify-between text-sm text-gray-600 font-nexa-regular mb-1">
                            <span>{{ Math.round(progressData.percentage) }}%</span>
                            <span>{{ progressData.successful || 0 }} pagos guardados</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div
                                class="bg-turquesa h-3 rounded-full transition-all duration-500"
                                :style="{ width: progressData.percentage + '%' }"
                            ></div>
                        </div>
                    </div>

                    <!-- Stats (if available) -->
                    <div v-if="progressData && progressData.stats" class="mt-4 w-full grid grid-cols-2 gap-2">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-2 text-center">
                            <div class="text-lg font-nexa-bold text-verde-oscuro">{{ progressData.stats.successful || 0 }}</div>
                            <div class="text-xs text-gray-600 font-nexa-regular">Guardados</div>
                        </div>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-2 text-center">
                            <div class="text-lg font-nexa-bold text-red-600">{{ progressData.stats.failed || 0 }}</div>
                            <div class="text-xs text-gray-600 font-nexa-regular">Errores</div>
                        </div>
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-2 text-center">
                            <div class="text-lg font-nexa-bold text-orange-600">{{ progressData.stats.duplicates || 0 }}</div>
                            <div class="text-xs text-gray-600 font-nexa-regular">Duplicados</div>
                        </div>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-2 text-center">
                            <div class="text-lg font-nexa-bold text-yellow-600">{{ progressData.stats.skipped || 0 }}</div>
                            <div class="text-xs text-gray-600 font-nexa-regular">Omitidos</div>
                        </div>
                    </div>

                    <!-- Message -->
                    <p v-if="!progressData" class="mt-2 text-gray-600 font-nexa-regular text-center">
                        {{ isConfirming ? 'Por favor espera mientras se insertan los pagos.' : 'Esto puede tardar varios minutos dependiendo del tamaño del archivo.' }}
                    </p>

                    <!-- File info -->
                    <div v-if="form.file" class="mt-4 px-4 py-2 bg-gray-100 rounded-lg">
                        <p class="text-sm text-gray-700 font-nexa-regular">
                            <span class="font-nexa-bold">{{ form.file.name }}</span>
                            <span class="text-gray-500 ml-2">({{ formatFileSize(form.file.size) }})</span>
                        </p>
                    </div>

                    <!-- Warning -->
                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-xs text-yellow-800 font-nexa-regular text-center">
                            Por favor, no cierres esta ventana ni navegues a otra página mientras se procesa el archivo.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Failed Imports Modal - Para reintentos manuales -->
        <div v-if="showFailedModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col">
                <!-- Modal Header -->
                <div class="bg-red-600 text-white px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-2xl font-nexa-bold">Pagos Fallidos</h3>
                        <p class="text-sm font-nexa-regular mt-1 opacity-90">
                            {{ importFailures.length }} pago(s) fallaron durante la importación. Puedes editar y reintentar.
                        </p>
                    </div>
                    <button @click="closeFailedModal" class="text-white hover:text-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Failed Items List -->
                <div class="flex-1 overflow-y-auto px-6 py-4">
                    <div class="space-y-4">
                        <div v-for="(failure, index) in importFailures" :key="'failure-' + index"
                            class="bg-red-50 rounded-lg border border-red-300 overflow-hidden">

                            <!-- Failure Header -->
                            <div class="px-4 py-3 bg-red-100 border-b border-red-200 flex justify-between items-center">
                                <div>
                                    <span class="font-nexa-bold text-red-900">Fila {{ failure.row }}</span>
                                    <span v-if="failure.data?.participant_name" class="ml-2 text-red-800">
                                        - {{ failure.data.participant_name }}
                                    </span>
                                </div>
                                <div class="flex gap-2">
                                    <button
                                        v-if="editingFailure !== index"
                                        @click="startEditingFailure(index)"
                                        class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 font-nexa-bold">
                                        Editar
                                    </button>
                                    <button
                                        @click="retryFailedPayment(index)"
                                        :disabled="retryingRow === index"
                                        class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700 font-nexa-bold disabled:opacity-50">
                                        <span v-if="retryingRow === index">Reintentando...</span>
                                        <span v-else>Reintentar</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Error Message -->
                            <div class="px-4 py-2 bg-red-50">
                                <p class="text-red-800 font-nexa-regular text-sm">
                                    <strong>Error:</strong> {{ failure.message }}
                                </p>
                            </div>

                            <!-- Editable Fields (when editing) -->
                            <div v-if="editingFailure === index" class="px-4 py-4 bg-white border-t border-red-200">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-nexa-bold text-gray-700 mb-1">Enrollment Code</label>
                                        <input
                                            v-model="failure.data.enrollment_code"
                                            type="text"
                                            class="w-full px-3 py-2 border border-gray-300 rounded text-sm font-nexa-regular focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-nexa-bold text-gray-700 mb-1">Monto</label>
                                        <input
                                            v-model="failure.data.payment_amount"
                                            type="number"
                                            class="w-full px-3 py-2 border border-gray-300 rounded text-sm font-nexa-regular focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-nexa-bold text-gray-700 mb-1">Fecha de Pago</label>
                                        <input
                                            v-model="failure.data.payment_date"
                                            type="date"
                                            class="w-full px-3 py-2 border border-gray-300 rounded text-sm font-nexa-regular focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-nexa-bold text-gray-700 mb-1">Tipo de Pago</label>
                                        <select
                                            v-model="failure.data.payment_type"
                                            class="w-full px-3 py-2 border border-gray-300 rounded text-sm font-nexa-regular focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="TE">Transferencia Electrónica</option>
                                            <option value="DP">Depósito</option>
                                            <option value="WP">Webpay</option>
                                            <option value="TC">Tarjeta de Crédito</option>
                                            <option value="AP">Aporte</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-3 flex justify-end gap-2">
                                    <button
                                        @click="cancelEditingFailure"
                                        class="px-3 py-1 bg-gray-500 text-white text-xs rounded hover:bg-gray-600 font-nexa-bold">
                                        Cancelar
                                    </button>
                                    <button
                                        @click="saveEditingFailure()"
                                        class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 font-nexa-bold">
                                        Guardar Cambios
                                    </button>
                                </div>
                            </div>

                            <!-- Data Preview (when not editing) -->
                            <div v-else class="px-4 py-3 bg-white border-t border-red-200">
                                <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm text-gray-700 font-nexa-regular">
                                    <div><span class="font-semibold">Enrollment:</span> {{ failure.data?.enrollment_code || 'N/A' }}</div>
                                    <div><span class="font-semibold">Monto:</span> ${{ formatNumber(failure.data?.payment_amount) }}</div>
                                    <div><span class="font-semibold">Fecha:</span> {{ failure.data?.payment_date || 'N/A' }}</div>
                                    <div><span class="font-semibold">Tipo:</span> {{ failure.data?.payment_type || 'N/A' }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty state -->
                        <div v-if="importFailures.length === 0" class="text-center py-8">
                            <svg class="w-16 h-16 mx-auto text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-gray-600 font-nexa-regular">¡Todos los pagos fallidos han sido reintentados exitosamente!</p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t flex justify-between items-center">
                    <div class="text-sm text-gray-600 font-nexa-regular">
                        <span class="text-red-600 font-nexa-bold">{{ importFailures.length }}</span> pago(s) pendiente(s) de reintentar
                    </div>
                    <div class="flex gap-3">
                        <button
                            @click="retryAllFailed"
                            :disabled="importFailures.length === 0 || retryingRow !== null"
                            class="px-4 py-2 bg-green-600 text-white font-nexa-bold rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            Reintentar Todos
                        </button>
                        <button @click="closeFailedModal"
                            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 font-nexa-bold transition-colors">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <div v-if="showPreviewModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl w-full max-w-7xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col">
                <!-- Modal Header -->
                <div class="bg-turquesa text-white px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-2xl font-nexa-bold">Vista Previa de Importación</h3>
                        <p class="text-sm font-nexa-regular mt-1 opacity-90">
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
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div class="bg-white rounded-lg p-4 border border-orange-200">
                            <div class="text-2xl font-nexa-bold text-orange-600">{{ previewData.stats.duplicates || 0 }}</div>
                            <div class="text-sm text-gray-600 font-nexa-regular">Duplicados</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-green-200">
                            <div class="text-2xl font-nexa-bold text-verde-oscuro">{{ previewData.stats.successful }}</div>
                            <div class="text-sm text-gray-600 font-nexa-regular">Pagos válidos</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-yellow-200">
                            <div class="text-2xl font-nexa-bold text-yellow-600">{{ previewData.stats.skipped }}</div>
                            <div class="text-sm text-gray-600 font-nexa-regular">Omitidos</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-red-200">
                            <div class="text-2xl font-nexa-bold text-red-600">{{ previewData.stats.failed }}</div>
                            <div class="text-sm text-gray-600 font-nexa-regular">Con errores</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 border border-gray-200">
                            <div class="text-2xl font-nexa-bold text-blue-600">{{ previewData.previewed_rows }}</div>
                            <div class="text-sm text-gray-600 font-nexa-regular">Total filas</div>
                        </div>
                    </div>
                </div>

                <!-- Preview Content -->
                <div class="flex-1 overflow-y-auto px-6 py-4">
                    <div v-if="previewData && previewData.details" class="space-y-4">
                        <!-- 1. DUPLICATES SECTION (FIRST) -->
                        <div v-if="previewData.details.filter(d => d.status === 'duplicate').length > 0" class="bg-white rounded-lg border border-orange-300">
                            <div class="bg-orange-50 px-4 py-3 border-b border-orange-200 flex justify-between items-center">
                                <h4 class="font-nexa-bold text-orange-800 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    Duplicados - Ya existen en BD ({{ duplicateDetails.length }})
                                </h4>
                                <div class="flex gap-2">
                                    <button @click="exportSection('duplicates')" class="text-xs px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 font-nexa-bold flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Excel
                                    </button>
                                    <button @click="selectAllDuplicates" class="text-xs px-3 py-1 bg-orange-600 text-white rounded hover:bg-orange-700 font-nexa-bold">
                                        Seleccionar Todos
                                    </button>
                                    <button @click="deselectAllDuplicates" class="text-xs px-3 py-1 bg-gray-600 text-white rounded hover:bg-gray-700 font-nexa-bold">
                                        Deseleccionar Todos
                                    </button>
                                </div>
                            </div>

                            <!-- Search filter for duplicates -->
                            <div class="px-4 pt-4 pb-2">
                                <div class="relative">
                                    <input
                                        v-model="duplicateSearchTerm"
                                        type="text"
                                        placeholder="Buscar por código, programa o participante..."
                                        class="w-full px-4 py-2 pr-10 border border-orange-300 rounded-lg font-nexa-regular text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                                    />
                                    <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                            </div>

                            <div class="p-4 max-h-96 overflow-y-auto">
                                <div class="space-y-2">
                                    <div v-for="(detail, index) in duplicateDetails" :key="'duplicate-' + index"
                                        class="p-3 bg-orange-50 rounded border border-orange-200 text-sm">
                                        <div class="flex gap-3 items-start">
                                            <input
                                                type="checkbox"
                                                :checked="selectedRows.has(detail.row)"
                                                @change="toggleRowSelection(detail.row)"
                                                class="mt-1 w-4 h-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500"
                                            />
                                            <div class="flex-1">
                                                <div class="font-nexa-bold text-orange-900 mb-1">
                                                    Fila {{ detail.row }}: {{ detail.data?.participant_name }}
                                                </div>
                                                <div class="text-orange-800 font-nexa-regular mb-2">{{ detail.message }}</div>
                                                <div class="text-orange-700 font-nexa-regular grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
                                                    <div><span class="font-semibold">RUT:</span> {{ detail.data?.participant_rut }}</div>
                                                    <div><span class="font-semibold">Código:</span> {{ detail.data?.program_code }}</div>
                                                    <div><span class="font-semibold">Programa:</span> {{ detail.data?.program_name }}</div>
                                                    <div><span class="font-semibold">Fecha:</span> {{ detail.data?.payment_date }}</div>
                                                    <div><span class="font-semibold">Monto:</span> ${{ formatNumber(detail.data?.payment_amount) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. SUCCESS/VALID SECTION (SECOND) -->
                        <div v-if="previewData.details.filter(d => d.status === 'success').length > 0" class="bg-white rounded-lg border border-green-300">
                            <div class="bg-green-50 px-4 py-3 border-b border-green-200 flex justify-between items-center">
                                <h4 class="font-nexa-bold text-green-800 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Pagos Válidos ({{ successfulDetails.length }})
                                </h4>
                                <div class="flex gap-2">
                                    <button @click="exportSection('valid')" class="text-xs px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 font-nexa-bold flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Excel
                                    </button>
                                    <button @click="selectAllValid" class="text-xs px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 font-nexa-bold">
                                        Seleccionar Todos
                                    </button>
                                    <button @click="deselectAllValid" class="text-xs px-3 py-1 bg-gray-600 text-white rounded hover:bg-gray-700 font-nexa-bold">
                                        Deseleccionar Todos
                                    </button>
                                </div>
                            </div>

                            <!-- Search filter for valid payments -->
                            <div class="px-4 pt-4 pb-2">
                                <div class="relative">
                                    <input
                                        v-model="validSearchTerm"
                                        type="text"
                                        placeholder="Buscar por código, programa o participante..."
                                        class="w-full px-4 py-2 pr-10 border border-green-300 rounded-lg font-nexa-regular text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                                    />
                                    <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                            </div>

                            <div class="p-4 max-h-96 overflow-y-auto">
                                <div class="space-y-2">
                                    <div v-for="(detail, index) in successfulDetails" :key="'success-' + index"
                                        class="p-3 bg-green-50 rounded border border-green-200 text-sm">
                                        <div class="flex gap-3 items-start">
                                            <input
                                                type="checkbox"
                                                :checked="selectedRows.has(detail.row)"
                                                @change="toggleRowSelection(detail.row)"
                                                class="mt-1 w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500"
                                            />
                                            <div class="flex-1">
                                                <div class="font-nexa-bold text-green-900 mb-1">
                                                    Fila {{ detail.row }}: {{ detail.data?.participant_name }}
                                                </div>
                                                <div class="text-green-800 font-nexa-regular grid grid-cols-2 gap-x-4 gap-y-1">
                                                    <div><span class="font-semibold">RUT:</span> {{ detail.data?.participant_rut }}</div>
                                                    <div><span class="font-semibold">Código:</span> {{ detail.data?.program_code }}</div>
                                                    <div><span class="font-semibold">Programa:</span> {{ detail.data?.program_name }}</div>
                                                    <div><span class="font-semibold">Tipo:</span> {{ detail.data?.payment_type_label }}</div>
                                                    <div><span class="font-semibold">Monto:</span> ${{ formatNumber(detail.data?.payment_amount) }}</div>
                                                    <div><span class="font-semibold">Fecha:</span> {{ detail.data?.payment_date }}</div>
                                                    <div><span class="font-semibold">Saldo Anterior:</span> ${{ formatNumber(detail.data?.previous_balance) }}</div>
                                                    <div><span class="font-semibold">Nuevo Saldo:</span> ${{ formatNumber(detail.data?.new_balance) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. SKIPPED SECTION (THIRD) -->
                        <div v-if="skippedDetails.length > 0" class="bg-white rounded-lg border border-yellow-300">
                            <div class="bg-yellow-50 px-4 py-3 border-b border-yellow-200">
                                <h4 class="font-nexa-bold text-yellow-800 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    Registros Omitidos ({{ skippedDetails.length }})
                                </h4>
                            </div>
                            <div class="p-4 max-h-96 overflow-y-auto">
                                <div class="space-y-2">
                                    <div v-for="(detail, index) in skippedDetails" :key="'skipped-' + index"
                                        class="p-3 bg-yellow-50 rounded border border-yellow-200 text-sm">
                                        <div class="font-nexa-bold text-yellow-900 mb-1">Fila {{ detail.row }}</div>
                                        <div class="text-yellow-800 font-nexa-regular">{{ detail.message }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. FAILED/ERROR SECTION (LAST) -->
                        <div v-if="failedDetails.length > 0" class="bg-white rounded-lg border border-red-300">
                            <div class="bg-red-50 px-4 py-3 border-b border-red-200 flex justify-between items-center">
                                <h4 class="font-nexa-bold text-red-800 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Registros con Errores ({{ failedDetails.length }})
                                </h4>
                                <button @click="exportSection('errors')" class="text-xs px-3 py-1 bg-purple-600 text-white rounded hover:bg-purple-700 font-nexa-bold flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Excel
                                </button>
                            </div>
                            <div class="p-4 max-h-96 overflow-y-auto">
                                <div class="space-y-2">
                                    <div v-for="(detail, index) in failedDetails" :key="'failed-' + index"
                                        class="p-3 bg-red-50 rounded border border-red-200 text-sm">
                                        <div class="font-nexa-bold text-red-900 mb-1">Fila {{ detail.row }}</div>
                                        <div class="text-red-800 font-nexa-regular">{{ detail.message }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t flex justify-between items-center">
                    <div class="text-sm text-gray-600 font-nexa-regular">
                        <span v-if="previewData && previewData.stats.successful > 0" class="text-verde-oscuro font-nexa-bold">
                            {{ previewData.stats.successful }} pagos válidos
                        </span>
                        <span v-if="previewData && previewData.stats.failed > 0" class="ml-3 text-red-600 font-nexa-bold">
                            {{ previewData.stats.failed }} con errores
                        </span>
                    </div>
                    <div class="flex gap-3">
                        <button @click="closePreview"
                            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 font-nexa-bold transition-colors">
                            Cancelar
                        </button>
                        <button @click="confirmImport"
                            :disabled="!previewData || previewData.stats.successful === 0 || isConfirming"
                            class="px-6 py-2 bg-turquesa hover:bg-turquesa-dark text-white font-nexa-bold rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                            <svg v-if="isConfirming" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span v-if="!isConfirming">Confirmar e Insertar Pagos</span>
                            <span v-else>Procesando Importación...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="py-6 lg:py-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <div class="flex items-center gap-2 mb-2">
                        <Link :href="route('admin.payments.presential.menu')" class="text-turquesa hover:text-turquesa-dark">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                        </Link>
                        <h1 class="text-2xl font-nexa-bold text-verde-oscuro">
                            Importar Pagos Offline Masivos
                        </h1>
                    </div>
                    <p class="text-gray-600 font-nexa-regular">
                        Carga múltiples pagos offline desde un archivo Excel
                    </p>
                </div>

                <!-- Upload Form -->
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <form @submit.prevent="submitPreview">
                        <!-- File Upload Area -->
                        <div
                            @dragover.prevent="dragOver = true"
                            @dragleave.prevent="dragOver = false"
                            @drop.prevent="handleDrop"
                            :class="[
                                'border-2 border-dashed rounded-lg p-8 text-center transition-colors',
                                dragOver ? 'border-turquesa bg-turquesa/5' : 'border-gray-300',
                                form.file ? 'bg-green-50 border-green-300' : ''
                            ]"
                        >
                            <input
                                type="file"
                                ref="fileInput"
                                @change="handleFileSelect"
                                accept=".xlsx,.xls"
                                class="hidden"
                            />

                            <div v-if="!form.file">
                                <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                <p class="text-gray-600 font-nexa-regular mb-2">
                                    Arrastra tu archivo Excel aquí o
                                </p>
                                <button
                                    type="button"
                                    @click="$refs.fileInput.click()"
                                    class="text-turquesa hover:text-turquesa-dark font-nexa-bold"
                                >
                                    haz clic para seleccionar
                                </button>
                                <p class="text-xs text-gray-500 mt-2 font-nexa-regular">
                                    Archivos .xlsx o .xls (máximo 10MB)
                                </p>
                            </div>

                            <div v-else class="flex items-center justify-center gap-3">
                                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <div class="text-left">
                                    <p class="font-nexa-bold text-verde-oscuro">{{ form.file.name }}</p>
                                    <p class="text-sm text-gray-600 font-nexa-regular">{{ formatFileSize(form.file.size) }}</p>
                                </div>
                                <button
                                    type="button"
                                    @click="removeFile"
                                    class="ml-4 text-red-600 hover:text-red-700"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Error Messages -->
                        <div v-if="form.errors.file" class="mt-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
                            {{ form.errors.file }}
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-6 flex gap-3">
                            <button
                                type="submit"
                                :disabled="!form.file || form.processing"
                                class="flex-1 bg-turquesa hover:bg-turquesa-dark text-white font-nexa-bold py-3 px-6 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                            >
                                <svg v-if="form.processing" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ form.processing ? 'Procesando...' : 'Ver Vista Previa' }}
                            </button>
                            <Link
                                :href="route('admin.payments.presential.menu')"
                                class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-nexa-bold transition-colors"
                            >
                                Cancelar
                            </Link>
                        </div>
                    </form>
                </div>

                <!-- Results Display (if available) -->
                <div v-if="results" class="mt-6 space-y-6">
                    <!-- Summary Cards -->
                    <div class="bg-white shadow-sm rounded-lg p-6">
                        <h3 class="font-nexa-bold text-verde-oscuro mb-4 text-lg">Resumen de la Importación</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                                <div class="text-2xl font-nexa-bold text-green-700">{{ results.successful }}</div>
                                <div class="text-sm text-green-600 font-nexa-regular">Pagos exitosos</div>
                            </div>
                            <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <div class="text-2xl font-nexa-bold text-yellow-700">{{ results.skipped }}</div>
                                <div class="text-sm text-yellow-600 font-nexa-regular">Registros omitidos</div>
                            </div>
                            <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                                <div class="text-2xl font-nexa-bold text-red-700">{{ results.failed }}</div>
                                <div class="text-sm text-red-600 font-nexa-regular">Registros fallidos</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import AlertWrapper from '@/Components/Admin/AlertWrapper.vue';
import axios from 'axios';

const page = usePage();
const fileInput = ref(null);
const dragOver = ref(false);
const results = ref(null);
const alertWrapper = ref(null);
const showPreviewModal = ref(false);
const previewData = ref(null);
const isConfirming = ref(false);
const selectedRows = ref(new Set());
const duplicateSearchTerm = ref('');
const validSearchTerm = ref('');
const columnIndices = ref(null);
const rawRowsData = ref(null);

// Progress tracking (procesamiento síncrono)
const progressData = ref(null);

// Failed imports tracking - para reintentos manuales
const showFailedModal = ref(false);
const importFailures = ref([]);
const retryingRow = ref(null);
const editingFailure = ref(null);

const form = useForm({
    file: null
});

// Computed properties to filter details
const duplicateDetails = computed(() => {
    if (!previewData.value || !previewData.value.details) return [];
    let filtered = previewData.value.details.filter(detail => detail.status === 'duplicate');

    // Filter by search term (program code)
    if (duplicateSearchTerm.value.trim()) {
        const searchLower = duplicateSearchTerm.value.toLowerCase().trim();
        filtered = filtered.filter(detail =>
            detail.data?.program_code?.toLowerCase().includes(searchLower) ||
            detail.data?.program_name?.toLowerCase().includes(searchLower) ||
            detail.data?.participant_name?.toLowerCase().includes(searchLower)
        );
    }

    return filtered;
});

const successfulDetails = computed(() => {
    if (!previewData.value || !previewData.value.details) return [];
    let filtered = previewData.value.details.filter(detail => detail.status === 'success');

    // Filter by search term (program code)
    if (validSearchTerm.value.trim()) {
        const searchLower = validSearchTerm.value.toLowerCase().trim();
        filtered = filtered.filter(detail =>
            detail.data?.program_code?.toLowerCase().includes(searchLower) ||
            detail.data?.program_name?.toLowerCase().includes(searchLower) ||
            detail.data?.participant_name?.toLowerCase().includes(searchLower)
        );
    }

    return filtered;
});

const skippedDetails = computed(() => {
    if (!previewData.value || !previewData.value.details) return [];
    return previewData.value.details.filter(detail => detail.status === 'skipped');
});

const failedDetails = computed(() => {
    if (!previewData.value || !previewData.value.details) return [];
    return previewData.value.details.filter(detail => detail.status === 'error');
});

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        validateAndSetFile(file);
    }
};

const handleDrop = (event) => {
    dragOver.value = false;
    const file = event.dataTransfer.files[0];
    if (file) {
        validateAndSetFile(file);
    }
};

const validateAndSetFile = (file) => {
    // Validate file type
    const allowedTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel'
    ];

    if (!allowedTypes.includes(file.type) && !file.name.match(/\.(xlsx|xls)$/i)) {
        alert('Por favor selecciona un archivo Excel válido (.xlsx o .xls)');
        return;
    }

    // Validate file size (10MB max)
    if (file.size > 10 * 1024 * 1024) {
        alert('El archivo es demasiado grande. El tamaño máximo es 10MB.');
        return;
    }

    form.file = file;
};

const removeFile = () => {
    form.file = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    previewData.value = null;
    showPreviewModal.value = false;
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const formatNumber = (value) => {
    if (!value) return '0';
    return new Intl.NumberFormat('es-CL').format(value);
};

const exportSection = async (type) => {
    try {
        let data = [];

        if (type === 'duplicates') {
            data = duplicateDetails.value;
        } else if (type === 'valid') {
            data = successfulDetails.value;
        } else if (type === 'errors') {
            data = failedDetails.value;
        }

        if (data.length === 0) {
            alertWrapper.value?.showWarning('Sin datos', 'No hay registros para exportar en esta sección.');
            return;
        }

        // Create form for download
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = route('admin.payments.presential.import-export-section');
        form.style.display = 'none';

        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = document.querySelector('meta[name="csrf-token"]').content;
        form.appendChild(csrfInput);

        // Add type
        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = 'type';
        typeInput.value = type;
        form.appendChild(typeInput);

        // Add data as JSON
        const dataInput = document.createElement('input');
        dataInput.type = 'hidden';
        dataInput.name = 'data';
        dataInput.value = JSON.stringify(data);
        form.appendChild(dataInput);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

    } catch (error) {
        console.error('Error al exportar:', error);
        alertWrapper.value?.showError('Error', 'Ocurrió un error al exportar la sección.');
    }
};

const submitPreview = async () => {
    if (!form.file) {
        alertWrapper.value?.showError('Error', 'Debe seleccionar un archivo para importar.');
        return;
    }

    form.processing = true;

    try {
        const formData = new FormData();
        formData.append('file', form.file);

        const response = await axios.post(route('admin.payments.presential.import-preview'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        if (response.data.success) {
            previewData.value = response.data;

            // Store column indices and raw rows data for import
            columnIndices.value = response.data.column_indices;

            console.log('📋 Preview response:', {
                column_indices: response.data.column_indices,
                details_count: response.data.details?.length,
                sample_detail: response.data.details?.[0]
            });

            // Build rows data map from details (send ALL detail.data, not just raw_row_data)
            rawRowsData.value = {};
            let rawDataCount = 0;
            if (response.data.details) {
                response.data.details.forEach(detail => {
                    if (detail.data) {
                        // Guardar TODO el detail.data (incluye enrollment_code, payment_amount, etc.)
                        rawRowsData.value[detail.row] = detail.data;
                        rawDataCount++;
                    }
                });
            }

            console.log('📦 Rows data capturados:', {
                total_con_data: rawDataCount,
                total_details: response.data.details?.length,
                sample_data: Object.values(rawRowsData.value)[0]
            });

            // Auto-select all valid (success) rows by default
            selectedRows.value = new Set();
            if (response.data.details) {
                response.data.details.forEach(detail => {
                    if (detail.status === 'success') {
                        selectedRows.value.add(detail.row);
                    }
                });
            }

            showPreviewModal.value = true;
        } else {
            alertWrapper.value?.showError('Error al previsualizar', response.data.error || 'Ocurrió un error al procesar el archivo.');
        }
    } catch (error) {
        console.error('Error al previsualizar:', error);
        const errorMessage = error.response?.data?.error || error.message || 'Ocurrió un error al procesar el archivo.';
        alertWrapper.value?.showError('Error al previsualizar', errorMessage);
    } finally {
        form.processing = false;
    }
};

// Row selection methods
const toggleRowSelection = (rowNumber) => {
    if (selectedRows.value.has(rowNumber)) {
        selectedRows.value.delete(rowNumber);
    } else {
        selectedRows.value.add(rowNumber);
    }
    // Force reactivity
    selectedRows.value = new Set(selectedRows.value);
};

const selectAllDuplicates = () => {
    duplicateDetails.value.forEach(detail => {
        selectedRows.value.add(detail.row);
    });
    selectedRows.value = new Set(selectedRows.value);
};

const deselectAllDuplicates = () => {
    duplicateDetails.value.forEach(detail => {
        selectedRows.value.delete(detail.row);
    });
    selectedRows.value = new Set(selectedRows.value);
};

const selectAllValid = () => {
    successfulDetails.value.forEach(detail => {
        selectedRows.value.add(detail.row);
    });
    selectedRows.value = new Set(selectedRows.value);
};

const deselectAllValid = () => {
    successfulDetails.value.forEach(detail => {
        selectedRows.value.delete(detail.row);
    });
    selectedRows.value = new Set(selectedRows.value);
};

const closePreview = () => {
    showPreviewModal.value = false;
    selectedRows.value = new Set();
    duplicateSearchTerm.value = '';
    validSearchTerm.value = '';
};

const BATCH_SIZE = 30; // Procesar 30 filas por petición

const confirmImport = async () => {
    if (selectedRows.value.size === 0) {
        alertWrapper.value?.showWarning('Sin selección', 'Debes seleccionar al menos un pago para importar.');
        return;
    }

    if (!columnIndices.value || !rawRowsData.value) {
        alertWrapper.value?.showError('Error', 'Datos de previsualización no disponibles. Por favor, vuelve a cargar el archivo.');
        return;
    }

    // Close preview modal immediately to show processing overlay
    showPreviewModal.value = false;

    isConfirming.value = true;

    // Build rows data array from selected rows
    const selectedRowsArray = Array.from(selectedRows.value);
    const rowsDataArray = selectedRowsArray.map(rowNumber => {
        const data = rawRowsData.value[rowNumber];
        return {
            row_number: rowNumber,
            data: data
        };
    }).filter(item => item.data);

    if (rowsDataArray.length === 0) {
        alertWrapper.value?.showError('Error', 'No se encontraron datos para las filas seleccionadas.');
        isConfirming.value = false;
        return;
    }

    // Dividir en lotes
    const batches = [];
    for (let i = 0; i < rowsDataArray.length; i += BATCH_SIZE) {
        batches.push(rowsDataArray.slice(i, i + BATCH_SIZE));
    }

    console.log(`📦 Procesando ${rowsDataArray.length} filas en ${batches.length} lotes de ${BATCH_SIZE}`);

    // Acumular estadísticas y failures
    const totalStats = { successful: 0, duplicates: 0, skipped: 0, failed: 0 };
    const collectedFailures = [];
    let processedRows = 0;

    progressData.value = {
        status: 'processing',
        message: `Procesando lote 1 de ${batches.length}...`,
        percentage: 0,
        successful: 0,
        stats: totalStats
    };

    try {
        // Procesar lotes secuencialmente
        for (let i = 0; i < batches.length; i++) {
            const batch = batches[i];
            const batchNumber = i + 1;

            progressData.value = {
                status: 'processing',
                message: `Procesando lote ${batchNumber} de ${batches.length}...`,
                percentage: Math.round((processedRows / rowsDataArray.length) * 100),
                successful: totalStats.successful,
                stats: { ...totalStats }
            };

            console.log(`🚀 Enviando lote ${batchNumber}/${batches.length} (${batch.length} filas)`);

            const response = await axios.post(route('admin.payments.presential.import-store'), {
                rows_data: batch,
                column_indices: columnIndices.value
            }, {
                headers: { 'Content-Type': 'application/json' },
                timeout: 60000 // 1 minuto por lote
            });

            if (response.data.success) {
                const batchStats = response.data.stats || {};
                totalStats.successful += batchStats.successful || 0;
                totalStats.duplicates += batchStats.duplicates || 0;
                totalStats.skipped += batchStats.skipped || 0;
                totalStats.failed += batchStats.failed || 0;
                processedRows += batch.length;

                // Collect failures from this batch
                if (response.data.details) {
                    response.data.details.forEach(detail => {
                        if (detail.status === 'error' || detail.status === 'failed') {
                            collectedFailures.push({
                                row: detail.row,
                                message: detail.message || 'Error desconocido',
                                data: detail.data || batch.find(b => b.row_number === detail.row)?.data || {}
                            });
                        }
                    });
                }

                console.log(`✅ Lote ${batchNumber} completado:`, batchStats);
            } else {
                console.error(`❌ Error en lote ${batchNumber}:`, response.data.error);
                // Mark all batch items as failed
                batch.forEach(item => {
                    collectedFailures.push({
                        row: item.row_number,
                        message: response.data.error || 'Error en el lote',
                        data: item.data || {}
                    });
                });
                totalStats.failed += batch.length;
                processedRows += batch.length;
            }

            // Actualizar progreso
            progressData.value = {
                status: 'processing',
                message: `Lote ${batchNumber} de ${batches.length} completado`,
                percentage: Math.round((processedRows / rowsDataArray.length) * 100),
                successful: totalStats.successful,
                stats: { ...totalStats }
            };
        }

        // Importación completada
        isConfirming.value = false;
        progressData.value = null;

        console.log('🎉 Importación completada:', totalStats);
        console.log('📋 Pagos fallidos:', collectedFailures);

        if (totalStats.successful > 0) {
            alertWrapper.value?.showSuccess(
                'Importación completada',
                `Se importaron ${totalStats.successful} pagos exitosamente. ${totalStats.duplicates} duplicados, ${totalStats.failed} fallidos.`
            );
        } else {
            alertWrapper.value?.showWarning(
                'Importación sin resultados',
                `No se importaron pagos. ${totalStats.duplicates} duplicados, ${totalStats.failed} fallidos.`
            );
        }

        // Si hay fallos, mostrar modal para reintentos
        if (collectedFailures.length > 0) {
            importFailures.value = collectedFailures;
            setTimeout(() => {
                showFailedModal.value = true;
            }, 1500); // Esperar a que se muestre el mensaje de éxito
        }

        removeFile();

    } catch (error) {
        isConfirming.value = false;
        progressData.value = null;
        console.error('Error en importación por lotes:', error);

        const errorMessage = error.response?.data?.error || error.message || 'Error durante la importación.';
        alertWrapper.value?.showError(
            'Error en importación',
            `${errorMessage}. Pagos procesados hasta el momento: ${totalStats.successful} exitosos.`
        );
    }
};

// ========== FAILED IMPORTS MANAGEMENT ==========

const closeFailedModal = () => {
    showFailedModal.value = false;
    editingFailure.value = null;
};

const startEditingFailure = (index) => {
    editingFailure.value = index;
};

const cancelEditingFailure = () => {
    editingFailure.value = null;
};

const saveEditingFailure = () => {
    // Just close editing mode - changes are already in the reactive object
    editingFailure.value = null;
    alertWrapper.value?.showSuccess('Guardado', 'Los cambios han sido guardados. Ahora puedes reintentar.');
};

const retryFailedPayment = async (index) => {
    const failure = importFailures.value[index];
    if (!failure) return;

    retryingRow.value = index;

    try {
        const response = await axios.post(route('admin.payments.presential.import-store'), {
            rows_data: [{
                row_number: failure.row,
                data: failure.data
            }],
            column_indices: columnIndices.value
        }, {
            headers: { 'Content-Type': 'application/json' },
            timeout: 30000
        });

        if (response.data.success && response.data.stats?.successful > 0) {
            // Remove from failures list
            importFailures.value.splice(index, 1);
            alertWrapper.value?.showSuccess('Pago registrado', `El pago de la fila ${failure.row} se registró exitosamente.`);

            // If no more failures, close modal
            if (importFailures.value.length === 0) {
                setTimeout(() => {
                    closeFailedModal();
                }, 1500);
            }
        } else {
            // Update error message
            const details = response.data.details || [];
            const failedDetail = details.find(d => d.status === 'error' || d.status === 'duplicate');
            if (failedDetail) {
                importFailures.value[index].message = failedDetail.message;
            }
            alertWrapper.value?.showError('Error', `No se pudo registrar el pago: ${failedDetail?.message || 'Error desconocido'}`);
        }
    } catch (error) {
        console.error('Error al reintentar pago:', error);
        const errorMessage = error.response?.data?.error || error.message || 'Error al reintentar';
        alertWrapper.value?.showError('Error', errorMessage);
    } finally {
        retryingRow.value = null;
    }
};

const retryAllFailed = async () => {
    if (importFailures.value.length === 0) return;

    const totalToRetry = importFailures.value.length;
    let successCount = 0;

    // Process one by one to track individual results
    for (let i = importFailures.value.length - 1; i >= 0; i--) {
        retryingRow.value = i;
        const failure = importFailures.value[i];

        try {
            const response = await axios.post(route('admin.payments.presential.import-store'), {
                rows_data: [{
                    row_number: failure.row,
                    data: failure.data
                }],
                column_indices: columnIndices.value
            }, {
                headers: { 'Content-Type': 'application/json' },
                timeout: 30000
            });

            if (response.data.success && response.data.stats?.successful > 0) {
                importFailures.value.splice(i, 1);
                successCount++;
            }
        } catch (error) {
            console.error(`Error reintentando fila ${failure.row}:`, error);
        }
    }

    retryingRow.value = null;

    if (successCount > 0) {
        alertWrapper.value?.showSuccess(
            'Reintentos completados',
            `${successCount} de ${totalToRetry} pagos se registraron exitosamente.`
        );
    }

    if (importFailures.value.length === 0) {
        setTimeout(() => {
            closeFailedModal();
        }, 1500);
    }
};

// Procesamiento síncrono - no se requiere polling ni onMounted
</script>

<style scoped>
.font-nexa-bold {
    font-family: 'Nexa-Bold', sans-serif;
    font-weight: 700;
}

.font-nexa-regular {
    font-family: 'Nexa-Regular', sans-serif;
    font-weight: 400;
}

.text-verde-oscuro {
    color: #1c4f4a;
}

.bg-turquesa {
    background-color: #007e93;
}

.hover\:bg-turquesa-dark:hover {
    background-color: #006477;
}

.text-turquesa {
    color: #007e93;
}

.hover\:text-turquesa-dark:hover {
    color: #006477;
}

.border-turquesa {
    border-color: #007e93;
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
