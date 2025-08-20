<template>
    <!-- Modal Backdrop -->
    <div 
        v-if="show" 
        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
        @click.self="closeModal"
    >
        <!-- Modal Content -->
        <div class="bg-white rounded-[20px] border border-[#d3d3d3] max-w-[1200px] w-full max-h-[90vh] flex flex-col">
            <!-- Header -->
            <div class="flex flex-row justify-center items-center relative p-8 border-b border-gray-200">
                <div class="text-[#434343] text-center font-nexa-bold text-[24px] leading-[28px] font-bold flex-1">
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
                <form @submit.prevent="saveParticipant" class="flex flex-row gap-8">
                <!-- Left Column - Datos del participante -->
                <div class="flex-1">
                    <div class="space-y-6">
                        <!-- Datos del participante -->
                        <div>
                            <h3 class="text-[#007e93] text-left font-nexa-bold text-[18px] leading-[22px] font-bold mb-4">
                                Datos del participante*
                            </h3>
                            
                            <div class="space-y-4">
                                <!-- Nombre y Apellido -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                            Primer Apellido *
                                        </label>
                                        <input 
                                            v-model="form.first_last_name"
                                            type="text"
                                            placeholder="Primer Apellido"
                                            :class="[
                                                'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                errors.first_last_name ? 'border-red-500' : 'border-[#5b5b5b]'
                                            ]"
                                        />
                                        <span v-if="errors.first_last_name" class="text-red-500 text-xs mt-1">{{ errors.first_last_name }}</span>
                                    </div>
                                    
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                            Segundo Apellido
                                        </label>
                                        <input 
                                            v-model="form.second_last_name"
                                            type="text"
                                            placeholder="Segundo Apellido (opcional)"
                                            :class="[
                                                'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                errors.second_last_name ? 'border-red-500' : 'border-[#5b5b5b]'
                                            ]"
                                        />
                                        <span v-if="errors.second_last_name" class="text-red-500 text-xs mt-1">{{ errors.second_last_name }}</span>
                                    </div>
                                </div>

                                <!-- Nombres -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                            Primer Nombre *
                                        </label>
                                        <input 
                                            v-model="form.first_name"
                                            type="text"
                                            placeholder="Primer Nombre"
                                            :class="[
                                                'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                errors.first_name ? 'border-red-500' : 'border-[#5b5b5b]'
                                            ]"
                                        />
                                        <span v-if="errors.first_name" class="text-red-500 text-xs mt-1">{{ errors.first_name }}</span>
                                    </div>
                                    
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                            Segundo Nombre
                                        </label>
                                        <input 
                                            v-model="form.second_name"
                                            type="text"
                                            placeholder="Segundo Nombre (opcional)"
                                            :class="[
                                                'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                errors.second_name ? 'border-red-500' : 'border-[#5b5b5b]'
                                            ]"
                                        />
                                        <span v-if="errors.second_name" class="text-red-500 text-xs mt-1">{{ errors.second_name }}</span>
                                    </div>
                                </div>

                                <!-- RUT y Fecha de nacimiento -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                            RUT *
                                        </label>
                                        <input 
                                            v-model="form.document_number"
                                            type="text"
                                            placeholder="000000000"
                                            @input="formatRut"
                                            @blur="validateRut"
                                            :class="[
                                                'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                errors.document_number ? 'border-red-500' : 'border-[#5b5b5b]',
                                                rutValidation.isValid === false ? 'border-red-500' : '',
                                                rutValidation.isValid === true ? 'border-green-500' : ''
                                            ]"
                                        />
                                        <span v-if="errors.document_number" class="text-red-500 text-xs mt-1">{{ errors.document_number }}</span>
                                        <span v-if="rutValidation.message" :class="[
                                            'text-xs mt-1',
                                            rutValidation.isValid === true ? 'text-green-500' : 'text-red-500'
                                        ]">{{ rutValidation.message }}</span>
                                    </div>
                                    
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                            Fecha de nacimiento *
                                        </label>
                                        <input 
                                            v-model="form.birth_date"
                                            type="date"
                                            placeholder="00/00/0000"
                                            :class="[
                                                'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                errors.birth_date ? 'border-red-500' : 'border-[#5b5b5b]'
                                            ]"
                                        />
                                        <span v-if="errors.birth_date" class="text-red-500 text-xs mt-1">{{ errors.birth_date }}</span>
                                    </div>
                                </div>

                                <!-- Email y Teléfono -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                            Email
                                        </label>
                                        <input 
                                            v-model="form.email"
                                            type="email"
                                            placeholder="Email"
                                            :class="[
                                                'w-full h-[46px] bg-white rounded border px-4 py-2 text-left font-nexa-regular text-[14px] leading-[22px] outline-none placeholder-[#c7c7c7]',
                                                errors.email ? 'border-red-500' : 'border-[#5b5b5b]'
                                            ]"
                                        />
                                        <span v-if="errors.email" class="text-red-500 text-xs mt-1">{{ errors.email }}</span>
                                    </div>
                                    
                                    <!-- Teléfono -->
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                            Teléfono
                                        </label>
                                        <div class="flex">
                                            <select 
                                                v-model="form.code_phone"
                                                :class="[
                                                    'w-[70px] h-[46px] bg-white border border-[#5b5b5b] rounded-l border-r-0 flex items-center justify-center gap-2 px-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none',
                                                    errors.phone ? 'border-red-500' : 'border-[#5b5b5b]'
                                                ]"
                                            >
                                                <option value="+56">🇨🇱</option>
                                                <option value="+54">🇦🇷</option>
                                                <option value="+51">🇵🇪</option>
                                                <option value="+598">🇺🇾</option>
                                            </select>
                                            <input 
                                                v-model="form.phone"
                                                type="tel"
                                                placeholder="9-- --- ---"
                                                :class="[
                                                    'flex-1 h-[46px] bg-white border rounded-r px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                    errors.phone ? 'border-red-500' : 'border-[#5b5b5b]'
                                                ]"
                                            />
                                        </div>
                                        <span v-if="errors.phone" class="text-red-500 text-xs mt-1">{{ errors.phone }}</span>
                                    </div>
                                </div>

                                

                                <!-- Curso -->
                                <div>
                                    <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                        Curso*
                                    </label>
                                    <select 
                                        v-model="form.course_id"
                                        :class="[
                                            'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none',
                                            errors.course_id ? 'border-red-500' : 'border-[#5b5b5b]'
                                        ]"
                                    >
                                        <option value="">Seleccione un curso</option>
                                        <option v-for="course in courses" :key="course.id" :value="course.id">
                                            {{ course.institution?.name || 'Sin institución' }} - {{ course.education_level }} {{ course.grade }}° {{ course.shift }}
                                        </option>
                                    </select>
                                    <span v-if="errors.course_id" class="text-red-500 text-xs mt-1">{{ errors.course_id }}</span>
                                </div>

                                <!-- Nivel de educación y detalles -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                            Nivel de educación *
                                        </label>
                                        <select 
                                            v-model="form.education_level"
                                                                                    :class="[
                                            'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none',
                                            errors.education_level ? 'border-red-500' : 'border-[#5b5b5b]'
                                        ]"
                                        >
                                            <option value="">Seleccione un nivel</option>
                                            <option value="preescolar">Preescolar</option>
                                            <option value="primaria">Primaria</option>
                                            <option value="secundaria">Secundaria</option>
                                            <option value="universitaria">Universitaria</option>
                                        </select>
                                        <span v-if="errors.education_level" class="text-red-500 text-xs mt-1">{{ errors.education_level }}</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                                Año*
                                            </label>
                                            <select 
                                                v-model="form.year"
                                                                                            :class="[
                                                'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none',
                                                errors.year ? 'border-red-500' : 'border-[#5b5b5b]'
                                            ]"
                                            >
                                                <option value="">2025</option>
                                                <option value="2024">2024</option>
                                                <option value="2025">2025</option>
                                                <option value="2026">2026</option>
                                            </select>
                                            <span v-if="errors.year" class="text-red-500 text-xs mt-1">{{ errors.year }}</span>
                                        </div>
                                        <div>
                                            <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                                Grado*
                                            </label>
                                            <select 
                                                v-model="form.grade"
                                                                                            :class="[
                                                'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none',
                                                errors.grade ? 'border-red-500' : 'border-[#5b5b5b]'
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
                                            <span v-if="errors.grade" class="text-red-500 text-xs mt-1">{{ errors.grade }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Condiciones médicas -->
                                <div>
                                    <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                        Condiciones medicas
                                    </label>
                                    <textarea 
                                        v-model="form.medical_conditions"
                                        rows="4"
                                        placeholder="Escriba aqui las condiciones medicas que presenta el alumno, si no tiene no es obligatorio completar."
                                        class="w-full bg-white rounded-lg border border-[#5b5b5b] px-4 py-2 text-[#c7c7c7] text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7] resize-none"
                                    ></textarea>
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
                            <h3 class="text-[#007e93] text-left font-nexa-bold text-[18px] leading-[22px] font-bold mb-4">
                                Contacto de emergencia *
                            </h3>
                            
                            <div class="space-y-4">
                                <!-- Nombre y Apellido contacto -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                            Nombre *
                                        </label>
                                        <input 
                                            v-model="form.emergency_contact_name"
                                            type="text"
                                            placeholder="Nombre"
                                            :class="[
                                                'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                errors['emergency_contacts.0.first_name'] ? 'border-red-500' : 'border-[#5b5b5b]'
                                            ]"
                                        />
                                        <span v-if="errors['emergency_contacts.0.first_name']" class="text-red-500 text-xs mt-1">{{ errors['emergency_contacts.0.first_name'] }}</span>
                                    </div>
                                    
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                            Apellido *
                                        </label>
                                        <input 
                                            v-model="form.emergency_contact_last_name"
                                            type="text"
                                            placeholder="Apellido"
                                            :class="[
                                                'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                errors['emergency_contacts.0.last_name'] ? 'border-red-500' : 'border-[#5b5b5b]'
                                            ]"
                                        />
                                        <span v-if="errors['emergency_contacts.0.last_name']" class="text-red-500 text-xs mt-1">{{ errors['emergency_contacts.0.last_name'] }}</span>
                                    </div>
                                </div>

                                <!-- Relación con el alumno -->
                                <div>
                                    <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                        Relación con el alumno*
                                    </label>
                                    <select 
                                        v-model="form.emergency_contact_relationship"
                                        :class="[
                                            'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none',
                                            errors['emergency_contacts.0.relationship'] ? 'border-red-500' : 'border-[#5b5b5b]'
                                        ]"
                                    >
                                        <option value="">Seleccione la relación con el alumno</option>
                                        <option value="padre">Padre</option>
                                        <option value="madre">Madre</option>
                                        <option value="tutor">Tutor/a</option>
                                        <option value="abuelo">Abuelo/a</option>
                                        <option value="hermano">Hermano/a</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                    <span v-if="errors['emergency_contacts.0.relationship']" class="text-red-500 text-xs mt-1">{{ errors['emergency_contacts.0.relationship'] }}</span>
                                </div>

                                <!-- Email y Teléfono contacto -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                            Email*
                                        </label>
                                        <input 
                                            v-model="form.emergency_contact_email"
                                            type="email"
                                            placeholder="Email"
                                            :class="[
                                                'w-full h-[46px] bg-white rounded border px-4 py-2 text-left font-nexa-regular text-[14px] leading-[22px] outline-none placeholder-[#c7c7c7]',
                                                errors['emergency_contacts.0.email'] ? 'border-red-500' : 'border-[#5b5b5b]'
                                            ]"
                                        />
                                        <span v-if="errors['emergency_contacts.0.email']" class="text-red-500 text-xs mt-1">{{ errors['emergency_contacts.0.email'] }}</span>
                                    </div>
                                    
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                            Teléfono*
                                        </label>
                                        <div class="flex">
                                            <select 
                                                v-model="form.emergency_contact_code_phone"
                                                :class="[
                                                    'w-[70px] h-[46px] bg-white border border-[#5b5b5b] rounded-l border-r-0 flex items-center justify-center gap-2 px-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none',
                                                    errors['emergency_contacts.0.phone'] ? 'border-red-500' : 'border-[#5b5b5b]'
                                                ]"
                                            >
                                                <option value="+56">🇨🇱</option>
                                                <option value="+54">🇦🇷</option>
                                                <option value="+51">🇵🇪</option>
                                                <option value="+598">🇺🇾</option>
                                            </select>
                                            <input 
                                                v-model="form.emergency_contact_phone"
                                                type="tel"
                                                placeholder="9-- --- ---"
                                                :class="[
                                                    'flex-1 h-[46px] bg-white border rounded-r px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                    errors['emergency_contacts.0.phone'] ? 'border-red-500' : 'border-[#5b5b5b]'
                                                ]"
                                            />
                                        </div>
                                        <span v-if="errors['emergency_contacts.0.phone']" class="text-red-500 text-xs mt-1">{{ errors['emergency_contacts.0.phone'] }}</span>
                                    </div>
                                </div>

                                <!-- Agregar otro contacto -->
                                <button 
                                    v-if="additionalContacts.length < 2"
                                    @click="addEmergencyContact"
                                    type="button"
                                    class="w-full h-[46px] bg-transparent rounded-lg border-2 border-dashed border-[#c7c7c7] flex items-center justify-center gap-3 hover:border-[#007e93] transition-colors"
                                >
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 5V19M5 12H19" stroke="#007e93" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span class="text-[#007e93] font-nexa-regular text-[14px] leading-[18px]">
                                        Agregar otro contacto de emergencia
                                    </span>
                                </button>

                                <!-- Contactos adicionales -->
                                <div v-for="(contact, index) in additionalContacts" :key="index" class="space-y-4 border-t pt-4 mt-4">
                                    <div class="flex justify-between items-center">
                                        <h4 class="text-[#007e93] text-left font-nexa-bold text-[16px] leading-[20px] font-bold">
                                            Contacto de emergencia {{ index + 2 }}
                                        </h4>
                                        <button 
                                            @click="removeEmergencyContact(index)"
                                            type="button"
                                            class="text-red-500 hover:text-red-700 p-1"
                                        >
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                                                <path d="M6 18L18 6M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    <!-- Nombre y Apellido contacto adicional -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                                Nombre *
                                            </label>
                                            <input 
                                                v-model="contact.name"
                                                type="text"
                                                placeholder="Nombre"
                                                :class="[
                                                    'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                    errors[`emergency_contacts.${index + 1}.first_name`] ? 'border-red-500' : 'border-[#5b5b5b]'
                                                ]"
                                            />
                                            <span v-if="errors[`emergency_contacts.${index + 1}.first_name`]" class="text-red-500 text-xs mt-1">{{ errors[`emergency_contacts.${index + 1}.first_name`] }}</span>
                                        </div>
                                        
                                        <div>
                                            <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                                Apellido *
                                            </label>
                                            <input 
                                                v-model="contact.last_name"
                                                type="text"
                                                placeholder="Apellido"
                                                :class="[
                                                    'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                    errors[`emergency_contacts.${index + 1}.last_name`] ? 'border-red-500' : 'border-[#5b5b5b]'
                                                ]"
                                            />
                                            <span v-if="errors[`emergency_contacts.${index + 1}.last_name`]" class="text-red-500 text-xs mt-1">{{ errors[`emergency_contacts.${index + 1}.last_name`] }}</span>
                                        </div>
                                    </div>

                                    <!-- Relación -->
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                            Relación con el alumno*
                                        </label>
                                        <select 
                                            v-model="contact.relationship"
                                            :class="[
                                                'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none',
                                                errors[`emergency_contacts.${index + 1}.relationship`] ? 'border-red-500' : 'border-[#5b5b5b]'
                                            ]"
                                        >
                                            <option value="">Seleccione la relación con el alumno</option>
                                            <option value="padre">Padre</option>
                                            <option value="madre">Madre</option>
                                            <option value="tutor">Tutor/a</option>
                                            <option value="abuelo">Abuelo/a</option>
                                            <option value="hermano">Hermano/a</option>
                                            <option value="otro">Otro</option>
                                        </select>
                                        <span v-if="errors[`emergency_contacts.${index + 1}.relationship`]" class="text-red-500 text-xs mt-1">{{ errors[`emergency_contacts.${index + 1}.relationship`] }}</span>
                                    </div>

                                    <!-- Email y Teléfono contacto adicional -->
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                                Email*
                                            </label>
                                            <input 
                                                v-model="contact.email"
                                                type="email"
                                                placeholder="Email"
                                                :class="[
                                                    'w-full h-[46px] bg-white rounded border px-4 py-2 text-left font-nexa-regular text-[14px] leading-[22px] outline-none placeholder-[#c7c7c7]',
                                                    errors[`emergency_contacts.${index + 1}.email`] ? 'border-red-500' : 'border-[#5b5b5b]'
                                                ]"
                                            />
                                            <span v-if="errors[`emergency_contacts.${index + 1}.email`]" class="text-red-500 text-xs mt-1">{{ errors[`emergency_contacts.${index + 1}.email`] }}</span>
                                        </div>
                                        
                                        <div>
                                            <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                                Teléfono*
                                            </label>
                                            <div class="flex">
                                                <select 
                                                    v-model="contact.code_phone"
                                                    :class="[
                                                        'w-[70px] h-[46px] bg-white border border-[#5b5b5b] rounded-l border-r-0 flex items-center justify-center gap-2 px-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none',
                                                        errors[`emergency_contacts.${index + 1}.phone`] ? 'border-red-500' : 'border-[#5b5b5b]'
                                                    ]"
                                                >
                                                    <option value="+56">🇨🇱</option>
                                                    <option value="+54">🇦🇷</option>
                                                    <option value="+51">🇵🇪</option>
                                                    <option value="+598">🇺🇾</option>
                                                </select>
                                                <input 
                                                    v-model="contact.phone"
                                                    type="tel"
                                                    placeholder="9-- --- ---"
                                                    :class="[
                                                        'flex-1 h-[46px] bg-white border rounded-r px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                                        errors[`emergency_contacts.${index + 1}.phone`] ? 'border-red-500' : 'border-[#5b5b5b]'
                                                    ]"
                                                />
                                            </div>
                                            <span v-if="errors[`emergency_contacts.${index + 1}.phone`]" class="text-red-500 text-xs mt-1">{{ errors[`emergency_contacts.${index + 1}.phone`] }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Separador -->
                        <hr class="border-[#c7c7c7]">

                        <!-- Programa -->
                        <div>
                            <h3 class="text-[#007e93] text-left font-nexa-bold text-[18px] leading-[22px] font-bold mb-4">
                                Programa
                            </h3>
                            
                            <div class="space-y-4">
                                <!-- Programa asociado -->
                                <div>
                                    <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                        Programa asociado
                                    </label>
                                    <input 
                                        v-model="form.associated_program"
                                        type="text"
                                        value="Ruta de lagos | Bariloche Arg-Sur de CL"
                                        class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] px-4 py-2 text-[#007e93] text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none"
                                        readonly
                                    />
                                </div>

                                <!-- Beneficio de liberado y fechas -->
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="col-span-1">
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[10px] leading-[11px] font-bold block mb-2">
                                            ¿ Tiene beneficio de Liberado ?
                                        </label>
                                        <select 
                                            v-model="form.has_benefit"
                                            class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] px-4 py-2 text-[#007e93] text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none"
                                        >
                                            <option value="No, Se reparte grupal">No, Se reparte grupal</option>
                                            <option value="Si, Es liberado">Sí, Es liberado</option>
                                            <option value="Parcial">Parcial</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold text-center block mb-2">
                                            Fecha de inicio
                                        </label>
                                        <input 
                                            v-model="form.start_date"
                                            type="date"
                                            class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] px-4 py-2 text-[#007e93] text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none text-center"
                                        />
                                    </div>
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold text-center block mb-2">
                                            Fecha de finalización
                                        </label>
                                        <input 
                                            v-model="form.end_date"
                                            type="date"
                                            class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] px-4 py-2 text-[#007e93] text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none text-center"
                                        />
                                    </div>
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
                    <div v-if="isSubmitting" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                    <div class="text-white text-center font-nexa-bold text-[16px] leading-[22px] font-bold">
                        {{ isSubmitting ? 'Guardando...' : 'Guardar cambios' }}
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import { CrossIcon } from "@/Components/Icons";
import { router } from "@inertiajs/vue3";

export default {
    name: "CreateParticipantModal",
    components: {
        CrossIcon,
    },
    props: {
        show: {
            type: Boolean,
            default: false
        },
        courses: {
            type: Array,
            default: () => []
        },
        institutions: {
            type: Array,
            default: () => []
        },
        errors: {
            type: Object,
            default: () => ({})
        }
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
                birth_date: "",
                email: "",
                phone: "",
                code_phone: "+56",
                course_id: "",
                education_level: "",
                year: "2025",
                grade: "",
                medical_conditions: "",
                individual_price: 4000, // Precio por defecto
                price_adjustments: 0,
                adjustment_reason: "",
                country: "Chile",
                document_type: "RUT",
                address: "",
                dietary_restrictions: "",
                
                // Contacto de emergencia principal
                emergency_contact_name: "",
                emergency_contact_relationship: "",
                emergency_contact_email: "",
                emergency_contact_phone: "",
                emergency_contact_code_phone: "+56",
                emergency_contact_country: "Chile",
                emergency_contact_birth_date: "",
                emergency_contact_address: "",
                
                // Programa
                associated_program: "Ruta de lagos | Bariloche Arg-Sur de CL",
                has_benefit: "No, Se reparte grupal",
                start_date: "2025-09-01",
                end_date: "2025-09-01"
            },
            additionalContacts: [],
            rutValidation: {
                isValid: null,
                message: ""
            },

        };
    },
    methods: {
        closeModal() {
            this.$emit('close');
            this.resetForm();
        },
        
        resetForm() {
            this.form = {
                first_last_name: "",
                second_last_name: "",
                first_name: "",
                second_name: "",
                document_number: "",
                birth_date: "",
                email: "",
                phone: "",
                code_phone: "+56",
                course_id: "",
                education_level: "",
                year: "2025",
                grade: "",
                medical_conditions: "",
                individual_price: 4000,
                price_adjustments: 0,
                adjustment_reason: "",
                country: "Chile",
                document_type: "RUT",
                address: "",
                dietary_restrictions: "",
                
                emergency_contact_name: "",
                emergency_contact_relationship: "",
                emergency_contact_email: "",
                emergency_contact_phone: "",
                emergency_contact_code_phone: "+56",
                emergency_contact_country: "Chile",
                emergency_contact_birth_date: "",
                emergency_contact_address: "",
                
                associated_program: "Ruta de lagos | Bariloche Arg-Sur de CL",
                has_benefit: "No, Se reparte grupal",
                start_date: "2025-09-01",
                end_date: "2025-09-01"
            };
            this.additionalContacts = [];
            this.isSubmitting = false;
            this.rutValidation = {
                isValid: null,
                message: ""
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
                    birth_date: this.form.birth_date,
                    email: this.form.email,
                    phone: this.form.phone,
                    code_phone: this.form.code_phone,
                    course_id: this.form.course_id,
                    education_level: this.form.education_level,
                    year: this.form.year,
                    grade: this.form.grade,
                    individual_price: this.form.individual_price,
                    price_adjustments: this.form.price_adjustments,
                    adjustment_reason: this.form.adjustment_reason,
                    country: this.form.country,
                    document_type: this.form.document_type,
                    address: this.form.address,
                    dietary_restrictions: this.form.dietary_restrictions,
                };

                // Preparar contactos de emergencia
                const emergencyContacts = [
                    {
                        name: this.form.emergency_contact_name,
                        email: this.form.emergency_contact_email,
                        code_phone: this.form.emergency_contact_code_phone,
                        phone: this.form.emergency_contact_phone,
                        country: this.form.emergency_contact_country,
                        birth_date: this.form.emergency_contact_birth_date || null,
                        address: this.form.emergency_contact_address || null,
                        relationship: this.form.emergency_contact_relationship,
                    }
                ];

                // Agregar contactos adicionales
                this.additionalContacts.forEach(contact => {
                    emergencyContacts.push({
                        name: contact.name,
                        email: contact.email,
                        code_phone: contact.code_phone || "+56",
                        phone: contact.phone,
                        country: "Chile",
                        birth_date: null,
                        address: null,
                        relationship: contact.relationship,
                    });
                });

                // Preparar condiciones médicas (como string, no array)
                const medicalConditions = this.form.medical_conditions || '';

                // Enviar datos al servidor
                await router.post(route('admin.participants.store'), {
                    ...participantData,
                    emergency_contacts: emergencyContacts,
                    medical_conditions: medicalConditions
                }, {
                    onSuccess: () => {
                        this.closeModal();
                    },
                    onError: (errors) => {
                        console.error('Errores de validación:', errors);
                    }
                });

            } catch (error) {
                console.error('Error al guardar participante:', error);
            } finally {
                this.isSubmitting = false;
            }
        },
        
        addEmergencyContact() {
            if (this.additionalContacts.length < 2) {
                this.additionalContacts.push({
                    name: "",
                    relationship: "",
                    email: "",
                    phone: "",
                    code_phone: "+56"
                });
            }
        },
        
        removeEmergencyContact(index) {
            this.additionalContacts.splice(index, 1);
        },

        formatRut() {
            // Remover todos los caracteres no numéricos excepto K
            let rut = this.form.document_number.replace(/[^0-9kK]/g, '');
            
            if (rut.length > 0) {
                rut = rut.toUpperCase();
                
                // Si tiene más de 1 carácter, separar cuerpo y dígito verificador
                if (rut.length > 1) {
                    const body = rut.slice(0, -1);
                    const dv = rut.slice(-1);
                    
                    // Formatear el cuerpo con puntos
                    let formattedBody = '';
                    for (let i = body.length - 1, j = 0; i >= 0; i--, j++) {
                        if (j > 0 && j % 3 === 0) {
                            formattedBody = '.' + formattedBody;
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
            const rut = this.form.document_number.replace(/\./g, '').replace(/-/g, '');
            
            if (rut.length === 0) {
                this.rutValidation.isValid = null;
                this.rutValidation.message = '';
                return;
            }
            
            // Validar formato básico
            if (!/^[0-9]+[0-9kK]$/.test(rut)) {
                this.rutValidation.isValid = false;
                this.rutValidation.message = 'Formato de RUT inválido';
                return;
            }
            
            // Separar cuerpo y dígito verificador
            const body = rut.slice(0, -1);
            const dv = rut.slice(-1).toUpperCase();
            
            // Validar que el cuerpo tenga al menos 7 dígitos
            if (body.length < 7) {
                this.rutValidation.isValid = false;
                this.rutValidation.message = 'RUT debe tener al menos 7 dígitos';
                return;
            }
            
            // Calcular dígito verificador
            const dvCalculado = this.calculateDv(body);
            
            // Comparar dígitos verificadores
            this.rutValidation.isValid = dv === dvCalculado;
            this.rutValidation.message = this.rutValidation.isValid ? 'RUT válido' : 'RUT inválido';
        },

        calculateDv(body) {
            let sum = 0;
            let factor = 2;
            for (let i = body.length - 1; i >= 0; i--) {
                sum += body[i] * factor;
                factor = factor === 7 ? 2 : factor + 1;
            }
            const dv = 11 - (sum % 11);
            return dv === 10 ? 'K' : dv === 11 ? '0' : dv.toString();
        },

        
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

/* Estilos para campos con error */
input.error, select.error, textarea.error {
    border-color: #ef4444 !important;
    color: #ef4444 !important;
}

/* Estilos para inputs cuando tienen contenido */
input:not(:placeholder-shown):not(.error),
select:not(.error),
textarea:not(:placeholder-shown):not(.error) {
    color: var(--Colores-OP2-Turquesa, #007E93) !important;
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
