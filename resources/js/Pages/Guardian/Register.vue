<template>
    <Head title="Registro de Pagador" />

    <div
        class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8"
    >
        <div class="register-card">
            <!-- Header -->
            <div class="register-header">
                <div class="register-logo">
                    <img src="/images/logo-color.png" alt="Latitud90" />
                </div>
                <h2 class="register-title">Registro de Pagador</h2>
                <p class="register-subtitle">
                    Crea tu cuenta para gestionar los programas de tus hijos
                </p>
            </div>

            <!-- Errores generales -->
            <div v-if="$page.props.errors.error" class="error-alert">
                <div class="error-content">
                    <svg
                        class="error-icon"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <p class="error-text">{{ $page.props.errors.error }}</p>
                </div>
            </div>

            <!-- Formulario -->
            <form class="register-form" @submit.prevent="submit">
                    <div class="form-columns">
                        <!-- Left Column -->
                        <div class="form-column">
                            <!-- Tipo de Documento -->
                            <div class="field-wrapper">
                                <label class="field-label">
                                    Tipo de documento
                                    <span class="required">*</span>
                                </label>
                                <div class="radio-group">
                                    <label class="radio-label">
                                        <div class="radio-wrapper">
                                            <input
                                                type="radio"
                                                name="documentType"
                                                :value="
                                                    getDocumentTypeId('RUT')
                                                "
                                                v-model="form.document_type_id"
                                                class="sr-only peer"
                                            />
                                            <div class="radio-circle">
                                                <div class="radio-dot"></div>
                                            </div>
                                        </div>
                                        <span class="radio-text">RUT</span>
                                    </label>
                                    <label class="radio-label">
                                        <div class="radio-wrapper">
                                            <input
                                                type="radio"
                                                name="documentType"
                                                :value="
                                                    getDocumentTypeId(
                                                        'Pasaporte'
                                                    )
                                                "
                                                v-model="form.document_type_id"
                                                class="sr-only peer"
                                            />
                                            <div class="radio-circle">
                                                <div class="radio-dot"></div>
                                            </div>
                                        </div>
                                        <span class="radio-text"
                                            >Pasaporte</span
                                        >
                                    </label>
                                    <label class="radio-label">
                                        <div class="radio-wrapper">
                                            <input
                                                type="radio"
                                                name="documentType"
                                                :value="
                                                    getDocumentTypeId('DNI')
                                                "
                                                v-model="form.document_type_id"
                                                class="sr-only peer"
                                            />
                                            <div class="radio-circle">
                                                <div class="radio-dot"></div>
                                            </div>
                                        </div>
                                        <span class="radio-text">DNI</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Número de Documento -->
                            <div class="field-wrapper">
                                <label for="documentNumber" class="field-label">
                                    {{ getDocumentLabel() }}
                                    <span class="required">*</span>
                                </label>
                                <input
                                    id="documentNumber"
                                    v-model="form.document_number"
                                    type="text"
                                    required
                                    :placeholder="getDocumentPlaceholder()"
                                    class="input-text"
                                    :class="{
                                        'border-red-500':
                                            $page.props.errors.document_number,
                                    }"
                                    @input="handleDocumentInput"
                                />
                                <span
                                    v-if="$page.props.errors.document_number"
                                    class="error-message"
                                >
                                    {{ $page.props.errors.document_number }}
                                </span>
                            </div>

                            <!-- Nombre completo -->
                            <div class="field-wrapper">
                                <label for="fullName" class="field-label">
                                    Nombre completo
                                    <span class="required">*</span>
                                </label>
                                <input
                                    id="fullName"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="Nombre y apellido"
                                    class="input-text"
                                    :class="{
                                        'border-red-500':
                                            $page.props.errors.name,
                                    }"
                                />
                                <span
                                    v-if="$page.props.errors.name"
                                    class="error-message"
                                >
                                    {{ $page.props.errors.name }}
                                </span>
                            </div>

                            <!-- Correo electrónico -->
                            <div class="field-wrapper">
                                <label for="email" class="field-label">
                                    Correo electrónico
                                    <span class="required">*</span>
                                </label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    placeholder="Escriba su correo electrónico"
                                    class="input-text"
                                    :class="{
                                        'border-red-500':
                                            $page.props.errors.email,
                                    }"
                                />
                                <span
                                    v-if="$page.props.errors.email"
                                    class="error-message"
                                >
                                    {{ $page.props.errors.email }}
                                </span>
                            </div>

                            <!-- Número de celular -->
                            <div class="field-wrapper">
                                <label for="phone" class="field-label">
                                    Número de celular
                                    <span class="required">*</span>
                                </label>
                                <div class="phone-input-group">
                                    <select
                                        v-model="form.phone_code"
                                        class="phone-code-select"
                                    >
                                        <option value="+56">🇨🇱</option>
                                        <option value="+54">🇦🇷</option>
                                        <option value="+51">🇵🇪</option>
                                        <option value="+598">🇺🇾</option>
                                    </select>
                                    <input
                                        id="phone"
                                        v-model="form.phone"
                                        type="tel"
                                        required
                                        placeholder="9-- --- ---"
                                        class="phone-input"
                                        :class="{
                                            'border-red-500':
                                                $page.props.errors.phone,
                                        }"
                                    />
                                </div>
                                <span
                                    v-if="$page.props.errors.phone"
                                    class="error-message"
                                >
                                    {{ $page.props.errors.phone }}
                                </span>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="form-column">
                            <!-- País -->
                            <div class="field-wrapper">
                                <label for="country" class="field-label">
                                    País <span class="required">*</span>
                                </label>
                                <SearchableSelect
                                    :options="countries"
                                    :value="form.country_id"
                                    placeholder="Busca y selecciona tu país"
                                    @input="handleCountryChange"
                                    search-key="name"
                                />
                                <span
                                    v-if="$page.props.errors.country_id"
                                    class="error-message"
                                >
                                    {{ $page.props.errors.country_id }}
                                </span>
                            </div>

                            <!-- Región -->
                            <div class="field-wrapper">
                                <label for="region" class="field-label">
                                    Región <span class="required">*</span>
                                </label>
                                <SearchableSelect
                                    :options="regions"
                                    :value="form.region_id"
                                    placeholder="Busca y selecciona tu región"
                                    @input="handleRegionChange"
                                    search-key="name"
                                />
                                <span
                                    v-if="$page.props.errors.region_id"
                                    class="error-message"
                                >
                                    {{ $page.props.errors.region_id }}
                                </span>
                            </div>

                            <!-- Comuna -->
                            <div class="field-wrapper">
                                <label for="city" class="field-label">
                                    Comuna <span class="required">*</span>
                                </label>
                                <SearchableSelect
                                    :options="filteredComunes"
                                    :value="form.comune_id"
                                    placeholder="Busca y selecciona tu comuna"
                                    :disabled="!form.region_id"
                                    @input="handleCityChange"
                                    search-key="name"
                                />
                                <span
                                    v-if="$page.props.errors.comune_id"
                                    class="error-message"
                                >
                                    {{ $page.props.errors.comune_id }}
                                </span>
                            </div>

                            <!-- Contraseña -->
                            <div class="field-wrapper">
                                <label for="password" class="field-label">
                                    Contraseña <span class="required">*</span>
                                </label>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    required
                                    placeholder="Mínimo 8 caracteres"
                                    class="input-text"
                                    :class="{
                                        'border-red-500':
                                            $page.props.errors.password,
                                    }"
                                />
                                <span
                                    v-if="$page.props.errors.password"
                                    class="error-message"
                                >
                                    {{ $page.props.errors.password }}
                                </span>
                                <p class="field-hint">
                                    Usa al menos 8 caracteres, combina letras y
                                    números
                                </p>
                            </div>

                            <!-- Confirmar Contraseña -->
                            <div class="field-wrapper">
                                <label
                                    for="password_confirmation"
                                    class="field-label"
                                >
                                    Confirmar Contraseña
                                    <span class="required">*</span>
                                </label>
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    required
                                    placeholder="Repite tu contraseña"
                                    class="input-text"
                                />
                            </div>

                            <!-- Términos y condiciones -->
                            <div class="checkbox-section">
                                <div class="checkbox-wrapper">
                                    <input
                                        type="checkbox"
                                        id="terms"
                                        v-model="form.terms_accepted"
                                        class="custom-checkbox"
                                    />
                                    <label for="terms" class="checkbox-label">
                                        <span class="checkbox-text"
                                            >Acepto todos los</span
                                        >
                                        <a
                                            href="/terminos-y-condiciones"
                                            target="_blank"
                                            class="checkbox-link"
                                        >
                                            términos y condiciones*
                                        </a>
                                    </label>
                                </div>
                                <span
                                    v-if="$page.props.errors.terms_accepted"
                                    class="error-message"
                                >
                                    {{ $page.props.errors.terms_accepted }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Botón de Enviar -->
                    <div class="submit-section">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="submit-button"
                        >
                            <span v-if="!form.processing">Crear Cuenta</span>
                            <span v-else class="submit-loading">
                                <svg
                                    class="spinner"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                Creando cuenta...
                            </span>
                        </button>
                    </div>

                    <!-- Links adicionales -->
                    <div class="additional-links">
                        <div class="divider"></div>
                        <p class="link-text">
                            ¿Ya tienes una cuenta?
                            <Link
                                :href="props.token ? `/guardian/login?token=${props.token}` : route('guardian.login')"
                                class="action-link"
                            >
                                Inicia sesión aquí
                            </Link>
                        </p>
                        <Link
                            :href="route('ecommerce.index')"
                            class="back-link"
                        >
                            ← Volver al inicio
                        </Link>
                    </div>
                </form>
            </div>
        </div>
</template>

<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import SearchableSelect from "@/Components/Ecommerce/SearchableSelect.vue";
import { computed } from "vue";

const props = defineProps({
    countries: {
        type: Array,
        default: () => [],
    },
    regions: {
        type: Array,
        default: () => [],
    },
    documentTypes: {
        type: Array,
        default: () => [],
    },
    token: {
        type: String,
        default: null,
    },
});

const form = useForm({
    document_type_id: "",
    document_number: "",
    name: "",
    email: "",
    phone_code: "+56",
    phone: "",
    country_id: "",
    region_id: "",
    comune_id: "",
    password: "",
    password_confirmation: "",
    terms_accepted: false,
});

const filteredComunes = computed(() => {
    if (!form.region_id) {
        return [];
    }

    const selectedRegion = props.regions.find((r) => r.id == form.region_id);

    if (!selectedRegion || !selectedRegion.comunes) {
        return [];
    }

    return selectedRegion.comunes;
});

const getDocumentTypeId = (name) => {
    const docType = props.documentTypes.find(
        (doc) => doc.name.toLowerCase() === name.toLowerCase()
    );
    return docType ? docType.id : "";
};

const getDocumentLabel = () => {
    if (!form.document_type_id) return "Número de documento";

    const selectedDocType = props.documentTypes.find(
        (doc) => doc.id == form.document_type_id
    );

    return selectedDocType ? selectedDocType.name : "Número de documento";
};

const getDocumentPlaceholder = () => {
    if (!form.document_type_id) return "Ingresa tu número de documento";

    const selectedDocType = props.documentTypes.find(
        (doc) => doc.id == form.document_type_id
    );

    if (!selectedDocType) return "Ingresa tu número de documento";

    switch (selectedDocType.name.toLowerCase()) {
        case "rut":
            return "Ej: 12.345.678-9";
        case "pasaporte":
            return "Ej: A12345678";
        case "dni":
            return "Ej: 12.345.678";
        default:
            return "Ingresa tu número de documento";
    }
};

const handleDocumentInput = () => {
    const selectedDocType = props.documentTypes.find(
        (doc) => doc.id == form.document_type_id
    );

    if (selectedDocType && selectedDocType.name.toLowerCase() === "rut") {
        formatRut();
    } else if (
        selectedDocType &&
        selectedDocType.name.toLowerCase() === "dni"
    ) {
        formatDni();
    }
};

const formatRut = () => {
    let rut = form.document_number.replace(/[^0-9kK]/g, "");

    if (rut.length > 0) {
        rut = rut.toUpperCase();

        if (rut.length > 1) {
            const body = rut.slice(0, -1);
            const dv = rut.slice(-1);

            let formattedBody = "";
            for (let i = body.length - 1, j = 0; i >= 0; i--, j++) {
                if (j > 0 && j % 3 === 0) {
                    formattedBody = "." + formattedBody;
                }
                formattedBody = body[i] + formattedBody;
            }

            form.document_number = `${formattedBody}-${dv}`;
        } else {
            form.document_number = rut;
        }
    }
};

const formatDni = () => {
    let documentNumber = form.document_number.replace(/[^0-9]/g, "");

    if (documentNumber.length > 0) {
        if (documentNumber.length > 6) {
            const formatted = documentNumber.replace(
                /(\d{2})(\d{3})(\d{3})/,
                "$1.$2.$3"
            );
            form.document_number = formatted;
        } else if (documentNumber.length > 3) {
            const formatted = documentNumber.replace(/(\d{2})(\d{3})/, "$1.$2");
            form.document_number = formatted;
        } else {
            form.document_number = documentNumber;
        }
    }
};

const handleCountryChange = (countryId) => {
    form.country_id = countryId;
};

const handleRegionChange = (regionId) => {
    form.region_id = regionId;
    form.comune_id = ""; // Clear commune when region changes
};

const handleCityChange = (cityId) => {
    form.comune_id = cityId;
};

const submit = () => {
    form.post(route("guardian.register.post"), {
        preserveScroll: true,
    });
};

// Set RUT as default document type on mount
if (props.documentTypes.length > 0) {
    form.document_type_id = getDocumentTypeId("RUT");
}
</script>

<style scoped>
/* Container principal */
.register-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 40px;
    max-width: 900px;
    width: 100%;
    box-shadow: 0px 4px 11.6px 0px rgba(163, 163, 163, 0.11);
}

/* Header */
.register-header {
    text-align: center;
    margin-bottom: 32px;
}

.register-logo {
    margin-bottom: 24px;
}

.register-logo img {
    height: 60px;
    width: auto;
}

.register-title {
    color: #1c4f4a;
    font-family: "Nexa-Bold", sans-serif;
    font-size: 24px;
    line-height: 28px;
    font-weight: 700;
    margin-bottom: 8px;
}

.register-subtitle {
    color: #434343;
    font-family: "Nexa-Regular", sans-serif;
    font-size: 14px;
    line-height: 18px;
}

/* Error Alert */
.error-alert {
    background: #fee2e2;
    border-left: 4px solid #ef4444;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 24px;
}

.error-content {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.error-icon {
    width: 20px;
    height: 20px;
    color: #dc2626;
    flex-shrink: 0;
}

.error-text {
    color: #991b1b;
    font-size: 14px;
    line-height: 20px;
}

/* Formulario */
.register-form {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* Form Columns */
.form-columns {
    display: flex;
    flex-direction: column;
    gap: 32px;
}

@media (min-width: 768px) {
    .form-columns {
        flex-direction: row;
        gap: 54px;
        justify-content: center;
    }
}

.form-column {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 18px;
    width: 100%;
}

@media (min-width: 768px) {
    .form-column {
        width: 364px;
    }
}

/* Field Wrapper */
.field-wrapper {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.field-label {
    color: #434343;
    font-family: "Nexa-Regular", sans-serif;
    font-size: 14px;
    line-height: 18px;
    font-weight: normal;
}

.required {
    color: #ef4444;
}

/* Radio Buttons */
.radio-group {
    display: flex;
    gap: 24px;
}

.radio-label {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
}

.radio-wrapper {
    position: relative;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}

.radio-circle {
    width: 20px;
    height: 20px;
    border: 2px solid #5b5b5b;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.radio-wrapper input:checked + .radio-circle {
    border-color: #fbbd51;
    background-color: #fbbd51;
}

.radio-dot {
    width: 8px;
    height: 8px;
    background: white;
    border-radius: 50%;
    opacity: 0;
    transition: opacity 0.2s;
}

.radio-wrapper input:checked + .radio-circle .radio-dot {
    opacity: 1;
}

.radio-text {
    color: #434343;
    font-family: "Nexa-Regular", sans-serif;
    font-size: 14px;
    line-height: 18px;
    transition: color 0.2s;
}

.radio-label:hover .radio-text {
    color: #fbbd51;
}

/* Input Styles */
.input-text {
    width: 100%;
    height: 46px;
    background: white;
    border-radius: 8px;
    border: 1px solid #5b5b5b;
    padding: 12px 16px;
    font-family: "Nexa-Bold", sans-serif;
    font-size: 12px;
    line-height: 18px;
    font-weight: bold;
    outline: none;
    transition: all 0.2s ease;
}

.input-text::placeholder {
    color: #c7c7c7;
}

.input-text:focus {
    border-color: #fbbd51;
    box-shadow: 0 0 0 3px rgba(251, 189, 81, 0.1);
}

.input-text.border-red-500 {
    border-color: #ef4444;
}

.input-text.border-red-500:focus {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

/* Phone Input Group */
.phone-input-group {
    display: flex;
}

.phone-code-select {
    width: 70px;
    height: 46px;
    background: white;
    border: 1px solid #5b5b5b;
    border-radius: 8px 0 0 8px;
    border-right: 0;
    padding: 0 8px;
    font-family: "Nexa-Bold", sans-serif;
    font-size: 12px;
    line-height: 18px;
    font-weight: bold;
    outline: none;
    appearance: none;
}

.phone-input {
    flex: 1;
    height: 46px;
    background: white;
    border: 1px solid #5b5b5b;
    border-radius: 0 8px 8px 0;
    padding: 12px 16px;
    font-family: "Nexa-Bold", sans-serif;
    font-size: 12px;
    line-height: 18px;
    font-weight: bold;
    outline: none;
}

.phone-input::placeholder {
    color: #c7c7c7;
}

.phone-input:focus {
    border-color: #fbbd51;
}

.phone-input.border-red-500 {
    border-color: #ef4444;
}

/* Field Hint */
.field-hint {
    color: #434343;
    font-family: "Nexa-Regular", sans-serif;
    font-size: 11px;
    line-height: 14px;
    font-style: italic;
    margin-top: -4px;
}

/* Error Message */
.error-message {
    color: #dc2626;
    font-family: "Nexa-Regular", sans-serif;
    font-size: 12px;
    line-height: 16px;
    margin-top: -4px;
}

/* Checkbox Section */
.checkbox-section {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 8px;
}

.checkbox-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
}

.custom-checkbox {
    width: 12px;
    height: 12px;
    border-radius: 1.5px;
    accent-color: #fbbd51;
    cursor: pointer;
}

.custom-checkbox:checked {
    background-color: #fbbd51;
    border-color: #fbbd51;
}

.checkbox-label {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    color: #434343;
    font-family: "Nexa-Regular", sans-serif;
    font-size: 10px;
    line-height: 16px;
    cursor: pointer;
}

.checkbox-text {
    font-family: "Nexa-Regular", sans-serif;
}

.checkbox-link {
    font-family: "Nexa-Bold", sans-serif;
    font-weight: bold;
    text-decoration: underline;
    color: #434343;
    transition: color 0.2s;
}

.checkbox-link:hover {
    color: #007e93;
}

/* Submit Section */
.submit-section {
    margin-top: 16px;
}

.submit-button {
    background: #fbbd51;
    border-radius: 59px;
    border: none;
    padding: 11px 20px;
    width: 100%;
    color: #ffffff;
    font-family: "Nexa-Bold", sans-serif;
    font-size: 16px;
    line-height: 20px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.submit-button:hover:not(:disabled) {
    background: #e0a840;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(251, 189, 81, 0.3);
}

.submit-button:disabled {
    background: #c7c7c7;
    cursor: not-allowed;
    transform: none;
}

.submit-loading {
    display: flex;
    align-items: center;
    gap: 8px;
}

.spinner {
    width: 20px;
    height: 20px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

/* Additional Links */
.additional-links {
    margin-top: 24px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    align-items: center;
}

.divider {
    width: 100%;
    height: 1px;
    background: #e5e5e5;
    margin-bottom: 8px;
}

.link-text {
    color: #434343;
    font-family: "Nexa-Regular", sans-serif;
    font-size: 14px;
    line-height: 18px;
    text-align: center;
}

.action-link {
    color: #007e93;
    font-family: "Nexa-Bold", sans-serif;
    text-decoration: none;
    transition: color 0.2s ease;
}

.action-link:hover {
    color: #005a6b;
    text-decoration: underline;
}

.back-link {
    color: #434343;
    font-family: "Nexa-Regular", sans-serif;
    font-size: 13px;
    text-decoration: none;
    transition: color 0.2s ease;
}

.back-link:hover {
    color: #007e93;
}

/* Responsive */
@media (max-width: 768px) {
    .register-card {
        padding: 24px;
        margin: 16px;
    }

    .register-title {
        font-size: 20px;
    }

    .register-subtitle {
        font-size: 12px;
    }

    .form-columns {
        gap: 24px;
    }

    .form-column {
        width: 100%;
    }
}
</style>
