<template>
    <!-- Modal Backdrop -->
    <div 
        v-if="show" 
        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
        @click.self="closeModal"
    >
        <!-- Modal Content -->
        <div class="bg-white rounded-[20px] border border-[#d3d3d3] p-6 max-w-[800px] w-full modal-content">
            <!-- Header -->
            <div class="flex flex-col gap-[20px] items-end justify-center mb-6">
                <div class="flex flex-row gap-[20px] items-start justify-end w-full">
                    <div class="text-[#434343] text-center font-nexa-bold text-[24px] leading-[28px] font-bold flex-1 flex items-end justify-center">
                        Editar curso
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
            <form @submit.prevent="updateCourse" class="modal-form">
                <div class="flex flex-col gap-4">
                    <!-- Datos Section -->
                    <div class="flex flex-col gap-[15px]">
                        <div class="text-[#007e93] text-left font-nexa-bold text-[18px] leading-[22px] font-bold">
                            Datos
                        </div>
                        
                        <div class="flex flex-col gap-[20px]">
                            <!-- Institución -->
                            <div class="flex flex-col gap-[10px]">
                                <div class="flex justify-between items-center">
                                    <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">
                                        Institución
                                    </label>
                                </div>
                                <select 
                                    v-model="form.institutionId"
                                    :class="[
                                        'bg-white rounded-lg border p-4 text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none',
                                        errors.institutionId ? 'border-red-500' : 'border-[#5b5b5b]'
                                    ]"
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
                                <span v-if="errors.institutionId" class="text-red-500 text-xs mt-1">{{ errors.institutionId }}</span>
                            </div>

                            <!-- Row with dropdowns -->
                            <div class="flex flex-row gap-3">
                                <!-- Nivel educación -->
                                <div class="flex flex-col gap-[7px] flex-1">
                                    <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">
                                        Nivel de educación
                                    </label>
                                    <select 
                                        v-model="form.educationLevel"
                                        :class="[
                                            'bg-white rounded-lg border p-4 text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none',
                                            errors.educationLevel ? 'border-red-500' : 'border-[#5b5b5b]'
                                        ]"
                                    >
                                        <option value="">Seleccione un nivel</option>
                                         <option value="preescolar">Preescolar</option>
                                         <option value="basica">Básica</option>
                                         <option value="media">Media</option>
                                        <option value="universitaria">Universitaria</option>
                                    </select>
                                    <span v-if="errors.educationLevel" class="text-red-500 text-xs mt-1">{{ errors.educationLevel }}</span>
                                </div>

                                <!-- Año -->
                                <div class="flex flex-col gap-[7px] flex-shrink-0">
                                    <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">
                                        Año
                                    </label>
                                    <select 
                                        v-model="form.year"
                                        :class="[
                                            'bg-white rounded-lg border p-4 text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none w-[80px]',
                                            errors.year ? 'border-red-500' : 'border-[#5b5b5b]'
                                        ]"
                                    >
                                        <option value="">2025</option>
                                        <option value="2023">2023</option>
                                        <option value="2024">2024</option>
                                        <option value="2025">2025</option>
                                        <option value="2026">2026</option>
                                    </select>
                                    <span v-if="errors.year" class="text-red-500 text-xs mt-1">{{ errors.year }}</span>
                                </div>

                                <!-- Curso -->
                                <div class="flex flex-col gap-[7px] flex-shrink-0">
                                    <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">
                                        Curso
                                    </label>
                                    <select 
                                        v-model="form.courseNumber"
                                        :class="[
                                            'bg-white rounded-lg border p-4 text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none w-[70px]',
                                            errors.courseNumber ? 'border-red-500' : 'border-[#5b5b5b]'
                                        ]"
                                    >
                                        <option value="">---</option>
                                        <option value="1">1°</option>
                                        <option value="2">2°</option>
                                        <option value="3">3°</option>
                                        <option value="4">4°</option>
                                        <option value="5">5°</option>
                                        <option value="6">6°</option>
                                    </select>
                                    <span v-if="errors.courseNumber" class="text-red-500 text-xs mt-1">{{ errors.courseNumber }}</span>
                                </div>

                                <!-- Grado -->
                                <div class="flex flex-col gap-[7px] flex-shrink-0">
                                    <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">
                                        Grado
                                    </label>
                                    <select 
                                        v-model="form.grade"
                                        :class="[
                                            'bg-white rounded-lg border p-4 text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none w-[70px]',
                                            errors.grade ? 'border-red-500' : 'border-[#5b5b5b]'
                                        ]"
                                    >
                                        <option value="">---</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                        <option value="E">E</option>
                                    </select>
                                    <span v-if="errors.grade" class="text-red-500 text-xs mt-1">{{ errors.grade }}</span>
                                </div>
                            </div>

                            <!-- Contact row -->
                            <div class="flex flex-row gap-[30px]">
                                <!-- Mail de contacto -->
                                <div class="flex flex-col gap-[10px] flex-1">
                                    <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">
                                        Mail de contacto
                                    </label>
                                    <input 
                                        v-model="form.contactEmail"
                                        type="email"
                                        placeholder="Mail@gmail.com"
                                        :class="[
                                            'w-full h-[46px] bg-white rounded-lg border p-4 text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none placeholder-[#c7c7c7]',
                                            errors.contactEmail ? 'border-red-500' : 'border-[#5b5b5b]'
                                        ]"
                                    />
                                    <span v-if="errors.contactEmail" class="text-red-500 text-xs mt-1">{{ errors.contactEmail }}</span>
                                </div>

                                <!-- Número de contacto -->
                                <div class="flex flex-col gap-[10px] flex-1">
                                    <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">
                                        Número de contacto
                                    </label>
                                    <input 
                                        v-model="form.contactPhone"
                                        type="tel"
                                        placeholder="000000000"
                                        :class="[
                                            'w-full h-[46px] bg-white rounded-lg border p-4 text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none placeholder-[#c7c7c7]',
                                            errors.contactPhone ? 'border-red-500' : 'border-[#5b5b5b]'
                                        ]"
                                    />
                                    <span v-if="errors.contactPhone" class="text-red-500 text-xs mt-1">{{ errors.contactPhone }}</span>
                                </div>
                            </div>

                            <!-- Program and date row -->
                            <div class="flex flex-row gap-[18px]">
                                <!-- Programa asociado -->
                                <div class="flex flex-col gap-[10px] flex-1">
                                    <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">
                                        Programa asociado
                                    </label>
                                    <select 
                                        v-model="form.associatedProgram"
                                        :class="[
                                            'bg-white rounded-lg border p-4 text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none',
                                            errors.associatedProgram ? 'border-red-500' : 'border-[#5b5b5b]'
                                        ]"
                                    >
                                        <option value="">Seleccione un programa</option>
                                        <option 
                                            v-for="program in programs" 
                                            :key="program.id" 
                                            :value="program.id"
                                        >
                                            {{ program.name }}
                                        </option>
                                    </select>
                                    <span v-if="errors.associatedProgram" class="text-red-500 text-xs mt-1">{{ errors.associatedProgram }}</span>
                                </div>

                                <!-- Fecha de finalización -->
                                <div class="flex flex-col gap-[10px] w-[150px]">
                                    <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">
                                        Fecha de finalización
                                    </label>
                                    <input 
                                        v-model="form.endDate"
                                        type="date"
                                        :class="[
                                            'w-full h-[46px] bg-white rounded-lg border p-4 text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none',
                                            errors.endDate ? 'border-red-500' : 'border-[#5b5b5b]'
                                        ]"
                                    />
                                    <span v-if="errors.endDate" class="text-red-500 text-xs mt-1">{{ errors.endDate }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save button -->
                <div class="mt-6 flex-shrink-0">
                    <button 
                        type="submit"
                        :disabled="isSubmitting"
                        class="bg-[#007e93] rounded-[112.89px] px-[18px] py-[14px] flex flex-row gap-[11.29px] items-center justify-center w-full hover:bg-[#006580] transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <div v-if="isSubmitting" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                        <div class="text-white text-center font-nexa-bold text-[16px] leading-[22px] font-bold">
                            {{ isSubmitting ? 'Guardando...' : 'Guardar cambios' }}
                        </div>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import { CrossIcon } from "@/Components/Icons";
import { router } from '@inertiajs/vue3';

export default {
    name: "EditCourseModal",
    components: {
        CrossIcon,
    },
    props: {
        show: {
            type: Boolean,
            default: false
        },
        course: {
            type: Object,
            default: null
        },
        errors: {
            type: Object,
            default: () => ({})
        },
        programs: {
            type: Array,
            default: () => []
        },
        institutions: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            isSubmitting: false,
            form: {
                institutionId: "",
                educationLevel: "",
                year: "2025",
                courseNumber: "",
                grade: "",
                contactEmail: "",
                contactPhone: "",
                associatedProgram: "",
                endDate: ""
            }
        };
    },
    watch: {
        course: {
            handler(newCourse) {
                if (newCourse) {
                    this.loadCourseData();
                }
            },
            immediate: true,
            deep: true
        },
        'form.institutionId': function(newInstitutionId) {
            if (newInstitutionId) {
                const selectedInstitution = this.institutions.find(inst => String(inst.id) === String(newInstitutionId));
                if (selectedInstitution) {
                    // Solo llenar los campos si están vacíos o si el usuario no los ha modificado manualmente
                    if (!this.form.contactEmail || this.form.contactEmail === this.course?.contact_email) {
                        this.form.contactEmail = selectedInstitution.email || "";
                    }
                    if (!this.form.contactPhone || this.form.contactPhone === this.course?.contact_phone) {
                        this.form.contactPhone = selectedInstitution.phone || "";
                    }
                }
            }
            // No limpiar los campos cuando se deselecciona la institución
        }
    },
    mounted() {
        // Cargar datos iniciales si el curso ya está disponible
        if (this.course) {
            this.loadCourseData();
        }
    },
    methods: {
        loadCourseData() {
            if (this.course) {
                
                // Convertir institution_id a string para comparación correcta
                const institutionId = this.course.institution_id ? String(this.course.institution_id) : "";
                
                this.form = {
                    institutionId: institutionId,
                    educationLevel: this.course.education_level || "",
                    year: this.course.year || "2025",
                    courseNumber: this.course.course_number || "",
                    grade: this.course.grade || "",
                    contactEmail: this.course.contact_email || "",
                    contactPhone: this.course.contact_phone || "",
                    associatedProgram: this.course.program_id ? String(this.course.program_id) : "",
                    endDate: this.formatDateForInput(this.course.end_date) || ""
                };
            }
        },
        closeModal() {
            this.$emit('close');
            this.resetForm();
        },
        resetForm() {
            this.form = {
                institutionId: "",
                educationLevel: "",
                year: "2025",
                grade: "",
                shift: "",
                contactEmail: "",
                contactPhone: "",
                associatedProgram: "",
                endDate: ""
            };
            this.isSubmitting = false;
        },
        formatDateForInput(dateString) {
            if (!dateString) {
                return "";
            }
            const date = new Date(dateString);
            if (isNaN(date.getTime())) {
                return "";
            }
            const formattedDate = date.toISOString().split('T')[0];
            return formattedDate; // Formato YYYY-MM-DD para input type="date"
        },
        updateCourse() {
            this.isSubmitting = true;
            
            // Create FormData for the update
            const formData = new FormData();
            
            // Agregar todos los campos del formulario
            formData.append('institutionId', this.form.institutionId || '');
            formData.append('educationLevel', this.form.educationLevel || '');
            formData.append('year', this.form.year || '');
            formData.append('courseNumber', this.form.courseNumber || '');
            formData.append('grade', this.form.grade || '');
            formData.append('contactEmail', this.form.contactEmail || (this.course.contact_email || ''));
            formData.append('contactPhone', this.form.contactPhone || (this.course.contact_phone || ''));
            formData.append('associatedProgram', this.form.associatedProgram || '');
            formData.append('endDate', this.form.endDate || '');
            
            // Add _method for PUT request
            formData.append('_method', 'PUT');
            
            router.post(route('admin.courses.update', this.course.id), formData, {
                onSuccess: (response) => {
                    this.closeModal();
                    // Recargar la página para mostrar los cambios actualizados
                    window.location.reload();
                },
                onError: (errors) => {
                    this.isSubmitting = false;
                },
                onFinish: () => {
                    this.isSubmitting = false;
                }
            });
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