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
                                            v-model="formData.total_price"
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
                                    <div class="field-label">
                                        Nombre de institución
                                    </div>
                                    <input
                                        type="text"
                                        v-model="formData.institution_name"
                                        placeholder="Escriba el nombre de la institución"
                                        class="admin-input-text"
                                    />
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

                            <!-- Carga de alumnos -->
                            <div class="students-upload-section">
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
                                        />
                                    </div>
                                </div>
                                <div class="group-benefit-field">
                                    <div class="field-wrapper">
                                        <div class="field-label">
                                            ¿El grupo tienen beneficio general?
                                        </div>
                                        <select
                                            v-model="formData.group_benefit"
                                            class="admin-input-text"
                                        >
                                            <option value="">Selecciona el beneficio grupal</option>
                                            <option value="descuento_10">Descuento 10%</option>
                                            <option value="descuento_15">Descuento 15%</option>
                                            <option value="descuento_20">Descuento 20%</option>
                                        </select>
                                    </div>
                                </div>
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
                                ¿Cómo querés que pague el grupo? Elegí las opciones disponibles.
                            </div>

                            <!-- Opción Pago Total -->
                            <div class="payment-option">
                                <div class="payment-option-header">
                                    <div class="payment-checkbox">
                                        <input
                                            type="radio"
                                            id="full-payment"
                                            name="payment-option"
                                            value="full_payment"
                                            :checked="formData.payment_option === 'full_payment'"
                                            @click="handlePaymentOptionClick('full_payment')"
                                            class="radio-input"
                                        />
                                        <label for="full-payment" class="radio-label"></label>
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
                                <div v-if="formData.payment_option === 'full_payment'" class="payment-method-select">
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
                                            type="radio"
                                            id="installments-payment"
                                            name="payment-option"
                                            value="installments"
                                            :checked="formData.payment_option === 'installments'"
                                            @click="handlePaymentOptionClick('installments')"
                                            class="radio-input"
                                        />
                                        <label for="installments-payment" class="radio-label"></label>
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
                                <div v-if="formData.payment_option === 'installments'" class="installments-options">
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
import { ref, watch, defineEmits } from "vue";
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
            education_level: "",
            shift: "",
            grade: "",
            students_file: null,
            group_benefit: "",
            payment_option: "", // "full_payment" o "installments"
            full_payment_method: "",
            installments_payment_method: "",
            max_installments: "",
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
    }
});

// Emits
const emit = defineEmits(['update:modelValue']);

// Reactive data
const formData = ref({ ...props.modelValue });

// Estados para los acordeones
const priceOpen = ref(false);
const travelersOpen = ref(false);
const paymentOpen = ref(false);

// Estado para archivo de estudiantes
const selectedStudentsFile = ref(null);

// Watch para sincronizar con el padre
watch(
    formData,
    (newValue) => {
        emit('update:modelValue', newValue);
    },
    { deep: true }
);

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

// Función para manejar la selección de opciones de pago
const handlePaymentOptionClick = (option) => {
    // Si ya está seleccionado, lo deselecciona
    if (formData.value.payment_option === option) {
        formData.value.payment_option = "";
    } else {
        // Si no está seleccionado, lo selecciona
        formData.value.payment_option = option;
    }
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

.radio-input {
    width: 22.69px;
    height: 22.69px;
    border-radius: 50%;
    border: 1px solid var(--colores-neutro-gris-4, #5b5b5b);
    appearance: none;
    cursor: pointer;
    position: relative;
    transition: all 0.2s ease;
}

.radio-input:checked {
    background-color: var(--colores-op2-turquesa, #007e93);
    border-color: var(--colores-op2-turquesa, #007e93);
}

.radio-label {
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
</style>
