<template>
    <div class="detalle-administrativo-card">
        <div class="administrative-form-container">
            <div class="administrative-form-header">
                <div class="administrative-title">
                    Detalle administrativo
                </div>

                <!-- Estado de Pago (solo en modo edit) -->
                <div v-if="mode === 'edit'" class="payment-status-section">
                    <div class="payment-status-card">
                        <div class="payment-status-content">
                            <div class="payment-status-row">
                                <!-- Percentage -->
                                <div class="payment-percentage">
                                    {{ paymentStatus.paymentPercentage }}%
                                </div>
                                
                                <!-- Remaining Amount and Total -->
                                <div class="payment-amounts">
                                    <span class="remaining-text">Resto pagar</span>
                                    <div class="amounts-row">
                                        <div class="remaining-amount">
                                            {{ formatPrice(paymentStatus.remainingAmount) }}
                                        </div>
                                        <div class="total-amount">
                                            /{{ formatPrice(paymentStatus.totalAmount) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Progress Bar -->
                            <div class="payment-progress-bar">
                                <div 
                                    class="payment-progress-fill"
                                    :style="{ width: `${paymentStatus.paymentPercentage}%` }"
                                >
                                    <div class="progress-stripes">
                                        <svg width="100%" height="100%" viewBox="0 0 100 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <defs>
                                                <pattern id="diagonalHatch" patternUnits="userSpaceOnUse" width="8" height="8" patternTransform="rotate(45)">
                                                    <line x1="0" y1="0" x2="0" y2="8" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
                                                </pattern>
                                            </defs>
                                            <rect width="100%" height="100%" fill="url(#diagonalHatch)"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Ver Estados de Pagos Button -->
                            <button type="button" class="payment-states-button" @click="viewPaymentStates">
                                Ver estados de pagos
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Acordeón 1: Precio del viaje -->
                <div class="accordion-section">
                    <div
                        class="accordion-header"
                        @click="priceOpen = !priceOpen"
                    >
                        <div class="accordion-title">
                            Precio del viaje
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
                            <g clip-path="url(#clip0_833_13975)">
                                <path
                                    d="M19.0873 3.27314L20.2008 4.38775L14.1319 10.4588C14.0347 10.5566 13.919 10.6343 13.7917 10.6873C13.6643 10.7403 13.5277 10.7676 13.3897 10.7676C13.2518 10.7676 13.1152 10.7403 12.9878 10.6873C12.8604 10.6343 12.7448 10.5566 12.6475 10.4588L6.57544 4.38775L7.689 3.27419L13.3881 8.97227L19.0873 3.27314Z"
                                    fill="#007E93"
                                />
                            </g>
                            <defs>
                                <clipPath id="clip0_833_13975">
                                    <rect
                                        width="12.6173"
                                        height="25.2128"
                                        fill="white"
                                        transform="translate(26 0.691406) rotate(90)"
                                    />
                                </clipPath>
                            </defs>
                        </svg>
                    </div>
                    <transition name="accordion-slide">
                        <div
                            v-if="priceOpen"
                            class="accordion-content"
                        >
                            <div class="price-fields-row">
                                <div class="field-container">
                                    <div class="field-wrapper">
                                        <div class="field-label">
                                            Pago total
                                        </div>
                                        <input
                                            type="text"
                                            v-model="formattedPrice"
                                            @input="handlePriceInput"
                                            @blur="handlePriceBlur"
                                            placeholder="--------"
                                            class="admin-input-text"
                                            :class="{ 'border-red-500': errors.total_price }"
                                        />
                                        <span v-if="errors.total_price" class="text-red-500 text-sm mt-1">
                                            {{ errors.total_price }}
                                        </span>
                                    </div>
                                </div>
                                <div class="field-container">
                                    <div class="field-wrapper">
                                        <div class="field-label">
                                            Fecha final de pago
                                        </div>
                                        <input
                                            type="date"
                                            v-model="formData.final_payment_date"
                                            class="admin-input-text"
                                            :class="{
                                                'border-red-500': errors.final_payment_date
                                            }"
                                        />
                                        <span v-if="errors.final_payment_date" class="text-red-500 text-sm mt-1">
                                            {{ errors.final_payment_date }}
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
                                        <button
                                            type="button"
                                            @click="$emit('create-executive')"
                                            class="text-[#007e93] text-xs font-nexa-bold hover:underline"
                                        >
                                            + Crear nuevo ejecutivo
                                        </button>
                                    </div>
                                    <SearchableSelect
                                        :options="salesExecutivesFormatted"
                                        :value="formData.sales_executive_id"
                                        placeholder="Busca por nombre o código"
                                        @input="handleExecutiveChange"
                                        search-key="searchText"
                                    />
                                    <span v-if="errors.sales_executive_id" class="text-red-500 text-sm mt-1">
                                        {{ errors.sales_executive_id }}
                                    </span>
                                </div>
                            </div>

                        </div>
                    </transition>
                </div>

                <!-- Separador -->
                <AccordionSeparator />

                <!-- Acordeón 2: Quienes viajan -->
                <div class="accordion-section">
                    <div
                        class="accordion-header"
                        @click="travelersOpen = !travelersOpen"
                    >
                        <div class="accordion-title">
                            Quienes viajan
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
                            <g clip-path="url(#clip0_833_13975_4)">
                                <path
                                    d="M19.0873 3.27314L20.2008 4.38775L14.1319 10.4588C14.0347 10.5566 13.919 10.6343 13.7917 10.6873C13.6643 10.7403 13.5277 10.7676 13.3897 10.7676C13.2518 10.7676 13.1152 10.7403 12.9878 10.6873C12.8604 10.6343 12.7448 10.5566 12.6475 10.4588L6.57544 4.38775L7.689 3.27419L13.3881 8.97227L19.0873 3.27314Z"
                                    fill="#007E93"
                                />
                            </g>
                            <defs>
                                <clipPath id="clip0_833_13975_4">
                                    <rect
                                        width="12.6173"
                                        height="25.2128"
                                        fill="white"
                                        transform="translate(26 0.691406) rotate(90)"
                                    />
                                </clipPath>
                            </defs>
                        </svg>
                    </div>
                    <transition name="accordion-slide">
                        <div
                            v-if="travelersOpen"
                            class="accordion-content"
                        >
                            <div class="institution-field-row">
                                <div class="field-wrapper">
                                    <div class="flex justify-between items-center">
                                        <div class="field-label">
                                            Nombre de institución
                                        </div>
                                        <button
                                            type="button"
                                            @click="createNewInstitution"
                                            class="text-[#007e93] text-xs font-nexa-bold hover:underline"
                                            :disabled="props.hasExistingCourse"
                                            :class="{ 'opacity-50 cursor-not-allowed': props.hasExistingCourse }"
                                        >
                                            + Crear nueva institución
                                        </button>
                                    </div>
                                    <SearchableSelect
                                        :options="institutionsFormatted"
                                        :value="formData.institution_id"
                                        placeholder="Busca por nombre de institución"
                                        @input="handleInstitutionChange"
                                        search-key="searchText"
                                        :disabled="props.hasExistingCourse"
                                    />
                                </div>
                            </div>
                            <div class="education-details-row">
                                <div class="field-container">
                                    <div class="field-wrapper">
                                        <div class="field-label">
                                            Nivel de educación
                                        </div>
                                        <SearchableSelect
                                            :options="educationLevelsFormatted"
                                            :value="formData.education_level"
                                            placeholder="Selecciona un nivel educativo"
                                            @input="handleEducationLevelChange"
                                            search-key="searchText"
                                            :disabled="!canEnableCourseFields()"
                                        />

                                    </div>
                                </div>
                                
                                <div class="field-container-small">
                                    <div class="field-wrapper">
                                        <div class="field-label">
                                            Curso
                                        </div>
                                        <select
                                            v-model="formData.course_number"
                                            class="admin-select-small"
                                            :disabled="!canEnableCourseFields()"
                                            :class="{ 'opacity-50 cursor-not-allowed': !canEnableCourseFields() }"
                                        >
                                            <option value="">---</option>
                                            <option value="1">1°</option>
                                            <option value="2">2°</option>
                                            <option value="3">3°</option>
                                            <option value="4">4°</option>
                                            <option value="5">5°</option>
                                            <option value="6">6°</option>
                                        </select>
            
                                    </div>
                                </div>
                                
                                <div class="field-container-small">
                                    <div class="field-wrapper">
                                        <div class="field-label">
                                            Grado
                                        </div>
                                        <select
                                            v-model="formData.grade"
                                            class="admin-select-small"
                                            :disabled="!canEnableCourseFields()"
                                            :class="{ 'opacity-50 cursor-not-allowed': !canEnableCourseFields() }"
                                        >
                                            <option value="">---</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                            <option value="E">E</option>
                                        </select>
            
                                    </div>
                                </div>
                            </div>

                            

                            <!-- Carga de alumnos (condicional) -->
                            <div v-if="shouldShowExcelInput()" class="students-upload-section">
                                <div class="students-section-title">
                                    Carga de alumnos
                                </div>
                                <div class="students-upload-field">
                                    <div class="field-wrapper">
                                        <div class="field-label">
                                            Adjunta la lista de alumnos
                                        </div>
                                        <label
                                            class="file-upload-area"
                                            :class="{ 'opacity-50 cursor-not-allowed': !canEnableExcel() }"
                                            for="students-file"
                                        >
                                            <div class="upload-text">
                                                {{ selectedStudentsFile ? selectedStudentsFile.name : 'Adjunta el archivo excel aquí' }}
                                            </div>
                                        </label>
                                        <input
                                            type="file"
                                            id="students-file"
                                            accept=".xlsx,.xls,.csv"
                                            style="display: none"
                                            @change="handleStudentsUpload"
                                            :disabled="!canEnableExcel()"
                                        />
                                        <span v-if="!canEnableExcel()" class="text-red-500 text-xs mt-1">
                                            Completa primero: Institución y Nivel de educación
                                        </span>
                                        <span v-if="errors.students_file" class="text-red-500 text-sm mt-1">
                                            {{ errors.students_file }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Botón Editar Grupo (solo en edición y con curso asociado) -->
                            <div v-if="shouldShowEditGroupButton()" class="edit-group-button-container">
                                <button 
                                    type="button"
                                    @click="handleEditGroup"
                                    class="edit-group-button"
                                >
                                    <span>Editar Grupo</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="19" height="18" viewBox="0 0 19 18" fill="none">
                                        <path d="M17.3132 0.913875C16.7274 0.328695 15.9332 0 15.1051 0C14.2771 0 13.4829 0.328695 12.8971 0.913875L2.46591 11.345C2.11117 11.6994 1.86189 12.1454 1.74589 12.6332L0.790226 16.6496C0.764351 16.7584 0.766777 16.872 0.797273 16.9796C0.82777 17.0872 0.88532 17.1852 0.964435 17.2642C1.04355 17.3432 1.14159 17.4007 1.24921 17.4311C1.35683 17.4614 1.47044 17.4637 1.5792 17.4377L5.59474 16.4812C6.08287 16.3653 6.52918 16.1161 6.8838 15.7612L7.88834 14.7566C7.76183 14.1854 7.72444 13.5981 7.7775 13.0155L5.9578 14.8361C5.77453 15.0193 5.54412 15.1485 5.29189 15.2087L2.31056 15.9191L3.02011 12.9378C3.08033 12.6847 3.2095 12.4543 3.39278 12.271L12.1186 3.54261L14.6845 6.10851L12.9163 7.87671C13.4986 7.82513 14.0854 7.8625 14.6565 7.98755L17.3132 5.33089C17.8984 4.74504 18.2271 3.95086 18.2271 3.12282C18.2271 2.29477 17.8984 1.49972 17.3132 0.913875ZM13.8222 1.83987C13.9907 1.67139 14.1907 1.53774 14.4108 1.44656C14.6309 1.35538 14.8669 1.30845 15.1051 1.30845C15.3434 1.30845 15.5793 1.35538 15.7995 1.44656C16.0196 1.53774 16.2196 1.67139 16.3881 1.83987C16.5566 2.00835 16.6902 2.20836 16.7814 2.42849C16.8726 2.64862 16.9195 2.88455 16.9195 3.12282C16.9195 3.36108 16.8726 3.59701 16.7814 3.81714C16.6902 4.03727 16.5566 4.23729 16.3881 4.40577L15.6096 5.18252L13.0437 2.61749L13.8222 1.83987ZM10.6148 10.4522C10.6799 10.6779 10.6989 10.9143 10.6704 11.1474C10.642 11.3806 10.5669 11.6056 10.4494 11.809C10.332 12.0123 10.1748 12.19 9.98707 12.3311C9.79939 12.4723 9.58515 12.5742 9.35718 12.6306L8.84749 12.7572C8.76537 13.2798 8.76714 13.8122 8.85273 14.3342L9.32402 14.4477C9.55406 14.5031 9.77043 14.6047 9.95999 14.7463C10.1495 14.8879 10.3083 15.0666 10.4267 15.2715C10.5451 15.4764 10.6205 15.7032 10.6485 15.9382C10.6765 16.1731 10.6564 16.4113 10.5895 16.6383L10.4263 17.189C10.8103 17.5259 11.2467 17.7999 11.7223 17.9937L12.1526 17.5407C12.3157 17.3692 12.5119 17.2326 12.7294 17.1393C12.9468 17.046 13.181 16.9979 13.4177 16.9979C13.6543 16.9979 13.8885 17.046 14.106 17.1393C14.3234 17.2326 14.5197 17.3692 14.6827 17.5407L15.1182 17.9998C15.5903 17.8073 16.0274 17.538 16.4117 17.203L16.2388 16.6043C16.1737 16.3786 16.1549 16.142 16.1833 15.9089C16.2118 15.6757 16.287 15.4507 16.4046 15.2473C16.5221 15.0438 16.6794 14.8663 16.8672 14.7251C17.055 14.584 17.2693 14.4822 17.4974 14.4259L18.0062 14.2993C18.0883 13.7767 18.0865 13.2443 18.0009 12.7222L17.5297 12.6088C17.2997 12.5533 17.0834 12.4516 16.894 12.3099C16.7045 12.1683 16.5458 11.9896 16.4275 11.7847C16.3093 11.5798 16.2339 11.353 16.2059 11.1181C16.178 10.8832 16.1981 10.6451 16.265 10.4182L16.4274 9.86746C16.0431 9.52944 15.6055 9.25752 15.1322 9.06278L14.7019 9.51487C14.5389 9.68652 14.3426 9.8232 14.125 9.9166C13.9075 10.01 13.6732 10.0582 13.4364 10.0582C13.1997 10.0582 12.9654 10.01 12.7478 9.9166C12.5303 9.8232 12.334 9.68652 12.1709 9.51487L11.7363 9.05667C11.2615 9.24868 10.8252 9.51923 10.442 9.8535L10.6148 10.4522ZM13.4277 14.8361C12.7295 14.8361 12.1622 14.2504 12.1622 13.5269C12.1622 12.8043 12.7295 12.2178 13.4277 12.2178C14.1259 12.2178 14.6932 12.8043 14.6932 13.5269C14.6932 14.2504 14.1259 14.8361 13.4277 14.8361Z" fill="#C7C7C7"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </transition>
                </div>

                <!-- Separador -->
                <AccordionSeparator />

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
                            <g clip-path="url(#clip0_833_13975_5)">
                                <path
                                    d="M19.0873 3.27314L20.2008 4.38775L14.1319 10.4588C14.0347 10.5566 13.919 10.6343 13.7917 10.6873C13.6643 10.7403 13.5277 10.7676 13.3897 10.7676C13.2518 10.7676 13.1152 10.7403 12.9878 10.6873C12.8604 10.6343 12.7448 10.5566 12.6475 10.4588L6.57544 4.38775L7.689 3.27419L13.3881 8.97227L19.0873 3.27314Z"
                                    fill="#007E93"
                                />
                            </g>
                            <defs>
                                <clipPath id="clip0_833_13975_5">
                                    <rect
                                        width="12.6173"
                                        height="25.2128"
                                        fill="white"
                                        transform="translate(26 0.691406) rotate(90)"
                                    />
                                </clipPath>
                            </defs>
                        </svg>
                    </div>
                    <transition name="accordion-slide">
                        <div
                            v-if="paymentOpen"
                            class="accordion-content"
                        >
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
                                                <input type="checkbox" :value="opt.code" @change="toggleFullOption(opt.code, $event)" :checked="formData.full_payment_options?.includes(opt.code)" />
                                                <span>{{ opt.label }}</span>
                                            </label>
                                        </div>
                                        <span v-if="errors.full_payment_options" class="text-red-500 text-sm mt-1">
                                            {{ errors.full_payment_options }}
                                        </span>
                                        <div v-if="fullPaymentChoices.length < fullPaymentChoicesBase.length && props.departureDate" class="text-blue-600 text-sm mt-1">
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
                                                    <input type="checkbox" :value="opt.code" @change="toggleSubscriptionOption(opt.code, $event)" :checked="formData.subscription_payment_options?.includes(opt.code)" />
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
                                        <div class="field-label">Número máximo de meses de suscripción</div>
                                        <div class="field-input-container">
                                            <select 
                                                v-model="formData.max_installments"
                                                class="field-input"
                                                :class="{ 
                                                    'border-red-500': errors.max_installments || !installmentsValidation.isValid,
                                                    'border-yellow-500': installmentsValidation.isValid && installmentsValidation.message
                                                }"

                                            >
                                                <option value="">Selecciona el número de cuotas</option>
                                                <option v-for="choice in maxInstallmentChoices" :key="choice.value" :value="choice.value">
                                                    {{ choice.label }}
                                                </option>
                                            </select>
                                        </div>
                                        <div v-if="errors.max_installments" class="error-message">
                                            {{ errors.max_installments }}
                                        </div>
                                        <div v-if="!installmentsValidation.isValid" class="text-red-500 text-sm mt-1">
                                            {{ installmentsValidation.message }}
                                        </div>
                                        <div v-if="maxInstallmentChoices.length < 12 && formData.max_installments" class="text-blue-600 text-sm mt-1">
                                            ℹ️ Máximo {{ maxInstallmentChoices.length }} cuotas disponibles hasta la fecha de pago final
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, defineEmits, onMounted, nextTick, computed } from "vue";
import { AccordionSeparator } from "@/Components/Icons";
import SearchableSelect from "@/Components/Ecommerce/SearchableSelect.vue";

// Props
const props = defineProps({
            modelValue: {
        type: Object,
        default: () => ({
            total_price: "",
            final_payment_date: "",
            sales_person: "",
            institution_name: "",
            institution_id: "",
            education_level: "",
            grade: "",
            course_number: "",
            students_file: null,
            group_benefit: "",
            // Opciones de pago múltiples
            payment_options: [], // Radio: seleccionar secciones activas
            full_payment_options: [], // Checkboxes: opciones específicas (mode=full)
            subscription_payment_options: [], // Checkboxes: opciones específicas (mode=subscription)
            discount_type: "", // Por defecto vacío para mostrar "Selecciona el beneficio grupal"
            discount_amount: "",
            sales_executive_id: "",
            max_installments: "", // Número máximo de cuotas para pago mensual
        }),
    },
    mode: {
        type: String,
        default: 'create',
        validator: (value) => ['create', 'edit'].includes(value)
    },
    errors: {
        type: Object,
        default: () => ({})
    },
    paymentStatus: {
        type: Object,
        default: () => ({
            paymentPercentage: 0,
            paidAmount: 0,
            totalAmount: 0,
            remainingAmount: 0
        })
    },
    institutions: {
        type: Array,
        default: () => []
    },
    salesExecutives: {
        type: Array,
        default: () => []
    },
    hasParticipants: {
        type: Boolean,
        default: false
    },
    hasExistingCourse: {
        type: Boolean,
        default: false
    },
    departureDate: {
        type: String,
        default: ''
    }
});

// Emits
const emit = defineEmits(['update:modelValue', 'create-institution', 'edit-group']);

// Reactive data
const formData = ref({ ...props.modelValue });
const isSyncingFromProps = ref(false);

// Estados para los acordeones
const priceOpen = ref(false);
const travelersOpen = ref(true);
const paymentOpen = ref(false);

// Estado para archivo de estudiantes
const selectedStudentsFile = ref(null);

// Propiedad computada para el precio formateado
const formattedPrice = ref('');
// Catálogos locales (idealmente venir desde backend)
const fullPaymentChoicesBase = [
    { code: 'full_transfer_khipu', label: 'Transferencia (Khipu)', installments: null },
    { code: 'full_debit_credit_0', label: 'Débito y crédito sin cuotas (Webpay)', installments: 0 },
    { code: 'full_debit_credit_3', label: 'Débito y crédito hasta 3 cuotas sin interés (Webpay)', installments: 3 },
    { code: 'full_debit_credit_6', label: 'Débito y crédito hasta 6 cuotas sin interés (Webpay)', installments: 6 },
    { code: 'full_debit_credit_9', label: 'Débito y crédito hasta 9 cuotas sin interés (Webpay)', installments: 9 },
    { code: 'full_debit_credit_12', label: 'Débito y crédito hasta 12 cuotas sin interés (Webpay)', installments: 12 },
    { code: 'full_international', label: 'Pago Internacional (Webpay)', installments: null },
];

// Opciones de pago filtradas según la fecha de salida (departure_date)
// Pago total (cuotas tarjeta sin interés) se valida contra departure_date
const fullPaymentChoices = computed(() => {
    if (!props.departureDate) {
        return fullPaymentChoicesBase; // Si no hay fecha, mostrar todas
    }

    const now = new Date();
    const end = new Date(props.departureDate + 'T00:00:00');

    // Calcular meses completos entre hoy y la fecha de salida
    let months = (end.getFullYear() - now.getFullYear()) * 12 + (end.getMonth() - now.getMonth());
    if (now.getDate() > end.getDate()) months -= 1; // Mes incompleto
    const availableMonths = Math.max(0, months);

    return fullPaymentChoicesBase.filter(option => {
        // Si no tiene cuotas (null) o es 0, siempre está disponible
        if (option.installments === null || option.installments === 0) {
            return true;
        }
        // Si tiene cuotas, verificar que no exceda los meses disponibles
        return option.installments <= availableMonths;
    });
});
const subscriptionChoices = [
    { code: 'subscription_virtualpos', label: 'Suscripción mensual (VirtualPos)' },
];

// Estado para el monto de descuento formateado
const formattedDiscountAmount = ref('');
// Cuotas permitidas según la fecha final de pago
const allowedInstallments = computed(() => {
    const options = [3, 6, 12];
    const selectedDate = formData.value.final_payment_date;
    if (!selectedDate) return options; // si no hay fecha, mostrar todas (validación en backend)

    const now = new Date();
    const end = new Date(selectedDate + 'T00:00:00');
    // calcular meses completos entre hoy y la fecha final
    let months = (end.getFullYear() - now.getFullYear()) * 12 + (end.getMonth() - now.getMonth());
    // si el día del mes de hoy es mayor al de la fecha final, resta un mes (mes incompleto)
    if (now.getDate() > end.getDate()) months -= 1;
    if (months < 0) months = 0;

    return options.filter(m => m <= months);
});

// Opciones de cuotas para el select (1-12 limitado por fecha final)
const maxInstallmentChoices = computed(() => {
    const choices = [];
    // Determinar máximo por fecha final
    let maxByDate = 12;
    if (formData.value.final_payment_date) {
        const now = new Date();
        const end = new Date(formData.value.final_payment_date + 'T00:00:00');
        let months = (end.getFullYear() - now.getFullYear()) * 12 + (end.getMonth() - now.getMonth());
        if (now.getDate() > end.getDate()) months -= 1;
        maxByDate = Math.max(0, months);
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

// Formatear ejecutivos para SearchableSelect
const salesExecutivesFormatted = computed(() => {
    return props.salesExecutives.map(exec => ({
        id: exec.id,
        name: `${exec.name} (${exec.code})`,
        searchText: `${exec.name} ${exec.code}` // Permite buscar por nombre o código
    }));
});

// Formatear instituciones para SearchableSelect
const institutionsFormatted = computed(() => {
    return props.institutions.map(inst => ({
        id: inst.id,
        name: inst.name,
        searchText: inst.name
    }));
});

// Opciones de nivel educativo para SearchableSelect
const educationLevelsFormatted = computed(() => {
    return [
        { id: 'preescolar', name: 'Preescolar', searchText: 'Preescolar' },
        { id: 'basica', name: 'Básica', searchText: 'Básica' },
        { id: 'media', name: 'Media', searchText: 'Media' }
    ];
});

// Función para formatear precio como moneda
const formatCurrency = (value) => {
    if (!value) return '';
    
    // Convertir a string y manejar decimales
    let stringValue = value.toString();
    
    // Si tiene decimales, tomar solo la parte entera
    if (stringValue.includes('.')) {
        stringValue = stringValue.split('.')[0];
    }
    
    // Remover todos los caracteres no numéricos
    const numericValue = stringValue.replace(/\D/g, '');
    
    if (numericValue === '') return '';
    
    // Formatear solo con separador de miles, sin símbolo de moneda
    return new Intl.NumberFormat('es-CL', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(parseInt(numericValue));
};

// Función para limpiar formato de moneda y obtener solo números
const cleanCurrency = (value) => {
    if (!value) return '';
    return value.toString().replace(/\D/g, '');
};

// Función para manejar input del precio
const handlePriceInput = (event) => {
    const input = event.target;
    const cursorPosition = input.selectionStart;
    const oldValue = formattedPrice.value;
    const newValue = event.target.value;
    
    // Limpiar el valor y formatear
    const cleanedValue = cleanCurrency(newValue);
    const formattedValue = formatCurrency(cleanedValue);
    
    // Actualizar el valor formateado
    formattedPrice.value = formattedValue;
    
    // Actualizar el valor real en formData
    formData.value.total_price = cleanedValue;
    
    // Restaurar la posición del cursor
    nextTick(() => {
        const newCursorPosition = Math.min(cursorPosition, formattedValue.length);
        input.setSelectionRange(newCursorPosition, newCursorPosition);
    });
};

// Función para manejar input del monto de descuento
const handleDiscountAmountInput = (event) => {
    const input = event.target;
    const cursorPosition = input.selectionStart;
    const newValue = event.target.value;
    
    // Limpiar el valor y formatear
    const cleanedValue = cleanCurrency(newValue);
    const formattedValue = formatCurrency(cleanedValue);
    
    // Actualizar el valor formateado
    formattedDiscountAmount.value = formattedValue;
    
    // Actualizar el valor real en formData
    formData.value.discount_amount = cleanedValue;
    
    // Restaurar la posición del cursor
    nextTick(() => {
        const newCursorPosition = Math.min(cursorPosition, formattedValue.length);
        input.setSelectionRange(newCursorPosition, newCursorPosition);
    });
};

// Función para manejar blur del precio
const handlePriceBlur = () => {
    if (formData.value.total_price) {
        // Convertir a string y manejar decimales
        let stringValue = formData.value.total_price.toString();
        
        // Si tiene decimales, tomar solo la parte entera
        if (stringValue.includes('.')) {
            stringValue = stringValue.split('.')[0];
        }
        
        // Remover caracteres no numéricos y formatear
        const numericValue = stringValue.replace(/\D/g, '');
        formattedPrice.value = formatCurrency(numericValue);
        
        // Actualizar el valor en formData sin decimales
        formData.value.total_price = numericValue;
    }
};

// Función para manejar blur del monto de descuento
const handleDiscountAmountBlur = () => {
    if (formData.value.discount_amount) {
        // Convertir a string y manejar decimales
        let stringValue = formData.value.discount_amount.toString();
        
        // Si tiene decimales, tomar solo la parte entera
        if (stringValue.includes('.')) {
            stringValue = stringValue.split('.')[0];
        }
        
        // Remover caracteres no numéricos y formatear
        const numericValue = stringValue.replace(/\D/g, '');
        formattedDiscountAmount.value = formatCurrency(numericValue);
        
        // Actualizar el valor en formData sin decimales
        formData.value.discount_amount = numericValue;
    }
};

// Inicializar el precio formateado
const initializeFormattedPrice = () => {
    if (formData.value.total_price) {
        // Convertir a string y manejar decimales
        let stringValue = formData.value.total_price.toString();
        
        // Si tiene decimales, tomar solo la parte entera
        if (stringValue.includes('.')) {
            stringValue = stringValue.split('.')[0];
        }
        
        // Remover caracteres no numéricos y formatear
        const numericValue = stringValue.replace(/\D/g, '');
        formattedPrice.value = formatCurrency(numericValue);
        
        // Actualizar el valor en formData sin decimales
        formData.value.total_price = numericValue;
    }
};

// Inicializar el monto de descuento formateado
const initializeFormattedDiscountAmount = () => {
    if (formData.value.discount_amount) {
        // Convertir a string y manejar decimales
        let stringValue = formData.value.discount_amount.toString();
        
        // Si tiene decimales, tomar solo la parte entera
        if (stringValue.includes('.')) {
            stringValue = stringValue.split('.')[0];
        }
        
        // Remover caracteres no numéricos y formatear
        const numericValue = stringValue.replace(/\D/g, '');
        formattedDiscountAmount.value = formatCurrency(numericValue);
        
        // Actualizar el valor en formData sin decimales
        formData.value.discount_amount = numericValue;
    }
};

// Inicializar cuando el componente se monta
onMounted(() => {
    // En modo edit, sincronizar los valores del modelValue
    if (props.mode === 'edit') {
        Object.keys(props.modelValue).forEach((key) => {
            if (props.modelValue[key] !== undefined) {
                formData.value[key] = props.modelValue[key];
            }
        });
    }
    
    // Inicializar payment_options si no existe
    if (!formData.value.payment_options) {
        formData.value.payment_options = [];
    }
    
    // Asegurar que el select inicie sin selección (placeholder)
    formData.value.sales_executive_id = formData.value.sales_executive_id ?? '';

    initializeFormattedPrice();
    initializeFormattedDiscountAmount();
    
    // Actualizar opciones de suscripción automáticamente si hay fecha final de pago
    if (formData.value.final_payment_date) {
        updateSubscriptionOptionsAutomatically();
    }
    
    // Emitir el estado inicial
    emit('update:modelValue', formData.value);

    // Asegurar arrays para checkboxes (evitar comportamiento booleano compartido)
    if (!Array.isArray(formData.value.full_payment_options)) {
        formData.value.full_payment_options = [];
    }
    if (!Array.isArray(formData.value.subscription_payment_options)) {
        formData.value.subscription_payment_options = [];
    }
});

// Handlers para checkboxes manuales (evitar efectos de referencia)
const toggleFullOption = (code, event) => {
    if (!Array.isArray(formData.value.full_payment_options)) formData.value.full_payment_options = [];
    const set = new Set(formData.value.full_payment_options);
    if (event.target.checked) set.add(code); else set.delete(code);
    formData.value.full_payment_options = Array.from(set);
    emit('update:modelValue', formData.value);
};
const toggleSubscriptionOption = (code, event) => {
    if (!Array.isArray(formData.value.subscription_payment_options)) formData.value.subscription_payment_options = [];
    const set = new Set(formData.value.subscription_payment_options);
    if (event.target.checked) set.add(code); else set.delete(code);
    formData.value.subscription_payment_options = Array.from(set);
    emit('update:modelValue', formData.value);
};

// Watch para sincronizar con el padre
watch(
    formData,
    (newValue) => {
        if (isSyncingFromProps.value) return;
        emit('update:modelValue', newValue);
    },
    { deep: true, immediate: true }
);

// Watch para sincronizar cambios del modelValue (especialmente en modo edit)
watch(
    () => props.modelValue,
    (newValue) => {
        if (props.mode === 'edit') {
            isSyncingFromProps.value = true;
            
            // Sincronizar valores del modelValue con formData
            Object.keys(newValue).forEach((key) => {
                if (newValue[key] !== undefined) {
                    formData.value[key] = newValue[key];
                }
            });
            
            // Registrar advertencia si no se puede cargar el grado en modo edición
            if (props.mode === 'edit' && !newValue.grade) {
                console.warn('⚠️ No se pudo cargar el grado del participante');
            }
            
            // Reinicializar los valores formateados después de sincronizar
            initializeFormattedPrice();
            initializeFormattedDiscountAmount();
            nextTick(() => { isSyncingFromProps.value = false; });
        }
    },
    { deep: true, immediate: true }
);

// Watcher para limpiar campos cuando se deselecciona la institución
watch(() => formData.value.institution_id, (newValue, oldValue) => {
    // En modo edición con curso existente, no limpiar campos automáticamente
    if (props.mode === 'edit' && props.hasExistingCourse) {
        return;
    }
    
    if (!newValue && oldValue) {
        // Si se deselecciona la institución, limpiar todos los campos dependientes
        formData.value.education_level = '';
        formData.value.grade = '';
        formData.value.course_number = '';
        formData.value.group_benefit = '';
        formData.value.students_file = null;
        selectedStudentsFile.value = null;
    }
});

// Watcher para sincronizar institution_name con institution_id
watch(() => formData.value.institution_id, (newValue) => {
    if (newValue) {
        const selectedInstitution = props.institutions.find(inst => inst.id == newValue);
        if (selectedInstitution) {
            formData.value.institution_name = selectedInstitution.name;
        }
    } else {
        formData.value.institution_name = '';
    }
});

// Watcher para limpiar monto de descuento cuando cambia el tipo
watch(() => formData.value.discount_type, (newValue, oldValue) => {
    if (newValue !== 'monto_fijo' && oldValue === 'monto_fijo') {
        // Si cambia de monto_fijo a otro tipo, limpiar el monto
        formData.value.discount_amount = '';
        formattedDiscountAmount.value = '';
    }
    
    // Emitir el cambio inmediatamente
    emit('update:modelValue', formData.value);
}, { immediate: true });

// Watcher para el monto de descuento
watch(() => formData.value.discount_amount, (newValue) => {
    emit('update:modelValue', formData.value);
}, { immediate: true });

// Watchers para los métodos de pago
watch(() => formData.value.full_payment_method, (newValue) => {
    emit('update:modelValue', formData.value);
}, { immediate: true });

watch(() => formData.value.installments_payment_method, (newValue) => {
    emit('update:modelValue', formData.value);
}, { immediate: true });

// Watcher para actualizar opciones de pago de suscripción cuando cambie la fecha final de pago
// (Las suscripciones/mensualidades se validan contra final_payment_date)
watch(() => formData.value.final_payment_date, (newValue, oldValue) => {
    if (newValue && newValue !== oldValue) {
        // Actualizar max installments según la nueva fecha
        nextTick(() => {
            updateSubscriptionOptionsAutomatically();
        });
    }
});

// Watcher para actualizar opciones de pago total cuando cambie la fecha de salida
// (Cuotas de tarjeta sin interés se validan contra departure_date)
watch(() => props.departureDate, (newValue, oldValue) => {
    if (newValue && newValue !== oldValue) {
        try {
            const now = new Date();
            const departureDate = new Date(newValue + 'T00:00:00');

            // Calcular meses disponibles hasta la fecha de salida
            let months = (departureDate.getFullYear() - now.getFullYear()) * 12 + (departureDate.getMonth() - now.getMonth());
            if (now.getDate() > departureDate.getDate()) months -= 1;
            const availableMonths = Math.max(0, months);

            // Filtrar opciones de pago total según meses disponibles
            const validFullOptions = fullPaymentChoicesBase.filter(option => {
                if (option.installments === null || option.installments === 0) {
                    return true;
                }
                return option.installments <= availableMonths;
            });

            // Filtrar opciones seleccionadas que ya no son válidas
            if (formData.value.full_payment_options) {
                formData.value.full_payment_options = formData.value.full_payment_options.filter(option =>
                    validFullOptions.some(valid => valid.code === option)
                );
                emit('update:modelValue', formData.value);
            }
        } catch (error) {
            console.error('Error al actualizar opciones de pago total:', error);
        }
    }
});

// Función para determinar si mostrar el input del Excel
const shouldShowExcelInput = () => {
    if (props.mode === 'create') {
        return true; // Siempre mostrar en modo create
    }
    
    if (props.mode === 'edit') {
        // No mostrar si ya hay participantes o si hay un curso existente
        return !props.hasParticipants && !props.hasExistingCourse;
    }
    
    return false;
};

// Función para verificar si se pueden habilitar los campos de curso
const canEnableCourseFields = () => {
    // Si ya existe un curso, no permitir modificar estos campos
    if (props.hasExistingCourse) {
        return false;
    }
    // Solo habilitar si hay una institución seleccionada
    return !!formData.value.institution_id;
};

// Función para verificar si se puede habilitar el Excel
const canEnableExcel = () => {
    return formData.value.institution_id && 
           formData.value.education_level;
};

// Función para determinar si mostrar el botón Editar Grupo
const shouldShowEditGroupButton = () => {
    if (props.mode === 'create') {
        return false; // Nunca mostrar en creación
    }
    
    if (props.mode === 'edit') {
        // Mostrar si hay participantes o si hay un curso asociado
        return props.hasParticipants || props.hasExistingCourse;
    }
    
    return false;
};

// Función para manejar la carga de archivos de estudiantes
const handleStudentsUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        const validTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'text/csv'
        ];
        
        if (validTypes.includes(file.type)) {
            formData.value.students_file = file;
            selectedStudentsFile.value = file;
        } else {
            alert("Por favor seleccione un archivo Excel (.xlsx, .xls) o CSV válido.");
            event.target.value = "";
        }
    }
};

// Función para verificar si una opción de pago está seleccionada
const isPaymentOptionSelected = (option) => {
    return formData.value.payment_options && formData.value.payment_options.includes(option);
};

// Función para manejar la selección de opciones de pago
const handlePaymentOptionChange = (option, event) => {
    // Inicializar el array si no existe
    if (!formData.value.payment_options) {
        formData.value.payment_options = [];
    }
    
    if (event.target.checked) {
        // Agregar la opción si no está ya seleccionada
        if (!formData.value.payment_options.includes(option)) {
            formData.value.payment_options.push(option);
        }
    } else {
        // Remover la opción si está seleccionada
        const index = formData.value.payment_options.indexOf(option);
        if (index > -1) {
            formData.value.payment_options.splice(index, 1);
        }
        
        // Limpiar los campos relacionados cuando se deselecciona
        if (option === 'full_payment') {
            formData.value.full_payment_method = "";
        } else if (option === 'subscription') {
            formData.value.subscription_payment_method = "";
            formData.value.max_installments = "";
        }
    }
    // Emitir inmediatamente para asegurar que los datos se envíen
    emit('update:modelValue', formData.value);
};

// Validación de campos requeridos para suscripciones
const installmentsValidation = computed(() => {
    if (!formData.value.payment_options?.includes('subscription')) {
        return { isValid: true, message: '' };
    }

    if (!formData.value.max_installments) {
        return {
            isValid: false,
            message: 'Debe seleccionar el número máximo de meses de suscripción'
        };
    }

    if (!formData.value.subscription_payment_options?.length) {
        return {
            isValid: false,
            message: 'Debe seleccionar al menos una opción de suscripción'
        };
    }

    return { isValid: true, message: '' };
});

// Función para crear una nueva institución
const createNewInstitution = () => {
    emit('create-institution');
};

// Función para manejar el botón Editar Grupo
const handleEditGroup = () => {
    emit('edit-group');
};

// Función para formatear precios
const formatPrice = (price) => {
    return new Intl.NumberFormat("es-CL", {
        style: "currency",
        currency: "CLP",
    })
        .format(price)
        .replace("CLP", "")
        .trim();
};

// Función para ver estados de pagos
const viewPaymentStates = () => {
    // Obtener el ID del programa del padre (que ya tiene acceso al curso)
    emit('navigate-to-course');
};

// Función para actualizar opciones de suscripción automáticamente (basado en final_payment_date)
const updateSubscriptionOptionsAutomatically = () => {
    try {
        if (!formData.value.final_payment_date) return;

        const now = new Date();
        const finalPaymentDate = new Date(formData.value.final_payment_date + 'T00:00:00');

        // Calcular meses disponibles hasta la fecha final de pago
        let months = (finalPaymentDate.getFullYear() - now.getFullYear()) * 12 + (finalPaymentDate.getMonth() - now.getMonth());
        if (now.getDate() > finalPaymentDate.getDate()) months -= 1;
        const availableMonths = Math.max(0, months);

        // Si el max_installments seleccionado excede los meses disponibles, ajustarlo
        if (formData.value.max_installments && parseInt(formData.value.max_installments) > availableMonths) {
            formData.value.max_installments = availableMonths > 0 ? availableMonths.toString() : '1';
            emit('update:modelValue', formData.value);
        }
    } catch (error) {
        console.error('Error actualizando opciones de suscripción:', error);
    }
};

// Funciones para manejar cambios en SearchableSelects
const handleExecutiveChange = (executiveId) => {
    formData.value.sales_executive_id = executiveId;
};

const handleInstitutionChange = (institutionId) => {
    formData.value.institution_id = institutionId;
};

const handleEducationLevelChange = (level) => {
    formData.value.education_level = level;
};
</script>

<style scoped>
.detalle-administrativo-card,
.detalle-administrativo-card * {
    box-sizing: border-box;
}

.detalle-administrativo-card {
    background: var(--colores-neutro-blanco, #ffffff);
    border-radius: 20px;
    padding: 30px;
    display: flex;
    flex-direction: row;
    gap: 10px;
    align-items: center;
    justify-content: flex-start;
    position: relative;
    box-shadow: var(
        --sombra-box-shadow,
        0px 4px 11.6px 0px rgba(163, 163, 163, 0.11)
    );
    overflow: hidden;
}

/* Administrative Form Container */
.administrative-form-container {
    display: flex;
    flex-direction: column;
    gap: 28px;
    align-items: flex-start;
    justify-content: flex-start;
    align-self: stretch;
    flex-shrink: 0;
    width: 100%;
    position: relative;
}

.administrative-form-header {
    display: flex;
    flex-direction: column;
    gap: 19px;
    align-items: flex-start;
    justify-content: flex-start;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
}

.administrative-title {
    color: var(--colores-op2-turquesa, #007e93);
    text-align: left;
    font-family: var(--subtitle-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--subtitle-font-size, 24px);
    line-height: var(--subtitle-line-height, 28px);
    font-weight: var(--subtitle-font-weight, 700);
    position: relative;
    display: flex;
    align-items: flex-end;
    justify-content: flex-start;
}

/* Accordion Styles */
.accordion-section {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: flex-start;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
    margin-bottom: 30px;
    z-index: 1;
}

.accordion-header {
    padding: 12px 0px 12px 0px;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
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
    font-size: var(--Numeros-Cuerpo-de-texto-XL, 18px);
    font-style: normal;
    font-weight: 700;
    line-height: 22px; /* 122.222% */
    position: relative;
    display: flex;
    align-items: center;
    justify-content: flex-start;
}

.accordion-content {
    display: flex;
    flex-direction: column;
    gap: 23px;
    align-items: center;
    justify-content: center;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
    padding: 20px 0;
}

/* Transiciones de acordeón */
.accordion-slide-enter-active,
.accordion-slide-leave-active {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    transform-origin: top;
    overflow: visible;
}

.accordion-slide-enter-from {
    opacity: 0;
    max-height: 0;
    transform: translateY(-20px) scaleY(0.8);
    padding-top: 0;
    padding-bottom: 0;
}

.accordion-slide-enter-to {
    opacity: 1;
    max-height: 5000px;
    transform: translateY(0) scaleY(1);
    padding-top: 20px;
    padding-bottom: 30px;
}

.accordion-slide-leave-from {
    opacity: 1;
    max-height: 5000px;
    transform: translateY(0) scaleY(1);
    padding-top: 20px;
    padding-bottom: 30px;
}

.accordion-slide-leave-to {
    opacity: 0;
    max-height: 0;
    transform: translateY(-20px) scaleY(0.8);
    padding-top: 0;
    padding-bottom: 0;
}

.accordion-arrow {
    flex-shrink: 0;
    width: 18px;
    height: 32px;
    position: relative;
    overflow: visible;
    transition: transform 0.3s ease;
    transform: rotate(0deg);
}

.accordion-arrow.rotated {
    transform: rotate(180deg);
}

/* Field Containers */
.price-fields-row,
.education-details-row {
    display: flex;
    gap: 12px;
    width: 100%;
}

.staff-field-row,
.institution-field-row {
    width: 100%;
}

.field-container {
    flex: 1;
}

.field-container-small {
    width: 120px;
}

.field-wrapper {
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: 100%;
}

.field-label {
    color: var(--colores-neutro-gris-4, #5b5b5b);
    text-align: left;
    font-family: var(--cuerpo-de-texto-s-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--cuerpo-de-texto-s-font-size, 12px);
    line-height: var(--cuerpo-de-texto-s-line-height, 13px);
    font-weight: var(--cuerpo-de-texto-s-font-weight, 700);
    position: relative;
}

/* Input Styles */
.admin-input-text,
.admin-select,
.admin-select-small {
    background: #ffffff;
    border-radius: 8px;
    border-style: solid;
    border-color: var(--colores-neutro-gris-4, #5b5b5b);
    border-width: 1px;
    padding: 8px 16px;
    height: 46px;
    color: var(--colores-op2-turquesa, #007e93);
    text-align: left;
    font-family: var(--cuerpo-de-texto-m-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--cuerpo-de-texto-m-font-size, 12px);
    line-height: var(--cuerpo-de-texto-m-line-height, 18px);
    font-weight: var(--cuerpo-de-texto-m-font-weight, 700);
    outline: none;
    width: 100%;
}

.admin-input-text:focus,
.admin-select:focus,
.admin-select-small:focus {
    border-color: var(--colores-op2-turquesa, #007e93);
    box-shadow: 0 0 0 2px rgba(0, 126, 147, 0.1);
}

.admin-input-text::placeholder {
    color: var(--colores-neutro-gris-3, #c7c7c7);
}

/* Students Upload Section */
.students-upload-section {
    display: flex;
    flex-direction: column;
    gap: 15px;
    width: 100%;
}

.students-section-title {
    color: var(--colores-neutro-negro, #434343);
    text-align: left;
    font-family: var(--cuerpo-de-texto-l-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--cuerpo-de-texto-l-font-size, 16px);
    line-height: var(--cuerpo-de-texto-l-line-height, 22px);
    font-weight: var(--cuerpo-de-texto-l-font-weight, 700);
    position: relative;
}

.students-upload-field,
.group-benefit-field {
    width: 100%;
}

.file-upload-area {
    background: #ffffff;
    border-radius: 8px;
    border-style: dashed;
    border-color: var(--colores-neutro-gris-4, #5b5b5b);
    border-width: 1px;
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 78px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: var(
        --neutral-shadow-02-box-shadow,
        0px 1px 4px 0px rgba(25, 33, 61, 0.08)
    );
}

.file-upload-area:hover {
    border-color: var(--colores-op2-turquesa, #007e93);
    background-color: rgba(0, 126, 147, 0.02);
}

.upload-text {
    color: var(--colores-neutro-gris-4, #5b5b5b);
    text-align: center;
    font-family: var(--cuerpo-de-texto-m-font-family, "Nexa-Regular", sans-serif);
    font-size: var(--cuerpo-de-texto-m-font-size, 12px);
    line-height: var(--cuerpo-de-texto-m-line-height, 18px);
    font-weight: var(--cuerpo-de-texto-m-font-weight, 400);
}

/* Payment Options */
.payment-description {
    color: var(--colores-neutro-gris-4, #5b5b5b);
    text-align: left;
    font-family: var(--cuerpo-de-texto-s-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--cuerpo-de-texto-s-font-size, 12px);
    line-height: var(--cuerpo-de-texto-s-line-height, 13px);
    font-weight: var(--cuerpo-de-texto-s-font-weight, 700);
    align-self: stretch;
    margin-bottom: 20px;
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

.payment-option-info {
    display: flex;
    align-items: center;
}

.info-icon {
    width: 18px;
    height: 18px;
}

.payment-method-select {
    margin-top: 15px;
}

.installments-options {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-top: 15px;
}

.installments-select {
    margin-top: 15px;
}

/* Payment Status Styles */
.payment-status-section {
    margin-bottom: 20px;
    width: 100%;
}

.payment-status-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 14px 16px;
    box-shadow: 0px 2px 8px rgba(0, 0, 0, 0.1);
    width: 100%;
}

.payment-status-content {
    display: flex;
    flex-direction: column;
    gap: 11px;
}

.payment-status-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.payment-percentage {
    color: #4B8D7F;
    font-family: 'Nexa', sans-serif;
    font-size: 18px;
    font-weight: bold;
    line-height: 22px;
}

.payment-amounts {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 2px;
}

.remaining-text {
    color: #4B8D7F;
    font-family: 'Nexa', sans-serif;
    font-size: 10px;
    font-weight: normal;
    line-height: 12px;
}

.amounts-row {
    display: flex;
    align-items: flex-end;
    gap: 6px;
}

.remaining-amount {
    color: #4B8D7F;
    font-family: 'Nexa', sans-serif;
    font-size: 20px;
    font-weight: bold;
    line-height: 24px;
}

.total-amount {
    color: #4B8D7F;
    font-family: 'Nexa', sans-serif;
    font-size: 12px;
    font-weight: bold;
    line-height: 13px;
}

.payment-progress-bar {
    border-radius: 100px;
    border: 3px solid #E5E5E5;
    background: white;
    height: 16px;
    position: relative;
    overflow: hidden;
}

.payment-progress-fill {
    background: #4B8D7F;
    border-radius: 100px;
    height: 16px;
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    overflow: hidden;
}

.progress-stripes {
    display: flex;
    align-items: center;
    height: auto;
    position: absolute;
    left: -3px;
    top: -2px;
    overflow: visible;
}

.payment-states-button {
    background: #F2A741;
    border-radius: 25px;
    border: none;
    padding: 12px 20px;
    color: white;
    font-family: 'Nexa', sans-serif;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 10px;
    width: 100%;
}

.payment-states-button:hover {
    background: #E09630;
    transform: translateY(-1px);
}

.edit-group-button-container {
    margin-top: 20px;
    width: 100%;
}

.edit-group-button {
    border-radius: 112.894px;
    border: 1px solid #D3D3D3;
    background: #F9F9F9;
    display: flex;
    padding: 14px 12px;
    justify-content: center;
    align-items: center;
    gap: 6px;
    align-self: stretch;
    width: 100%;
    cursor: pointer;
    transition: all 0.3s ease;
    font-family: 'Nexa', sans-serif;
    font-size: 14px;
    font-weight: normal;
    color: #333;
}

.edit-group-button:hover {
    background: #E9E9E9;
    border-color: #C0C0C0;
}

.edit-group-button svg {
    width: 17.455px;
    height: 18px;
}

/* Estilos para el select de cuotas */
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
    border-color: var(--Colores-OP2-Amarillo, #FFB232);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255, 178, 50, 0.15);
}

.field-input:focus {
    border-color: var(--Colores-OP2-Amarillo, #FFB232);
    box-shadow: 0 0 0 3px rgba(255, 178, 50, 0.1);
    transform: translateY(-1px);
}

.field-input:disabled {
    background-color: #f9f9f9;
    color: #9ca3af;
    cursor: not-allowed;
    border-color: #e5e7eb;
}

/* Estilos para el contenedor del select */
.field-input-container {
    position: relative;
    width: 100%;
}

/* Estilos para los checkboxes de opciones de pago (subopciones) */
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
    background-color: rgba(255, 178, 50, 0.05);
    border-color: rgba(255, 178, 50, 0.2);
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
    border-color: var(--Colores-OP2-Amarillo, #FFB232);
    transform: scale(1.05);
}

.payment-method-select .flex.flex-col.gap-2 label input[type="checkbox"]:checked {
    background-color: var(--Colores-OP2-Amarillo, #FFB232);
    border-color: var(--Colores-OP2-Amarillo, #FFB232);
    box-shadow: 0 2px 8px rgba(255, 178, 50, 0.3);
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

/* Estilos para mensajes de error */
.error-message {
    color: #dc2626;
    font-size: 14px;
    margin-top: 8px;
    font-weight: 500;
}

.text-red-500 {
    color: #dc2626;
}

.text-red-500.text-sm {
    font-size: 14px;
}

.text-red-500.text-xs {
    font-size: 12px;
}
</style>
