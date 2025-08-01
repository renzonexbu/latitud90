<template>
    <!-- Modal Backdrop -->
    <div 
        v-if="show" 
        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
        @click.self="closeModal"
    >
        <!-- Modal Content -->
        <div class="bg-white rounded-[20px] border border-[#d3d3d3] p-8 max-w-[1200px] w-full max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex flex-row justify-center items-center relative mb-8">
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
            <div class="flex flex-row gap-8">
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
                                            Nombre *
                                        </label>
                                        <input 
                                            v-model="form.first_name"
                                            type="text"
                                            placeholder="Nombre"
                                            class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] px-4 py-2 text-[#c7c7c7] text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
                                        />
                                    </div>
                                    
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                            Apellido *
                                        </label>
                                        <input 
                                            v-model="form.last_name"
                                            type="text"
                                            placeholder="Apellido"
                                            class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] px-4 py-2 text-[#c7c7c7] text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
                                        />
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
                                            class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] px-4 py-2 text-[#c7c7c7] text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
                                        />
                                    </div>
                                    
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                            Fecha de nacimiento *
                                        </label>
                                        <input 
                                            v-model="form.birth_date"
                                            type="date"
                                            placeholder="00/00/0000"
                                            class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] px-4 py-2 text-[#c7c7c7] text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
                                        />
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
                                            class="w-full h-[46px] bg-white rounded border border-[#5b5b5b] px-4 py-2 text-[#c7c7c7] text-left font-nexa-regular text-[14px] leading-[22px] outline-none placeholder-[#c7c7c7]"
                                        />
                                    </div>
                                    
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                            Numero de telefono
                                        </label>
                                        <div class="flex">
                                            <div class="w-[70px] h-[46px] bg-white border border-[#5b5b5b] rounded-l border-r-0 flex items-center justify-center gap-2 px-2">
                                                <div class="w-6 h-6 bg-red-500 rounded-sm flex items-center justify-center">
                                                    🇨🇱
                                                </div>
                                                <svg class="w-3 h-3" viewBox="0 0 12 12" fill="none">
                                                    <path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </div>
                                            <input 
                                                v-model="form.phone"
                                                type="tel"
                                                placeholder="+56 9-- --- ---"
                                                class="flex-1 h-[46px] bg-white border border-[#5b5b5b] rounded-r px-4 py-2 text-[#c7c7c7] text-left font-nexa-regular text-[14px] leading-[22px] outline-none placeholder-[#c7c7c7]"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Institución -->
                                <div>
                                    <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                        Institución*
                                    </label>
                                    <select 
                                        v-model="form.institution"
                                        class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] px-4 py-2 text-[#c7c7c7] text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none"
                                    >
                                        <option value="">Seleccione una institución</option>
                                        <option value="pedro-valdivia">Pedro de Valdivia</option>
                                        <option value="san-patricio">Colegio San Patricio</option>
                                    </select>
                                </div>

                                <!-- Nivel de educación y detalles -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                            Nivel de educación *
                                        </label>
                                        <select 
                                            v-model="form.education_level"
                                            class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] px-4 py-2 text-[#c7c7c7] text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none"
                                        >
                                            <option value="">Seleccione un nivel</option>
                                            <option value="preescolar">Preescolar</option>
                                            <option value="primaria">Primaria</option>
                                            <option value="secundaria">Secundaria</option>
                                            <option value="universitaria">Universitaria</option>
                                        </select>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                                Año*
                                            </label>
                                            <select 
                                                v-model="form.year"
                                                class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] px-4 py-2 text-[#c7c7c7] text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none"
                                            >
                                                <option value="">2025</option>
                                                <option value="2024">2024</option>
                                                <option value="2025">2025</option>
                                                <option value="2026">2026</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                                Grado*
                                            </label>
                                            <select 
                                                v-model="form.grade"
                                                class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] px-4 py-2 text-[#c7c7c7] text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none"
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
                                        <div>
                                            <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                                Turno*
                                            </label>
                                            <select 
                                                v-model="form.shift"
                                                class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] px-4 py-2 text-[#c7c7c7] text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none"
                                            >
                                                <option value="">---</option>
                                                <option value="mañana">Mañana</option>
                                                <option value="tarde">Tarde</option>
                                                <option value="noche">Noche</option>
                                            </select>
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
                                            class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] px-4 py-2 text-[#c7c7c7] text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
                                        />
                                    </div>
                                    
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                            Apellido *
                                        </label>
                                        <input 
                                            v-model="form.emergency_contact_last_name"
                                            type="text"
                                            placeholder="Apellido"
                                            class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] px-4 py-2 text-[#c7c7c7] text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
                                        />
                                    </div>
                                </div>

                                <!-- Relación con el alumno -->
                                <div>
                                    <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                        Relación con el alumno*
                                    </label>
                                    <select 
                                        v-model="form.emergency_contact_relationship"
                                        class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] px-4 py-2 text-[#c7c7c7] text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none"
                                    >
                                        <option value="">Seleccione la relación con el alumno</option>
                                        <option value="padre">Padre</option>
                                        <option value="madre">Madre</option>
                                        <option value="tutor">Tutor/a</option>
                                        <option value="abuelo">Abuelo/a</option>
                                        <option value="hermano">Hermano/a</option>
                                        <option value="otro">Otro</option>
                                    </select>
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
                                            class="w-full h-[46px] bg-white rounded border border-[#5b5b5b] px-4 py-2 text-[#c7c7c7] text-left font-nexa-regular text-[14px] leading-[22px] outline-none placeholder-[#c7c7c7]"
                                        />
                                    </div>
                                    
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                            Numero de telefono*
                                        </label>
                                        <div class="flex">
                                            <div class="w-[70px] h-[46px] bg-white border border-[#5b5b5b] rounded-l border-r-0 flex items-center justify-center gap-2 px-2">
                                                <div class="w-6 h-6 bg-red-500 rounded-sm flex items-center justify-center">
                                                    🇨🇱
                                                </div>
                                                <svg class="w-3 h-3" viewBox="0 0 12 12" fill="none">
                                                    <path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </div>
                                            <input 
                                                v-model="form.emergency_contact_phone"
                                                type="tel"
                                                placeholder="+56 9-- --- ---"
                                                class="flex-1 h-[46px] bg-white border border-[#5b5b5b] rounded-r px-4 py-2 text-[#c7c7c7] text-left font-nexa-regular text-[14px] leading-[22px] outline-none placeholder-[#c7c7c7]"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Agregar otro contacto -->
                                <button 
                                    @click="addEmergencyContact"
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
                                                class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] px-4 py-2 text-[#c7c7c7] text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
                                            />
                                        </div>
                                        
                                        <div>
                                            <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                                Apellido *
                                            </label>
                                            <input 
                                                v-model="contact.last_name"
                                                type="text"
                                                placeholder="Apellido"
                                                class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] px-4 py-2 text-[#c7c7c7] text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
                                            />
                                        </div>
                                    </div>

                                    <!-- Relación -->
                                    <div>
                                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                            Relación con el alumno*
                                        </label>
                                        <select 
                                            v-model="contact.relationship"
                                            class="w-full h-[46px] bg-white rounded-lg border border-[#5b5b5b] px-4 py-2 text-[#c7c7c7] text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none"
                                        >
                                            <option value="">Seleccione la relación con el alumno</option>
                                            <option value="padre">Padre</option>
                                            <option value="madre">Madre</option>
                                            <option value="tutor">Tutor/a</option>
                                            <option value="abuelo">Abuelo/a</option>
                                            <option value="hermano">Hermano/a</option>
                                            <option value="otro">Otro</option>
                                        </select>
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
                                                class="w-full h-[46px] bg-white rounded border border-[#5b5b5b] px-4 py-2 text-[#c7c7c7] text-left font-nexa-regular text-[14px] leading-[22px] outline-none placeholder-[#c7c7c7]"
                                            />
                                        </div>
                                        
                                        <div>
                                            <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold block mb-2">
                                                Numero de telefono*
                                            </label>
                                            <div class="flex">
                                                <div class="w-[70px] h-[46px] bg-white border border-[#5b5b5b] rounded-l border-r-0 flex items-center justify-center gap-2 px-2">
                                                    <div class="w-6 h-6 bg-red-500 rounded-sm flex items-center justify-center">
                                                        🇨🇱
                                                    </div>
                                                    <svg class="w-3 h-3" viewBox="0 0 12 12" fill="none">
                                                        <path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </div>
                                                <input 
                                                    v-model="contact.phone"
                                                    type="tel"
                                                    placeholder="+56 9-- --- ---"
                                                    class="flex-1 h-[46px] bg-white border border-[#5b5b5b] rounded-r px-4 py-2 text-[#c7c7c7] text-left font-nexa-regular text-[14px] leading-[22px] outline-none placeholder-[#c7c7c7]"
                                                />
                                            </div>
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
            </div>

            <!-- Save button -->
            <div class="mt-8">
                <button 
                    @click="saveParticipant"
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
    name: "CreateParticipantModal",
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
                // Datos del participante
                first_name: "",
                last_name: "",
                document_number: "",
                birth_date: "",
                email: "",
                phone: "",
                institution: "",
                education_level: "",
                year: "2025",
                grade: "",
                shift: "",
                medical_conditions: "",
                
                // Contacto de emergencia
                emergency_contact_name: "",
                emergency_contact_last_name: "",
                emergency_contact_relationship: "",
                emergency_contact_email: "",
                emergency_contact_phone: "",
                
                // Programa
                associated_program: "Ruta de lagos | Bariloche Arg-Sur de CL",
                has_benefit: "No, Se reparte grupal",
                start_date: "2025-09-01",
                end_date: "2025-09-01"
            },
            additionalContacts: []
        };
    },
    methods: {
        closeModal() {
            this.$emit('close');
        },
        saveParticipant() {
            console.log('Saving participant:', this.form);
            console.log('Additional contacts:', this.additionalContacts);
            // Here you would typically send the data to the server
            this.closeModal();
        },
        addEmergencyContact() {
            this.additionalContacts.push({
                name: "",
                last_name: "",
                relationship: "",
                email: "",
                phone: ""
            });
        },
        removeEmergencyContact(index) {
            this.additionalContacts.splice(index, 1);
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
