<template>
    <AdminLayout>
        <Head title="Reportes y Estadísticas" />

        <div class="py-12">
            <div class="max-w-[1800px] mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">
                        Reportes
                    </h2>
                    <!-- Solo visible para usuarios con permiso ver_contacto_pagador -->
                    <Link
                        v-if="canViewPayerContact"
                        :href="route('admin.reports.executives.index')"
                        class="bg-teal-500 hover:bg-teal-600 text-white font-semibold py-2 px-4 rounded-lg flex items-center transition-colors"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Reportes Ejecutivo Comercial
                    </Link>
                </div>

                <!-- Layout de 2 columnas -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- Columna izquierda: Menú de Reportes -->
                    <div class="lg:col-span-3">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Menú de Reportes</h3>
                                <nav class="space-y-1">
                                    <!-- Estado de Cuenta Parcial -->
                                    <Link
                                        :href="route('admin.reports.partial-account')"
                                        class="flex items-center px-3 py-2 text-sm font-medium rounded-md hover:bg-blue-50 transition-colors group"
                                    >
                                        <svg class="w-5 h-5 mr-3 text-blue-400 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <span class="text-gray-700 group-hover:text-blue-700">Estado de Cuenta Parcial</span>
                                    </Link>

                                    <!-- Cronograma de Recuperación -->
                                    <Link
                                        :href="route('admin.reports.payment-schedule')"
                                        class="flex items-center px-3 py-2 text-sm font-medium rounded-md hover:bg-green-50 transition-colors group"
                                    >
                                        <svg class="w-5 h-5 mr-3 text-green-400 group-hover:text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="text-gray-700 group-hover:text-green-700">Cronograma de Recuperación</span>
                                    </Link>

                                    <!-- Pagos Diarios -->
                                    <Link
                                        :href="route('admin.reports.daily-payments')"
                                        class="flex items-center px-3 py-2 text-sm font-medium rounded-md hover:bg-yellow-50 transition-colors group"
                                    >
                                        <svg class="w-5 h-5 mr-3 text-yellow-400 group-hover:text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                        <span class="text-gray-700 group-hover:text-yellow-700">Pagos Diarios</span>
                                    </Link>

                                    <!-- Consolidado de Pagos -->
                                    <Link
                                        :href="route('admin.reports.consolidated-payments')"
                                        class="flex items-center px-3 py-2 text-sm font-medium rounded-md hover:bg-purple-50 transition-colors group"
                                    >
                                        <svg class="w-5 h-5 mr-3 text-purple-400 group-hover:text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                        <span class="text-gray-700 group-hover:text-purple-700">Consolidado de Pagos</span>
                                    </Link>

                                    <!-- Softland -->
                                    <Link
                                        :href="route('admin.reports.softland')"
                                        class="flex items-center px-3 py-2 text-sm font-medium rounded-md hover:bg-indigo-50 transition-colors group"
                                    >
                                        <svg class="w-5 h-5 mr-3 text-indigo-400 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <span class="text-gray-700 group-hover:text-indigo-700">Softland</span>
                                    </Link>

                                    <!-- Documentos Generados -->
                                    <Link
                                        :href="route('admin.reports.bsale-documents')"
                                        class="flex items-center px-3 py-2 text-sm font-medium rounded-md hover:bg-red-50 transition-colors group"
                                    >
                                        <svg class="w-5 h-5 mr-3 text-red-400 group-hover:text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="text-gray-700 group-hover:text-red-700">Documentos Generados</span>
                                    </Link>

                                    <!-- Aceptación TyC -->
                                    <Link
                                        :href="route('admin.reports.terms-acceptance')"
                                        class="flex items-center px-3 py-2 text-sm font-medium rounded-md hover:bg-pink-50 transition-colors group"
                                    >
                                        <svg class="w-5 h-5 mr-3 text-pink-400 group-hover:text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span class="text-gray-700 group-hover:text-pink-700">Aceptación TyC</span>
                                    </Link>

                                    <!-- Cuotas Pagadas -->
                                    <Link
                                        :href="route('admin.reports.paid-installments')"
                                        class="flex items-center px-3 py-2 text-sm font-medium rounded-md hover:bg-teal-50 transition-colors group"
                                    >
                                        <svg class="w-5 h-5 mr-3 text-teal-400 group-hover:text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                        <span class="text-gray-700 group-hover:text-teal-700">Cuotas Pagadas</span>
                                    </Link>

                                    <!-- Reporte TI -->
                                    <Link
                                        :href="route('admin.reports.it-simple')"
                                        class="flex items-center px-3 py-2 text-sm font-medium rounded-md hover:bg-slate-50 transition-colors group"
                                    >
                                        <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="text-gray-700 group-hover:text-slate-700">Reporte TI</span>
                                    </Link>
                                </nav>
                            </div>
                        </div>
                    </div>

                    <!-- Columna derecha: Gráficos y Reporte General -->
                    <div class="lg:col-span-9 space-y-6">

                <!-- Reporte General (Actual) -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-xl font-bold mb-6">Reporte General</h3>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                    >Fecha Inicio</label
                                >
                                <input
                                    v-model="filters.dateFrom"
                                    type="date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                    >Fecha Fin</label
                                >
                                <input
                                    v-model="filters.dateTo"
                                    type="date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                    >Programa</label
                                >
                                <select
                                    v-model="filters.program"
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
                                        {{ program.code }} - {{ program.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button
                                    @click="generateReport"
                                    class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg"
                                >
                                    Generar Reporte
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resumen de los 4 Módulos -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-6">
                            Resumen de Módulos de Reportes
                        </h3>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Pagos Diarios -->
                            <div
                                class="bg-yellow-50 p-6 rounded-lg border border-yellow-200"
                            >
                                <div class="flex items-center mb-4">
                                    <div
                                        class="p-2 bg-yellow-100 rounded-lg mr-3"
                                    >
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
                                    <h4
                                        class="text-lg font-semibold text-yellow-800"
                                    >
                                        Pagos Diarios
                                    </h4>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            Total Pagos
                                        </p>
                                        <p
                                            class="text-xl font-bold text-yellow-600"
                                        >
                                            {{
                                                summary.dailyPayments
                                                    .totalPayments || 0
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            Monto Total
                                        </p>
                                        <p
                                            class="text-xl font-bold text-yellow-600"
                                        >
                                            ${{
                                                (
                                                    summary.dailyPayments
                                                        .totalAmount || 0
                                                ).toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            Pagos Hoy
                                        </p>
                                        <p
                                            class="text-lg font-semibold text-yellow-600"
                                        >
                                            {{
                                                summary.dailyPayments
                                                    .paymentsToday || 0
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            Promedio
                                        </p>
                                        <p
                                            class="text-lg font-semibold text-yellow-600"
                                        >
                                            ${{
                                                (
                                                    summary.dailyPayments
                                                        .averageAmount || 0
                                                ).toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Consolidado de Pagos -->
                            <div
                                class="bg-purple-50 p-6 rounded-lg border border-purple-200"
                            >
                                <div class="flex items-center mb-4">
                                    <div
                                        class="p-2 bg-purple-100 rounded-lg mr-3"
                                    >
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
                                    <h4
                                        class="text-lg font-semibold text-purple-800"
                                    >
                                        Consolidado de Pagos
                                    </h4>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            Programas
                                        </p>
                                        <p
                                            class="text-xl font-bold text-purple-600"
                                        >
                                            {{
                                                summary.consolidatedPayments
                                                    .totalPrograms || 0
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            Transacciones
                                        </p>
                                        <p
                                            class="text-xl font-bold text-purple-600"
                                        >
                                            {{
                                                summary.consolidatedPayments
                                                    .totalTransactions || 0
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            Monto Total
                                        </p>
                                        <p
                                            class="text-lg font-semibold text-purple-600"
                                        >
                                            ${{
                                                (
                                                    summary.consolidatedPayments
                                                        .totalAmount || 0
                                                ).toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            Promedio/Programa
                                        </p>
                                        <p
                                            class="text-lg font-semibold text-purple-600"
                                        >
                                            ${{
                                                (
                                                    summary.consolidatedPayments
                                                        .averagePerProgram || 0
                                                ).toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Cronograma de Recuperación -->
                            <div
                                class="bg-green-50 p-6 rounded-lg border border-green-200"
                            >
                                <div class="flex items-center mb-4">
                                    <div
                                        class="p-2 bg-green-100 rounded-lg mr-3"
                                    >
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
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                            ></path>
                                        </svg>
                                    </div>
                                    <h4
                                        class="text-lg font-semibold text-green-800"
                                    >
                                        Cronograma de Recuperación
                                    </h4>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            Programados
                                        </p>
                                        <p
                                            class="text-xl font-bold text-green-600"
                                        >
                                            {{
                                                summary.paymentSchedule
                                                    .totalScheduled || 0
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            Pagados
                                        </p>
                                        <p
                                            class="text-xl font-bold text-green-600"
                                        >
                                            {{
                                                summary.paymentSchedule
                                                    .totalPaid || 0
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            Pendientes
                                        </p>
                                        <p
                                            class="text-lg font-semibold text-green-600"
                                        >
                                            {{
                                                summary.paymentSchedule
                                                    .totalPending || 0
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            Tasa Completitud
                                        </p>
                                        <p
                                            class="text-lg font-semibold text-green-600"
                                        >
                                            {{
                                                summary.paymentSchedule
                                                    .completionRate || 0
                                            }}%
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Estado de Cuenta Parcial -->
                            <div
                                class="bg-blue-50 p-6 rounded-lg border border-blue-200"
                            >
                                <div class="flex items-center mb-4">
                                    <div
                                        class="p-2 bg-blue-100 rounded-lg mr-3"
                                    >
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
                                    <h4
                                        class="text-lg font-semibold text-blue-800"
                                    >
                                        Estado de Cuenta Parcial
                                    </h4>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            Participantes
                                        </p>
                                        <p
                                            class="text-xl font-bold text-blue-600"
                                        >
                                            {{
                                                summary.partialAccount
                                                    .totalParticipants || 0
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            Activos
                                        </p>
                                        <p
                                            class="text-xl font-bold text-blue-600"
                                        >
                                            {{
                                                summary.partialAccount
                                                    .activeParticipants || 0
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            Balance Total
                                        </p>
                                        <p
                                            class="text-lg font-semibold text-blue-600"
                                        >
                                            ${{
                                                (
                                                    summary.partialAccount
                                                        .totalBalance || 0
                                                ).toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            Promedio Balance
                                        </p>
                                        <p
                                            class="text-lg font-semibold text-blue-600"
                                        >
                                            ${{
                                                (
                                                    summary.partialAccount
                                                        .averageBalance || 0
                                                ).toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos de Ecommerce -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Gráfico de Programas Más Vistos -->
                    <div
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                    >
                        <div class="p-6 text-gray-900">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold">
                                    Programas Más Vistos
                                </h3>
                                <div class="flex space-x-2">
                                    <button
                                        @click="programChartPeriod = '7days'"
                                        :class="[
                                            'px-3 py-1 text-sm rounded',
                                            programChartPeriod === '7days'
                                                ? 'bg-blue-500 text-white'
                                                : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                                        ]"
                                    >
                                        7 días
                                    </button>
                                    <button
                                        @click="programChartPeriod = '30days'"
                                        :class="[
                                            'px-3 py-1 text-sm rounded',
                                            programChartPeriod === '30days'
                                                ? 'bg-blue-500 text-white'
                                                : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                                        ]"
                                    >
                                        30 días
                                    </button>
                                </div>
                            </div>
                            <div class="h-64">
                                <canvas ref="programViewsChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico de Participantes Más Buscados -->
                    <div
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                    >
                        <div class="p-6 text-gray-900">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold">
                                    Participantes Más Buscados
                                </h3>
                                <div class="flex space-x-2">
                                    <button
                                        @click="
                                            frequentParticipantsPeriod = 'all'
                                        "
                                        :class="[
                                            'px-3 py-1 text-sm rounded',
                                            frequentParticipantsPeriod === 'all'
                                                ? 'bg-green-500 text-white'
                                                : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                                        ]"
                                    >
                                        Todo
                                    </button>
                                    <button
                                        @click="
                                            frequentParticipantsPeriod =
                                                'thisYear'
                                        "
                                        :class="[
                                            'px-3 py-1 text-sm rounded',
                                            frequentParticipantsPeriod ===
                                            'thisYear'
                                                ? 'bg-green-500 text-white'
                                                : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                                        ]"
                                    >
                                        Este año
                                    </button>
                                </div>
                            </div>
                            <div class="h-64">
                                <canvas
                                    ref="frequentParticipantsChart"
                                ></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Funnel de Conversión -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">
                                Tasa de Conversión
                            </h3>
                            <div class="flex space-x-2">
                                <button
                                    @click="funnelPeriod = '7days'"
                                    :class="[
                                        'px-3 py-1 text-sm rounded',
                                        funnelPeriod === '7days'
                                            ? 'bg-purple-500 text-white'
                                            : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                                    ]"
                                >
                                    7 días
                                </button>
                                <button
                                    @click="funnelPeriod = '30days'"
                                    :class="[
                                        'px-3 py-1 text-sm rounded',
                                        funnelPeriod === '30days'
                                            ? 'bg-purple-500 text-white'
                                            : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                                    ]"
                                >
                                    30 días
                                </button>
                                <button
                                    @click="funnelPeriod = '90days'"
                                    :class="[
                                        'px-3 py-1 text-sm rounded',
                                        funnelPeriod === '90days'
                                            ? 'bg-purple-500 text-white'
                                            : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                                    ]"
                                >
                                    90 días
                                </button>
                            </div>
                        </div>
                        <div class="h-80">
                            <canvas ref="funnelChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Métodos de Pago vs Conversión -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">
                                Métodos de Pago vs Conversión
                            </h3>
                            <div class="flex space-x-2">
                                <button
                                    @click="paymentMethodsPeriod = '30days'"
                                    :class="[
                                        'px-3 py-1 text-sm rounded',
                                        paymentMethodsPeriod === '30days'
                                            ? 'bg-orange-500 text-white'
                                            : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                                    ]"
                                >
                                    30 días
                                </button>
                                <button
                                    @click="paymentMethodsPeriod = '90days'"
                                    :class="[
                                        'px-3 py-1 text-sm rounded',
                                        paymentMethodsPeriod === '90days'
                                            ? 'bg-orange-500 text-white'
                                            : 'bg-gray-200 text-gray-700 hover:bg-gray-300',
                                    ]"
                                >
                                    90 días
                                </button>
                            </div>
                        </div>
                        <div class="h-64">
                            <canvas ref="paymentMethodsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas de Reembolso -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-6">
                            Estadísticas de Reembolso
                        </h3>

                        <!-- Resumen de Reembolsos -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                            <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                                <div class="flex items-center">
                                    <div class="p-2 bg-red-100 rounded-lg mr-3">
                                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Total Reembolsos</p>
                                        <p class="text-xl font-bold text-red-600">{{ refundStats.total_refunds || 0 }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-orange-50 p-4 rounded-lg border border-orange-200">
                                <div class="flex items-center">
                                    <div class="p-2 bg-orange-100 rounded-lg mr-3">
                                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Monto Total</p>
                                        <p class="text-xl font-bold text-orange-600">${{ (refundStats.total_refund_amount || 0).toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                                <div class="flex items-center">
                                    <div class="p-2 bg-yellow-100 rounded-lg mr-3">
                                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Promedio</p>
                                        <p class="text-xl font-bold text-yellow-600">${{ (refundStats.average_refund_amount || 0).toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                                <div class="flex items-center">
                                    <div class="p-2 bg-purple-100 rounded-lg mr-3">
                                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">% del Total</p>
                                        <p class="text-xl font-bold text-purple-600">
                                            {{ summary.consolidatedPayments.totalAmount > 0 ? 
                                                ((refundStats.total_refund_amount / summary.consolidatedPayments.totalAmount) * 100).toFixed(1) : 0 }}%
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Gráficos de Reembolso -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Reembolsos por Estado -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="text-md font-semibold mb-4">Reembolsos Realizados</h4>
                                <div class="space-y-3">
                                    <div v-for="(data, status) in refundStats.refunds_by_status" :key="status" class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span :class="getStatusClass(status)" class="px-2 py-1 text-xs font-semibold rounded-full mr-3">
                                                {{ getStatusLabel(status) }}
                                            </span>
                                            <span class="text-sm">{{ data.count }} reembolsos</span>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold">${{ data.amount.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Top Programas con Reembolsos -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="text-md font-semibold mb-4">Programas con Más Reembolsos</h4>
                                <div class="space-y-3">
                                    <div v-for="(program, index) in refundStats.top_programs_refunds" :key="index" class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <span class="w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3">
                                                {{ index + 1 }}
                                            </span>
                                            <div>
                                                <p class="font-medium text-sm">{{ program.code }} - {{ program.name }}</p>
                                                <p class="text-xs text-gray-500">{{ program.count }} reembolsos</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-sm">${{ program.amount.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>

                <!-- Distribución por Tipo de Pago -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">
                            Distribución por Tipo de Pago
                        </h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Compara la cantidad de pagos por tipo: Totales (Full), Suscripciones, Presenciales y Devoluciones.
                        </p>
                        <div class="h-64">
                            <canvas ref="paymentTypesChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Programas Más Populares -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                    >
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-semibold mb-4">
                                Programas Más Populares
                            </h3>

                            <div class="space-y-3">
                                <div
                                    v-for="(program, index) in summary
                                        .consolidatedPayments.topPrograms"
                                    :key="index"
                                    class="flex items-center justify-between p-3 bg-gray-50 rounded"
                                >
                                    <div class="flex items-center">
                                        <span
                                            class="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3"
                                        >
                                            {{ index + 1 }}
                                        </span>
                                        <div>
                                            <p
                                                class="font-medium text-gray-900"
                                            >
                                                {{
                                                    program.name ||
                                                    "Programa sin nombre"
                                                }}
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                {{ program.count || 0 }}
                                                transacciones
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-gray-900">
                                            ${{
                                                (
                                                    program.amount || 0
                                                ).toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
                                            }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            ingresos
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Métodos de Pago -->
                    <div
                        class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                    >
                        <div class="p-6 text-gray-900">
                            <h3 class="text-lg font-semibold mb-4">
                                Métodos de Pago
                            </h3>

                            <div class="space-y-3">
                                <div
                                    v-for="payment in summary.dailyPayments
                                        .topPaymentMethods"
                                    :key="payment.gateway"
                                    class="flex items-center justify-between p-3 bg-gray-50 rounded"
                                >
                                    <div class="flex items-center">
                                        <span
                                            :class="
                                                getGatewayClass(payment.gateway)
                                            "
                                            class="px-2 py-1 text-xs font-semibold rounded-full mr-3"
                                        >
                                            {{
                                                (typeof payment.gateway ===
                                                "string"
                                                    ? payment.gateway
                                                    : "desconocido"
                                                ).toUpperCase()
                                            }}
                                        </span>
                                        <span class="font-medium"
                                            >{{
                                                payment.count || 0
                                            }}
                                            transacciones</span
                                        >
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold">
                                            ${{
                                                (
                                                    payment.amount || 0
                                                ).toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
                                            }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{
                                                (
                                                    (payment.amount /
                                                        (summary.dailyPayments
                                                            .totalAmount ||
                                                            1)) *
                                                    100
                                                ).toFixed(1)
                                            }}%
                                        </p>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>
                </div>

                <!-- Acciones de Exportación -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">
                            Exportar Reportes
                        </h3>

                        <div class="flex space-x-4">
                            <button
                                @click="exportConsolidatedReport"
                                class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg flex items-center"
                            >
                                <svg
                                    class="w-4 h-4 mr-2"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    ></path>
                                </svg>
                                Exportar Reporte Consolidado (Excel)
                            </button>
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted, computed, nextTick, watch } from "vue";
import { Head, router, Link, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Chart, registerables } from "chart.js";

// Obtener permisos del usuario
const page = usePage();
const canViewPayerContact = computed(() => {
    const permissions = page.props.auth?.user?.permissions || [];
    return permissions.includes('ver_contacto_pagador') || permissions.includes('ver_reportes_executives');
});

Chart.register(...registerables);

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({
            dailyPayments: {},
            consolidatedPayments: {},
            paymentSchedule: {},
            partialAccount: {},
            general: {},
        }),
    },
    programs: {
        type: Array,
        default: () => [],
    },
    ecommerceData: {
        type: Object,
        default: () => ({
            programViews: [],
            frequentParticipants: [],
            paymentMethods: [],
            paymentTypes: [],
        }),
    },
    funnelAnalysis: {
        type: Object,
        default: () => ({
            total_sessions: 0,
            funnel_stages: {},
            conversion_rates: {},
            payment_analysis: {},
            top_participants: [],
            top_programs: [],
        }),
    },
    refundStats: {
        type: Object,
        default: () => ({
            total_refunds: 0,
            total_refund_amount: 0,
            average_refund_amount: 0,
            refunds_by_status: {},
            monthly_refunds: [],
            top_programs_refunds: [],
            refunds_by_payment_method: [],
        }),
    },
});

const filters = reactive({
    dateFrom: "",
    dateTo: "",
    program: "",
});

// Variables para los gráficos
const programChartPeriod = ref("30days");
const frequentParticipantsPeriod = ref("all");
const funnelPeriod = ref("30days");
const paymentMethodsPeriod = ref("30days");

// Referencias a los canvas
const programViewsChart = ref(null);
const frequentParticipantsChart = ref(null);
const funnelChart = ref(null);
const paymentMethodsChart = ref(null);
const paymentTypesChart = ref(null);

// Instancias de los gráficos
let programViewsChartInstance = null;
let frequentParticipantsChartInstance = null;
let funnelChartInstance = null;
let paymentMethodsChartInstance = null;
let paymentTypesChartInstance = null;

// Establecer fechas por defecto (último mes)
onMounted(() => {
    const today = new Date();
    const lastMonth = new Date(
        today.getFullYear(),
        today.getMonth() - 1,
        today.getDate()
    );

    filters.dateTo = today.toISOString().split("T")[0];
    filters.dateFrom = lastMonth.toISOString().split("T")[0];

    // DEBUG: Ver datos de métodos de pago
    console.log('=== DEBUG: Datos del Card Métodos de Pago ===');
    console.log('summary completo:', props.summary);
    console.log('dailyPayments completo:', props.summary?.dailyPayments);
    console.log('topPaymentMethods:', props.summary?.dailyPayments?.topPaymentMethods);

    // Crear gráficos después de que el DOM esté listo
    nextTick(() => {
        createAllCharts();
    });
});

const createAllCharts = () => {
    createProgramViewsChart();
    createFrequentParticipantsChart();
    createFunnelChart();
    createPaymentMethodsChart();
    createPaymentTypesChart();
};

const createProgramViewsChart = () => {
    if (programViewsChart.value && props.ecommerceData.programViews) {
        if (programViewsChartInstance) {
            programViewsChartInstance.destroy();
        }

        const ctx = programViewsChart.value.getContext("2d");
        const data = props.ecommerceData.programViews.slice(0, 10); // Top 10 programas

        programViewsChartInstance = new Chart(ctx, {
            type: "bar",
            data: {
                labels: data.map(
                    (item) =>
                        item.program_name || `Programa #${item.program_id || ''}`
                ),
                datasets: [
                    {
                        label: "Vistas",
                        data: data.map((item) => item.views || 0),
                        backgroundColor: "rgba(59, 130, 246, 0.8)",
                        borderColor: "rgba(59, 130, 246, 1)",
                        borderWidth: 1,
                    },
                    {
                        label: "Conversiones",
                        data: data.map((item) => item.conversions || 0),
                        backgroundColor: "rgba(34, 197, 94, 0.8)",
                        borderColor: "rgba(34, 197, 94, 1)",
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: "y", // Barras horizontales para mejor legibilidad
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: "Cantidad",
                        },
                    },
                },
                plugins: {
                    legend: {
                        position: "top",
                    },
                    title: {
                        display: true,
                        text: "Programas Más Vistos",
                    },
                },
            },
        });
    }
};

// Función para formatear RUT
const formatRut = (rut) => {
    if (!rut) return "Sin RUT";

    // Limpiar el RUT de puntos y guiones
    let rutLimpio = rut.toString().replace(/\./g, "").replace(/-/g, "");

    // Verificar que tenga el formato correcto de RUT chileno
    if (!/^\d{7,8}[\dK]$/.test(rutLimpio)) {
        return rut.toString().toUpperCase(); // Fallback si no es RUT válido
    }

    // Separar número y dígito verificador
    let dv = rutLimpio.slice(-1);
    let numero = rutLimpio.slice(0, -1);

    // Formatear con puntos y guión
    let numeroFormateado = numero.replace(/\B(?=(\d{3})+(?!\d))/g, ".");

    return `${numeroFormateado}-${dv.toUpperCase()}`;
};

const createFrequentParticipantsChart = () => {
    if (
        frequentParticipantsChart.value &&
        props.ecommerceData.frequentParticipants
    ) {
        if (frequentParticipantsChartInstance) {
            frequentParticipantsChartInstance.destroy();
        }

        const ctx = frequentParticipantsChart.value.getContext("2d");
        const data = props.ecommerceData.frequentParticipants.slice(0, 8); // Top 8 participantes

        frequentParticipantsChartInstance = new Chart(ctx, {
            type: "bar",
            data: {
                labels: data.map((item) => formatRut(item.participant_rut)),
                datasets: [
                    {
                        label: "Búsquedas",
                        data: data.map((item) => item.search_count || 0),
                        backgroundColor: "rgba(34, 197, 94, 0.8)",
                        borderColor: "rgba(34, 197, 94, 1)",
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: "y", // Barras horizontales para mejor legibilidad
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: "Número de Búsquedas",
                        },
                    },
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    title: {
                        display: true,
                        text: "Participantes Más Buscados",
                    },
                },
            },
        });
    }
};

const createFunnelChart = () => {
    if (funnelChart.value && props.funnelAnalysis.funnel_stages) {
        if (funnelChartInstance) {
            funnelChartInstance.destroy();
        }

        const ctx = funnelChart.value.getContext("2d");
        const funnelStages = props.funnelAnalysis.funnel_stages;

        funnelChartInstance = new Chart(ctx, {
            type: "bar",
            data: {
                labels: [
                    "Búsquedas participantes",
                    "Listado programas",
                    "Detalle programa",
                    "Datos comprador",
                    "Confirmación pago",
                    "Pagos Iniciados",
                    "Pagos Completados",
                ],
                datasets: [
                    {
                        label: "Cantidad",
                        data: [
                            funnelStages.hero_search || 0,
                            funnelStages.program_list_view || 0,
                            funnelStages.program_detail_view || 0,
                            funnelStages.payment_details_view || 0,
                            funnelStages.confirmation_view || 0,
                            funnelStages.payment_initiated || 0,
                            funnelStages.payment_completed || 0,
                        ],
                        backgroundColor: [
                            "rgba(59, 130, 246, 0.8)",
                            "rgba(16, 185, 129, 0.8)",
                            "rgba(168, 85, 247, 0.8)",
                            "rgba(251, 146, 60, 0.8)",
                            "rgba(239, 68, 68, 0.8)",
                            "rgba(99, 102, 241, 0.8)",
                            "rgba(34, 197, 94, 0.8)",
                        ],
                        borderColor: [
                            "rgba(59, 130, 246, 1)",
                            "rgba(16, 185, 129, 1)",
                            "rgba(168, 85, 247, 1)",
                            "rgba(251, 146, 60, 1)",
                            "rgba(239, 68, 68, 1)",
                            "rgba(99, 102, 241, 1)",
                            "rgba(34, 197, 94, 1)",
                        ],
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: "y", // Barras horizontales para mejor visualización del funnel
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: "Cantidad",
                        },
                    },
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    title: {
                        display: true,
                        text: "Tasa de Conversión",
                    },
                    tooltip: {
                        callbacks: {
                            afterLabel: function (context) {
                                const index = context.dataIndex;
                                const current = context.parsed.x;
                                const previous =
                                    index > 0
                                        ? funnelChartInstance.data.datasets[0]
                                              .data[index - 1]
                                        : current;
                                const rate =
                                    previous > 0
                                        ? ((current / previous) * 100).toFixed(
                                              1
                                          )
                                        : 0;
                                return `Tasa de conversión: ${rate}%`;
                            },
                        },
                    },
                },
            },
        });
    }
};

const createPaymentMethodsChart = () => {
    if (paymentMethodsChart.value && props.ecommerceData.paymentMethods) {
        if (paymentMethodsChartInstance) {
            paymentMethodsChartInstance.destroy();
        }

        const ctx = paymentMethodsChart.value.getContext("2d");
        const data = props.ecommerceData.paymentMethods;

        paymentMethodsChartInstance = new Chart(ctx, {
            type: "bar",
            data: {
                labels: data.map(
                    (item) => item.payment_method || "Desconocido"
                ),
                datasets: [
                    {
                        label: "Total Intentos",
                        data: data.map((item) => item.total || 0),
                        backgroundColor: "rgba(251, 146, 60, 0.8)",
                        borderColor: "rgba(251, 146, 60, 1)",
                        borderWidth: 1,
                        yAxisID: "y",
                    },
                    {
                        label: "Pagos Exitosos",
                        data: data.map((item) => item.successful || 0),
                        backgroundColor: "rgba(34, 197, 94, 0.8)",
                        borderColor: "rgba(34, 197, 94, 1)",
                        borderWidth: 1,
                        yAxisID: "y",
                    },
                    {
                        label: "Tasa de Éxito (%)",
                        data: data.map((item) => item.success_rate || 0),
                        backgroundColor: "rgba(168, 85, 247, 0.8)",
                        borderColor: "rgba(168, 85, 247, 1)",
                        borderWidth: 1,
                        yAxisID: "y1",
                        type: "line",
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: "linear",
                        display: true,
                        position: "left",
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: "Cantidad",
                        },
                    },
                    y1: {
                        type: "linear",
                        display: true,
                        position: "right",
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: "Tasa de Éxito (%)",
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    },
                },
                plugins: {
                    legend: {
                        position: "top",
                    },
                    title: {
                        display: true,
                        text: "Métodos de Pago vs Conversión",
                    },
                },
            },
        });
    }
};

const createInstallmentsChart = () => {
    if (installmentsChart.value && props.ecommerceData.installmentsAnalysis) {
        if (installmentsChartInstance) {
            installmentsChartInstance.destroy();
        }

        const ctx = installmentsChart.value.getContext("2d");
        const data = props.ecommerceData.installmentsAnalysis;

        installmentsChartInstance = new Chart(ctx, {
            type: "doughnut",
            data: {
                labels: data.map(
                    (item) => item.label || `${item.installments_count} cuotas`
                ),
                datasets: [
                    {
                        data: data.map((item) => item.count || 0),
                        backgroundColor: [
                            "rgba(34, 197, 94, 0.8)",  // Verde para sin cuotas
                            "rgba(99, 102, 241, 0.8)",  // Azul para 1 cuota
                            "rgba(251, 146, 60, 0.8)",  // Naranja para 2+ cuotas
                        ],
                        borderWidth: 2,
                        borderColor: "#fff",
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "right",
                    },
                    title: {
                        display: true,
                        text: "Distribución por Número de Cuotas",
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const total = context.dataset.data.reduce(
                                    (a, b) => a + b,
                                    0
                                );
                                const percentage = (
                                    (context.parsed / total) *
                                    100
                                ).toFixed(1);
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            },
                        },
                    },
                },
            },
        });
    }
};

const createPaymentTypesChart = () => {
    if (paymentTypesChart.value && props.ecommerceData.paymentTypes) {
        if (paymentTypesChartInstance) {
            paymentTypesChartInstance.destroy();
        }

        const ctx = paymentTypesChart.value.getContext("2d");
        const data = props.ecommerceData.paymentTypes;

        paymentTypesChartInstance = new Chart(ctx, {
            type: "doughnut",
            data: {
                labels: data.map(
                    (item) =>
                        item.label || item.payment_type || "No especificado"
                ),
                datasets: [
                    {
                        data: data.map((item) => item.count || 0),
                        backgroundColor: [
                            "rgba(59, 102, 241, 0.8)",
                            "rgba(34, 197, 94, 0.8)",
                            "rgba(168, 85, 247, 0.8)",
                            "rgba(251, 146, 60, 0.8)",
                            "rgba(239, 68, 68, 0.8)",
                        ],
                        borderWidth: 2,
                        borderColor: "#fff",
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "right",
                    },
                    title: {
                        display: true,
                        text: "Distribución por Tipo de Pago",
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const total = context.dataset.data.reduce(
                                    (a, b) => a + b,
                                    0
                                );
                                const percentage = (
                                    (context.parsed / total) *
                                    100
                                ).toFixed(1);
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            },
                        },
                    },
                },
            },
        });
    }
};

// Watchers para actualizar gráficas cuando cambien los períodos
watch(programChartPeriod, () => {
    createProgramViewsChart();
});

watch(frequentParticipantsPeriod, () => {
    createFrequentParticipantsChart();
});

watch(funnelPeriod, () => {
    createFunnelChart();
});

watch(paymentMethodsPeriod, () => {
    createPaymentMethodsChart();
});

const generateReport = () => {
    try {
        const params = {
            ...filters,
            programChartPeriod: programChartPeriod.value,
            frequentParticipantsPeriod: frequentParticipantsPeriod.value,
            funnelPeriod: funnelPeriod.value,
            paymentMethodsPeriod: paymentMethodsPeriod.value,
        };

        router.get("/admin/reports", params, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                nextTick(() => {
                    createAllCharts();
                });
            },
        });
    } catch (error) {
        console.error("Error al generar reporte:", error);
    }
};

const getGatewayClass = (gateway) => {
    if (!gateway || typeof gateway !== "string")
        return "bg-gray-100 text-gray-800";

    const gatewayLower = gateway.toLowerCase();
    const classes = {
        transbank: "bg-blue-100 text-blue-800",
        khipu: "bg-green-100 text-green-800",
        presencial: "bg-purple-100 text-purple-800",
        webpay: "bg-blue-100 text-blue-800",
        desconocido: "bg-gray-100 text-gray-800",
    };
    return classes[gatewayLower] || "bg-gray-100 text-gray-800";
};

// Funciones helper para estados de reembolso
const getStatusClass = (status) => {
    const classes = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'completed': 'bg-green-100 text-green-800',
        'failed': 'bg-red-100 text-red-800',
        'authorized': 'bg-blue-100 text-blue-800',
        'cancelled': 'bg-gray-100 text-gray-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
    const labels = {
        'pending': 'Pendiente',
        'completed': 'Completado',
        'failed': 'Fallido',
        'authorized': 'Autorizado',
        'cancelled': 'Cancelado',
    };
    return labels[status] || status;
};

const exportConsolidatedReport = () => {
    try {
        const params = new URLSearchParams({
            dateFrom: filters.dateFrom,
            dateTo: filters.dateTo,
            program: filters.program,
        });

        window.open(`/admin/reports/export?${params.toString()}`, "_blank");
    } catch (error) {
        console.error("Error al exportar reporte consolidado:", error);
    }
};
</script>
