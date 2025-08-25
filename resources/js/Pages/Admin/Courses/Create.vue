<template>
    <!-- Modal Backdrop -->
    <div 
        v-if="show" 
        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
        @click.self="closeModal"
    >
        <!-- Modal Content -->
        <div class="bg-white rounded-[20px] border border-[#d3d3d3] p-6 max-w-[580px] w-full modal-content">
            <!-- Header -->
            <div class="flex flex-col gap-[20px] items-end justify-center mb-6">
                <div class="flex flex-row gap-[20px] items-start justify-end w-full">
                    <div class="text-[#434343] text-center font-nexa-bold text-[24px] leading-[28px] font-bold flex-1 flex items-end justify-center">
                        Crear curso
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
            <form @submit.prevent="saveCourse" class="modal-form">
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
                                    <button 
                                        type="button"
                                        @click="$emit('create-institution')"
                                        class="text-[#007e93] text-xs font-nexa-bold hover:underline"
                                    >
                                        + Crear nueva institución
                                    </button>
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

                                <!-- Curso (número) -->
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

                                <!-- Grado (sección) -->
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
                            <div class="flex flex-row gap-[18px]">
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
                                            :value="program.name"
                                        >
                                            {{ program.code }} - {{ program.name }}
                                        </option>
                                    </select>
                                    <span v-if="errors.associatedProgram" class="text-red-500 text-xs mt-1">{{ errors.associatedProgram }}</span>
                                </div>

                                <!-- Fecha de finalización -->
                                <div class="flex flex-col gap-[10px] w-[123px]">
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

                    <!-- Separator line -->
                    <div class="w-full h-px bg-gray-300 my-4"></div>

                    <!-- Carga de alumnos Section -->
                    <div class="flex flex-col gap-[15px]">
                        <div class="text-[#007e93] text-left font-nexa-regular text-[18px] leading-[22px] font-normal">
                            Carga de alumnos
                        </div>
                        
                        <div class="flex flex-col gap-[10px]">
                                                         <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">
                                 Adjunta la lista de alumnos
                             </label>
                            <div class="relative">
                                <input 
                                    type="file"
                                    ref="fileInput"
                                    @change="handleFileUpload"
                                    accept=".xlsx,.xls,.csv"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                />
                                <div class="bg-white rounded-lg border-dashed border border-[#5b5b5b] p-4 flex flex-col items-center justify-center h-[80px] shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] hover:border-[#007e93] hover:bg-gray-50 transition-colors">
                                    <div v-if="!form.studentsFile" class="text-[#5b5b5b] text-center font-nexa-regular text-[12px] leading-[18px] font-normal">
                                        Adjunta el archivo excel aquí
                                    </div>
                                    <div v-else class="text-center">
                                        <div class="text-[#007e93] font-nexa-bold text-[12px] leading-[18px] font-bold mb-1">
                                            📁 {{ form.studentsFile.name }}
                                        </div>
                                        <div class="text-[#5b5b5b] font-nexa-regular text-[10px] leading-[14px] font-normal">
                                            {{ formatFileSize(form.studentsFile.size) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <span v-if="errors.studentsFile" class="text-red-500 text-xs mt-1">{{ errors.studentsFile }}</span>
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
    name: "CreateCourseModal",
    components: {
        CrossIcon,
    },
    props: {
        show: {
            type: Boolean,
            default: false
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
                grade: "",
                year: "2025",
                courseNumber: "",
                contactEmail: "",
                contactPhone: "",
                associatedProgram: "",
                endDate: "",
                studentsFile: null
            }
        };
    },
    watch: {
        'form.institutionId': function(newInstitutionId) {
            if (newInstitutionId) {
                const selectedInstitution = this.institutions.find(inst => inst.id == newInstitutionId);
                if (selectedInstitution) {
                    this.form.contactEmail = selectedInstitution.email || "";
                    this.form.contactPhone = selectedInstitution.phone || "";
                }
            } else {
                // Si no hay institución seleccionada, limpiar los campos
                this.form.contactEmail = "";
                this.form.contactPhone = "";
            }
        }
    },
    methods: {
        closeModal() {
            this.$emit('close');
            this.resetForm();
        },
        handleFileUpload(event) {
            const file = event.target.files[0];
            if (file) {
                // Validate file type
                const allowedTypes = [
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                    'application/vnd.ms-excel', // .xls
                    'text/csv' // .csv
                ];
                
                if (allowedTypes.includes(file.type) || file.name.match(/\.(xlsx|xls|csv)$/i)) {
                    this.form.studentsFile = file;
                } else {
                    alert('Por favor selecciona un archivo Excel válido (.xlsx, .xls, .csv)');
                    event.target.value = '';
                }
            }
        },
        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        resetForm() {
            this.form = {
                institutionId: "",
                educationLevel: "",
                grade: "",
                year: "2025",
                courseNumber: "",
                contactEmail: "",
                contactPhone: "",
                associatedProgram: "",
                endDate: "",
                studentsFile: null
            };
            this.isSubmitting = false;
            if (this.$refs.fileInput) {
                this.$refs.fileInput.value = '';
            }
        },
        saveCourse() {
            this.isSubmitting = true;
            
            // Create FormData for file upload
            const formData = new FormData();
            Object.keys(this.form).forEach(key => {
                if (key === 'studentsFile' && this.form[key]) {
                    formData.append('students_file', this.form[key]);
                } else if (this.form[key]) {
                    formData.append(key, this.form[key]);
                }
            });
            
            router.post(route('admin.courses.store'), formData, {
                onSuccess: () => {
                    this.closeModal();
                },
                onError: (errors) => {
                    console.error('Validation errors:', errors);
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
