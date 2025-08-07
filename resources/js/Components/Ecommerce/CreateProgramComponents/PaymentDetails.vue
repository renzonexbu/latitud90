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
                                            :class="{ 'border-red-500': errors.final_payment_date }"
                                        />
                                        <span v-if="errors.final_payment_date" class="text-red-500 text-sm mt-1">
                                            {{ errors.final_payment_date }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="staff-field-row">
                                <div class="field-wrapper">
                                    <div class="field-label">
                                        Personal que vendió el viaje
                                    </div>
                                    <input
                                        type="text"
                                        v-model="formData.sales_person"
                                        placeholder="Nombre"
                                        class="admin-input-text"
                                        :class="{ 'border-red-500': errors.sales_person }"
                                    />
                                    <span v-if="errors.sales_person" class="text-red-500 text-sm mt-1">
                                        {{ errors.sales_person }}
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
                                    <select
                                        v-model="formData.institution_id"
                                        class="admin-input-text"
                                        :disabled="props.hasExistingCourse"
                                        :class="{ 'opacity-50 cursor-not-allowed': props.hasExistingCourse }"
                                    >
                                        <option value="">Seleccione una institución</option>
                                        <option v-for="institution in institutions" :key="institution.id" :value="institution.id">
                                            {{ institution.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="education-details-row">
                                <div class="field-container">
                                    <div class="field-wrapper">
                                        <div class="field-label">
                                            Nivel de educación
                                        </div>
                                        <select
                                            v-model="formData.education_level"
                                            class="admin-select"
                                            :disabled="!canEnableCourseFields()"
                                            :class="{ 'opacity-50 cursor-not-allowed': !canEnableCourseFields() }"
                                        >
                                            <option value="">Seleccione un nivel</option>
                                            <option value="inicial">Inicial</option>
                                            <option value="primario">Primario</option>
                                            <option value="secundario">Secundario</option>
                                            <option value="universitario">Universitario</option>
                                        </select>

                                    </div>
                                </div>
                                <div class="field-container-small">
                                    <div class="field-wrapper">
                                        <div class="field-label">
                                            Turno
                                        </div>
                                        <select
                                            v-model="formData.shift"
                                            class="admin-select-small"
                                            :disabled="!canEnableCourseFields()"
                                            :class="{ 'opacity-50 cursor-not-allowed': !canEnableCourseFields() }"
                                        >
                                            <option value="">---</option>
                                            <option value="mañana">Mañana</option>
                                            <option value="tarde">Tarde</option>
                                            <option value="noche">Noche</option>
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
                                            <option value="1">1°</option>
                                            <option value="2">2°</option>
                                            <option value="3">3°</option>
                                            <option value="4">4°</option>
                                            <option value="5">5°</option>
                                            <option value="6">6°</option>
                                        </select>
            
                                    </div>
                                </div>
                            </div>

                            <!-- Beneficio grupal -->
                            <div class="group-benefit-field">
                                <div class="field-wrapper">
                                    <div class="field-label">
                                        ¿El grupo tienen beneficio general?
                                    </div>
                                    <select
                                        v-model="formData.discount_type"
                                        class="admin-input-text"
                                        :disabled="!canEnableCourseFields()"
                                        :class="{ 'opacity-50 cursor-not-allowed': !canEnableCourseFields() }"
                                    >
                                        <option value="">Selecciona el beneficio grupal</option>
                                        <option value="porcentaje_10">Descuento 10%</option>
                                        <option value="porcentaje_15">Descuento 15%</option>
                                        <option value="porcentaje_20">Descuento 20%</option>
                                        <option value="monto_fijo">Monto fijo</option>
                                    </select>
       
                                    
                                    <!-- Input para monto fijo (solo visible cuando se selecciona monto_fijo) -->
                                    <div v-if="formData.discount_type === 'monto_fijo' && canEnableCourseFields()" class="discount-amount-field">
                                        <div class="field-wrapper">
                                            <div class="field-label">
                                                Monto a descontar
                                            </div>
                                            <input
                                                type="text"
                                                v-model="formattedDiscountAmount"
                                                @input="handleDiscountAmountInput"
                                                @blur="handleDiscountAmountBlur"
                                                placeholder="--------"
                                                class="admin-input-text"
                                                :class="{ 'border-red-500': errors.discount_amount }"
                                            />
                                            <span v-if="errors.discount_amount" class="text-red-500 text-sm mt-1">
                                                {{ errors.discount_amount }}
                                            </span>
                                        </div>
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
                                            Completa primero: Institución, Nivel de educación, Turno y Grado
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
                                        <div class="field-label">
                                            ¿Forma de pago?
                                        </div>
                                        <select
                                            v-model="formData.full_payment_method"
                                            class="admin-input-text"
                                        >
                                            <option value="">Seleccione la forma de pago</option>
                                            <option value="todos_medios">Todos los medios (Débito/Crédito/Transferencia)</option>
                                            <option value="solo_tarjeta">Solo pago con Tarjeta (Débito/Crédito)</option>
                                            <option value="solo_transferencia">Solo pago transferencia</option>
                                            <option value="solo_contado">Solo pago contado (Débito/Transferencia)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Opción Cuotas -->
                            <div class="payment-option">
                                <div class="payment-option-header">
                                    <div class="payment-checkbox">
                                        <input
                                            type="checkbox"
                                            id="installments-payment"
                                            value="installments"
                                            :checked="isPaymentOptionSelected('installments')"
                                            @change="handlePaymentOptionChange('installments', $event)"
                                            class="checkbox-input"
                                        />
                                        <label for="installments-payment" class="checkbox-label"></label>
                                    </div>
                                    <div class="payment-option-content">
                                        <div class="payment-option-title">
                                            Mensual | Cuota Lat 90
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
                                <div v-if="isPaymentOptionSelected('installments')" class="installments-options">
                                    <div class="payment-method-select">
                                        <div class="field-wrapper">
                                            <div class="field-label">
                                                ¿Forma de pago?
                                            </div>
                                            <select
                                                v-model="formData.installments_payment_method"
                                                class="admin-input-text"
                                            >
                                                <option value="">Seleccione la forma de pago</option>
                                                <option value="todos_medios">Todos los medios (Débito/Crédito/Transferencia)</option>
                                                <option value="solo_tarjeta">Solo pago con Tarjeta (Débito/Crédito)</option>
                                                <option value="solo_transferencia">Solo pago transferencia</option>
                                                <option value="solo_contado">Solo pago contado (Débito/Transferencia)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="installments-select">
                                        <div class="field-wrapper">
                                            <div class="field-label">
                                                Cuantas cuotas máximas
                                            </div>
                                            <select
                                                v-model="formData.max_installments"
                                                class="admin-select-small"
                                            >
                                                <option value="">---</option>
                                                <option value="3">3 cuotas</option>
                                                <option value="6">6 cuotas</option>
                                                <option value="9">9 cuotas</option>
                                                <option value="12">12 cuotas</option>
                                            </select>
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
import { ref, watch, defineEmits, onMounted, nextTick } from "vue";
import { AccordionSeparator } from "@/Components/Icons";

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
            shift: "",
            grade: "",
            students_file: null,
            group_benefit: "",
            // Opciones de pago múltiples
            payment_options: [], // Array para almacenar múltiples opciones
            full_payment_method: "",
            installments_payment_method: "",
            max_installments: "",
            discount_type: "", // Por defecto vacío para mostrar "Selecciona el beneficio grupal"
            discount_amount: "",
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
    hasParticipants: {
        type: Boolean,
        default: false
    },
    hasExistingCourse: {
        type: Boolean,
        default: false
    }
});

// Emits
const emit = defineEmits(['update:modelValue', 'create-institution', 'edit-group']);

// Reactive data
const formData = ref({ ...props.modelValue });

// Estados para los acordeones
const priceOpen = ref(false);
const travelersOpen = ref(true);
const paymentOpen = ref(false);

// Estado para archivo de estudiantes
const selectedStudentsFile = ref(null);

// Propiedad computada para el precio formateado
const formattedPrice = ref('');

// Estado para el monto de descuento formateado
const formattedDiscountAmount = ref('');



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
    
    initializeFormattedPrice();
    initializeFormattedDiscountAmount();
    // Emitir el estado inicial
    emit('update:modelValue', formData.value);
});

// Watch para sincronizar con el padre
watch(
    formData,
    (newValue) => {
        emit('update:modelValue', newValue);
    },
    { deep: true, immediate: true }
);

// Watch para sincronizar cambios del modelValue (especialmente en modo edit)
watch(
    () => props.modelValue,
    (newValue) => {
        if (props.mode === 'edit') {
            // En modo edit, sincronizar los valores del modelValue con formData
            Object.keys(newValue).forEach((key) => {
                if (newValue[key] !== undefined) {
                    formData.value[key] = newValue[key];
                }
            });
            // Reinicializar los valores formateados después de sincronizar
            initializeFormattedPrice();
            initializeFormattedDiscountAmount();
        }
    },
    { deep: true, immediate: true }
);

// Watcher para limpiar campos cuando se deselecciona la institución
watch(() => formData.value.institution_id, (newValue, oldValue) => {
    if (!newValue && oldValue) {
        // Si se deselecciona la institución, limpiar todos los campos dependientes
        formData.value.education_level = '';
        formData.value.shift = '';
        formData.value.grade = '';
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
           formData.value.education_level && 
           formData.value.shift && 
           formData.value.grade;
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
            console.log(`Archivo de estudiantes seleccionado:`, file.name);
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
        } else if (option === 'installments') {
            formData.value.installments_payment_method = "";
            formData.value.max_installments = "";
        }
    }
};

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
    // TODO: Implementar vista de estados de pagos
    alert('Función "Ver estados de pagos" - Por implementar');
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
</style>
