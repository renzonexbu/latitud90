<template>
    <!-- Modal Backdrop -->
    <div 
        v-if="show" 
        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
        @click.self="closeModal"
    >
        <!-- Modal Content -->
        <div class="bg-white rounded-[20px] border border-[#d3d3d3] p-8 max-w-[580px] w-full max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex flex-col gap-[26px] items-end justify-center mb-8">
                <div class="flex flex-row gap-[26px] items-start justify-end w-full">
                    <div class="text-[#434343] text-center font-nexa-bold text-[24px] leading-[28px] font-bold w-[385px] h-[31px] flex items-end justify-center">
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
            <div class="flex flex-col gap-5">
                <!-- Datos Section -->
                <div class="flex flex-col gap-[19px]">
                    <div class="text-[#007e93] text-left font-nexa-bold text-[18px] leading-[22px] font-bold">
                        Datos
                    </div>
                    
                    <div class="flex flex-col gap-[26px]">
                        <!-- Nombre de institución -->
                        <div class="flex flex-col gap-[10px]">
                            <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">
                                Nombre de institución
                            </label>
                            <input 
                                v-model="form.institutionName"
                                type="text"
                                placeholder="Escriba el nombre de la institución"
                                class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] p-4 text-[#007e93] text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none placeholder-[#007e93]"
                            />
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
                                    class="bg-white rounded-lg border border-[#5b5b5b] p-4 text-[#007e93] text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none"
                                >
                                    <option value="">Seleccione un nivel</option>
                                    <option value="preescolar">Preescolar</option>
                                    <option value="primaria">Primaria</option>
                                    <option value="secundaria">Secundaria</option>
                                    <option value="universitaria">Universitaria</option>
                                </select>
                            </div>

                            <!-- Año -->
                            <div class="flex flex-col gap-[7px] flex-shrink-0">
                                <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">
                                    Año
                                </label>
                                <select 
                                    v-model="form.year"
                                    class="bg-white rounded-lg border border-[#5b5b5b] p-4 text-[#007e93] text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none w-[80px]"
                                >
                                    <option value="">2025</option>
                                    <option value="2023">2023</option>
                                    <option value="2024">2024</option>
                                    <option value="2025">2025</option>
                                    <option value="2026">2026</option>
                                </select>
                            </div>

                            <!-- Grado -->
                            <div class="flex flex-col gap-[7px] flex-shrink-0">
                                <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">
                                    Grado
                                </label>
                                <select 
                                    v-model="form.grade"
                                    class="bg-white rounded-lg border border-[#5b5b5b] p-4 text-[#007e93] text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none w-[70px]"
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

                            <!-- Turno -->
                            <div class="flex flex-col gap-[7px] flex-shrink-0">
                                <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">
                                    Turno
                                </label>
                                <select 
                                    v-model="form.shift"
                                    class="bg-white rounded-lg border border-[#5b5b5b] p-4 text-[#007e93] text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none w-[80px]"
                                >
                                    <option value="">---</option>
                                    <option value="mañana">Mañana</option>
                                    <option value="tarde">Tarde</option>
                                    <option value="noche">Noche</option>
                                </select>
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
                                    class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] p-4 text-[#007e93] text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none placeholder-[#007e93]"
                                />
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
                                    class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] p-4 text-[#007e93] text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none placeholder-[#007e93]"
                                />
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
                                    class="bg-white rounded-lg border border-[#5b5b5b] p-4 text-[#007e93] text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none appearance-none"
                                >
                                    <option value="">Seleccione un programa</option>
                                    <option value="ruta-lagos">Ruta de lagos | Bariloche Arg-Sur de CL</option>
                                </select>
                            </div>

                            <!-- Fecha de finalización -->
                            <div class="flex flex-col gap-[10px] w-[123px]">
                                <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">
                                    Fecha de finalización
                                </label>
                                <input 
                                    v-model="form.endDate"
                                    type="date"
                                    class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] p-4 text-[#007e93] text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Separator line -->
                <div class="w-full h-px bg-gray-300 my-5"></div>

                <!-- Carga de alumnos Section -->
                <div class="flex flex-col gap-[19px]">
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
                            <div class="bg-white rounded-lg border-dashed border border-[#5b5b5b] p-4 flex flex-col items-center justify-center h-[107px] shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] hover:border-[#007e93] hover:bg-gray-50 transition-colors">
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
                    </div>
                </div>
            </div>

            <!-- Save button -->
            <div class="mt-8">
                <button 
                    @click="saveChanges"
                    class="bg-[#007e93] rounded-[112.89px] px-[18px] py-[14px] flex flex-row gap-[11.29px] items-center justify-center w-full hover:bg-[#006580] transition-colors"
                >
                    <div class="text-white text-center font-nexa-bold text-[16px] leading-[22px] font-bold">
                        Guardar cambios
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { CrossIcon } from "@/Components/Icons";

export default {
    name: "CreateCourseModal",
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
            form: {
                institutionName: "",
                educationLevel: "",
                year: "2025",
                grade: "",
                shift: "",
                contactEmail: "",
                contactPhone: "",
                associatedProgram: "",
                endDate: "",
                studentsFile: null
            }
        };
    },
    methods: {
        closeModal() {
            this.$emit('close');
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
        removeFile() {
            this.form.studentsFile = null;
            this.$refs.fileInput.value = '';
        },
        saveChanges() {
            console.log('Saving course:', this.form);
            
            // Create FormData for file upload
            const formData = new FormData();
            Object.keys(this.form).forEach(key => {
                if (key === 'studentsFile' && this.form[key]) {
                    formData.append('students_file', this.form[key]);
                } else if (this.form[key]) {
                    formData.append(key, this.form[key]);
                }
            });
            
            // Here you would typically send the FormData to the server
            console.log('FormData ready for upload');
            this.closeModal();
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
</style>
