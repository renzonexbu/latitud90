<template>
    <!-- Modal Backdrop -->
    <div
        v-if="show"
        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
        @click.self="closeModal"
    >
        <!-- Modal Content -->
        <div
            class="bg-white rounded-[20px] border border-[#d3d3d3] max-w-[1200px] w-full max-h-[90vh] flex flex-col"
        >
            <!-- Header -->
            <div
                class="flex flex-row justify-center items-center relative p-8 border-b border-gray-200"
            >
                <div
                    class="text-[#434343] text-center font-nexa-bold text-[24px] leading-[28px] font-bold flex-1"
                >
                    Crear nuevo participante
                </div>
                <button
                    @click="closeModal"
                    class="bg-gray-500 rounded-full p-2 hover:bg-gray-600 transition-colors"
                >
                    <CrossIcon
                        width="8.969"
                        height="8.969"
                        stroke-color="#FFF"
                        stroke-width="2.07"
                    />
                </button>
            </div>

            <!-- Form Content - Two Columns -->
            <div class="p-8 overflow-y-auto flex-1">
                <form
                    @submit.prevent="saveParticipant"
                    class="flex flex-row gap-8"
                >
                    <!-- Left Column - Datos del participante -->
                    <div class="flex-1">
                        <div class="space-y-6">
                            <!-- Datos del participante -->
                            <div>
                                <h3
                                    class="text-[#007e93] text-left font-nexa-bold text-[18px] leading-[22px] font-bold mb-4"
                                >
                                    Datos del participante*
                                </h3>

                                <div class="space-y-4">
                                    <!-- Nombres -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label
                                                class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2"
                                            >
                                                Primer Nombre *
                                            </label>
                                            <input
                                                v-model="form.first_name"
                                                type="text"
                                                placeholder="Primer Nombre"
                                                :class="[
                                                    'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                    errors.first_name
                                                        ? 'border-red-500'
                                                        : 'border-[#5b5b5b]',
                                                ]"
                                            />
                                            <span
                                                v-if="errors.first_name"
                                                class="text-red-500 text-xs mt-1"
                                                >{{ errors.first_name }}</span
                                            >
                                        </div>

                                        <div>
                                            <label
                                                class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2"
                                            >
                                                Segundo Nombre
                                            </label>
                                            <input
                                                v-model="form.second_name"
                                                type="text"
                                                placeholder="Segundo Nombre (opcional)"
                                                :class="[
                                                    'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                    errors.second_name
                                                        ? 'border-red-500'
                                                        : 'border-[#5b5b5b]',
                                                ]"
                                            />
                                            <span
                                                v-if="errors.second_name"
                                                class="text-red-500 text-xs mt-1"
                                                >{{ errors.second_name }}</span
                                            >
                                        </div>
                                    </div>
                                    <!-- Nombre y Apellido -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label
                                                class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2"
                                            >
                                                Primer Apellido *
                                            </label>
                                            <input
                                                v-model="form.first_last_name"
                                                type="text"
                                                placeholder="Primer Apellido"
                                                :class="[
                                                    'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                    errors.first_last_name
                                                        ? 'border-red-500'
                                                        : 'border-[#5b5b5b]',
                                                ]"
                                            />
                                            <span
                                                v-if="errors.first_last_name"
                                                class="text-red-500 text-xs mt-1"
                                                >{{
                                                    errors.first_last_name
                                                }}</span
                                            >
                                        </div>

                                        <div>
                                            <label
                                                class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2"
                                            >
                                                Segundo Apellido
                                            </label>
                                            <input
                                                v-model="form.second_last_name"
                                                type="text"
                                                placeholder="Segundo Apellido (opcional)"
                                                :class="[
                                                    'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                    errors.second_last_name
                                                        ? 'border-red-500'
                                                        : 'border-[#5b5b5b]',
                                                ]"
                                            />
                                            <span
                                                v-if="errors.second_last_name"
                                                class="text-red-500 text-xs mt-1"
                                                >{{
                                                    errors.second_last_name
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                    <!-- Tipo de documento -->
                                    <div>
                                        <label
                                            class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2"
                                        >
                                            Tipo de documento *
                                        </label>
                                        <select
                                            v-model="form.document_type"
                                            @change="onDocumentTypeChange"
                                            :class="[
                                                'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none',
                                                errors.document_type
                                                    ? 'border-red-500'
                                                    : 'border-[#5b5b5b]',
                                            ]"
                                        >
                                            <option value="">
                                                Seleccione tipo de documento
                                            </option>
                                            <option
                                                v-for="docType in documentTypes"
                                                :key="docType.id"
                                                :value="docType.id"
                                            >
                                                {{ docType.name }}
                                            </option>
                                        </select>
                                        <span
                                            v-if="errors.document_type"
                                            class="text-red-500 text-xs mt-1"
                                            >{{ errors.document_type }}</span
                                        >
                                    </div>

                                    <!-- Documento -->
                                    <div>
                                        <label
                                            class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2"
                                        >
                                            {{ getDocumentLabel() }} *
                                        </label>
                                        <input
                                            v-model="form.document_number"
                                            type="text"
                                            :placeholder="
                                                getDocumentPlaceholder()
                                            "
                                            @input="onDocumentInput"
                                            @blur="onDocumentBlur"
                                            :class="[
                                                'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                errors.document_number
                                                    ? 'border-red-500'
                                                    : 'border-[#5b5b5b]',
                                                isRutDocument &&
                                                rutValidation.isValid === false
                                                    ? 'border-red-500'
                                                    : '',
                                                isRutDocument &&
                                                rutValidation.isValid === true
                                                    ? 'border-green-500'
                                                    : '',
                                            ]"
                                        />
                                        <span
                                            v-if="errors.document_number"
                                            class="text-red-500 text-xs mt-1"
                                            >{{ errors.document_number }}</span
                                        >
                                        <span
                                            v-if="
                                                isRutDocument &&
                                                rutValidation.message
                                            "
                                            :class="[
                                                'text-xs mt-1',
                                                rutValidation.isValid === true
                                                    ? 'text-green-500'
                                                    : 'text-red-500',
                                            ]"
                                            >{{ rutValidation.message }}</span
                                        >
                                    </div>

                                    <!-- Fecha de nacimiento -->
                                    <div>
                                        <label
                                            class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2"
                                        >
                                            Fecha de nacimiento
                                        </label>
                                        <input
                                            v-model="form.birth_date"
                                            type="date"
                                            placeholder="00/00/0000"
                                            :class="[
                                                'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                errors.birth_date
                                                    ? 'border-red-500'
                                                    : 'border-[#5b5b5b]',
                                            ]"
                                        />
                                        <span
                                            v-if="errors.birth_date"
                                            class="text-red-500 text-xs mt-1"
                                            >{{ errors.birth_date }}</span
                                        >
                                    </div>

                                    <!-- Nacionalidad y Sexo -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label
                                                class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2"
                                            >
                                                Nacionalidad
                                            </label>
                                            <input
                                                v-model="form.nationality"
                                                type="text"
                                                placeholder="Nacionalidad"
                                                :class="[
                                                    'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                    errors.nationality
                                                        ? 'border-red-500'
                                                        : 'border-[#5b5b5b]',
                                                ]"
                                            />
                                            <span
                                                v-if="errors.nationality"
                                                class="text-red-500 text-xs mt-1"
                                                >{{ errors.nationality }}</span
                                            >
                                        </div>

                                        <div>
                                            <label
                                                class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2"
                                            >
                                                Sexo
                                            </label>
                                            <select
                                                v-model="form.gender"
                                                :class="[
                                                    'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none',
                                                    errors.gender
                                                        ? 'border-red-500'
                                                        : 'border-[#5b5b5b]',
                                                ]"
                                            >
                                                <option value="">
                                                    Seleccione sexo
                                                </option>
                                                <option value="Masculino">
                                                    Masculino
                                                </option>
                                                <option value="Femenino">
                                                    Femenino
                                                </option>
                                            </select>
                                            <span
                                                v-if="errors.gender"
                                                class="text-red-500 text-xs mt-1"
                                                >{{ errors.gender }}</span
                                            >
                                        </div>
                                    </div>

                                    <!-- Restricción alimenticia -->
                                    <div>
                                        <label
                                            class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2"
                                        >
                                            Restricción alimenticia
                                        </label>
                                        <input
                                            v-model="form.dietary_restrictions"
                                            type="text"
                                            placeholder="Restricción alimenticia (opcional)"
                                            :class="[
                                                'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                errors.dietary_restrictions
                                                    ? 'border-red-500'
                                                    : 'border-[#5b5b5b]',
                                            ]"
                                        />
                                        <span
                                            v-if="errors.dietary_restrictions"
                                            class="text-red-500 text-xs mt-1"
                                            >{{
                                                errors.dietary_restrictions
                                            }}</span
                                        >
                                    </div>

                                    <!-- Intolerancias -->
                                    <div>
                                        <label
                                            class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2"
                                        >
                                            Intolerancias
                                        </label>
                                        <input
                                            v-model="form.intolerances"
                                            type="text"
                                            placeholder="Intolerancias (opcional)"
                                            :class="[
                                                'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                errors.intolerances
                                                    ? 'border-red-500'
                                                    : 'border-[#5b5b5b]',
                                            ]"
                                        />
                                        <span
                                            v-if="errors.intolerances"
                                            class="text-red-500 text-xs mt-1"
                                            >{{ errors.intolerances }}</span
                                        >
                                    </div>

                                    <!-- Alergias -->
                                    <div>
                                        <label
                                            class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2"
                                        >
                                            Alergias
                                        </label>
                                        <input
                                            v-model="form.allergies"
                                            type="text"
                                            placeholder="Alergias (opcional)"
                                            :class="[
                                                'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                errors.allergies
                                                    ? 'border-red-500'
                                                    : 'border-[#5b5b5b]',
                                            ]"
                                        />
                                        <span
                                            v-if="errors.allergies"
                                            class="text-red-500 text-xs mt-1"
                                            >{{ errors.allergies }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Contacto de emergencia y Programa -->
                    <div class="flex-1">
                        <div class="space-y-6">
                            <!-- Contacto de emergencia -->
                            <div>
                                <h3
                                    class="text-[#007e93] text-left font-nexa-bold text-[18px] leading-[22px] font-bold mb-4"
                                >
                                    Contacto de emergencia
                                </h3>

                                <div class="space-y-4">
                                    <!-- Nombre completo -->
                                    <div>
                                        <label
                                            class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2"
                                        >
                                            Nombre Completo
                                        </label>
                                        <input
                                            v-model="
                                                form.emergency_contact_name
                                            "
                                            type="text"
                                            placeholder="Nombre Completo"
                                            :class="[
                                                'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                errors[
                                                    'emergency_contacts.0.name'
                                                ]
                                                    ? 'border-red-500'
                                                    : 'border-[#5b5b5b]',
                                            ]"
                                        />
                                        <span
                                            v-if="
                                                errors[
                                                    'emergency_contacts.0.name'
                                                ]
                                            "
                                            class="text-red-500 text-xs mt-1"
                                            >{{
                                                errors[
                                                    "emergency_contacts.0.name"
                                                ]
                                            }}</span
                                        >
                                    </div>

                                    <!-- Correo del apoderado -->
                                    <div>
                                        <label
                                            class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2"
                                        >
                                            Correo del apoderado
                                        </label>
                                        <input
                                            v-model="
                                                form.emergency_contact_email
                                            "
                                            type="email"
                                            placeholder="Correo del apoderado"
                                            :class="[
                                                'w-full h-[46px] bg-white rounded border px-4 py-2 text-left font-nexa-regular text-[14px] leading-[22px] outline-none placeholder-[#c7c7c7]',
                                                errors[
                                                    'emergency_contacts.0.email'
                                                ]
                                                    ? 'border-red-500'
                                                    : 'border-[#5b5b5b]',
                                            ]"
                                        />
                                        <span
                                            v-if="
                                                errors[
                                                    'emergency_contacts.0.email'
                                                ]
                                            "
                                            class="text-red-500 text-xs mt-1"
                                            >{{
                                                errors[
                                                    "emergency_contacts.0.email"
                                                ]
                                            }}</span
                                        >
                                    </div>

                                    <!-- RUT del apoderado -->
                                    <div>
                                        <label
                                            class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2"
                                        >
                                            RUT del apoderado
                                        </label>
                                        <input
                                            v-model="
                                                form.emergency_contact_document_number
                                            "
                                            type="text"
                                            placeholder="00.000.000-0"
                                            @input="formatEmergencyRut"
                                            @blur="validateEmergencyRut"
                                            :class="[
                                                'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                errors[
                                                    'emergency_contacts.0.document_number'
                                                ]
                                                    ? 'border-red-500'
                                                    : 'border-[#5b5b5b]',
                                            ]"
                                        />
                                        <span
                                            v-if="
                                                errors[
                                                    'emergency_contacts.0.document_number'
                                                ]
                                            "
                                            class="text-red-500 text-xs mt-1"
                                            >{{
                                                errors[
                                                    "emergency_contacts.0.document_number"
                                                ]
                                            }}</span
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Separador -->
                            <hr class="border-[#c7c7c7]" />

                            <!-- Programa -->
                            <div>
                                <h3
                                    class="text-[#007e93] text-left font-nexa-bold text-[18px] leading-[22px] font-bold mb-4"
                                >
                                    Programa
                                </h3>

                                <div class="space-y-4">
                                    <!-- Programa asociado -->
                                    <div>
                                        <label
                                            class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2"
                                        >
                                            Programa asociado *
                                        </label>
                                        <SearchableSelect
                                            :options="programsFormatted"
                                            :value="form.program_id"
                                            placeholder="Buscar por código, nombre o destino"
                                            search-key="searchText"
                                            @input="form.program_id = $event"
                                        />
                                        <span
                                            v-if="errors.program_id"
                                            class="text-red-500 text-xs mt-1"
                                            >{{ errors.program_id }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Save button -->
            <div class="p-8 border-t border-gray-200">
                <button
                    @click="saveParticipant"
                    :disabled="isSubmitting"
                    class="bg-[#007e93] rounded-[112.89px] px-[18px] py-[14px] flex flex-row gap-[11.29px] items-center justify-center w-full hover:bg-[#006580] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <div
                        v-if="isSubmitting"
                        class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"
                    ></div>
                    <div
                        class="text-white text-center font-nexa-bold text-[16px] leading-[22px] font-bold"
                    >
                        {{ isSubmitting ? "Guardando..." : "Guardar cambios" }}
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { CrossIcon } from "@/Components/Icons";
import { router } from "@inertiajs/vue3";
import SearchableSelect from "@/Components/Ecommerce/SearchableSelect.vue";

export default {
    name: "CreateParticipantModal",
    components: {
        CrossIcon,
        SearchableSelect,
    },
    props: {
        show: {
            type: Boolean,
            default: false,
        },
        courses: {
            type: Array,
            default: () => [],
        },
        institutions: {
            type: Array,
            default: () => [],
        },
        programs: {
            type: Array,
            default: () => [],
        },
        documentTypes: {
            type: Array,
            default: () => [],
        },
        errors: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            isSubmitting: false,
            form: {
                // Datos del participante
                first_last_name: "",
                second_last_name: "",
                first_name: "",
                second_name: "",
                document_number: "",
                document_type: "",
                birth_date: "",
                nationality: "",
                gender: "",
                dietary_restrictions: "",
                intolerances: "",
                allergies: "",
                individual_price: 4000, // Precio por defecto
                price_adjustments: 0,
                adjustment_reason: "",
                country: "Chile",
                address: "",

                // Contacto de emergencia principal
                emergency_contact_name: "",
                emergency_contact_email: "",
                emergency_contact_document_number: "",
                emergency_contact_document_type: 1,

                // Programa
                program_id: "",
            },
            // No se permiten contactos adicionales (se mantiene por compatibilidad de estado)
            additionalContacts: [],
            rutValidation: {
                isValid: null,
                message: "",
            },
        };
    },
    computed: {
        isRutDocument() {
            const selectedDocType = this.documentTypes.find(
                (dt) => dt.id == this.form.document_type
            );
            return (
                selectedDocType && selectedDocType.name.toLowerCase() === "rut"
            );
        },
        programsFormatted() {
            return this.programs.map(program => ({
                id: program.id,
                name: `${program.code || ''} - ${program.name}`.replace(/^\s*-\s*/, '').trim(),
                searchText: `${program.code || ''} ${program.name}`.toLowerCase()
            }));
        },
    },
    methods: {
        closeModal() {
            this.$emit("close");
            this.resetForm();
        },

        resetForm() {
            this.form = {
                first_last_name: "",
                second_last_name: "",
                first_name: "",
                second_name: "",
                document_number: "",
                document_type: "",
                birth_date: "",
                nationality: "",
                gender: "",
                dietary_restrictions: "",
                intolerance: "",
                allergies: "",
                individual_price: 4000,
                price_adjustments: 0,
                adjustment_reason: "",
                country: "Chile",
                address: "",

                emergency_contact_name: "",
                emergency_contact_email: "",
                emergency_contact_document_number: "",
                emergency_contact_document_type: 1,

                program_id: "",
            };
            this.additionalContacts = [];
            this.isSubmitting = false;
            this.rutValidation = {
                isValid: null,
                message: "",
            };
        },

        async saveParticipant() {
            this.isSubmitting = true;

            try {
                // Preparar datos del participante
                const participantData = {
                    first_last_name: this.form.first_last_name,
                    second_last_name: this.form.second_last_name,
                    first_name: this.form.first_name,
                    second_name: this.form.second_name,
                    document_number: this.form.document_number,
                    document_type: this.form.document_type,
                    birth_date: this.form.birth_date,
                    nationality: this.form.nationality,
                    gender: this.form.gender,
                    dietary_restrictions: this.form.dietary_restrictions,
                    intolerances: this.form.intolerances,
                    allergies: this.form.allergies,
                    program_id: this.form.program_id,
                    individual_price: this.form.individual_price,
                    price_adjustments: this.form.price_adjustments,
                    adjustment_reason: this.form.adjustment_reason,
                    country: this.form.country,
                    address: this.form.address,
                };

                // Preparar contactos de emergencia
                const emergencyContacts = [
                    {
                        name: this.form.emergency_contact_name,
                        email: this.form.emergency_contact_email,
                        document_type:
                            this.form.emergency_contact_document_type,
                        document_number:
                            this.form.emergency_contact_document_number,
                    },
                ];

                // Solo se admite un contacto de emergencia (ya incluido en posición 0)

                // Enviar datos al servidor
                await router.post(
                    route("admin.participants.store"),
                    {
                        ...participantData,
                        emergency_contacts: emergencyContacts,
                    },
                    {
                        onSuccess: () => {
                            this.closeModal();
                        },
                        onError: (errors) => {
                            // Error logging for validation errors
                            console.error("❌ Validation errors while saving participant:", errors);
                        },
                    }
                );
            } catch (error) {
                console.error("❌ Error saving participant:", error);
            } finally {
                this.isSubmitting = false;
            }
        },

        // Removidos métodos para contactos adicionales: solo uno permitido

        formatRut() {
            // Remover todos los caracteres no numéricos excepto K
            let rut = this.form.document_number.replace(/[^0-9kK]/g, "");

            if (rut.length > 0) {
                rut = rut.toUpperCase();

                // Si tiene más de 1 carácter, separar cuerpo y dígito verificador
                if (rut.length > 1) {
                    const body = rut.slice(0, -1);
                    const dv = rut.slice(-1);

                    // Formatear el cuerpo con puntos
                    let formattedBody = "";
                    for (let i = body.length - 1, j = 0; i >= 0; i--, j++) {
                        if (j > 0 && j % 3 === 0) {
                            formattedBody = "." + formattedBody;
                        }
                        formattedBody = body[i] + formattedBody;
                    }

                    // Combinar cuerpo formateado con dígito verificador
                    this.form.document_number = `${formattedBody}-${dv}`;
                } else {
                    this.form.document_number = rut;
                }
            }
        },

        validateRut() {
            const rut = this.form.document_number
                .replace(/\./g, "")
                .replace(/-/g, "");

            if (rut.length === 0) {
                this.rutValidation.isValid = null;
                this.rutValidation.message = "";
                return;
            }

            // Validar formato básico
            if (!/^[0-9]+[0-9kK]$/.test(rut)) {
                this.rutValidation.isValid = false;
                this.rutValidation.message = "Formato de RUT inválido";
                return;
            }

            // Separar cuerpo y dígito verificador
            const body = rut.slice(0, -1);
            const dv = rut.slice(-1).toUpperCase();

            // Validar que el cuerpo tenga al menos 7 dígitos
            if (body.length < 7) {
                this.rutValidation.isValid = false;
                this.rutValidation.message =
                    "RUT debe tener al menos 7 dígitos";
                return;
            }

            // Calcular dígito verificador
            const dvCalculado = this.calculateDv(body);

            // Comparar dígitos verificadores
            this.rutValidation.isValid = dv === dvCalculado;
            this.rutValidation.message = this.rutValidation.isValid
                ? "RUT válido"
                : "RUT inválido";
        },

        formatEmergencyRut() {
            if (!this.form.emergency_contact_document_number) return;
            let rut = this.form.emergency_contact_document_number
                .replace(/[^0-9kK]/g, "")
                .toUpperCase();
            if (rut.length > 1) {
                const body = rut.slice(0, -1);
                const dv = rut.slice(-1);
                let formattedBody = "";
                for (let i = body.length - 1, j = 0; i >= 0; i--, j++) {
                    if (j > 0 && j % 3 === 0)
                        formattedBody = "." + formattedBody;
                    formattedBody = body[i] + formattedBody;
                }
                this.form.emergency_contact_document_number = `${formattedBody}-${dv}`;
            } else {
                this.form.emergency_contact_document_number = rut;
            }
        },

        validateEmergencyRut() {
            const rut = (
                this.form.emergency_contact_document_number || ""
            ).replace(/[\.\-]/g, "");
            if (!rut) return;
            if (!/^[0-9]+[0-9kK]$/.test(rut)) return;
            const body = rut.slice(0, -1);
            const dv = rut.slice(-1).toUpperCase();
            const dvCalc = this.calculateDv(body);
            if (dv !== dvCalc) {
                // mantener UX, solo marcar borde via errors si backend devuelve error
            }
        },
        calculateDv(body) {
            let sum = 0;
            let factor = 2;
            for (let i = body.length - 1; i >= 0; i--) {
                sum += body[i] * factor;
                factor = factor === 7 ? 2 : factor + 1;
            }
            const dv = 11 - (sum % 11);
            return dv === 10 ? "K" : dv === 11 ? "0" : dv.toString();
        },

        // Métodos para manejar tipo de documento
        onDocumentTypeChange() {
            // Limpiar validación de RUT cuando cambia el tipo de documento
            this.rutValidation = {
                isValid: null,
                message: "",
            };
            // Limpiar número de documento
            this.form.document_number = "";
        },

        onDocumentInput() {
            const selectedDocType = this.documentTypes.find(
                (dt) => dt.id == this.form.document_type
            );

            if (
                selectedDocType &&
                selectedDocType.name.toLowerCase() === "rut"
            ) {
                this.formatRut();
            } else if (
                selectedDocType &&
                selectedDocType.name.toLowerCase() === "pasaporte"
            ) {
                // Convertir a mayúsculas para pasaporte
                this.form.document_number =
                    this.form.document_number.toUpperCase();
            }
        },

        onDocumentBlur() {
            const selectedDocType = this.documentTypes.find(
                (dt) => dt.id == this.form.document_type
            );

            if (
                selectedDocType &&
                selectedDocType.name.toLowerCase() === "rut"
            ) {
                this.validateRut();
            }
        },

        getDocumentLabel() {
            const selectedDocType = this.documentTypes.find(
                (dt) => dt.id == this.form.document_type
            );
            return selectedDocType
                ? selectedDocType.name
                : "Número de documento";
        },

        getDocumentPlaceholder() {
            const selectedDocType = this.documentTypes.find(
                (dt) => dt.id == this.form.document_type
            );
            if (
                selectedDocType &&
                selectedDocType.name.toLowerCase() === "rut"
            ) {
                return "00.000.000-0";
            } else if (
                selectedDocType &&
                selectedDocType.name.toLowerCase() === "pasaporte"
            ) {
                return "Número de pasaporte";
            }
            return "Número de documento";
        },
    },
};
</script>

<style scoped>
.font-nexa-bold {
    font-family: "Nexa-Bold", sans-serif;
}

.font-nexa-regular {
    font-family: "Nexa-Regular", sans-serif;
}

/* Estilos para campos con error */
input.error,
select.error,
textarea.error {
    border-color: #ef4444 !important;
    color: #ef4444 !important;
}

/* Estilos para inputs cuando tienen contenido */
input:not(:placeholder-shown):not(.error),
select:not(.error),
textarea:not(:placeholder-shown):not(.error) {
    color: var(--Colores-OP2-Turquesa, #007e93) !important;
    font-family: Nexa;
    font-size: var(--Numeros-Cuerpo-de-texto-M, 12px);
    font-style: normal;
    font-weight: 700;
    line-height: 18px;
}

/* Estilos para inputs con placeholder (vacíos) */
input::placeholder,
textarea::placeholder {
    color: #c7c7c7 !important;
}

/* Estilos para selects vacíos */
select option:first-child {
    color: #c7c7c7;
}

select:not([value]):not(.error) {
    color: #c7c7c7;
}

/* Animación de carga */
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
