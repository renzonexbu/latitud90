<template>
    <div
        v-if="show"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
        <div
            class="bg-white rounded-[20px] w-[800px] max-h-[90vh] overflow-y-auto"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between p-6 border-b border-gray-200"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="w-8 h-8 bg-turquesa rounded-full flex items-center justify-center"
                    >
                        <svg
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z"
                                fill="white"
                            />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-[24px] font-nexa-bold text-turquesa">
                            Editar Participante
                        </h2>
                        <p class="text-[14px] text-gray-600">
                            Modificar datos personales del participante
                        </p>
                    </div>
                </div>
                <button
                    @click="$emit('close')"
                    class="text-gray-400 hover:text-gray-600"
                >
                    <svg
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z"
                            fill="currentColor"
                        />
                    </svg>
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="updateParticipant" class="p-6">
                <!-- Datos del participante -->
                <div class="mb-6">
                    <h3 class="text-[18px] font-nexa-bold text-turquesa mb-4">
                        Datos Del Participante*
                    </h3>

                    <div class="grid grid-cols-2 gap-6">
                        <!-- Primer Apellido -->
                        <div>
                            <label
                                class="block text-[14px] font-nexa-bold text-gray-700 mb-2"
                            >
                                Primer Apellido *
                            </label>
                            <input
                                v-model="form.first_last_name"
                                type="text"
                                placeholder="Primer Apellido"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                :class="{
                                    'border-red-500': errors?.first_last_name,
                                }"
                            />
                            <div
                                v-if="errors?.first_last_name"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ errors.first_last_name }}
                            </div>
                        </div>

                        <!-- Segundo Apellido -->
                        <div>
                            <label
                                class="block text-[14px] font-nexa-bold text-gray-700 mb-2"
                            >
                                Segundo Apellido
                            </label>
                            <input
                                v-model="form.second_last_name"
                                type="text"
                                placeholder="Segundo Apellido (opcional)"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                :class="{ 'border-red-500': errors?.second_last_name }"
                            />
                            <div
                                v-if="errors?.second_last_name"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ errors.second_last_name }}
                            </div>
                        </div>

                        <!-- Primer Nombre -->
                        <div>
                            <label
                                class="block text-[14px] font-nexa-bold text-gray-700 mb-2"
                            >
                                Primer Nombre *
                            </label>
                            <input
                                v-model="form.first_name"
                                type="text"
                                placeholder="Primer Nombre"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                :class="{
                                    'border-red-500': errors?.first_name,
                                }"
                            />
                            <div
                                v-if="errors?.first_name"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ errors.first_name }}
                            </div>
                        </div>

                        <!-- Segundo Nombre -->
                        <div>
                            <label
                                class="block text-[14px] font-nexa-bold text-gray-700 mb-2"
                            >
                                Segundo Nombre
                            </label>
                            <input
                                v-model="form.second_name"
                                type="text"
                                placeholder="Segundo Nombre (opcional)"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                :class="{ 'border-red-500': errors?.second_name }"
                            />
                            <div
                                v-if="errors?.second_name"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ errors.second_name }}
                            </div>
                        </div>

                        <!-- RUT/PASAPORTE (bloqueado y formateado) -->
                        <div>
                            <label
                                class="block text-[14px] font-nexa-bold text-gray-700 mb-2"
                            >
                                RUT / PASAPORTE *
                            </label>
                            <input
                                :value="formattedDocument"
                                type="text"
                                placeholder="000000000"
                                disabled
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed"
                            />
                            <p class="text-gray-500 text-sm mt-1">
                                El documento no se puede modificar
                            </p>
                        </div>

                        <!-- Fecha de nacimiento -->
                        <div>
                            <label
                                class="block text-[14px] font-nexa-bold text-gray-700 mb-2"
                            >
                                Fecha De Nacimiento
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.birth_date"
                                    type="date"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent pr-10"
                                    :class="{
                                        'border-red-500': errors?.birth_date,
                                    }"
                                />
                                <div
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none"
                                >
                                    <svg
                                        width="20"
                                        height="20"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path
                                            d="M19 3H5C3.89 3 3 3.9 3 5V19C3 20.1 3.89 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3ZM19 19H5V8H19V19ZM7 10H12V15H7V10Z"
                                            fill="#6B7280"
                                        />
                                    </svg>
                                </div>
                            </div>
                            <div
                                v-if="errors?.birth_date"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ errors.birth_date }}
                            </div>
                        </div>

                        <!-- Email -->
                        <div>
                            <label
                                class="block text-[14px] font-nexa-bold text-gray-700 mb-2"
                            >
                                Email
                            </label>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="Email"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                :class="{ 'border-red-500': errors?.email }"
                            />
                            <div
                                v-if="errors?.email"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ errors.email }}
                            </div>
                        </div>

                        <!-- Teléfono -->
                        <div>
                            <label
                                class="block text-[14px] font-nexa-bold text-gray-700 mb-2"
                            >
                                Teléfono
                            </label>
                            <div class="flex gap-2">
                                <div class="relative">
                                    <select
                                        v-model="form.code_phone"
                                        class="w-20 px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent bg-white appearance-none cursor-pointer pr-8"
                                    >
                                        <option value="+56">CL</option>
                                        <option value="+54">AR</option>
                                        <option value="+57">CO</option>
                                        <option value="+51">PE</option>
                                        <option value="+593">EC</option>
                                    </select>
                                    <div
                                        class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none"
                                    ></div>
                                </div>
                                <input
                                    v-model="form.phone"
                                    type="text"
                                    placeholder="9--- ---"
                                    class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                    :class="{ 'border-red-500': errors?.phone }"
                                />
                            </div>
                            <div
                                v-if="errors?.phone"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ errors.phone }}
                            </div>
                        </div>

                        <!-- Ajustes económicos (integrados al layout 2x2) -->
                        <!-- Aplicar ajuste: Izquierda -->
                        <div>
                            <label class="block text-[14px] font-nexa-bold text-gray-700 mb-2">
                                Aplicar Ajuste A Curso/Programa
                            </label>
                            <select
                                v-model="form.pivot_course_id"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                :disabled="programsWithPendingBalance.length === 0"
                            >
                                <option value="">-- Selecciona --</option>
                                <option
                                    v-for="prog in programsWithPendingBalance"
                                    :key="prog.id"
                                    :value="prog.id"
                                >
                                    {{ (prog.name || 'Sin programa') + ' - ' + (prog.code || '') }}
                                </option>
                            </select>
                            <p v-if="programsWithPendingBalance.length === 0" class="text-yellow-600 text-xs mt-1">
                                No hay programas con saldo pendiente para aplicar descuentos
                            </p>

                            <!-- Botón de Toggle Status del Programa -->
                            <button
                                v-if="currentProgram"
                                type="button"
                                @click="toggleProgramStatus"
                                :class="[
                                    'mt-3 w-full px-4 py-2 rounded-lg transition-colors text-sm font-medium',
                                    currentProgramStatus === 'cancelled'
                                        ? 'bg-green-500 text-white hover:bg-green-600'
                                        : 'bg-red-500 text-white hover:bg-red-600'
                                ]"
                            >
                                {{ currentProgramStatus === 'cancelled' ? 'Reactivar Programa' : 'Cancelar Programa' }}
                            </button>
                        </div>

                        <!-- Precio individual: Derecha -->
                        <div>
                            <label class="block text-[14px] font-nexa-bold text-gray-700 mb-2">
                                Precio Individual (CLP)
                            </label>
                            <input
                                v-model="form.individual_price"
                                type="number"
                                min="0"
                                step="1"
                                placeholder="0"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed"
                                disabled
                            />
                        </div>
                    </div>
                </div>

                <!-- Sistema de Descuentos Múltiples -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-[18px] font-nexa-bold text-turquesa">
                            Descuentos Y Ajustes
                        </h3>
                        <button
                            type="button"
                            @click="addDiscount"
                            :disabled="hasActiveSubscription"
                            :class="[
                                'px-4 py-2 rounded-lg transition-colors text-sm',
                                hasActiveSubscription
                                    ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                    : 'bg-turquesa text-white hover:bg-turquesa-dark'
                            ]"
                        >
                            + Agregar Descuento
                        </button>
                    </div>

                    <!-- Mensaje de bloqueo por suscripción -->
                    <div v-if="hasActiveSubscription" class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-sm text-yellow-800">
                            ⚠️ No se pueden aplicar descuentos ni ajustes porque este participante tiene una suscripción activa para este programa.
                        </p>
                    </div>

                    <!-- Lista de descuentos existentes -->
                    <div v-if="discounts.length > 0" class="space-y-3 mb-4">
                        <div
                            v-for="(discount, index) in discounts"
                            :key="index"
                            class="border border-gray-200 rounded-lg p-4 bg-gray-50"
                        >
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                        <!-- Descripción -->
                                        <div class="md:col-span-2">
                                            <label class="block text-[12px] font-nexa-bold text-gray-700 mb-1">
                                                Descripción Del Descuento
                                            </label>
                                            <input
                                                v-model="discount.comment"
                                                type="text"
                                                placeholder="Ej: Descuento familiar, Beca institucional, etc."
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                            />
                                        </div>

                                        <!-- Tipo de descuento -->
                                        <div>
                                            <label class="block text-[12px] font-nexa-bold text-gray-700 mb-1">
                                                Tipo
                                            </label>
                                            <select
                                                v-model="discount.type"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                            >
                                                <option value="percent">Porcentaje (%)</option>
                                                <option value="amount">Monto fijo (CLP)</option>
                                                <option value="liberado">Liberado</option>
                                            </select>
                                        </div>

                                        <!-- Valor -->
                                        <div>
                                            <label class="block text-[12px] font-nexa-bold text-gray-700 mb-1">
                                                {{ getDiscountValueLabel(discount.type) }}
                                            </label>
                                            <input
                                                v-model.number="discount.value"
                                                type="number"
                                                :min="0"
                                                :max="discount.type === 'percent' || discount.type === 'liberado' ? 100 : null"
                                                :step="discount.type === 'percent' || discount.type === 'liberado' ? 0.01 : 1"
                                                :placeholder="getDiscountValuePlaceholder(discount.type)"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Botón eliminar -->
                                <button
                                    type="button"
                                    @click="removeDiscount(index)"
                                    class="ml-3 p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                                    title="Eliminar descuento"
                                >
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z" fill="currentColor"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Resumen del descuento -->
                            <div class="text-sm text-gray-600 bg-white p-2 rounded border">
                                <strong>Descuento calculado:</strong>
                                ${{ formatNumber(calculateDiscountAmount(discount)) }}
                                <span v-if="discount.type === 'percent'">({{ discount.value || 0 }}%)</span>
                                <span v-if="discount.type === 'liberado'" class="inline-flex items-center">
                                    ({{ discount.value || 0 }}%)
                                    <span class="ml-2 px-2 py-0.5 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                                        LIBERADO
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen total de descuentos -->
                    <div v-if="discounts.length > 0" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="font-semibold text-gray-700">Precio base:</span>
                                <div class="text-lg font-bold text-turquesa">${{ formatNumber(basePrice) }}</div>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-700">Total descuentos:</span>
                                <div class="text-lg font-bold text-red-600">-${{ formatNumber(totalDiscounts) }}</div>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-700">Precio final:</span>
                                <div class="text-lg font-bold text-green-600">${{ formatNumber(finalPrice) }}</div>
                            </div>
                            <div>
                                <span class="font-semibold text-gray-700">Ahorro total:</span>
                                <div class="text-lg font-bold text-blue-600">{{ formatPercentage(totalDiscountPercentage) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Mensaje cuando no hay descuentos -->
                    <div v-else class="text-center py-8 text-gray-500 border-2 border-dashed border-gray-200 rounded-lg">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                        </svg>
                        <p class="text-sm">No hay descuentos aplicados</p>
                        <p class="text-xs mt-1">Haz clic en "Agregar Descuento" para comenzar</p>
                    </div>
                </div>

                <!-- Reestructuración de Cuotas (Oculto temporalmente - funcionalidad no utilizada) -->
                <div v-if="false" class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-[18px] font-nexa-bold text-turquesa">
                            Reestructuración de Cuotas
                        </h3>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                @click="showRestructureForm = !showRestructureForm"
                                :class="[
                                    'px-4 py-2 rounded-lg transition-colors text-sm font-medium',
                                    showRestructureForm 
                                        ? 'bg-gray-500 text-white hover:bg-gray-600' 
                                        : 'bg-turquesa text-white hover:bg-turquesa-dark'
                                ]"
                            >
                                {{ showRestructureForm ? 'Ocultar' : 'Reestructurar Cuotas' }}
                            </button>
                        </div>
                    </div>

                    <!-- Formulario de reestructuración -->
                    <div v-if="showRestructureForm" class="border border-gray-200 rounded-lg p-6 bg-gray-50">
                        <!-- Información del plan actual -->
                        <div class="mb-6">
                            <h4 class="text-[16px] font-nexa-bold text-gray-800 mb-3">
                                Plan de Cuotas Actual
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div class="bg-white p-3 rounded-lg border">
                                    <span class="font-semibold text-gray-700">Total de cuotas:</span>
                                    <div class="text-lg font-bold text-turquesa">{{ currentInstallmentPlan?.total_installments || 'N/A' }}</div>
                                </div>
                                <div class="bg-white p-3 rounded-lg border">
                                    <span class="font-semibold text-gray-700">Cuotas pagadas:</span>
                                    <div class="text-lg font-bold text-green-600">{{ paidInstallmentsCount || 0 }}</div>
                                </div>
                                <div class="bg-white p-3 rounded-lg border">
                                    <span class="font-semibold text-gray-700">Cuotas pendientes:</span>
                                    <div class="text-lg font-bold text-orange-600">{{ pendingInstallmentsCount || 0 }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Formulario de nueva estructura -->
                        <div class="mb-6">
                            <h4 class="text-[16px] font-nexa-bold text-gray-800 mb-3">
                                Nueva Estructura de Cuotas
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[14px] font-nexa-bold text-gray-700 mb-2">
                                        Nuevo número de cuotas *
                                    </label>
                                    <input
                                        v-model.number="restructureForm.newTotalInstallments"
                                        type="number"
                                        min="1"
                                        :max="maxPossibleInstallments"
                                        placeholder="Ej: 8"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                        :class="{ 'border-red-500': restructureErrors?.newTotalInstallments }"
                                    />
                                    <div v-if="restructureErrors?.newTotalInstallments" class="text-red-500 text-sm mt-1">
                                        {{ restructureErrors.newTotalInstallments }}
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Mínimo: {{ paidInstallmentsCount || 1 }} | Máximo: {{ maxPossibleInstallments }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-[14px] font-nexa-bold text-gray-700 mb-2">
                                        Razón del cambio *
                                    </label>
                                    <select
                                        v-model="restructureForm.reason"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                        :class="{ 'border-red-500': restructureErrors?.reason }"
                                    >
                                        <option value="">-- Selecciona una razón --</option>
                                        <option value="Solicitud del cliente">Solicitud del cliente</option>
                                        <option value="Cambio de situación financiera">Cambio de situación financiera</option>
                                        <option value="Ajuste por descuento aplicado">Ajuste por descuento aplicado</option>
                                        <option value="Reestructuración administrativa">Reestructuración administrativa</option>
                                        <option value="Otro">Otro</option>
                                    </select>
                                    <div v-if="restructureErrors?.reason" class="text-red-500 text-sm mt-1">
                                        {{ restructureErrors.reason }}
                                    </div>
                                </div>
                            </div>

                            <!-- Razón personalizada -->
                            <div v-if="restructureForm.reason === 'Otro'" class="mt-4">
                                <label class="block text-[14px] font-nexa-bold text-gray-700 mb-2">
                                    Especificar razón personalizada *
                                </label>
                                <textarea
                                    v-model="restructureForm.customReason"
                                    rows="3"
                                    placeholder="Describe la razón específica para reestructurar las cuotas..."
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-turquesa focus:border-transparent"
                                    :class="{ 'border-red-500': restructureErrors?.customReason }"
                                ></textarea>
                                <div v-if="restructureErrors?.customReason" class="text-red-500 text-sm mt-1">
                                    {{ restructureErrors.customReason }}
                                </div>
                            </div>
                        </div>

                        <!-- Resumen de la reestructuración -->
                        <div class="mb-6">
                            <h4 class="text-[16px] font-nexa-bold text-gray-800 mb-3">
                                Resumen de la Reestructuración
                            </h4>
                            <div class="bg-white border border-gray-200 rounded-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="font-semibold text-gray-700">Estado actual:</span>
                                        <div class="text-gray-600">
                                            {{ currentInstallmentPlan?.total_installments || 0 }} cuotas totales
                                            <span class="text-green-600">({{ paidInstallmentsCount || 0 }} pagadas)</span>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-gray-700">Nuevo estado:</span>
                                        <div class="text-turquesa font-semibold">
                                            {{ restructureForm.newTotalInstallments || 'N/A' }} cuotas totales
                                            <span class="text-green-600">({{ paidInstallmentsCount || 0 }} pagadas)</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 pt-3 border-t border-gray-200">
                                    <span class="font-semibold text-gray-700">Acción:</span>
                                    <div class="text-orange-600">
                                        Se cancelarán {{ (currentInstallmentPlan?.total_installments || 0) - (paidInstallmentsCount || 0) }} cuotas pendientes y se crearán {{ (restructureForm.newTotalInstallments || 0) - (paidInstallmentsCount || 0) }} nuevas cuotas
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botón de reestructuración -->
                        <div class="flex justify-end">
                            <button
                                type="button"
                                @click="restructureInstallments"
                                :disabled="!canRestructure || isRestructuring"
                                class="px-6 py-3 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed font-medium"
                            >
                                <span v-if="isRestructuring" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Reestructurando...
                                </span>
                                <span v-else>
                                    Confirmar Reestructuración
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Mensaje cuando no hay plan de cuotas -->
                    <div v-else-if="!currentInstallmentPlan" class="text-center py-8 text-gray-500 border-2 border-dashed border-gray-200 rounded-lg">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-sm">No hay plan de cuotas disponible</p>
                        <p class="text-xs mt-1">Este participante no tiene un plan de cuotas activo para reestructurar</p>
                    </div>
                </div>

                <!-- Botones -->
                <div
                    class="flex justify-end gap-4 pt-6 border-t border-gray-200"
                >
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="isSubmitting"
                        class="px-6 py-3 bg-turquesa text-white rounded-lg hover:bg-turquesa-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ isSubmitting ? "Guardando..." : "Guardar Cambios" }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    participant: {
        type: Object,
        default: () => ({}),
    },
    participantPrograms: {
        type: Array,
        default: () => [],
    },
    participantProgramsWithDiscounts: {
        type: Array,
        default: () => [],
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    preSelectedCourseId: {
        type: [String, Number],
        default: null,
    },
});

const emit = defineEmits(["close"]);

const isSubmitting = ref(false);
const discounts = ref([]);

const form = ref({
    first_last_name: "",
    second_last_name: "",
    first_name: "",
    second_name: "",
    document_number: "",
    birth_date: "",
    email: "",
    code_phone: "+56",
    phone: "",
    pivot_course_id: "",
    individual_price: "",
});

// Variables para reestructuración de cuotas
const showRestructureForm = ref(false);
const isRestructuring = ref(false);
const isRecalculating = ref(false);
const restructureErrors = ref({});
const restructureForm = ref({
    newTotalInstallments: null,
    reason: "",
    customReason: ""
});

// Computed properties para reestructuración
const currentInstallmentPlan = computed(() => {
    if (!form.value.pivot_course_id || !props.participantProgramsWithDiscounts) {
        return null;
    }

    // NOTA: pivot_course_id contiene el ProgramCourse.id (no Course.id)
    // Buscar directamente en participantProgramsWithDiscounts usando el program_id (que es el ProgramCourse.id)
    const participantProgram = props.participantProgramsWithDiscounts.find(
        pp => pp.program_id == form.value.pivot_course_id
    );

    return participantProgram?.installment_plan || null;
});

const paidInstallmentsCount = computed(() => {
    if (!currentInstallmentPlan.value) return 0;
    
    // Contar cuotas pagadas (que tienen payment_id O status 'paid')
    return currentInstallmentPlan.value.installments?.filter(
        installment => installment.payment_id || installment.status === 'paid'
    ).length || 0;
});

const pendingInstallmentsCount = computed(() => {
    if (!currentInstallmentPlan.value) return 0;
    
    // Contar cuotas pendientes (que NO tienen payment_id Y NO tienen status 'paid')
    return currentInstallmentPlan.value.installments?.filter(
        installment => !installment.payment_id && installment.status !== 'paid'
    ).length || 0;
});

const maxPossibleInstallments = computed(() => {
    // El máximo posible es el precio total dividido por un monto mínimo razonable por cuota
    // Por ejemplo, si el precio es 1,000,000, el mínimo por cuota podría ser 50,000
    const basePrice = Number(form.value.individual_price || 0);
    const minAmountPerInstallment = 50000; // 50,000 CLP mínimo por cuota
    
    if (basePrice <= 0) return 12; // Default máximo
    
    return Math.min(24, Math.floor(basePrice / minAmountPerInstallment)); // Máximo 24 cuotas
});

const canRestructure = computed(() => {
    return restructureForm.value.newTotalInstallments &&
           restructureForm.value.newTotalInstallments >= paidInstallmentsCount.value &&
           restructureForm.value.newTotalInstallments <= maxPossibleInstallments.value &&
           restructureForm.value.reason &&
           (restructureForm.value.reason !== 'Otro' || restructureForm.value.customReason) &&
           currentInstallmentPlan.value &&
           pendingInstallmentsCount.value > 0;
});

// Detectar si hay descuentos aplicados
const hasDiscountApplied = computed(() => {
    return discounts.value.length > 0;
});

// Obtener el programa actual seleccionado
const currentProgram = computed(() => {
    if (!form.value.pivot_course_id || !props.participantPrograms || props.participantPrograms.length === 0) {
        return null;
    }

    // Buscar en participantPrograms el programa que coincida con el ID seleccionado
    const program = props.participantPrograms.find(p => p.id == form.value.pivot_course_id);

    return program || null;
});

// Filtrar programas disponibles para el dropdown
// Mostrar programas con saldo pendiente, con descuentos existentes, o el programa actualmente seleccionado
const programsWithPendingBalance = computed(() => {
    if (!props.participantPrograms || props.participantPrograms.length === 0) {
        return [];
    }

    const currentSelectedId = form.value.pivot_course_id;

    // Obtener IDs de programas que tienen descuentos existentes
    const programsWithDiscountsIds = (props.participantProgramsWithDiscounts || [])
        .filter(pp => pp.discounts && pp.discounts.length > 0)
        .map(pp => pp.program_id);

    // Mostrar programas con saldo pendiente (balance > 0), con descuentos existentes, O el programa actualmente seleccionado
    return props.participantPrograms.filter(prog => {
        const balance = prog.participant_balance ?? 0;
        const hasExistingDiscounts = programsWithDiscountsIds.includes(prog.id);
        // Incluir si tiene saldo pendiente, tiene descuentos existentes, o es el programa actualmente seleccionado
        return balance > 0 || hasExistingDiscounts || prog.id == currentSelectedId;
    });
});

// Verificar si el programa actual ya está completamente pagado (para mostrar advertencia)
const currentProgramFullyPaid = computed(() => {
    if (!currentProgram.value) return false;
    const balance = currentProgram.value.participant_balance ?? 0;
    return balance <= 0;
});

// Obtener el status actual del programa en participant_program
const currentProgramStatus = computed(() => {
    if (!currentProgram.value) {
        return 'pending_payment';
    }

    // El status viene directamente del backend en participant_program_status
    return currentProgram.value.participant_program_status || 'pending_payment';
});

// Verificar si el programa actual tiene suscripción activa
const hasActiveSubscription = computed(() => {
    if (!currentProgram.value) {
        return false;
    }
    return currentProgram.value.has_active_subscription === true;
});

const formatDateForInput = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    return date.toISOString().split("T")[0];
};

// Cargar descuentos existentes desde la tabla participant_program_discounts
const loadExistingDiscounts = () => {
    const programCourseId = form.value.pivot_course_id;
    if (!programCourseId) {
        discounts.value = [];
        return;
    }

    // NOTA: pivot_course_id contiene el ProgramCourse.id (no Course.id)
    // Buscar directamente en participantProgramsWithDiscounts usando el program_id (que es el ProgramCourse.id)
    const participantProgram = props.participantProgramsWithDiscounts?.find(
        pp => pp.program_id == programCourseId
    );

    if (participantProgram && participantProgram.discounts) {
        discounts.value = participantProgram.discounts.map(discount => {
            // Determinar el tipo basado en los datos almacenados
            let type = "percent";
            let value = discount.percent || 0;

            // Si discount_type es 'released', es un liberado (con cualquier porcentaje)
            if (discount.discount_type === 'released') {
                type = "liberado";
                value = discount.percent || 100; // Usar el porcentaje almacenado
            } else if (discount.amount && discount.amount > 0) {
                type = "amount";
                value = discount.amount;
            }

            return {
                id: discount.id,
                type: type,
                value: value,
                comment: discount.comment,
                approved_by: discount.approved_by
            };
        });
    } else {
        discounts.value = [];
    }
};

// Cargar datos del participante cuando se abre el modal
watch(
    () => props.participant,
    (newParticipant) => {
        if (newParticipant && Object.keys(newParticipant).length > 0) {
            // NOTA: pivot_course_id debe contener ProgramCourse.id (no Course.id)
            // Usar preSelectedCourseId (que ya es ProgramCourse.id) o el primer programa de participantPrograms
            const defaultProgramCourseId = props.preSelectedCourseId ||
                (props.participantPrograms && props.participantPrograms[0]?.id) || "";

            form.value = {
                first_last_name: newParticipant.first_last_name || "",
                second_last_name: newParticipant.second_last_name || "",
                first_name: newParticipant.first_name || "",
                second_name: newParticipant.second_name || "",
                document_number: newParticipant.document_number || "",
                birth_date: formatDateForInput(newParticipant.birth_date),
                email: newParticipant.email || "",
                code_phone: newParticipant.code_phone || "+56",
                phone: newParticipant.phone || "",
                pivot_course_id: defaultProgramCourseId,
                individual_price: newParticipant.individual_price ?? "",
            };

            // Cargar descuentos existentes si los hay
            loadExistingDiscounts();
        }
    },
    { immediate: true, deep: true }
);

// Sincronizar cuando cambie el preSelectedCourseId
watch(
    () => props.preSelectedCourseId,
    (newProgramCourseId) => {
        if (newProgramCourseId && props.participantPrograms) {
            // NOTA: preSelectedCourseId contiene ProgramCourse.id
            // Verificar que el programa existe en participantPrograms
            const programExists = props.participantPrograms.some(p => p.id == newProgramCourseId);
            if (programExists) {
                form.value.pivot_course_id = newProgramCourseId;
                // Recargar descuentos para el programa seleccionado
                loadExistingDiscounts();
            }
        }
    },
    { immediate: true }
);

// Watcher para el plan de cuotas
watch(
    () => currentInstallmentPlan.value,
    (newPlan) => {
    },
    { immediate: true }
);

// Watcher para cambios en el curso seleccionado
watch(
    () => form.value.pivot_course_id,
    () => {
        // Actualizaciones necesarias cuando cambia el curso
    }
);

// Sincronizar precio individual mostrado según el curso/programa seleccionado
watch(
    () => form.value.pivot_course_id,
    (newProgramId) => {
        if (!newProgramId || !props.participantPrograms) {
            return;
        }
        // Buscar el programa por ID
        const program = props.participantPrograms.find((p) => p.id == newProgramId);
        if (program && program.participant_amount !== undefined) {
            form.value.individual_price = program.participant_amount;
        }

        // Recargar descuentos cuando cambie el programa
        loadExistingDiscounts();
    },
    { immediate: true }
);

// Watch para actualizar precio individual cuando cambie el programa seleccionado
watch(
    currentProgram,
    (newProgram) => {
        if (newProgram && newProgram.participant_amount !== undefined) {
            form.value.individual_price = newProgram.participant_amount;
        }
    }
);

// Funciones para el sistema de descuentos múltiples
const addDiscount = () => {
    discounts.value.push({
        type: "percent",
        value: 0,
        comment: "",
    });
};

const removeDiscount = (index) => {
    discounts.value.splice(index, 1);
};

const getDiscountValueLabel = (type) => {
    switch (type) {
        case "percent": return "Porcentaje (%)";
        case "amount": return "Monto (CLP)";
        case "liberado": return "Porcentaje (%)";
        default: return "Valor";
    }
};

const getDiscountValuePlaceholder = (type) => {
    switch (type) {
        case "percent": return "Ej: 10";
        case "amount": return "Ej: 50000";
        case "liberado": return "Ej: 50 (%)";
        default: return "";
    }
};

const calculateDiscountAmount = (discount) => {
    const basePrice = Number(form.value.individual_price || 0);

    if (discount.type === "liberado" || discount.type === "percent") {
        // Liberado ahora usa porcentaje variable (igual que percent)
        const percentage = Math.max(0, Math.min(100, Number(discount.value || 0)));
        return (basePrice * percentage) / 100;
    }

    if (discount.type === "amount") {
        return Math.min(basePrice, Math.max(0, Number(discount.value || 0)));
    }

    return 0;
};

// Computed properties para el resumen
const basePrice = computed(() => Number(form.value.individual_price || 0));

const totalDiscounts = computed(() => {
    return discounts.value.reduce((total, discount) => {
        return total + calculateDiscountAmount(discount);
    }, 0);
});

const finalPrice = computed(() => {
    return Math.max(0, basePrice.value - totalDiscounts.value);
});

const totalDiscountPercentage = computed(() => {
    if (basePrice.value === 0) return 0;
    return (totalDiscounts.value / basePrice.value) * 100;
});

const updateParticipant = () => {
    // Validar descuentos antes de enviar
    if (discounts.value.length > 0) {
        // Validar que hay un programa seleccionado
        if (!form.value.pivot_course_id) {
            alert('Debes seleccionar un programa para aplicar los descuentos.');
            return;
        }

        // Validar cada descuento
        for (let i = 0; i < discounts.value.length; i++) {
            const discount = discounts.value[i];

            // Validar descripción
            if (!discount.comment || discount.comment.trim() === '') {
                alert(`El descuento #${i + 1} debe tener una descripción.`);
                return;
            }

            // Validar valor para todos los tipos
            if (!discount.value || discount.value <= 0) {
                alert(`El descuento #${i + 1} debe tener un valor mayor a 0.`);
                return;
            }

            // Validar porcentaje máximo (aplica a percent y liberado)
            if ((discount.type === 'percent' || discount.type === 'liberado') && discount.value > 100) {
                alert(`El descuento #${i + 1} no puede ser mayor al 100%.`);
                return;
            }
        }
    }

    isSubmitting.value = true;

    const formData = new FormData();
    formData.append("first_last_name", form.value.first_last_name);
    formData.append("second_last_name", form.value.second_last_name);
    formData.append("first_name", form.value.first_name);
    formData.append("second_name", form.value.second_name);
    formData.append("document_number", form.value.document_number);
    formData.append("birth_date", form.value.birth_date);
    formData.append("email", form.value.email);
    formData.append("code_phone", form.value.code_phone);
    formData.append("phone", form.value.phone);

    if (form.value.pivot_course_id) {
        formData.append("pivot_course_id", form.value.pivot_course_id);
    }

    if (form.value.individual_price !== "") {
        formData.append("individual_price", form.value.individual_price);
    }

    // Enviar los descuentos como JSON para procesarlos en el backend
    formData.append("discounts", JSON.stringify(discounts.value));

    formData.append("_method", "PUT");

    router.post(
        route("admin.participants.update", props.participant.id),
        formData,
        {
            onSuccess: async () => {
                // AUTO-RECÁLCULO: Recalcular cuotas después de aplicar/cancelar descuentos
                if (currentInstallmentPlan.value && hasDiscountApplied.value) {
                    try {
                        await recalculateInstallmentsAfterDiscountChange();
                    } catch (error) {
                        console.warn('No se pudieron recalcular las cuotas automáticamente:', error);
                    }
                }
                
                // Cerrar el modal primero
                emit("close");
                // Luego recargar la página
                window.location.reload();
            },
            onError: (errors) => {
                isSubmitting.value = false;
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        }
    );
};

// Toggle del status del programa (pending_payment <-> cancelled)
const toggleProgramStatus = () => {
    if (!currentProgram.value) {
        alert('Por favor selecciona un curso/programa primero');
        return;
    }

    const statusText = currentProgramStatus.value === 'cancelled' ? 'reactivar' : 'cancelar';
    const confirmMessage = `¿Estás seguro de que quieres ${statusText} este programa para el participante?`;

    if (!confirm(confirmMessage)) {
        return;
    }

    router.post(
        route('admin.participants.toggle-program-status', {
            participant: props.participant.id,
            program: currentProgram.value.id
        }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                window.location.reload();
            },
            onError: (errors) => {
                const errorMessage = errors.error || errors.message || 'Error desconocido';
                alert('❌ Error: ' + errorMessage);
            }
        }
    );
};

// Formatear documento para visualización
const formattedDocument = computed(() => {
    const document = form.value.document_number || "";
    const documentType = props.participant?.document_type || 'RUT';
    
    if (documentType === 'RUT') {
        const clean = document.replace(/\./g, "").replace(/-/g, "");
        if (clean.length < 2) return document;
        const body = clean.slice(0, -1);
        const dv = clean.slice(-1);
        const withDots = body.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        return `${withDots}-${dv}`;
    } else {
        // Para pasaporte u otros documentos, mostrar en uppercase
        return document.toUpperCase();
    }
});

const formatNumber = (n) => new Intl.NumberFormat('es-CL').format(Number(n || 0));

const formatPercentage = (n) => {
    return `${Number(n || 0).toFixed(1)}%`;
};

// Método para reestructurar cuotas
const restructureInstallments = async () => {
    if (!canRestructure.value) {
        return;
    }

    // Validar formulario
    restructureErrors.value = {};
    
    if (!restructureForm.value.newTotalInstallments) {
        restructureErrors.value.newTotalInstallments = 'El número de cuotas es requerido';
        return;
    }
    
    if (restructureForm.value.newTotalInstallments < paidInstallmentsCount.value) {
        restructureErrors.value.newTotalInstallments = `No se puede reducir a menos de ${paidInstallmentsCount.value} cuotas (ya pagadas)`;
        return;
    }
    
    if (restructureForm.value.newTotalInstallments > maxPossibleInstallments.value) {
        restructureErrors.value.newTotalInstallments = `El máximo permitido es ${maxPossibleInstallments.value} cuotas`;
        return;
    }
    
    if (!restructureForm.value.reason) {
        restructureErrors.value.reason = 'La razón del cambio es requerida';
        return;
    }
    
    if (restructureForm.value.reason === 'Otro' && !restructureForm.value.customReason) {
        restructureErrors.value.customReason = 'Debe especificar la razón personalizada';
        return;
    }

    // Confirmar acción
    const finalReason = restructureForm.value.reason === 'Otro' 
        ? restructureForm.value.customReason 
        : restructureForm.value.reason;
    
    const confirmMessage = `¿Estás seguro de que quieres reestructurar las cuotas?\n\n` +
        `Cambio: ${currentInstallmentPlan.value.total_installments} → ${restructureForm.value.newTotalInstallments} cuotas\n` +
        `Razón: ${finalReason}\n\n` +
        `Esta acción:\n` +
        `• Mantendrá las ${paidInstallmentsCount.value} cuotas ya pagadas\n` +
        `• Cancelará ${pendingInstallmentsCount.value} cuotas pendientes\n` +
        `• Creará ${restructureForm.value.newTotalInstallments - paidInstallmentsCount.value} nuevas cuotas\n\n` +
        `¿Deseas continuar?`;

    if (!confirm(confirmMessage)) {
        return;
    }

    isRestructuring.value = true;
    restructureErrors.value = {};

    try {
        // Usar Inertia.js para evitar problemas de CSRF
        router.post(route('admin.installments.restructure'), {
            installment_plan_id: currentInstallmentPlan.value.id,
            new_total_installments: restructureForm.value.newTotalInstallments,
            reason: finalReason
        }, {
            onSuccess: (page) => {
                // Éxito - mostrar mensaje de éxito
                alert(`✅ Cuotas reestructuradas exitosamente!\n\n` +
                    `El plan ahora tiene ${restructureForm.value.newTotalInstallments} cuotas.\n` +
                    `Se han eliminado ${pendingInstallmentsCount.value} cuotas pendientes y creado ${restructureForm.value.newTotalInstallments - paidInstallmentsCount.value} nuevas cuotas.`);
                
                // Limpiar formulario
                restructureForm.value = {
                    newTotalInstallments: null,
                    reason: "",
                    customReason: ""
                };
                showRestructureForm.value = false;
                
                // Cerrar el modal completo
                emit('close');
                
                // Recargar la página para mostrar los cambios
                window.location.reload();
            },
            onError: (errors) => {
                console.error('Error al reestructurar cuotas:', errors);
                
                // Manejar errores específicos
                if (errors.installment_plan_id) {
                    restructureErrors.value.general = 'Error: Plan de cuotas no válido';
                } else if (errors.new_total_installments) {
                    restructureErrors.value.newTotalInstallments = errors.new_total_installments;
                } else if (errors.reason) {
                    restructureErrors.value.reason = errors.reason;
                } else if (errors.restructure) {
                    restructureErrors.value.general = errors.restructure;
                } else {
                    restructureErrors.value.general = 'Error al reestructurar las cuotas. Por favor, inténtalo de nuevo.';
                }
                
                // Mostrar error al usuario
                alert(`❌ Error al reestructurar cuotas:\n\n${restructureErrors.value.general}`);
            },
            onFinish: () => {
                isRestructuring.value = false;
            }
        });
    } catch (error) {
        console.error('Error al reestructurar cuotas:', error);
        
        if (error.name === 'TypeError' && error.message.includes('fetch')) {
            restructureErrors.value.general = 'Error de conexión. Verifica tu conexión a internet.';
        } else {
            restructureErrors.value.general = error.message || 'Error inesperado al reestructurar las cuotas.';
        }
        
        alert(`❌ Error al reestructurar cuotas:\n\n${restructureErrors.value.general}`);
        isRestructuring.value = false;
    }
};

    // Método para recalcular cuotas después de aplicar descuento
    const recalculateInstallmentsAfterDiscount = async () => {
        if (!currentInstallmentPlan.value) {
            alert('❌ No se encontró un plan de cuotas activo');
            return;
        }

        // Confirmar acción
        const confirmMessage = `¿Estás seguro de que quieres recalcular las cuotas?\n\n` +
            `Esta acción recalculará las cuotas pendientes basándose en el precio final con descuentos aplicados.\n\n` +
            `• Se mantendrán las cuotas ya pagadas\n` +
            `• Se recalcularán las cuotas pendientes\n` +
            `• Los montos se ajustarán al nuevo precio final`;

        if (!confirm(confirmMessage)) {
            return;
        }

        isRecalculating.value = true;

        try {
            const response = await fetch('/admin/installments/recalculate-after-discount', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    installment_plan_id: currentInstallmentPlan.value.id
                })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                // Éxito
                alert(`✅ Cuotas recalculadas exitosamente!\n\n` +
                    `• Precio anterior: $${formatNumber(result.data.old_total_amount)}\n` +
                    `• Precio nuevo: $${formatNumber(result.data.new_total_amount)}\n` +
                    `• Descuento aplicado: $${formatNumber(result.data.discount_applied)}\n` +
                    `• Monto pagado: $${formatNumber(result.data.paid_amount)}\n` +
                    `• Nuevo saldo pendiente: $${formatNumber(result.data.new_remaining_balance)}\n` +
                    `• Cuotas recalculadas: ${result.data.cuotas_nuevas}`);
                
                // Cerrar el modal completo
                emit('close');
                
                // Recargar la página para mostrar los cambios
                window.location.reload();
            } else {
                // Error
                const errorMessage = result.error || 'Error desconocido al recalcular las cuotas';
                alert(`❌ Error: ${errorMessage}`);
            }
        } catch (error) {
            console.error('Error al recalcular cuotas:', error);
            alert('❌ Error de conexión al recalcular las cuotas');
        } finally {
            isRecalculating.value = false;
        }
    };

    // Método para recalcular cuotas automáticamente después de cambios en descuentos
    const recalculateInstallmentsAfterDiscountChange = async () => {
        if (!currentInstallmentPlan.value) {
            return; // No hay plan de cuotas, no hacer nada
        }

        try {
            const response = await fetch('/admin/installments/recalculate-after-discount', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    installment_plan_id: currentInstallmentPlan.value.id
                })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                console.log('✅ Cuotas recalculadas automáticamente después de cambio en descuentos:', result.data);
            } else {
                console.warn('⚠️ No se pudieron recalcular las cuotas automáticamente:', result.error);
            }
        } catch (error) {
            console.warn('⚠️ Error al recalcular cuotas automáticamente:', error);
        }
    };
</script>

<style scoped>
.text-turquesa {
    color: #007e93;
}

.bg-turquesa {
    background-color: #007e93;
}

.bg-turquesa-dark {
    background-color: #006b7d;
}

.focus\:ring-turquesa:focus {
    --tw-ring-color: #007e93;
}

.font-nexa-bold {
    font-family: "Nexa-Bold", sans-serif;
}
</style>
