<template>
    <AdminLayout>
        <Head :title="`Suscripción #${subscription.id}`" />

        <!-- Sistema de Alertas -->
        <AlertWrapper ref="alertWrapper" />

        <div class="bg-white min-h-screen">
            <!-- Header -->
            <div class="px-8 py-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <Link
                            :href="route('admin.subscriptions.index')"
                            class="text-sm text-gray-600 hover:text-gray-900 mb-2 inline-flex items-center"
                        >
                            <svg
                                class="w-4 h-4 mr-1"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 19l-7-7 7-7"
                                ></path>
                            </svg>
                            Volver a Suscripciones
                        </Link>
                        <h1 class="text-2xl font-bold text-gray-900">
                            Detalle de Suscripción
                        </h1>
                        <p class="text-sm text-gray-600 mt-1">
                            ID VirtualPos: {{ subscription.virtualpos_subscription_id }}
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <button
                            v-if="canCancel"
                            @click="cancelSubscription"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center gap-2"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                ></path>
                            </svg>
                            Cancelar Suscripción
                        </button>
                        <button
                            @click="resendSubscriptionEmail"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2"
                            :disabled="isProcessing"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                ></path>
                            </svg>
                            Reenviar Email Suscripción
                        </button>
                        <button
                            @click="syncWithVirtualPos"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
                            :disabled="isProcessing"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                ></path>
                            </svg>
                            Sincronizar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Status Badge -->
            <div class="px-8 py-4 bg-gray-50 border-b border-gray-200">
                <div class="flex items-center gap-4">
                    <span
                        :class="[
                            'px-4 py-2 rounded-full text-sm font-medium',
                            getStatusClass(subscription.status),
                        ]"
                    >
                        {{ getStatusLabel(subscription.status) }}
                    </span>
                    <span class="text-sm text-gray-600">
                        Fecha de creación: {{ subscription.created_at }}
                    </span>
                </div>
            </div>

            <!-- Information Cards -->
            <div class="px-8 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Participant Card -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3
                            class="text-lg font-semibold text-gray-900 mb-4 flex items-center"
                        >
                            <svg
                                class="w-5 h-5 mr-2 text-blue-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                ></path>
                            </svg>
                            Información del Participante
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600">Nombre</p>
                                <p class="text-base font-medium text-gray-900">
                                    {{ subscription.participant.name }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Documento</p>
                                <p class="text-base font-medium text-gray-900">
                                    {{ subscription.participant.document_type }}:
                                    {{ subscription.participant.document }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Email</p>
                                <p class="text-base font-medium text-gray-900">
                                    {{ subscription.participant.email }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Program Card -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <h3
                            class="text-lg font-semibold text-gray-900 mb-4 flex items-center"
                        >
                            <svg
                                class="w-5 h-5 mr-2 text-green-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                ></path>
                            </svg>
                            Información del Programa
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600">Programa</p>
                                <p class="text-base font-medium text-gray-900">
                                    {{ subscription.program.name }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Destino</p>
                                <p class="text-base font-medium text-gray-900">
                                    {{ subscription.program.destination }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">
                                    Fecha de partida
                                </p>
                                <p class="text-base font-medium text-gray-900">
                                    {{ formatDate(subscription.program.departure_date) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Institución</p>
                                <p class="text-base font-medium text-gray-900">
                                    {{ subscription.institution.name }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method Card -->
                <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                    <h3
                        class="text-lg font-semibold text-gray-900 mb-4 flex items-center"
                    >
                        <svg
                            class="w-5 h-5 mr-2 text-purple-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                            ></path>
                        </svg>
                        Método de Pago
                    </h3>
                    <div class="text-base font-medium text-gray-900">
                        {{ formatPaymentMethod(subscription.payment_method) }}
                    </div>
                </div>

                <!-- Plan Information Card (Solo para planes personalizados) -->
                <div
                    v-if="subscription.plan?.is_personalized"
                    class="bg-purple-50 border border-purple-200 rounded-lg p-6 mb-6"
                >
                    <h3
                        class="text-lg font-semibold text-gray-900 mb-4 flex items-center"
                    >
                        <svg
                            class="w-5 h-5 mr-2 text-purple-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"
                            ></path>
                        </svg>
                        Plan Personalizado
                        <span class="ml-2 px-2 py-1 text-xs font-medium bg-purple-500 text-white rounded-full">
                            {{ getDiscountTypeLabel(subscription.plan.discount_type) }}
                        </span>
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Precio Original</p>
                            <p class="text-base font-medium text-gray-900 line-through">
                                ${{ formatAmount(subscription.plan.original_price) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Descuento</p>
                            <p class="text-base font-medium text-red-600">
                                -${{ formatAmount(subscription.plan.discount_amount) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Precio Final</p>
                            <p class="text-base font-medium text-green-600">
                                ${{ formatAmount(subscription.plan.trip_price) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Cuota Mensual</p>
                            <p class="text-base font-medium text-gray-900">
                                ${{ formatAmount(subscription.plan.monthly_amount) }}
                            </p>
                        </div>
                    </div>
                    <div v-if="subscription.plan.discount_reason" class="mt-4">
                        <p class="text-sm text-gray-600">Motivo del descuento:</p>
                        <p class="text-base text-gray-900">
                            {{ subscription.plan.discount_reason }}
                        </p>
                    </div>
                </div>

                <!-- Installments Section -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3
                            class="text-lg font-semibold text-gray-900 flex items-center"
                        >
                            <svg
                                class="w-5 h-5 mr-2 text-orange-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                ></path>
                            </svg>
                            Plan de Cuotas
                        </h3>
                        <div class="flex items-center gap-4">
                            <div class="text-sm text-gray-600">
                                <span class="font-medium text-green-600">
                                    {{ subscription.paid_installments }}
                                </span>
                                de
                                <span class="font-medium text-gray-900">
                                    {{ subscription.total_installments }}
                                </span>
                                cuotas pagadas
                            </div>
                            <button
                                v-if="canCreateCharge"
                                @click="showNewChargeModal = true"
                                class="px-3 py-1.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors flex items-center gap-1"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Nuevo Cargo
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="subscription.installments.length > 0"
                        class="overflow-x-auto"
                    >
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Cuota
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Monto
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Fecha de vencimiento
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Estado
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Fecha de pago
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        ID VirtualPos
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="installment in subscription.installments"
                                    :key="installment.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"
                                    >
                                        Cuota {{ installment.installment_number }}
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        ${{ formatAmount(installment.amount) }}
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ installment.due_date }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            :class="[
                                                'px-2 py-1 text-xs font-medium rounded-full',
                                                getInstallmentStatusClass(
                                                    installment
                                                ),
                                            ]"
                                        >
                                            {{
                                                getInstallmentStatusLabel(
                                                    installment
                                                )
                                            }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{
                                            installment.paid_at
                                                ? installment.paid_at
                                                : "-"
                                        }}
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 font-mono"
                                    >
                                        {{
                                            installment.virtualpos_charge_id ||
                                            "-"
                                        }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <!-- Botón Cobrar/Reintentar (solo para cuotas pendientes no pagadas y no canceladas) -->
                                            <button
                                                v-if="canShowChargeButton(installment)"
                                                @click="createCharge(installment)"
                                                class="px-3 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700 transition-colors"
                                                :disabled="isProcessing"
                                            >
                                                {{ installment.virtualpos_charge_id ? 'Reintentar' : 'Cobrar' }}
                                            </button>
                                            <!-- Botón Eliminar Cargo -->
                                            <button
                                                v-if="canShowDeleteButton(installment)"
                                                @click="deleteCharge(installment)"
                                                class="px-2 py-1 text-xs font-medium text-white bg-red-600 rounded hover:bg-red-700 transition-colors"
                                                :disabled="isProcessing"
                                                title="Eliminar cargo"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                            <!-- Estado final (pagado o cancelado) -->
                                            <span v-if="installment.is_paid" class="text-green-600 text-xs font-medium">
                                                Pagado
                                            </span>
                                            <span v-else-if="installment.status === 'cancelled'" class="text-red-600 text-xs font-medium">
                                                Cancelado
                                            </span>
                                            <!-- Botón Reenviar Email (solo para cuotas pagadas) -->
                                            <button
                                                v-if="installment.is_paid"
                                                @click="resendPaymentEmail(installment)"
                                                class="px-2 py-1 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700 transition-colors"
                                                :disabled="isProcessing"
                                                title="Reenviar email de pago"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-else
                        class="text-center py-8 text-gray-500"
                    >
                        No hay cuotas registradas para esta suscripción.
                    </div>
                </div>

                <!-- Additional Charges Section -->
                <div
                    v-if="subscription.additional_charges && subscription.additional_charges.length > 0"
                    class="bg-white border border-gray-200 rounded-lg p-6 mt-6"
                >
                    <h3
                        class="text-lg font-semibold text-gray-900 mb-4 flex items-center"
                    >
                        <svg
                            class="w-5 h-5 mr-2 text-indigo-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                        Cargos Adicionales
                    </h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Estos son cargos creados manualmente que no están asociados a cuotas del plan original.
                    </p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Descripción
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Monto
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Fecha de cobro
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Estado
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        ID VirtualPos
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="charge in subscription.additional_charges"
                                    :key="charge.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"
                                    >
                                        {{ charge.description }}
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        ${{ formatAmount(charge.amount) }}
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ charge.charge_date || '-' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            :class="[
                                                'px-2 py-1 text-xs font-medium rounded-full',
                                                getChargeStatusClass(charge.status),
                                            ]"
                                        >
                                            {{ getChargeStatusLabel(charge.status) }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 font-mono"
                                    >
                                        {{ charge.id || '-' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para crear nuevo cargo -->
        <div
            v-if="showNewChargeModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            @click.self="showNewChargeModal = false"
        >
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    Crear Nuevo Cargo
                </h3>
                <form @submit.prevent="submitNewCharge">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Monto (CLP) *
                            </label>
                            <input
                                type="number"
                                v-model="newChargeForm.amount"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="125000"
                                required
                                min="1"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Descripción *
                            </label>
                            <input
                                type="text"
                                v-model="newChargeForm.description"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Cargo adicional - Descripción"
                                required
                                maxlength="255"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Fecha de cobro (opcional)
                            </label>
                            <input
                                type="date"
                                v-model="newChargeForm.due_date"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button
                            type="button"
                            @click="showNewChargeModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors"
                            :disabled="isProcessing"
                        >
                            Crear Cargo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AlertWrapper from "@/Components/Admin/AlertWrapper.vue";

const props = defineProps({
    subscription: {
        type: Object,
        required: true,
    },
});

const isProcessing = ref(false);
const showNewChargeModal = ref(false);
const newChargeForm = ref({
    amount: '',
    description: '',
    due_date: '',
});

const canCancel = computed(() => {
    return (
        props.subscription.status === "ACTIVA" ||
        props.subscription.status === "SUSCRIBIENDO"
    );
});

const canCreateCharge = computed(() => {
    return props.subscription.status === "ACTIVA";
});

// Verificar si se puede mostrar el botón de cobrar/reintentar
const canShowChargeButton = (installment) => {
    return !installment.is_paid &&
           installment.status !== 'cancelled' &&
           canCreateCharge.value;
};

// Verificar si se puede mostrar el botón de eliminar
const canShowDeleteButton = (installment) => {
    return !installment.is_paid &&
           installment.status !== 'cancelled' &&
           installment.virtualpos_charge_id &&
           canCreateCharge.value;
};

const getStatusLabel = (status) => {
    const labels = {
        ACTIVA: "Activa",
        SUSCRIBIENDO: "Suscribiendo",
        PENDIENTE: "Pendiente",
        CANCELADA: "Cancelada",
        RECHAZADA: "Rechazada",
        SUSCRIPCION_FALLIDA: "Suscripción Fallida",
    };
    return labels[status] || status;
};

const getStatusClass = (status) => {
    const classes = {
        ACTIVA: "bg-green-100 text-green-800",
        SUSCRIBIENDO: "bg-yellow-100 text-yellow-800",
        PENDIENTE: "bg-blue-100 text-blue-800",
        CANCELADA: "bg-red-100 text-red-800",
        RECHAZADA: "bg-red-100 text-red-800",
        SUSCRIPCION_FALLIDA: "bg-red-100 text-red-800",
    };
    return classes[status] || "bg-gray-100 text-gray-800";
};

const formatPaymentMethod = (paymentMethod) => {
    if (!paymentMethod) return "N/A";

    if (typeof paymentMethod === "string") {
        return paymentMethod;
    }

    if (paymentMethod.card_brand && paymentMethod.last_four_digits) {
        return `${paymentMethod.card_brand.toUpperCase()} •••• ${paymentMethod.last_four_digits}`;
    }

    return "Suscripción";
};

const formatAmount = (amount) => {
    return new Intl.NumberFormat("es-CL").format(amount);
};

const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    const date = new Date(dateString);
    return date.toLocaleDateString("es-CL", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    });
};

const getDiscountTypeLabel = (discountType) => {
    const labels = {
        'scholarship': 'Beca',
        'released': 'Liberado',
    };
    return labels[discountType] || discountType || 'Descuento';
};

const getInstallmentStatusLabel = (installment) => {
    if (installment.is_paid) {
        return "Pagado";
    }

    const statuses = {
        pending: "Pendiente",
        paid: "Pagado",
        cancelled: "Cancelado",
        overdue: "Vencido",
    };

    return statuses[installment.status] || installment.status;
};

const getInstallmentStatusClass = (installment) => {
    if (installment.is_paid) {
        return "bg-green-100 text-green-800";
    }

    const classes = {
        pending: "bg-yellow-100 text-yellow-800",
        paid: "bg-green-100 text-green-800",
        cancelled: "bg-red-100 text-red-800",
        overdue: "bg-red-100 text-red-800",
    };

    return classes[installment.status] || "bg-gray-100 text-gray-800";
};

const getChargeStatusLabel = (status) => {
    const labels = {
        pendiente: "Pendiente",
        pending: "Pendiente",
        pagado: "Pagado",
        paid: "Pagado",
        cancelado: "Cancelado",
        cancelled: "Cancelado",
        rechazado: "Rechazado",
        rejected: "Rechazado",
    };
    return labels[status?.toLowerCase()] || status || "Desconocido";
};

const getChargeStatusClass = (status) => {
    const normalizedStatus = status?.toLowerCase() || "";

    if (["pagado", "paid"].includes(normalizedStatus)) {
        return "bg-green-100 text-green-800";
    }
    if (["pendiente", "pending"].includes(normalizedStatus)) {
        return "bg-yellow-100 text-yellow-800";
    }
    if (["cancelado", "cancelled", "rechazado", "rejected"].includes(normalizedStatus)) {
        return "bg-red-100 text-red-800";
    }

    return "bg-gray-100 text-gray-800";
};

const cancelSubscription = () => {
    if (
        confirm(
            `¿Estás seguro de que deseas cancelar la suscripción de ${props.subscription.participant.name}?\n\nEsta acción no se puede deshacer y cancelará todos los cobros pendientes en VirtualPos.`
        )
    ) {
        router.delete(
            route("admin.subscriptions.cancel", props.subscription.id),
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.visit(route("admin.subscriptions.index"));
                },
            }
        );
    }
};

const syncWithVirtualPos = () => {
    isProcessing.value = true;
    router.post(
        route("admin.subscriptions.sync", props.subscription.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isProcessing.value = false;
            },
        }
    );
};

const resendSubscriptionEmail = () => {
    if (
        confirm(
            `¿Reenviar el email de confirmación de suscripción a ${props.subscription.participant.email}?`
        )
    ) {
        isProcessing.value = true;
        router.post(
            route("admin.subscriptions.resend-subscription-email", props.subscription.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    isProcessing.value = false;
                },
            }
        );
    }
};

const resendPaymentEmail = (installment) => {
    if (
        confirm(
            `¿Reenviar el email de confirmación de pago de la cuota ${installment.installment_number}?`
        )
    ) {
        isProcessing.value = true;
        router.post(
            route("admin.subscriptions.resend-payment-email", props.subscription.id),
            {
                installment_number: installment.installment_number,
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    isProcessing.value = false;
                },
            }
        );
    }
};

const createCharge = (installment) => {
    const action = installment.virtualpos_charge_id ? 'reintentar el cobro de' : 'crear un cobro para';

    if (
        confirm(
            `¿Estás seguro de que deseas ${action} la cuota ${installment.installment_number}?\n\nMonto: $${new Intl.NumberFormat("es-CL").format(installment.amount)}`
        )
    ) {
        isProcessing.value = true;

        const routeName = installment.virtualpos_charge_id
            ? "admin.subscriptions.retry-charge"
            : "admin.subscriptions.charge";

        router.post(
            route(routeName, props.subscription.id),
            {
                installment_id: installment.id,
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    isProcessing.value = false;
                },
            }
        );
    }
};

const deleteCharge = (installment) => {
    if (
        confirm(
            `¿Estás seguro de que deseas eliminar el cargo de la cuota ${installment.installment_number}?\n\nID del cargo: ${installment.virtualpos_charge_id}`
        )
    ) {
        isProcessing.value = true;

        router.delete(
            route("admin.subscriptions.delete-charge", props.subscription.id),
            {
                data: {
                    installment_id: installment.id,
                },
                preserveScroll: true,
                onFinish: () => {
                    isProcessing.value = false;
                },
            }
        );
    }
};

const submitNewCharge = () => {
    if (!newChargeForm.value.amount || !newChargeForm.value.description) {
        alert('Por favor completa los campos obligatorios');
        return;
    }

    isProcessing.value = true;

    router.post(
        route("admin.subscriptions.new-charge", props.subscription.id),
        {
            amount: parseInt(newChargeForm.value.amount),
            description: newChargeForm.value.description,
            due_date: newChargeForm.value.due_date || null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showNewChargeModal.value = false;
                newChargeForm.value = {
                    amount: '',
                    description: '',
                    due_date: '',
                };
            },
            onFinish: () => {
                isProcessing.value = false;
            },
        }
    );
};
</script>
