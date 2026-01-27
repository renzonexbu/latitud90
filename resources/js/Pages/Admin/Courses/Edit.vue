<template>
    <AdminLayout>
        <Head title="Editar Programa - Curso" />

        <!-- Alerta de errores de validación -->
        <Alerts
            v-if="showErrorAlert"
            :show="showErrorAlert"
            type="error"
            title="Error de validación"
            :message="errorMessage"
            :auto-close="true"
            :duration="8000"
            @close="closeErrorAlert"
        />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Page Header -->
                <div class="mb-8">
                    <h1 class="text-[#007e93] font-nexa-bold text-[32px] leading-[38px] font-bold">
                        Editar Programa - Curso
                    </h1>
                    <p class="text-[#5b5b5b] font-nexa-regular text-[14px] leading-[20px] mt-2">
                        Modifica la plantilla de programa y los detalles administrativos del curso
                    </p>
                </div>

                <!-- 2-Column Layout -->
                <form @submit.prevent="saveCourse">
                    <div class="flex flex-col md:flex-row items-start gap-6 md:gap-[30px]">
                        <!-- Left Column - Program Template Selection -->
                        <div class="w-full md:w-[500px] flex-shrink-0">
                            <div class="bg-white rounded-[20px] border border-[#d3d3d3] p-6 shadow-[0px_4px_11.6px_0px_rgba(163,163,163,0.11)]">
                                <div class="mb-6">
                                    <h2 class="text-[#007e93] font-nexa-bold text-[18px] leading-[22px] font-bold">
                                        Plantillas de Programa
                                    </h2>
                                    <p class="text-[#5b5b5b] font-nexa-regular text-[12px] leading-[16px] mt-2">
                                        Selecciona la plantilla de programa que deseas asignar al curso
                                    </p>
                                </div>

                                <!-- Searchable Select for Program Templates -->
                                <div class="field-wrapper">
                                    <div class="field-label mb-2">
                                        Buscar plantilla de programa *
                                    </div>
                                    <SearchableSelect
                                        :options="programsFormatted"
                                        :value="form.program_id"
                                        placeholder="Busca por nombre o destino"
                                        @input="selectProgram"
                                        search-key="searchText"
                                    />
                                    <span v-if="errors.program_id" class="text-red-500 text-sm mt-1">
                                        {{ errors.program_id }}
                                    </span>
                                </div>

                                <!-- Selected Program Preview -->
                                <div v-if="selectedProgram" class="mt-6 p-4 bg-[#007e93]/5 rounded-lg border border-[#007e93]/20">
                                    <h3 class="text-[#007e93] font-nexa-bold text-[14px] leading-[18px] font-bold mb-2">
                                        Plantilla seleccionada
                                    </h3>
                                    <div class="space-y-1">
                                        <p class="text-[#434343] font-nexa-bold text-[14px] leading-[18px]">
                                            {{ selectedProgram.name }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Administrative Details -->
                        <div class="flex-1 w-full">
                            <div class="detalle-administrativo-card">
                                <div class="administrative-form-header">
                                    <div class="administrative-title">
                                        Detalle administrativo
                                    </div>

                                    <!-- Acordeón 1: Precio del programa -->
                                    <div class="accordion-section">
                                        <div
                                            class="accordion-header"
                                            @click="priceOpen = !priceOpen"
                                        >
                                            <div class="accordion-title">
                                                Precio del programa
                                            </div>
                                            <svg
                                                class="accordion-arrow"
                                                :class="{ rotated: priceOpen }"
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="26"
                                                height="14"
                                                viewBox="0 0 26 14"
                                                fill="none"
                                            >
                                                <path
                                                    d="M19.0873 3.27314L20.2008 4.38775L14.1319 10.4588C14.0347 10.5566 13.919 10.6343 13.7917 10.6873C13.6643 10.7403 13.5277 10.7676 13.3897 10.7676C13.2518 10.7676 13.1152 10.7403 12.9878 10.6873C12.8604 10.6343 12.7448 10.5566 12.6475 10.4588L6.57544 4.38775L7.689 3.27419L13.3881 8.97227L19.0873 3.27314Z"
                                                    fill="#007E93"
                                                />
                                            </svg>
                                        </div>
                                        <transition name="accordion-slide">
                                            <div v-if="priceOpen" class="accordion-content">
                                                <div class="price-fields-row">
                                                    <div class="field-container">
                                                        <div class="field-wrapper">
                                                            <div class="field-label">
                                                                Código del programa *
                                                            </div>
                                                            <input
                                                                type="text"
                                                                v-model="form.code"
                                                                placeholder="Ej: PROG2025-001"
                                                                class="admin-input-text"
                                                                :class="{ 'border-red-500': errors.code }"
                                                            />
                                                            <span v-if="errors.code" class="text-red-500 text-sm mt-1">
                                                                {{ errors.code }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="field-container">
                                                        <div class="field-wrapper">
                                                            <div class="field-label">
                                                                Destino *
                                                            </div>
                                                            <input
                                                                type="text"
                                                                v-model="form.destination"
                                                                placeholder="Ej: Torres del Paine"
                                                                class="admin-input-text"
                                                                :class="{ 'border-red-500': errors.destination }"
                                                            />
                                                            <span v-if="errors.destination" class="text-red-500 text-sm mt-1">
                                                                {{ errors.destination }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="price-fields-row">
                                                    <div class="field-container">
                                                        <div class="field-wrapper">
                                                            <div class="field-label">
                                                                Precio del programa * (CLP)
                                                            </div>
                                                            <input
                                                                type="text"
                                                                :value="formatPrice(form.trip_price)"
                                                                @input="handlePriceInput"
                                                                placeholder="0"
                                                                class="admin-input-text"
                                                                :class="{ 'border-red-500': errors.trip_price }"
                                                            />
                                                            <span v-if="errors.trip_price" class="text-red-500 text-sm mt-1">
                                                                {{ errors.trip_price }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="price-fields-row">
                                                    <div class="field-container">
                                                        <div class="field-wrapper">
                                                            <div class="field-label">
                                                                Fecha de inicio *
                                                            </div>
                                                            <input
                                                                type="date"
                                                                v-model="form.departure_date"
                                                                class="admin-input-text"
                                                                :class="{ 'border-red-500': errors.departure_date || departureDateError }"
                                                                :min="todayDate"
                                                            />
                                                            <span v-if="departureDateError" class="text-red-500 text-sm mt-1">
                                                                {{ departureDateError }}
                                                            </span>
                                                            <span v-else-if="errors.departure_date" class="text-red-500 text-sm mt-1">
                                                                {{ errors.departure_date }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="field-container">
                                                        <div class="field-wrapper">
                                                            <div class="field-label">
                                                                Fecha límite de pago *
                                                            </div>
                                                            <select
                                                                v-model="form.payment_days_before"
                                                                class="admin-input-text"
                                                                :class="{ 'border-red-500': errors.final_payment_date }"
                                                            >
                                                                <option value="31">30 días antes</option>
                                                                <option value="61">60 días antes</option>
                                                                <option value="custom">Otra fecha</option>
                                                            </select>
                                                            <input
                                                                v-if="form.payment_days_before === 'custom'"
                                                                v-model="form.custom_final_payment_date"
                                                                type="date"
                                                                class="admin-input-text mt-2"
                                                                :class="{ 'border-red-500': customPaymentDateError }"
                                                            />
                                                            <span v-if="customPaymentDateError" class="text-red-500 text-sm mt-1">
                                                                {{ customPaymentDateError }}
                                                            </span>
                                                            <span v-if="errors.final_payment_date" class="text-red-500 text-sm mt-1">
                                                                {{ errors.final_payment_date }}
                                                            </span>
                                                            <span v-if="form.payment_days_before !== 'custom' && calculatedFinalPaymentDate && form.departure_date" class="text-gray-600 text-xs mt-1 block">
                                                                Fecha calculada: {{ formatDateForDisplay(calculatedFinalPaymentDate) }}
                                                            </span>
                                                            <span v-if="finalPaymentDateWarning" class="text-red-500 text-sm mt-1 block">
                                                                ⚠️ {{ finalPaymentDateWarning }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="staff-field-row">
                                                    <div class="field-wrapper">
                                                        <div class="flex justify-between items-center">
                                                            <div class="field-label">
                                                                Ejecutivo comercial
                                                            </div>
                                                        </div>
                                                        <select
                                                            v-model="form.sales_executive_id"
                                                            class="admin-input-text"
                                                            :class="{ 'border-red-500': errors.sales_executive_id }"
                                                        >
                                                            <option value="">Seleccione un ejecutivo</option>
                                                            <option
                                                                v-for="exec in salesExecutives"
                                                                :key="exec.id"
                                                                :value="exec.id"
                                                            >
                                                                {{ exec.name }} ({{ exec.code }})
                                                            </option>
                                                        </select>
                                                        <span v-if="errors.sales_executive_id" class="text-red-500 text-sm mt-1">
                                                            {{ errors.sales_executive_id }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </transition>
                                    </div>

                                    <!-- Separator -->
                                    <div class="accordion-separator"></div>

                                    <!-- Acordeón 2: Participantes -->
                                    <div class="accordion-section">
                                        <div
                                            class="accordion-header"
                                            @click="travelersOpen = !travelersOpen"
                                        >
                                            <div class="accordion-title">
                                                Participantes
                                            </div>
                                            <svg
                                                class="accordion-arrow"
                                                :class="{ rotated: travelersOpen }"
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="26"
                                                height="14"
                                                viewBox="0 0 26 14"
                                                fill="none"
                                            >
                                                <path
                                                    d="M19.0873 3.27314L20.2008 4.38775L14.1319 10.4588C14.0347 10.5566 13.919 10.6343 13.7917 10.6873C13.6643 10.7403 13.5277 10.7676 13.3897 10.7676C13.2518 10.7676 13.1152 10.7403 12.9878 10.6873C12.8604 10.6343 12.7448 10.5566 12.6475 10.4588L6.57544 4.38775L7.689 3.27419L13.3881 8.97227L19.0873 3.27314Z"
                                                    fill="#007E93"
                                                />
                                            </svg>
                                        </div>
                                        <transition name="accordion-slide">
                                            <div v-if="travelersOpen" class="accordion-content">
                                                <div class="institution-field-row">
                                                    <div class="field-wrapper">
                                                        <div class="field-label">
                                                            Nombre de institución *
                                                        </div>
                                                        <select
                                                            v-model="form.institutionId"
                                                            class="admin-input-text"
                                                            :class="{ 'border-red-500': errors.institutionId }"
                                                        >
                                                            <option value="">Seleccione una institución</option>
                                                            <option
                                                                v-for="institution in institutions"
                                                                :key="institution.id"
                                                                :value="institution.id"
                                                            >
                                                                {{ institution.name }}
                                                            </option>
                                                        </select>
                                                        <span v-if="errors.institutionId" class="text-red-500 text-sm mt-1">
                                                            {{ errors.institutionId }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="education-details-row">
                                                    <div class="field-container">
                                                        <div class="field-wrapper">
                                                            <div class="field-label">
                                                                Nivel de educación *
                                                            </div>
                                                            <select
                                                                v-model="form.educationLevel"
                                                                class="admin-select"
                                                                :class="{ 'border-red-500': errors.educationLevel }"
                                                            >
                                                                <option value="">Seleccione un nivel</option>
                                                                <option value="preescolar">Preescolar</option>
                                                                <option value="basica">Básica</option>
                                                                <option value="media">Media</option>
                                                                <option value="universitaria">Universitaria</option>
                                                            </select>
                                                            <span v-if="errors.educationLevel" class="text-red-500 text-sm mt-1">
                                                                {{ errors.educationLevel }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <div class="field-container-small">
                                                        <div class="field-wrapper">
                                                            <div class="field-label">
                                                                Año *
                                                            </div>
                                                            <select
                                                                v-model="form.year"
                                                                class="admin-select-small"
                                                                :class="{ 'border-red-500': errors.year }"
                                                            >
                                                                <option value="2023">2023</option>
                                                                <option value="2024">2024</option>
                                                                <option value="2025">2025</option>
                                                                <option value="2026">2026</option>
                                                                <option value="2027">2027</option>
                                                            </select>
                                                            <span v-if="errors.year" class="text-red-500 text-sm mt-1">
                                                                {{ errors.year }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <div class="field-container-small">
                                                        <div class="field-wrapper">
                                                            <div class="field-label">
                                                                Curso
                                                            </div>
                                                            <select
                                                                v-model="form.courseNumber"
                                                                class="admin-select-small"
                                                            >
                                                                <option value="">---</option>
                                                                <option value="1">1°</option>
                                                                <option value="2">2°</option>
                                                                <option value="3">3°</option>
                                                                <option value="4">4°</option>
                                                                <option value="5">5°</option>
                                                                <option value="6">6°</option>
                                                                <option value="7">7°</option>
                                                                <option value="8">8°</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="field-container-small">
                                                        <div class="field-wrapper">
                                                            <div class="field-label">
                                                                Grado
                                                            </div>
                                                            <select
                                                                v-model="form.grade"
                                                                class="admin-select-small"
                                                            >
                                                                <option value="">---</option>
                                                                <option value="A">A</option>
                                                                <option value="B">B</option>
                                                                <option value="C">C</option>
                                                                <option value="D">D</option>
                                                                <option value="E">E</option>
                                                                <option value="F">F</option>
                                                                <option value="G">G</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="contact-fields-row">
                                                    <div class="field-container">
                                                        <div class="field-wrapper">
                                                            <div class="field-label">
                                                                Mail de contacto
                                                            </div>
                                                            <input
                                                                type="email"
                                                                v-model="form.contactEmail"
                                                                placeholder="correo@ejemplo.com"
                                                                class="admin-input-text"
                                                            />
                                                        </div>
                                                    </div>
                                                    <div class="field-container">
                                                        <div class="field-wrapper">
                                                            <div class="field-label">
                                                                Teléfono de contacto
                                                            </div>
                                                            <input
                                                                type="tel"
                                                                v-model="form.contactPhone"
                                                                placeholder="000000000"
                                                                class="admin-input-text"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Students File Upload -->
                                                <div class="students-upload-section">
                                                    <div class="students-section-title">
                                                        Carga de participantes
                                                    </div>
                                                    <div class="students-upload-field">
                                                        <div class="field-wrapper">
                                                            <div class="field-label">
                                                                Adjunta la lista de participantes (opcional - solo si deseas cambiarla)
                                                            </div>
                                                            <div v-if="props.course.students_file_name && !form.studentsFile" class="mb-2">
                                                                <div class="text-sm text-gray-600">
                                                                    Archivo actual: <span class="font-semibold text-[#007e93]">{{ props.course.students_file_name }}</span>
                                                                </div>
                                                            </div>
                                                            <label class="file-upload-area" for="students-file">
                                                                <div class="upload-text">
                                                                    {{
                                                                        form.studentsFile
                                                                            ? form.studentsFile.name
                                                                            : (props.course.students_file_name ? 'Haz clic para reemplazar el archivo' : 'Adjunta el archivo excel aquí')
                                                                    }}
                                                                </div>
                                                            </label>
                                                            <input
                                                                type="file"
                                                                id="students-file"
                                                                ref="fileInput"
                                                                accept=".xlsx,.xls,.csv"
                                                                style="display: none"
                                                                @change="handleFileUpload"
                                                            />
                                                            <span v-if="errors.studentsFile" class="text-red-500 text-sm mt-1">
                                                                {{ errors.studentsFile }}
                                                            </span>

                                                            <!-- Import Errors Details -->
                                                            <div v-if="importErrorDetails && importErrorDetails.errors && importErrorDetails.errors.length > 0" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                                                                <div class="flex items-start justify-between mb-3">
                                                                    <div>
                                                                        <h4 class="text-red-800 font-nexa-bold text-sm">Errores en el archivo de estudiantes</h4>
                                                                        <p class="text-red-600 text-xs mt-1">
                                                                            {{ importErrorDetails.error_count }} error(es) encontrado(s) en {{ importErrorDetails.total_rows }} filas
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                                <div class="max-h-60 overflow-y-auto space-y-2">
                                                                    <div
                                                                        v-for="(error, index) in importErrorDetails.errors"
                                                                        :key="index"
                                                                        class="text-xs bg-white p-3 rounded border border-red-100"
                                                                    >
                                                                        <div class="flex items-start gap-2">
                                                                            <span class="flex-shrink-0 bg-red-600 text-white px-2 py-1 rounded font-bold text-[10px]">
                                                                                Fila {{ error.row }}
                                                                            </span>
                                                                            <div class="flex-1">
                                                                                <p class="text-gray-700 font-semibold">{{ error.participant_name }}</p>
                                                                                <p class="text-red-600 mt-1">{{ error.error }}</p>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Participants Management Component -->
                                                    <div v-if="travelersOpen">
                                                        <ParticipantsManagement
                                                            v-if="programCourse?.id"
                                                            :program-course-id="programCourse.id"
                                                        />
                                                    </div>
                                                </div>
                                            </div>
                                        </transition>
                                    </div>

                                    <!-- Separator -->
                                    <div class="accordion-separator"></div>

                                    <!-- Acordeón 3: Forma de pago -->
                                    <div class="accordion-section">
                                        <div
                                            class="accordion-header"
                                            @click="paymentOpen = !paymentOpen"
                                        >
                                            <div class="accordion-title">
                                                Forma de pago
                                            </div>
                                            <svg
                                                class="accordion-arrow"
                                                :class="{ rotated: paymentOpen }"
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="26"
                                                height="14"
                                                viewBox="0 0 26 14"
                                                fill="none"
                                            >
                                                <path
                                                    d="M19.0873 3.27314L20.2008 4.38775L14.1319 10.4588C14.0347 10.5566 13.919 10.6343 13.7917 10.6873C13.6643 10.7403 13.5277 10.7676 13.3897 10.7676C13.2518 10.7676 13.1152 10.7403 12.9878 10.6873C12.8604 10.6343 12.7448 10.5566 12.6475 10.4588L6.57544 4.38775L7.689 3.27419L13.3881 8.97227L19.0873 3.27314Z"
                                                    fill="#007E93"
                                                />
                                            </svg>
                                        </div>
                                        <transition name="accordion-slide">
                                            <div v-if="paymentOpen" class="accordion-content">
                                                <div class="payment-description">
                                                    ¿Cómo quieres que pague el grupo? Elige las opciones disponibles.
                                                </div>
                                                <span v-if="errors.payment_options" class="text-red-500 text-sm mb-4 block">
                                                    {{ errors.payment_options }}
                                                </span>

                                                <!-- Opción Pago Total -->
                                                <div class="payment-option">
                                                    <div class="payment-option-header">
                                                        <div class="payment-checkbox">
                                                            <input
                                                                type="checkbox"
                                                                id="full-payment"
                                                                value="full_payment"
                                                                :checked="isPaymentOptionSelected('full_payment')"
                                                                @change="handlePaymentOptionChange('full_payment', $event)"
                                                                class="checkbox-input"
                                                            />
                                                            <label for="full-payment" class="checkbox-label"></label>
                                                        </div>
                                                        <div class="payment-option-content">
                                                            <div class="payment-option-title">
                                                                Pago total
                                                            </div>
                                                            <div class="payment-option-info">
                                                                <svg
                                                                    class="info-icon"
                                                                    width="18"
                                                                    height="18"
                                                                    viewBox="0 0 18 18"
                                                                    fill="none"
                                                                >
                                                                    <circle
                                                                        cx="9"
                                                                        cy="9"
                                                                        r="8"
                                                                        stroke="#007E93"
                                                                        stroke-width="2"
                                                                    />
                                                                    <path
                                                                        d="M9 13V9M9 5H9.01"
                                                                        stroke="#007E93"
                                                                        stroke-width="2"
                                                                        stroke-linecap="round"
                                                                    />
                                                                </svg>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div v-if="isPaymentOptionSelected('full_payment')" class="payment-method-select">
                                                        <div class="field-wrapper">
                                                            <div class="field-label">Opciones disponibles para Pago Total</div>
                                                            <div class="flex flex-col gap-2">
                                                                <label v-for="opt in fullPaymentChoices" :key="opt.code" class="flex items-center gap-2">
                                                                    <input type="checkbox" :value="opt.code" @change="toggleFullOption(opt.code, $event)" :checked="form.full_payment_options?.includes(opt.code)" />
                                                                    <span>{{ opt.label }}</span>
                                                                </label>
                                                            </div>
                                                            <span v-if="errors.full_payment_options" class="text-red-500 text-sm mt-1">
                                                                {{ errors.full_payment_options }}
                                                            </span>
                                                            <div v-if="fullPaymentChoices.length < fullPaymentChoicesBase.length && form.departure_date" class="text-blue-600 text-sm mt-1">
                                                                ℹ️ Algunas opciones de cuotas no están disponibles debido a la fecha de salida
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Opción Suscripciones -->
                                                <div class="payment-option">
                                                    <div class="payment-option-header">
                                                        <div class="payment-checkbox">
                                                            <input
                                                                type="checkbox"
                                                                id="subscription-payment"
                                                                value="subscription"
                                                                :checked="isPaymentOptionSelected('subscription')"
                                                                @change="handlePaymentOptionChange('subscription', $event)"
                                                                class="checkbox-input"
                                                            />
                                                            <label for="subscription-payment" class="checkbox-label"></label>
                                                        </div>
                                                        <div class="payment-option-content">
                                                            <div class="payment-option-title">
                                                                Suscripciones
                                                            </div>
                                                            <div class="payment-option-info">
                                                                <svg
                                                                    class="info-icon"
                                                                    width="18"
                                                                    height="18"
                                                                    viewBox="0 0 18 18"
                                                                    fill="none"
                                                                >
                                                                    <circle
                                                                        cx="9"
                                                                        cy="9"
                                                                        r="8"
                                                                        stroke="#007E93"
                                                                        stroke-width="2"
                                                                    />
                                                                    <path
                                                                        d="M9 13V9M9 5H9.01"
                                                                        stroke="#007E93"
                                                                        stroke-width="2"
                                                                        stroke-linecap="round"
                                                                    />
                                                                </svg>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div v-if="isPaymentOptionSelected('subscription')" class="installments-options">
                                                        <div class="payment-method-select">
                                                            <div class="field-wrapper">
                                                                <div class="field-label">Opciones disponibles para Suscripciones</div>
                                                                <div class="flex flex-col gap-2">
                                                                    <label v-for="opt in subscriptionChoices" :key="opt.code" class="flex items-center gap-2">
                                                                        <input type="checkbox" :value="opt.code" @change="toggleSubscriptionOption(opt.code, $event)" :checked="form.subscription_payment_options?.includes(opt.code)" />
                                                                        <span>{{ opt.label }}</span>
                                                                    </label>
                                                                </div>
                                                                <span v-if="errors.subscription_payment_options" class="text-red-500 text-sm mt-1">
                                                                    {{ errors.subscription_payment_options }}
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <!-- Campo para seleccionar número de cuotas -->
                                                        <div class="field-wrapper mt-4">
                                                            <div class="field-label">Número máximo de meses de suscripción *</div>
                                                            <div class="field-input-container">
                                                                <select
                                                                    v-model="form.subscription_max_months"
                                                                    class="field-input"
                                                                    :class="{ 'border-red-500': errors.subscription_max_months }"
                                                                >
                                                                    <option value="">Selecciona el número de cuotas</option>
                                                                    <option v-for="choice in maxInstallmentChoices" :key="choice.value" :value="choice.value">
                                                                        {{ choice.label }}
                                                                    </option>
                                                                </select>
                                                            </div>
                                                            <div v-if="errors.subscription_max_months" class="error-message">
                                                                {{ errors.subscription_max_months }}
                                                            </div>
                                                            <div v-if="maxInstallmentChoices.length < 12 && form.subscription_max_months" class="text-blue-600 text-sm mt-1">
                                                                ℹ️ Máximo {{ maxInstallmentChoices.length }} cuotas disponibles hasta la fecha de pago final
                                                            </div>
                                                            <div class="text-xs text-gray-500 mt-2">
                                                                El plan de VirtualPos se creará automáticamente al guardar.
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </transition>
                                    </div>

                                    <!-- Acordeón 4: Archivos del programa - OCULTO TEMPORALMENTE
                                    <div class="accordion-separator"></div>
                                    <div class="accordion-section">
                                        <div
                                            class="accordion-header"
                                            @click="filesOpen = !filesOpen"
                                        >
                                            <div class="accordion-title">
                                                Archivos del programa
                                            </div>
                                            <svg
                                                class="accordion-arrow"
                                                :class="{ rotated: filesOpen }"
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="26"
                                                height="14"
                                                viewBox="0 0 26 14"
                                                fill="none"
                                            >
                                                <path
                                                    d="M19.0873 3.27314L20.2008 4.38775L14.1319 10.4588C14.0347 10.5566 13.919 10.6343 13.7917 10.6873C13.6643 10.7403 13.5277 10.7676 13.3897 10.7676C13.2518 10.7676 13.1152 10.7403 12.9878 10.6873C12.8604 10.6343 12.7448 10.5566 12.6475 10.4588L6.57544 4.38775L7.689 3.27419L13.3881 8.97227L19.0873 3.27314Z"
                                                    fill="#007E93"
                                                />
                                            </svg>
                                        </div>
                                        <transition name="accordion-slide">
                                            <div v-if="filesOpen" class="accordion-content">
                                                <div class="pdf-upload-grid">
                                                    <div class="pdf-upload-item">
                                                        <div class="pdf-label">Itinerario</div>
                                                        <div v-if="existingFiles.itinerary_file && !form.itinerary_file" class="existing-file">
                                                            <a :href="existingFiles.itinerary_file" target="_blank" class="existing-file-link">
                                                                Ver archivo actual
                                                            </a>
                                                            <button type="button" @click="removeExistingFile('itinerary')" class="existing-file-remove">&times;</button>
                                                        </div>
                                                        <label class="pdf-upload-btn" :for="'itinerary-file-edit'">
                                                            <span>{{ existingFiles.itinerary_file ? 'Cambiar PDF' : 'Adjunte aquí el PDF' }}</span>
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48" />
                                                            </svg>
                                                        </label>
                                                        <input
                                                            type="file"
                                                            id="itinerary-file-edit"
                                                            accept=".pdf"
                                                            style="display: none"
                                                            @change="handlePdfUpload('itinerary', $event)"
                                                        />
                                                        <div v-if="form.itinerary_file" class="pdf-preview">
                                                            <span class="pdf-name">{{ form.itinerary_file.name }}</span>
                                                            <span class="pdf-size">{{ formatFileSize(form.itinerary_file.size) }}</span>
                                                            <button type="button" @click="removePdfFile('itinerary')" class="pdf-remove">&times;</button>
                                                        </div>
                                                    </div>
                                                    <div class="pdf-upload-item">
                                                        <div class="pdf-label">Cobertura de asistencia</div>
                                                        <div v-if="existingFiles.coverage_file && !form.coverage_file" class="existing-file">
                                                            <a :href="existingFiles.coverage_file" target="_blank" class="existing-file-link">
                                                                Ver archivo actual
                                                            </a>
                                                            <button type="button" @click="removeExistingFile('coverage')" class="existing-file-remove">&times;</button>
                                                        </div>
                                                        <label class="pdf-upload-btn" :for="'coverage-file-edit'">
                                                            <span>{{ existingFiles.coverage_file ? 'Cambiar PDF' : 'Adjunte aquí el PDF' }}</span>
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48" />
                                                            </svg>
                                                        </label>
                                                        <input
                                                            type="file"
                                                            id="coverage-file-edit"
                                                            accept=".pdf"
                                                            style="display: none"
                                                            @change="handlePdfUpload('coverage', $event)"
                                                        />
                                                        <div v-if="form.coverage_file" class="pdf-preview">
                                                            <span class="pdf-name">{{ form.coverage_file.name }}</span>
                                                            <span class="pdf-size">{{ formatFileSize(form.coverage_file.size) }}</span>
                                                            <button type="button" @click="removePdfFile('coverage')" class="pdf-remove">&times;</button>
                                                        </div>
                                                    </div>
                                                    <div class="pdf-upload-item">
                                                        <div class="pdf-label">Lista de equipo</div>
                                                        <div v-if="existingFiles.equipment_file && !form.equipment_file" class="existing-file">
                                                            <a :href="existingFiles.equipment_file" target="_blank" class="existing-file-link">
                                                                Ver archivo actual
                                                            </a>
                                                            <button type="button" @click="removeExistingFile('equipment')" class="existing-file-remove">&times;</button>
                                                        </div>
                                                        <label class="pdf-upload-btn" :for="'equipment-file-edit'">
                                                            <span>{{ existingFiles.equipment_file ? 'Cambiar PDF' : 'Adjunte aquí el PDF' }}</span>
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48" />
                                                            </svg>
                                                        </label>
                                                        <input
                                                            type="file"
                                                            id="equipment-file-edit"
                                                            accept=".pdf"
                                                            style="display: none"
                                                            @change="handlePdfUpload('equipment', $event)"
                                                        />
                                                        <div v-if="form.equipment_file" class="pdf-preview">
                                                            <span class="pdf-name">{{ form.equipment_file.name }}</span>
                                                            <span class="pdf-size">{{ formatFileSize(form.equipment_file.size) }}</span>
                                                            <button type="button" @click="removePdfFile('equipment')" class="pdf-remove">&times;</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </transition>
                                    </div>
                                    FIN Acordeón 4: Archivos del programa - OCULTO TEMPORALMENTE -->

                                    <!-- Estado del programa -->
                                    <div class="accordion-section">
                                        <div class="accordion-title mb-4">
                                            Estado del programa
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <label class="flex items-center gap-3 cursor-pointer">
                                                <div
                                                    @click="form.active = !form.active"
                                                    :class="[
                                                        'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 ease-in-out cursor-pointer',
                                                        form.active ? 'bg-green-500' : 'bg-gray-300'
                                                    ]"
                                                >
                                                    <span
                                                        :class="[
                                                            'inline-block h-4 w-4 transform rounded-full bg-white transition duration-200 ease-in-out',
                                                            form.active ? 'translate-x-6' : 'translate-x-1'
                                                        ]"
                                                    ></span>
                                                </div>
                                                <span :class="[
                                                    'font-nexa-bold text-sm',
                                                    form.active ? 'text-green-600' : 'text-gray-500'
                                                ]">
                                                    {{ form.active ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </label>
                                            <span class="text-xs text-gray-500">
                                                Define si el programa estará visible y disponible para inscripciones
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-8 flex justify-center gap-4">
                        <button
                            type="button"
                            @click="checkCanDeleteProgram"
                            :disabled="isCheckingDelete"
                            class="bg-red-500 hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed text-white px-6 py-3 rounded-full font-nexa-bold transition-colors inline-flex items-center gap-2"
                        >
                            <svg v-if="isCheckingDelete" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            Eliminar programa
                        </button>
                        <Link
                            :href="route('admin.courses.index')"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-full font-nexa-bold transition-colors inline-flex items-center gap-2"
                        >
                            Cancelar
                        </Link>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="bg-[#007e93] hover:bg-[#006b7a] disabled:opacity-50 disabled:cursor-not-allowed rounded-full py-4 px-8 text-white font-nexa-bold text-lg transition-colors duration-200"
                        >
                            <div v-if="isSubmitting" class="flex items-center gap-2">
                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                                <span>Actualizando curso...</span>
                            </div>
                            <span v-else>Actualizar curso</span>
                        </button>
                    </div>
                </form>

                <!-- Modal de confirmación para eliminar programa -->
                <Modal :show="showDeleteModal" @close="closeDeleteModal">
                    <div class="p-6">
                        <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 rounded-full" :class="canDelete ? 'bg-red-100' : 'bg-yellow-100'">
                            <svg v-if="canDelete" class="w-6 h-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <svg v-else class="w-6 h-6 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>

                        <h3 class="text-lg font-bold text-center text-gray-900 mb-2">
                            {{ canDelete ? 'Eliminar Programa' : 'No se puede eliminar' }}
                        </h3>

                        <div v-if="canDelete" class="text-center">
                            <p class="text-gray-600 mb-4">
                                ¿Estás seguro de que deseas eliminar el programa <strong>{{ deleteInfo.program_name }}</strong>?
                            </p>
                            <p class="text-sm text-red-600 mb-4">
                                Esta acción eliminará permanentemente el programa, todos los participantes inscritos y sus datos asociados. Esta acción no se puede deshacer.
                            </p>
                        </div>

                        <div v-else class="text-center">
                            <p class="text-gray-600 mb-4">
                                El programa <strong>{{ deleteInfo.program_name }}</strong> no puede ser eliminado por las siguientes razones:
                            </p>
                            <ul class="text-left text-sm text-red-600 bg-red-50 rounded-lg p-4 mb-4 list-disc list-inside">
                                <li v-for="(reason, index) in deleteReasons" :key="index" class="mb-1">
                                    {{ reason }}
                                </li>
                            </ul>
                        </div>

                        <div class="flex justify-center gap-3 mt-6">
                            <button
                                type="button"
                                @click="closeDeleteModal"
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-full font-nexa-bold transition-colors"
                            >
                                {{ canDelete ? 'Cancelar' : 'Entendido' }}
                            </button>
                            <button
                                v-if="canDelete"
                                type="button"
                                @click="confirmDeleteProgram"
                                :disabled="isDeleting"
                                class="px-4 py-2 bg-red-500 hover:bg-red-600 disabled:opacity-50 text-white rounded-full font-nexa-bold transition-colors inline-flex items-center gap-2"
                            >
                                <svg v-if="isDeleting" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ isDeleting ? 'Eliminando...' : 'Sí, eliminar' }}
                            </button>
                        </div>
                    </div>
                </Modal>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, useForm, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed, nextTick } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SearchableSelect from '@/Components/Ecommerce/SearchableSelect.vue';
import ParticipantsManagement from '@/Components/Courses/ParticipantsManagement.vue';
import Alerts from '@/Components/Alerts.vue';
import Modal from '@/Components/Modal.vue';

// Props
const props = defineProps({
    course: {
        type: Object,
        required: true,
    },
    programs: {
        type: Array,
        default: () => [],
    },
    institutions: {
        type: Array,
        default: () => [],
    },
    salesExecutives: {
        type: Array,
        default: () => [],
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

// Obtener errores de importación desde la sesión
const page = usePage();
const importErrorDetails = computed(() => page.props.importErrorDetails || null);

// Reactive errors from Inertia page props
const errors = computed(() => page.props.errors || {});

// Watch for errors from Inertia (when they come through page props)
watch(errors, (newErrors) => {
    if (newErrors && Object.keys(newErrors).length > 0) {
        console.error('Validation errors from Inertia props:', newErrors);
        const errorList = Object.values(newErrors);
        errorMessage.value = errorList.join(' | ');
        showErrorAlert.value = true;
        isSubmitting.value = false;
        // Scroll al inicio para mostrar la alerta
        nextTick(() => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
}, { immediate: true, deep: true });

// Accordion states
const priceOpen = ref(true);
const travelersOpen = ref(true);
const paymentOpen = ref(true);
const filesOpen = ref(false);

// Existing files from programCourse
const existingFiles = ref({
    itinerary_file: null,
    coverage_file: null,
    equipment_file: null,
});

// Form state
const isSubmitting = ref(false);
const fileInput = ref(null);

// Alert state for validation errors
const showErrorAlert = ref(false);
const errorMessage = ref('');

// Delete program state
const showDeleteModal = ref(false);
const isCheckingDelete = ref(false);
const isDeleting = ref(false);
const canDelete = ref(false);
const deleteReasons = ref([]);
const deleteInfo = ref({ program_name: '', program_code: '' });

// Get programCourse data (first one from the course)
// Laravel puede serializar como 'program_courses' o 'programCourses'
const programCourse = computed(() => {
    const pc = props.course.program_courses?.[0] || props.course.programCourses?.[0] || {};
    console.log('ProgramCourse computed:', pc);
    return pc;
});

// Get selected program data with PDFs
const selectedProgram = computed(() => {
    const programId = form.value.program_id;
    if (!programId) return null;

    // Try to get from loaded programCourse first (has full data)
    const pcProgram = programCourse.value?.program;
    if (pcProgram && pcProgram.id === programId) {
        return pcProgram;
    }

    // Otherwise look in programs list
    return props.programs.find(p => p.id === programId) || null;
});

// Helper function to format date from datetime to YYYY-MM-DD
const formatDate = (datetime) => {
    if (!datetime) return '';
    // Si ya está en formato YYYY-MM-DD, retornarlo tal cual
    if (datetime.match(/^\d{4}-\d{2}-\d{2}$/)) return datetime;
    // Si es formato ISO 8601 (2027-12-20T03:00:00.000000Z), extraer la fecha
    if (datetime.includes('T')) {
        return datetime.split('T')[0];
    }
    // Si tiene espacio (formato MySQL datetime), extraer solo la fecha
    if (datetime.includes(' ')) {
        return datetime.split(' ')[0];
    }
    return datetime;
};

// Helper function to reconstruct payment options from programCourse
const getPaymentOptions = () => {
    const pc = programCourse.value;
    const options = [];
    if (pc.enable_total_payment) {
        options.push('full_payment');
    }
    if (pc.enable_subscription_payment) {
        options.push('subscription');
    }
    return options;
};

// Helper to get full payment options
const getFullPaymentOptions = () => {
    const pc = programCourse.value;

    // Check both camelCase and snake_case (Laravel can serialize either way)
    const paymentOptions = pc.payment_options || pc.paymentOptions;
    console.log('getFullPaymentOptions - paymentOptions from backend:', paymentOptions);

    // Si tiene paymentOptions guardadas, filtrar solo las de tipo "full"
    if (paymentOptions && Array.isArray(paymentOptions) && paymentOptions.length > 0) {
        const fullOptions = paymentOptions
            .filter(opt => opt.code && opt.code.startsWith('full_'))
            .map(opt => opt.code);
        console.log('getFullPaymentOptions - filtered full options:', fullOptions);
        return fullOptions;
    }

    // Fallback: si no hay opciones guardadas pero el pago total está habilitado, no retornar nada
    // El usuario deberá seleccionar manualmente las opciones
    console.log('getFullPaymentOptions - no options found, returning empty array');
    return [];
};

const getSubscriptionPaymentOptions = () => {
    const pc = programCourse.value;

    // Check both camelCase and snake_case (Laravel can serialize either way)
    const paymentOptions = pc.payment_options || pc.paymentOptions;
    console.log('getSubscriptionPaymentOptions - paymentOptions from backend:', paymentOptions);

    // Si tiene paymentOptions guardadas, filtrar solo las de tipo "subscription"
    if (paymentOptions && Array.isArray(paymentOptions) && paymentOptions.length > 0) {
        const subscriptionOptions = paymentOptions
            .filter(opt => opt.code && opt.code.startsWith('subscription_'))
            .map(opt => opt.code);
        console.log('getSubscriptionPaymentOptions - filtered subscription options:', subscriptionOptions);
        return subscriptionOptions;
    }

    // Fallback: si no hay opciones guardadas pero la suscripción está habilitada, no retornar nada
    console.log('getSubscriptionPaymentOptions - no options found, returning empty array');
    return [];
};

// Calculate payment_days_before based on existing dates
const calculatePaymentDaysBefore = () => {
    if (!programCourse.value.departure_date || !programCourse.value.final_payment_date) {
        return '61'; // Default value
    }

    // Usar formatDate para obtener fechas en formato YYYY-MM-DD
    const departureDateStr = formatDate(programCourse.value.departure_date);
    const finalPaymentDateStr = formatDate(programCourse.value.final_payment_date);

    const departureDate = new Date(departureDateStr + 'T00:00:00');
    const finalPaymentDate = new Date(finalPaymentDateStr + 'T00:00:00');

    const diffTime = departureDate - finalPaymentDate;
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

    // Si es exactamente 31 o 61 días, retornar esos valores
    if (diffDays === 31) {
        return '31';
    } else if (diffDays === 61) {
        return '61';
    } else {
        // Fecha personalizada
        return 'custom';
    }
};

// Form data - Initialize with existing course data
const form = ref({
    // Course fields
    institutionId: props.course.institution_id || '',
    educationLevel: props.course.education_level || '',
    grade: props.course.grade || '',
    year: props.course.year ? String(props.course.year) : '2025',
    courseNumber: props.course.course_number || '',
    contactEmail: props.course.contact_email || '',
    contactPhone: props.course.contact_phone || '',
    studentsFile: null,

    // Program plan fields
    program_id: programCourse.value.program_id || '',
    code: programCourse.value.code || '',
    destination: programCourse.value.destination || '',
    departure_date: formatDate(programCourse.value.departure_date),
    trip_price: programCourse.value.trip_price || '',
    payment_days_before: calculatePaymentDaysBefore(),
    final_payment_date: formatDate(programCourse.value.final_payment_date),
    custom_final_payment_date: calculatePaymentDaysBefore() === 'custom' ? formatDate(programCourse.value.final_payment_date) : '',

    // Payment options
    payment_options: getPaymentOptions(),
    full_payment_options: getFullPaymentOptions(),
    subscription_payment_options: getSubscriptionPaymentOptions(),
    subscription_max_months: programCourse.value.subscription_max_months ? String(programCourse.value.subscription_max_months) : '',
    immediate_first_charge: programCourse.value.immediate_first_charge ?? true,

    // Sales executive
    sales_executive_id: programCourse.value.sales_executive_id || '',

    // Archivos del programa
    itinerary_file: null,
    coverage_file: null,
    equipment_file: null,

    // Estado activo
    active: programCourse.value.active ?? true,
});

// Debug: Log para verificar qué datos se están cargando
console.log('=== EDIT COURSE DEBUG ===');
console.log('1. Full Course Data:', props.course);
console.log('2. Program Courses Array:', props.course.program_courses || props.course.programCourses);
console.log('3. First ProgramCourse:', programCourse.value);
console.log('4. ProgramCourse.payment_options:', programCourse.value.payment_options);
console.log('5. Form Initial Values:', form.value);
console.log('   - Departure Date (formatted):', form.value.departure_date);
console.log('   - Final Payment Date (formatted):', form.value.final_payment_date);
console.log('   - Trip Price:', form.value.trip_price);
console.log('   - Subscription Max Months:', form.value.subscription_max_months);
console.log('   - Payment Options:', form.value.payment_options);
console.log('   - Full Payment Options:', form.value.full_payment_options);
console.log('   - Subscription Payment Options:', form.value.subscription_payment_options);
console.log('========================');

// Payment options data (from PaymentDetails.vue)
const fullPaymentChoicesBase = [
    { code: 'full_transfer_khipu', label: 'Transferencia (Khipu)', installments: null },
    { code: 'full_debit_credit_0', label: 'Débito y crédito sin cuotas (Webpay)', installments: 0 },
    { code: 'full_debit_credit_3', label: 'Débito y crédito hasta 3 cuotas sin interés (Webpay)', installments: 3 },
    { code: 'full_debit_credit_6', label: 'Débito y crédito hasta 6 cuotas sin interés (Webpay)', installments: 6 },
    { code: 'full_debit_credit_9', label: 'Débito y crédito hasta 9 cuotas sin interés (Webpay)', installments: 9 },
    { code: 'full_debit_credit_12', label: 'Débito y crédito hasta 12 cuotas sin interés (Webpay)', installments: 12 },
    { code: 'full_international', label: 'Pago Internacional (Webpay)', installments: null },
];

const subscriptionChoices = [
    { code: 'subscription_virtualpos', label: 'Suscripción mensual (VirtualPos)' },
];

// Watch for institution selection to auto-fill contact details
watch(
    () => form.value.institutionId,
    (newInstitutionId) => {
        if (newInstitutionId) {
            const selectedInstitution = props.institutions.find(
                (inst) => inst.id == newInstitutionId
            );
            if (selectedInstitution) {
                form.value.contactEmail = selectedInstitution.email || '';
                form.value.contactPhone = selectedInstitution.phone || '';
            }
        } else {
            form.value.contactEmail = '';
            form.value.contactPhone = '';
        }
    }
);

// Computed properties for filtering payment options based on date
// Pago total (cuotas tarjeta sin interés) se valida contra departure_date
const fullPaymentChoices = computed(() => {
    if (!form.value.departure_date) {
        return fullPaymentChoicesBase;
    }

    const now = new Date();
    now.setHours(0, 0, 0, 0); // Normalizar a inicio del día
    const end = new Date(form.value.departure_date + 'T00:00:00');

    // Calcular días exactos de diferencia
    const diffTime = end.getTime() - now.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    // Cada cuota = 30 días aproximadamente
    const availableMonths = Math.max(0, Math.floor(diffDays / 30));

    return fullPaymentChoicesBase.filter(option => {
        if (option.installments === null || option.installments === 0) {
            return true;
        }
        return option.installments <= availableMonths;
    });
});

const maxInstallmentChoices = computed(() => {
    const choices = [];
    let maxByDate = 12;

    // Usar la fecha final de pago como límite para calcular cuotas
    if (form.value.final_payment_date) {
        const now = new Date();
        now.setHours(0, 0, 0, 0); // Normalizar a inicio del día
        const end = new Date(form.value.final_payment_date + 'T00:00:00');

        // Calcular días exactos de diferencia
        const diffTime = end.getTime() - now.getTime();
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        // Cada cuota = 30 días aproximadamente
        maxByDate = Math.max(0, Math.floor(diffDays / 30));
    }

    const hardMax = 12;
    const max = Math.min(hardMax, maxByDate);

    for (let i = 1; i <= max; i++) {
        choices.push({ value: i.toString(), label: `${i}` });
    }

    if (choices.length === 0) {
        choices.push({ value: '1', label: '1' });
    }

    return choices;
});

// Calcular fecha límite de pago automáticamente
const calculatedFinalPaymentDate = computed(() => {
    if (!form.value.departure_date || !form.value.payment_days_before || form.value.payment_days_before === 'custom') {
        return null;
    }

    const departureDate = new Date(form.value.departure_date + 'T00:00:00');
    const daysToSubtract = parseInt(form.value.payment_days_before);

    // Restar los días a la fecha de inicio
    const finalPaymentDate = new Date(departureDate);
    finalPaymentDate.setDate(departureDate.getDate() - daysToSubtract);

    // Retornar en formato YYYY-MM-DD
    return finalPaymentDate.toISOString().split('T')[0];
});

// Fecha de hoy en formato YYYY-MM-DD para el atributo min del input
const todayDate = computed(() => {
    const today = new Date();
    return today.toISOString().split('T')[0];
});

// Advertencia si la fecha final de pago es anterior a hoy (solo visual, no bloquea)
const finalPaymentDateWarning = computed(() => {
    const dateToCheck = form.value.payment_days_before === 'custom'
        ? form.value.custom_final_payment_date
        : calculatedFinalPaymentDate.value;

    if (!dateToCheck) return '';

    const selectedDate = new Date(dateToCheck + 'T00:00:00');
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (selectedDate < today) {
        return 'La fecha seleccionada es anterior a hoy';
    }
    return '';
});

// Validación en tiempo real de la fecha de inicio (departure_date)
const departureDateError = computed(() => {
    if (!form.value.departure_date) {
        return null;
    }
    const selectedDate = new Date(form.value.departure_date + 'T00:00:00');
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (selectedDate < today) {
        return 'La fecha de inicio no puede ser anterior a hoy';
    }
    return null;
});

// Validación de fecha personalizada
const customPaymentDateError = computed(() => {
    if (form.value.payment_days_before !== 'custom') {
        return null;
    }
    if (!form.value.custom_final_payment_date) {
        return 'Debe seleccionar una fecha';
    }
    return null;
});

// Watcher para actualizar final_payment_date automáticamente
watch([() => form.value.departure_date, () => form.value.payment_days_before, () => form.value.custom_final_payment_date], () => {
    if (form.value.payment_days_before === 'custom') {
        // Usar fecha personalizada
        if (form.value.custom_final_payment_date) {
            form.value.final_payment_date = form.value.custom_final_payment_date;
        }
    } else if (calculatedFinalPaymentDate.value) {
        // Usar fecha calculada
        form.value.final_payment_date = calculatedFinalPaymentDate.value;
    }
}, { immediate: false }); // No immediate para evitar sobrescribir el valor inicial en custom

// Payment option functions
const isPaymentOptionSelected = (option) => {
    return form.value.payment_options && form.value.payment_options.includes(option);
};

const handlePaymentOptionChange = (option, event) => {
    if (!form.value.payment_options) {
        form.value.payment_options = [];
    }

    if (event.target.checked) {
        if (!form.value.payment_options.includes(option)) {
            form.value.payment_options.push(option);
        }
    } else {
        const index = form.value.payment_options.indexOf(option);
        if (index > -1) {
            form.value.payment_options.splice(index, 1);
        }

        if (option === 'full_payment') {
            form.value.full_payment_options = [];
        } else if (option === 'subscription') {
            form.value.subscription_payment_options = [];
            form.value.subscription_max_months = '';
        }
    }
};

const toggleFullOption = (code, event) => {
    console.log('toggleFullOption called:', { code, checked: event.target.checked, currentOptions: form.value.full_payment_options });
    if (!Array.isArray(form.value.full_payment_options)) form.value.full_payment_options = [];
    const set = new Set(form.value.full_payment_options);
    if (event.target.checked) set.add(code); else set.delete(code);
    form.value.full_payment_options = Array.from(set);
    console.log('toggleFullOption result:', form.value.full_payment_options);
};

const toggleSubscriptionOption = (code, event) => {
    console.log('toggleSubscriptionOption called:', { code, checked: event.target.checked, currentOptions: form.value.subscription_payment_options });
    if (!Array.isArray(form.value.subscription_payment_options)) form.value.subscription_payment_options = [];
    const set = new Set(form.value.subscription_payment_options);
    if (event.target.checked) set.add(code); else set.delete(code);
    form.value.subscription_payment_options = Array.from(set);
    console.log('toggleSubscriptionOption result:', form.value.subscription_payment_options);
};

// Select program
const selectProgram = (programId) => {
    form.value.program_id = programId;
};

// Format programs for SearchableSelect with searchable text
const programsFormatted = computed(() => {
    return props.programs.map(program => ({
        id: program.id,
        name: program.name,
        searchText: program.name // Solo el nombre para búsqueda (destino ya no está en plantillas)
    }));
});

// Format price with thousands separator (no decimals)
const formatPrice = (value) => {
    if (!value && value !== 0) return '';
    // Convert to number and remove decimals
    const number = Math.floor(parseFloat(value));
    if (isNaN(number)) return '';
    // Format with thousands separator
    return new Intl.NumberFormat('es-CL').format(number);
};

// Handle price input
const handlePriceInput = (event) => {
    const value = event.target.value;
    // Remove all non-digit characters
    const cleanValue = value.replace(/\D/g, '');
    // Update form with the clean number
    form.value.trip_price = cleanValue ? parseInt(cleanValue) : '';
};

// Handle file upload
const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        const allowedTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'text/csv',
        ];

        if (
            allowedTypes.includes(file.type) ||
            file.name.match(/\.(xlsx|xls|csv)$/i)
        ) {
            form.value.studentsFile = file;
        } else {
            alert(
                'Por favor selecciona un archivo Excel válido (.xlsx, .xls, .csv)'
            );
            event.target.value = '';
        }
    }
};

// Initialize existing files from programCourse
const initExistingFiles = () => {
    const pc = programCourse.value;
    console.log('=== INIT EXISTING FILES ===');
    console.log('ProgramCourse data:', pc);
    console.log('itinerary_file (raw):', pc.itinerary_file);
    console.log('itinerary_file_url:', pc.itinerary_file_url);
    console.log('travel_assistance_coverage (raw):', pc.travel_assistance_coverage);
    console.log('travel_assistance_coverage_url:', pc.travel_assistance_coverage_url);
    console.log('equipment_list (raw):', pc.equipment_list);
    console.log('equipment_list_url:', pc.equipment_list_url);

    existingFiles.value = {
        itinerary_file: pc.itinerary_file_url || pc.itineraryFileUrl || null,
        coverage_file: pc.travel_assistance_coverage_url || pc.travelAssistanceCoverageUrl || null,
        equipment_file: pc.equipment_list_url || pc.equipmentListUrl || null,
    };

    console.log('Existing files after init:', existingFiles.value);
};
initExistingFiles();

// Handle PDF file upload
const handlePdfUpload = (type, event) => {
    const file = event.target.files[0];
    if (file) {
        if (file.type !== 'application/pdf') {
            alert('Por favor seleccione un archivo PDF válido.');
            event.target.value = '';
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            alert(`${file.name} es demasiado grande. El tamaño máximo es 10MB.`);
            event.target.value = '';
            return;
        }
        form.value[`${type}_file`] = file;
    }
};

// Remove PDF file (new upload)
const removePdfFile = (type) => {
    form.value[`${type}_file`] = null;
};

// Remove existing PDF file
const removeExistingFile = (type) => {
    existingFiles.value[`${type}_file`] = null;
    // Mark for deletion on server
    form.value[`remove_${type}_file`] = true;
};

// Format file size
const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

// Format date for display (DD/MM/YYYY)
const formatDateForDisplay = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString + 'T00:00:00');
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
};

// Submit form
const saveCourse = () => {
    console.log('=== SAVING COURSE ===');
    console.log('Form values before submit:', {
        full_payment_options: form.value.full_payment_options,
        subscription_payment_options: form.value.subscription_payment_options,
        payment_options: form.value.payment_options,
    });

    isSubmitting.value = true;

    // Create FormData for file upload
    const formData = new FormData();

    // Laravel method spoofing for PUT request
    formData.append('_method', 'PUT');

    // Course fields
    if (form.value.institutionId) formData.append('institutionId', form.value.institutionId);
    if (form.value.educationLevel) formData.append('educationLevel', form.value.educationLevel);
    if (form.value.grade) formData.append('grade', form.value.grade);
    if (form.value.year) formData.append('year', form.value.year);
    if (form.value.courseNumber) formData.append('courseNumber', form.value.courseNumber);
    if (form.value.contactEmail) formData.append('contactEmail', form.value.contactEmail);
    if (form.value.contactPhone) formData.append('contactPhone', form.value.contactPhone);
    if (form.value.studentsFile) formData.append('studentsFile', form.value.studentsFile);

    // Program plan fields
    if (form.value.program_id) formData.append('program_id', form.value.program_id);
    if (form.value.code) formData.append('code', form.value.code);
    if (form.value.destination) formData.append('destination', form.value.destination);
    if (form.value.departure_date) formData.append('departure_date', form.value.departure_date);
    if (form.value.trip_price) formData.append('trip_price', form.value.trip_price);
    if (form.value.final_payment_date) formData.append('final_payment_date', form.value.final_payment_date);
    if (form.value.payment_days_before) formData.append('min_days_before_departure', form.value.payment_days_before);

    // Payment options - new structure
    if (form.value.payment_options && form.value.payment_options.length > 0) {
        form.value.payment_options.forEach(option => {
            formData.append('payment_options[]', option);
        });
    }

    if (form.value.full_payment_options && form.value.full_payment_options.length > 0) {
        console.log('Appending full_payment_options to FormData:', form.value.full_payment_options);
        form.value.full_payment_options.forEach(option => {
            formData.append('full_payment_options[]', option);
        });
    } else {
        console.log('NO full_payment_options to append (empty or null)');
    }

    if (form.value.subscription_payment_options && form.value.subscription_payment_options.length > 0) {
        console.log('Appending subscription_payment_options to FormData:', form.value.subscription_payment_options);
        form.value.subscription_payment_options.forEach(option => {
            formData.append('subscription_payment_options[]', option);
        });
    } else {
        console.log('NO subscription_payment_options to append (empty or null)');
    }

    if (form.value.subscription_max_months) {
        formData.append('subscription_max_months', form.value.subscription_max_months);
    }

    // Immediate first charge - siempre enviar el valor (true o false)
    formData.append('immediate_first_charge', form.value.immediate_first_charge ? '1' : '0');

    // Enable payment options - siempre enviar los valores
    formData.append('enable_total_payment', form.value.payment_options?.includes('full_payment') ? '1' : '0');
    formData.append('enable_subscription_payment', form.value.payment_options?.includes('subscription') ? '1' : '0');

    // Sales executive
    if (form.value.sales_executive_id)
        formData.append('sales_executive_id', form.value.sales_executive_id);

    // Estado activo
    formData.append('active', form.value.active ? '1' : '0');

    // Archivos del programa
    if (form.value.itinerary_file) formData.append('itinerary_file', form.value.itinerary_file);
    if (form.value.coverage_file) formData.append('coverage_file', form.value.coverage_file);
    if (form.value.equipment_file) formData.append('equipment_file', form.value.equipment_file);

    // Marcar archivos para eliminar
    if (form.value.remove_itinerary_file) formData.append('remove_itinerary_file', '1');
    if (form.value.remove_coverage_file) formData.append('remove_coverage_file', '1');
    if (form.value.remove_equipment_file) formData.append('remove_equipment_file', '1');

    // Submit as PUT request to update route
    router.post(route('admin.courses.update', props.course.id), formData, {
        onSuccess: () => {
            isSubmitting.value = false;
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
            isSubmitting.value = false;

            // Mostrar alerta con los errores
            const errorList = Object.values(errors);
            errorMessage.value = errorList.join(' | ');
            showErrorAlert.value = true;

            // Scroll al inicio
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

// Close error alert
const closeErrorAlert = () => {
    showErrorAlert.value = false;
};

// Check if program can be deleted
const checkCanDeleteProgram = async () => {
    const pcId = programCourse.value?.id;
    if (!pcId) {
        errorMessage.value = 'No se encontró el programa asociado al curso.';
        showErrorAlert.value = true;
        return;
    }

    isCheckingDelete.value = true;

    try {
        const response = await fetch(route('admin.courses.program.can-delete', { program_course_id: pcId }), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await response.json();

        canDelete.value = data.can_delete;
        deleteReasons.value = data.reasons || [];
        deleteInfo.value = {
            program_name: data.program_name || programCourse.value?.name || 'Este programa',
            program_code: data.program_code || programCourse.value?.code || '',
        };

        showDeleteModal.value = true;
    } catch (error) {
        console.error('Error checking if program can be deleted:', error);
        errorMessage.value = 'Error al verificar si el programa puede ser eliminado.';
        showErrorAlert.value = true;
    } finally {
        isCheckingDelete.value = false;
    }
};

// Close delete modal
const closeDeleteModal = () => {
    showDeleteModal.value = false;
    canDelete.value = false;
    deleteReasons.value = [];
};

// Confirm and execute program deletion
const confirmDeleteProgram = async () => {
    const pcId = programCourse.value?.id;
    if (!pcId) return;

    isDeleting.value = true;

    try {
        const response = await fetch(route('admin.courses.program.delete'), {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({ program_course_id: pcId }),
        });

        const data = await response.json();

        if (data.success) {
            closeDeleteModal();
            // Redirect to courses index with success message
            router.visit(route('admin.courses.index'), {
                preserveState: false,
                onSuccess: () => {
                    // The flash message will be handled by the index page
                },
            });
        } else {
            closeDeleteModal();
            errorMessage.value = data.message || 'No se pudo eliminar el programa.';
            if (data.reasons && data.reasons.length > 0) {
                errorMessage.value += ' ' + data.reasons.join(' ');
            }
            showErrorAlert.value = true;
        }
    } catch (error) {
        console.error('Error deleting program:', error);
        closeDeleteModal();
        errorMessage.value = 'Error al eliminar el programa. Por favor, intente nuevamente.';
        showErrorAlert.value = true;
    } finally {
        isDeleting.value = false;
    }
};
</script>

<style scoped>
/* Font families */
.font-nexa-bold {
    font-family: 'Nexa-Bold', sans-serif;
}

.font-nexa-regular {
    font-family: 'Nexa-Regular', sans-serif;
}

/* Custom scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Administrative Details Card */
.detalle-administrativo-card {
    background: var(--colores-neutro-blanco, #ffffff);
    border: 1px solid #d3d3d3;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0px 4px 11.6px 0px rgba(163, 163, 163, 0.11);
}

.administrative-form-header {
    display: flex;
    flex-direction: column;
    gap: 19px;
}

.administrative-title {
    color: var(--Colores-OP2-Turquesa, #007e93);
    font-family: Nexa;
    font-size: 24px;
    font-weight: 700;
    line-height: 28px;
}

/* Accordion Styles */
.accordion-section {
    margin-bottom: 20px;
}

.accordion-header {
    padding: 12px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.accordion-header:hover {
    background-color: rgba(0, 126, 147, 0.05);
    border-radius: 8px;
    padding-left: 16px;
    padding-right: 16px;
}

.accordion-title {
    color: var(--Colores-OP2-Turquesa, #007e93);
    font-family: Nexa;
    font-size: 18px;
    font-weight: 700;
    line-height: 22px;
}

.accordion-arrow {
    transition: transform 0.3s ease;
}

.accordion-arrow.rotated {
    transform: rotate(180deg);
}

.accordion-content {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 20px 0;
}

/* Accordion transitions */
.accordion-slide-enter-active,
.accordion-slide-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
}

.accordion-slide-enter-from,
.accordion-slide-leave-to {
    opacity: 0;
    max-height: 0;
}

.accordion-slide-enter-to,
.accordion-slide-leave-from {
    opacity: 1;
    max-height: 2000px;
}

/* Separator */
.accordion-separator {
    width: 100%;
    height: 1px;
    background: var(--colores-neutro-gris-3, #d3d3d3);
    margin: 10px 0;
}

/* Field Rows */
.price-fields-row {
    display: flex;
    gap: 18px;
    width: 100%;
}

.staff-field-row {
    display: flex;
    width: 100%;
}

.institution-field-row {
    display: flex;
    width: 100%;
}

.education-details-row {
    display: flex;
    gap: 18px;
    width: 100%;
}

.contact-fields-row {
    display: flex;
    gap: 18px;
    width: 100%;
}

.subscription-fields-row {
    display: flex;
    gap: 18px;
    width: 100%;
}

.field-container {
    flex: 1;
}

.field-container-small {
    flex-shrink: 0;
    width: 80px;
}

.field-wrapper {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.field-label {
    color: var(--colores-neutro-gris-1, #5b5b5b);
    font-family: Nexa;
    font-size: 12px;
    font-weight: 700;
    line-height: 13px;
}

/* Input styles */
.admin-input-text,
.admin-select,
.admin-select-small {
    width: 100%;
    height: 46px;
    background: white;
    border-radius: 8px;
    border: 1px solid var(--colores-neutro-gris-1, #5b5b5b);
    padding: 12px 16px;
    color: var(--Colores-OP2-Turquesa, #007e93);
    font-family: Nexa;
    font-size: 12px;
    font-weight: 700;
    line-height: 18px;
    outline: none;
    box-shadow: 0px 1px 4px 0px rgba(25, 33, 61, 0.08);
}

.admin-input-text::placeholder {
    color: #c7c7c7;
}

/* Students Upload */
.students-upload-section {
    display: flex;
    flex-direction: column;
    gap: 15px;
    padding-top: 20px;
    border-top: 1px solid #d3d3d3;
}

.students-section-title {
    color: var(--Colores-OP2-Turquesa, #007e93);
    font-family: Nexa;
    font-size: 16px;
    font-weight: 700;
    line-height: 20px;
}

.students-upload-field {
    width: 100%;
}

.file-upload-area {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 80px;
    background: white;
    border: 2px dashed var(--colores-neutro-gris-1, #5b5b5b);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.file-upload-area:hover {
    border-color: var(--Colores-OP2-Turquesa, #007e93);
    background: rgba(0, 126, 147, 0.02);
}

.upload-text {
    color: var(--colores-neutro-gris-1, #5b5b5b);
    font-family: Nexa;
    font-size: 12px;
    font-weight: 400;
    line-height: 18px;
    text-align: center;
}

/* Payment Options */
.payment-description {
    color: var(--colores-neutro-gris-1, #5b5b5b);
    font-family: Nexa;
    font-size: 14px;
    font-weight: 400;
    line-height: 20px;
    margin-bottom: 10px;
}

.payment-option {
    background: var(--colores-neutro-gris-1, #f9f9f9);
    border-radius: 13.61px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-self: stretch;
    margin-bottom: 15px;
    overflow: hidden;
}

.payment-option-header {
    display: flex;
    align-items: center;
    gap: 20px;
    width: 100%;
    margin-bottom: 10px;
}

.payment-checkbox {
    position: relative;
}

.checkbox-input {
    width: 22.69px;
    height: 22.69px;
    border-radius: 43.963px;
    border: 0.709px solid var(--Colores-OP2-Amarillo, #FFB232);
    appearance: none;
    cursor: pointer;
    position: relative;
    transition: all 0.2s ease;
    display: flex;
    padding: 5.673px;
    align-items: center;
    gap: 7.091px;
    flex: 1 0 0;
}

.checkbox-input:checked {
    background-color: transparent;
    border-color: var(--Colores-OP2-Amarillo, #FFB232);
}

.checkbox-input:checked::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    border-radius: 29.781px;
    background: #FAB547;
    display: flex;
    width: 11.345px;
    height: 11.345px;
    padding: 7.091px;
    flex-direction: column;
    align-items: flex-start;
    gap: 7.091px;
}

.checkbox-label {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.payment-option-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex: 1;
}

.payment-option-title {
    color: var(--colores-neutro-negro, #434343);
    text-align: left;
    font-family: "Outfit-Medium", sans-serif;
    font-size: 20px;
    line-height: 69.69px;
    font-weight: 500;
}

.subscription-details {
    padding-top: 10px;
}

/* Payment Option Info */
.payment-option-info {
    display: flex;
    align-items: center;
    margin-left: auto;
}

.info-icon {
    width: 18px;
    height: 18px;
}

/* Payment Method Select */
.payment-method-select {
    margin-top: 15px;
}

.payment-method-select .flex.flex-col.gap-2 label {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 16px;
    border-radius: 12px;
    transition: all 0.2s ease;
    cursor: pointer;
    border: 1px solid transparent;
}

.payment-method-select .flex.flex-col.gap-2 label:hover {
    background-color: rgba(0, 126, 147, 0.05);
    border-color: rgba(0, 126, 147, 0.2);
    transform: translateX(4px);
}

.payment-method-select .flex.flex-col.gap-2 label input[type="checkbox"] {
    width: 22px;
    height: 22px;
    border-radius: 12px;
    border: 2px solid #E5E5E5;
    appearance: none;
    cursor: pointer;
    position: relative;
    transition: all 0.2s ease;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.payment-method-select .flex.flex-col.gap-2 label input[type="checkbox"]:hover {
    border-color: var(--Colores-OP2-Turquesa, #007e93);
    transform: scale(1.05);
}

.payment-method-select .flex.flex-col.gap-2 label input[type="checkbox"]:checked {
    background-color: var(--Colores-OP2-Turquesa, #007e93);
    border-color: var(--Colores-OP2-Turquesa, #007e93);
    box-shadow: 0 2px 8px rgba(0, 126, 147, 0.3);
}

.payment-method-select .flex.flex-col.gap-2 label input[type="checkbox"]:checked::after {
    content: '✓';
    position: absolute;
    color: #ffffff;
    font-size: 14px;
    font-weight: bold;
    line-height: 1;
}

.payment-method-select .flex.flex-col.gap-2 label span {
    color: #434343;
    font-family: 'Nexa', sans-serif;
    font-size: 14px;
    font-weight: 500;
    user-select: none;
}

/* Installments Options */
.installments-options {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-top: 15px;
}

/* Field Input (for select) */
.field-input-container {
    position: relative;
    width: 100%;
}

.field-input {
    background: #ffffff;
    border-radius: 20px;
    border: 2px solid #E5E5E5;
    padding: 12px 16px;
    height: 48px;
    color: #434343;
    font-family: 'Nexa', sans-serif;
    font-size: 14px;
    font-weight: 500;
    outline: none;
    width: 100%;
    transition: all 0.2s ease;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 16px center;
    background-repeat: no-repeat;
    background-size: 16px;
    padding-right: 48px;
}

.field-input:hover {
    border-color: var(--Colores-OP2-Turquesa, #007e93);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 126, 147, 0.15);
}

.field-input:focus {
    border-color: var(--Colores-OP2-Turquesa, #007e93);
    box-shadow: 0 0 0 3px rgba(0, 126, 147, 0.1);
    transform: translateY(-1px);
}

.field-input:disabled {
    background-color: #f9f9f9;
    color: #9ca3af;
    cursor: not-allowed;
    border-color: #e5e7eb;
}

/* Error Message */
.error-message {
    color: #dc2626;
    font-size: 14px;
    margin-top: 8px;
    font-weight: 500;
}

/* Utility classes */
.text-red-500 {
    color: #dc2626;
}

.text-blue-600 {
    color: #2563eb;
}

.text-gray-500 {
    color: #6b7280;
}

.text-xs {
    font-size: 12px;
}

.text-sm {
    font-size: 14px;
}

.mt-1 {
    margin-top: 4px;
}

.mt-2 {
    margin-top: 8px;
}

.mt-4 {
    margin-top: 16px;
}

.mb-4 {
    margin-bottom: 16px;
}

.gap-2 {
    gap: 8px;
}

.flex {
    display: flex;
}

.flex-col {
    flex-direction: column;
}

.items-center {
    align-items: center;
}

.block {
    display: block;
}

/* PDF Upload Styles */
.pdf-upload-grid {
    display: flex;
    flex-direction: row;
    gap: 20px;
    align-items: flex-start;
    justify-content: flex-start;
    width: 100%;
}

.pdf-upload-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
    align-items: center;
    flex: 1;
}

.pdf-label {
    color: var(--colores-neutro-gris-4, #5b5b5b);
    font-family: 'Nexa-Bold', sans-serif;
    font-size: 12px;
    font-weight: 700;
}

.pdf-upload-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 12px 16px;
    background: #fefeff;
    border: 1px dashed #5b5b5b;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #c7c7c7;
    font-size: 14px;
}

.pdf-upload-btn:hover {
    border-color: #007e93;
    background-color: rgba(0, 126, 147, 0.05);
}

.pdf-preview {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    width: 100%;
}

.pdf-name {
    color: #5b5b5b;
    font-family: 'Nexa-Bold', sans-serif;
    font-size: 12px;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.pdf-size {
    color: #c7c7c7;
    font-size: 11px;
}

.pdf-remove {
    background: none;
    border: none;
    color: #dc2626;
    cursor: pointer;
    font-size: 18px;
    font-weight: bold;
    padding: 0 4px;
}

.pdf-remove:hover {
    color: #b91c1c;
}

.existing-file {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: #f0f9ff;
    border: 1px solid #0284c7;
    border-radius: 6px;
    width: 100%;
}

.existing-file-link {
    color: #0284c7;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    flex: 1;
}

.existing-file-link:hover {
    color: #0369a1;
    text-decoration: underline;
}

.existing-file-remove {
    background: none;
    border: none;
    color: #dc2626;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
    padding: 0 4px;
}

.existing-file-remove:hover {
    color: #b91c1c;
}
</style>
