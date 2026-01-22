<template>
    <!-- Modal Backdrop - No se cierra al hacer clic afuera -->
    <div
        v-if="show"
        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
    >
        <!-- Modal Content - @click.stop previene propagación -->
        <div @click.stop class="bg-white rounded-[20px] border border-[#d3d3d3] p-6 max-w-[500px] w-full modal-content">
            <!-- Header -->
            <div class="flex flex-col gap-[20px] items-end justify-center mb-6">
                <div class="flex flex-row gap-[20px] items-start justify-end w-full">
                    <div class="text-[#434343] font-nexa-bold text-[24px] leading-[28px] font-bold flex-1 text-center">
                        Crear institución
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
            </div>

            <!-- Content -->
            <form @submit.prevent="saveInstitution" class="modal-form">
                <div class="flex flex-col gap-4">
                    <!-- Código de institución -->
                    <div class="flex flex-col gap-[10px]">
                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">
                            Código
                        </label>
                        <input
                            v-model="form.code"
                            type="text"
                            placeholder="Ej: INST001"
                            :class="[
                                'w-full h-[46px] bg-white rounded-lg border p-4 text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none placeholder-[#c7c7c7]',
                                localErrors.code ? 'border-red-500' : 'border-[#5b5b5b]'
                            ]"
                        />
                        <span v-if="localErrors.code" class="text-red-500 text-xs mt-1">{{ getError('code') }}</span>
                    </div>

                    <!-- Nombre de institución -->
                    <div class="flex flex-col gap-[10px]">
                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">
                            Nombre de institución *
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Escriba el nombre de la institución"
                            :class="[
                                'w-full h-[46px] bg-white rounded-lg border p-4 text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none placeholder-[#c7c7c7]',
                                localErrors.name ? 'border-red-500' : 'border-[#5b5b5b]'
                            ]"
                        />
                        <span v-if="localErrors.name" class="text-red-500 text-xs mt-1">{{ getError('name') }}</span>
                    </div>

                    <!-- Tipo de institución -->
                    <div class="flex flex-col gap-[10px]">
                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">
                            Tipo de institución *
                        </label>
                        <select
                            v-model="form.type"
                            :class="[
                                'bg-white rounded-lg border p-4 text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none',
                                localErrors.type ? 'border-red-500' : 'border-[#5b5b5b]'
                            ]"
                        >
                            <option value="">Seleccione un tipo</option>
                            <option value="school">Escuela</option>
                            <option value="colegio">Colegio</option>
                            <option value="liceo">Liceo</option>
                            <option value="university">Universidad</option>
                            <option value="other">Otro</option>
                        </select>
                        <span v-if="localErrors.type" class="text-red-500 text-xs mt-1">{{ getError('type') }}</span>
                    </div>

                    <!-- Dirección -->
                    <div class="flex flex-col gap-[10px]">
                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">
                            Dirección
                        </label>
                        <input 
                            v-model="form.address"
                            type="text"
                            placeholder="Dirección de la institución"
                            :class="[
                                'w-full h-[46px] bg-white rounded-lg border p-4 text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none placeholder-[#c7c7c7]',
                                localErrors.address ? 'border-red-500' : 'border-[#5b5b5b]'
                            ]"
                        />
                        <span v-if="localErrors.address" class="text-red-500 text-xs mt-1">{{ getError('address') }}</span>
                    </div>

                    <!-- Contact row -->
                    <div class="flex flex-row gap-[18px]">
                        <!-- Email -->
                        <div class="flex flex-col gap-[10px] flex-1">
                            <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">
                                Email
                            </label>
                            <input 
                                v-model="form.email"
                                type="email"
                                placeholder="email@institucion.com"
                                :class="[
                                    'w-full h-[46px] bg-white rounded-lg border p-4 text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none placeholder-[#c7c7c7]',
                                    localErrors.email ? 'border-red-500' : 'border-[#5b5b5b]'
                                ]"
                            />
                            <span v-if="localErrors.email" class="text-red-500 text-xs mt-1">{{ getError('email') }}</span>
                        </div>

                        <!-- Teléfono -->
                        <div class="flex flex-col gap-[10px] flex-1">
                            <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">
                                Teléfono
                            </label>
                            <input 
                                v-model="form.phone"
                                type="tel"
                                placeholder="000000000"
                                :class="[
                                    'w-full h-[46px] bg-white rounded-lg border p-4 text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none placeholder-[#c7c7c7]',
                                    localErrors.phone ? 'border-red-500' : 'border-[#5b5b5b]'
                                ]"
                            />
                            <span v-if="localErrors.phone" class="text-red-500 text-xs mt-1">{{ getError('phone') }}</span>
                        </div>
                    </div>

                    <!-- Sitio web -->
                    <div class="flex flex-col gap-[10px]">
                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">
                            Sitio web
                        </label>
                        <input 
                            v-model="form.website"
                            type="url"
                            placeholder="https://www.institucion.com"
                            :class="[
                                'w-full h-[46px] bg-white rounded-lg border p-4 text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none placeholder-[#c7c7c7]',
                                localErrors.website ? 'border-red-500' : 'border-[#5b5b5b]'
                            ]"
                        />
                        <span v-if="localErrors.website" class="text-red-500 text-xs mt-1">{{ getError('website') }}</span>
                    </div>
                </div>

                <!-- Save button -->
                <div class="mt-6">
                    <button 
                        type="submit"
                        :disabled="isSubmitting"
                        class="bg-[#007e93] rounded-[112.89px] px-[18px] py-[14px] flex flex-row gap-[11.29px] items-center justify-center w-full hover:bg-[#006580] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <div v-if="isSubmitting" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                        <div class="text-white text-center font-nexa-bold text-[16px] leading-[22px] font-bold">
                            {{ isSubmitting ? 'Guardando...' : 'Crear institución' }}
                        </div>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import { CrossIcon } from "@/Components/Icons";
import axios from 'axios';

export default {
    name: "CreateInstitutionModal",
    components: {
        CrossIcon,
    },
    props: {
        show: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            isSubmitting: false,
            localErrors: {},
            form: {
                code: "",
                name: "",
                type: "",
                address: "",
                email: "",
                phone: "",
                website: ""
            }
        };
    },
    methods: {
        closeModal() {
            this.$emit('close');
            this.resetForm();
        },
        resetForm() {
            this.form = {
                code: "",
                name: "",
                type: "",
                address: "",
                email: "",
                phone: "",
                website: ""
            };
            this.localErrors = {};
            this.isSubmitting = false;
        },
        getError(field) {
            if (this.localErrors[field]) {
                return Array.isArray(this.localErrors[field])
                    ? this.localErrors[field][0]
                    : this.localErrors[field];
            }
            return '';
        },
        clearErrors() {
            this.localErrors = {};
        },
        async saveInstitution() {
            this.isSubmitting = true;
            this.clearErrors();

            try {
                const response = await axios.post(route('admin.institutions.store'), this.form);

                if (response.data.success) {
                    this.$emit('institution-created', response.data.institution);
                    this.$emit('success', 'Institución creada exitosamente');
                    this.resetForm();
                    this.$emit('close');
                }
            } catch (error) {
                console.error('Error creating institution:', error);
                if (error.response && error.response.data.errors) {
                    this.localErrors = error.response.data.errors;
                } else if (error.response && error.response.data.message) {
                    this.$emit('error', error.response.data.message);
                } else {
                    this.$emit('error', 'Error al crear la institución');
                }
            } finally {
                this.isSubmitting = false;
            }
        }
    }
};
</script>

<style scoped>
.font-nexa-bold {
    font-family: 'Nexa-Bold', sans-serif;
}

.font-nexa-regular {
    font-family: 'Nexa-Regular', sans-serif;
}

/* Input text color when typing */
input:not([type="file"]), select {
    color: var(--Colores-OP2-Turquesa, #007E93);
    font-family: Nexa;
    font-size: 12px;
    font-weight: 700;
    line-height: 18px;
}

/* Placeholder color */
input::placeholder {
    color: #c7c7c7;
}

/* Modal responsive styles */
.modal-content {
    max-height: calc(100vh - 2rem);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-form {
    flex: 1;
    overflow-y: auto;
    padding-right: 0.5rem;
}

/* Custom scrollbar for modal */
.modal-form::-webkit-scrollbar {
    width: 6px;
}

.modal-form::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.modal-form::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.modal-form::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Responsive adjustments */
@media (max-height: 700px) {
    .modal-content {
        max-height: calc(100vh - 1rem);
    }
    
    .modal-content .p-6 {
        padding: 1rem;
    }
    
    .modal-content .mb-6 {
        margin-bottom: 1rem;
    }
    
    .modal-content .mt-6 {
        margin-top: 1rem;
    }
    
    .modal-content .gap-4 {
        gap: 0.75rem;
    }
    
    .modal-content .gap-\[20px\] {
        gap: 1rem;
    }
    
    .modal-content .gap-\[15px\] {
        gap: 0.75rem;
    }
}
</style> 