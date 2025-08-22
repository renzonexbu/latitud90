<template>
    <div class="bg-white rounded-[20px] overflow-hidden">
        <!-- Table Container -->
        <div class="flex flex-col gap-0">
            <!-- Table Header -->
            <div
                class="bg-turquesa rounded-t-[20px] px-5 py-[11px] flex items-center justify-between h-[61.51px]"
            >
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    RUT / PASAPORTE
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Nombre
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Apellido
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[180px]"
                >
                    Institución
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]"
                >
                    Nivel
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[180px]"
                >
                    Programa
                </div>

                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[100px]"
                >
                    Estado de pago
                </div>
                <div
                    class="text-white font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                >
                    Total pagado
                </div>
                <!-- Columna de acciones (vacía en header) -->
                <div class="w-[140px]"></div>
            </div>

            <!-- Table Body -->
            <div class="flex flex-col h-[574px] overflow-hidden">
                <div
                    v-for="(participant, index) in participants"
                    :key="
                        participant.id +
                        '-' +
                        (getFirstCourseInfo(participant, 'program', 'code') ||
                            'none')
                    "
                    :class="[
                        'px-5 py-[14px] flex items-center justify-between',
                        index % 2 === 0 ? 'bg-white' : 'bg-[#f9f9f9]',
                    ]"
                >
                    <!-- RUT/PASAPORTE -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        {{ formatDocument(participant.document_number, participant.document_type) }}
                    </div>

                    <!-- Nombre -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        {{ getFullName(participant) }}
                    </div>

                    <!-- Apellido -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        {{ getFullLastName(participant) }}
                    </div>

                    <!-- Institución -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[180px]"
                    >
                        {{
                            capitalizeWords(
                                getFirstCourseInfo(
                                    participant,
                                    "institution",
                                    "name"
                                ) || "N/A"
                            )
                        }}
                    </div>

                    <!-- Nivel -->
                    <div
                        class="text-[#1c4f4a] font-nexa-bold text-[14px] leading-[18px] text-center w-[140px]"
                    >
                        {{ formatCourseInfo(participant) }}
                    </div>

                    <!-- Programa -->
                    <div
                        class="text-[#1c4f4a] font-nexa-bold text-[14px] leading-[18px] text-center w-[180px]"
                    >
                        {{
                            capitalizeWords(
                                getFirstCourseInfo(
                                    participant,
                                    "program",
                                    "name"
                                ) || "N/A"
                            )
                        }}
                    </div>

                    <!-- Estado de pago -->
                    <div class="flex justify-center items-center w-[100px]">
                        <div
                            :class="[
                                'rounded-[12px] px-[8px] py-[6px] text-white font-nexa-xbold text-[12px] leading-[13px] text-center flex items-center justify-center',
                                getPaymentStatusClass(
                                    getFirstCoursePivotStatus(participant),
                                    getFirstCoursePivotPercentage(participant)
                                ),
                            ]"
                        >
                            {{
                                getPaymentStatusText(
                                    getFirstCoursePivotStatus(participant),
                                    getFirstCoursePivotPercentage(participant)
                                )
                            }}
                        </div>
                    </div>

                    <!-- Total pagado -->
                    <div
                        class="text-[#5b5b5b] font-nexa-bold text-[14px] leading-[18px] text-center w-[120px]"
                    >
                        {{
                            formatPaymentPair(
                                getPaidAmount(participant),
                                getTotalDue(participant)
                            )
                        }}
                    </div>

                    <!-- Acciones -->
                    <div class="flex gap-4 items-center justify-center w-[140px]">
                        <!-- Estado Activo/Inactivo -->
                        <div
                            :class="[
                                'rounded-[12px] px-[10px] py-[6px] text-white font-nexa-xbold text-[12px] leading-[13px] text-center flex items-center justify-center',
                                participant.is_active ? 'bg-[#4b8d7f]' : 'bg-[#d54b44]',
                            ]"
                        >
                            {{ participant.is_active ? 'Activo' : 'Inactivo' }}
                        </div>

                        <!-- Edit Button -->
                        <button
                            @click="$emit('edit-participant', participant.id)"
                            class="w-[18px] h-[19px] hover:opacity-75 transition-opacity"
                        >
                            <EditPencilIcon fill-color="#C7C7C7" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { EditPencilIcon } from "@/Components/Icons";

export default {
    name: "ParticipantsTable",
    components: {
        EditPencilIcon,
    },
    props: {
        participants: {
            type: Array,
            default: () => [],
        },
    },
    mounted() {
        // Componente montado
        console.log('🔍 ParticipantsTable - Datos recibidos:', this.participants);
        console.log('🔍 ParticipantsTable - Primer participante:', this.participants[0]);
        if (this.participants[0]) {
            console.log('🔍 ParticipantsTable - Campo is_active del primer participante:', this.participants[0].is_active);
            console.log('🔍 ParticipantsTable - Tipo de is_active:', typeof this.participants[0].is_active);
        }
    },
    methods: {
        formatRut(rut) {
            if (!rut) return "N/A";

            // Limpiar el RUT de puntos y guiones
            let rutLimpio = rut.toString().replace(/\./g, "").replace(/-/g, "");

            // Verificar que tenga el formato correcto de RUT chileno
            if (!/^\d{7,8}[\dK]$/.test(rutLimpio)) {
                return rut.toString().toUpperCase(); // Fallback si no es RUT válido
            }

            // Separar número y dígito verificador
            let dv = rutLimpio.slice(-1);
            let numero = rutLimpio.slice(0, -1);

            // Formatear con puntos y guión
            let numeroFormateado = numero.replace(/\B(?=(\d{3})+(?!\d))/g, ".");

            return `${numeroFormateado}-${dv}`;
        },

        formatCourseInfo(participant) {
            const firstCourse = this.getFirstCourse(participant);
            if (!firstCourse) return "N/A";
            const level = this.capitalizeWords(firstCourse.education_level);
            const num = firstCourse.course_number
                ? `${firstCourse.course_number}°`
                : "";
            const label =
                firstCourse.education_level === "basica"
                    ? "Básico"
                    : firstCourse.education_level === "media"
                    ? "Medio"
                    : "";
            const courseStr = num ? `${num} ${label}`.trim() : "";
            return [level, courseStr].filter(Boolean).join("\n");
        },

        getFirstCourse(participant) {
            if (!participant.courses || participant.courses.length === 0) {
                return null;
            }
            return participant.courses[0];
        },

        getFirstCourseInfo(participant, relation, field) {
            const firstCourse = this.getFirstCourse(participant);
            if (!firstCourse || !firstCourse[relation]) {
                return null;
            }
            return firstCourse[relation][field];
        },

        getFirstCoursePivotStatus(participant) {
            const firstCourse = this.getFirstCourse(participant);
            if (!firstCourse || !firstCourse.pivot) {
                return "pending_payment";
            }
            return firstCourse.pivot.status || "pending_payment";
        },

        getStatusLabel(status) {
            const labels = {
                pending_payment: "Pendiente de Pago",
                confirmed: "Confirmado",
                cancelled: "Cancelado",
            };
            return labels[status] || status;
        },

        getFirstCoursePivotPercentage(participant) {
            const total = this.getTotalDue(participant);
            const paid = this.getPaidAmount(participant);

            if (total <= 0) return 0;

            const percentage = Math.round((paid / total) * 100);
            return percentage;
        },

        getFirstCoursePivotAmount(participant) {
            const firstCourse = this.getFirstCourse(participant);
            if (!firstCourse || !firstCourse.pivot) {
                return 0;
            }

            const individualPrice =
                parseFloat(firstCourse.pivot.individual_price) || 0;
            const adjustments =
                parseFloat(firstCourse.pivot.price_adjustments) || 0;

            // Por ahora retornar el precio individual, se puede calcular basado en pagos reales
            return individualPrice;
        },

        getPaymentStatusClass(status, percentage = 0) {
            switch (status) {
                case "confirmed":
                    return "bg-[#4b8d7f]"; // Verde completado
                case "cancelled":
                    return "bg-[#1c4f4a]"; // Verde oscuro - Liberado
                case "pending_payment":
                default:
                    if (percentage >= 80) {
                        return "bg-[#ffb232]"; // Amarillo 80%
                    } else if (percentage >= 10) {
                        return "bg-[#d54b44]"; // Rojo 10%
                    } else {
                        return "bg-[#ffb232]"; // Amarillo por defecto
                    }
            }
        },

        getPaymentStatusText(status, percentage = 0) {
            switch (status) {
                case "confirmed":
                    return "Completado";
                case "cancelled":
                    return "Liberado";
                case "pending_payment":
                default:
                    return `${percentage || 0}%`;
            }
        },

        getPaidAmount(participant) {
            // Campo auxiliar inyectado desde Index.vue si viene del backend
            if (typeof participant.__paid_amount !== "undefined")
                return Number(participant.__paid_amount || 0);
            return 0;
        },

        getTotalDue(participant) {
            if (typeof participant.__total_due !== "undefined")
                return Number(participant.__total_due || 0);
            const firstCourse = this.getFirstCourse(participant);
            if (!firstCourse || !firstCourse.pivot) return 0;
            // Usar participant_total_due si está disponible (ya incluye descuentos)
            if (firstCourse.participant_total_due !== undefined) {
                return Number(firstCourse.participant_total_due || 0);
            }
            // Fallback al cálculo antiguo
            const individualPrice =
                parseFloat(firstCourse.pivot.individual_price) || 0;
            const adjustments =
                parseFloat(firstCourse.pivot.price_adjustments) || 0;
            return individualPrice + adjustments;
        },

        formatPayment(amount) {
            if (!amount || amount === 0) {
                return "----";
            }
            return `$${parseInt(amount).toLocaleString()}`;
        },

        formatPaymentPair(paid, total) {
            const p = isNaN(paid) ? 0 : paid;
            const t = isNaN(total) ? 0 : total;
            return `$${parseInt(p).toLocaleString()} / $${parseInt(
                t
            ).toLocaleString()}`;
        },

        capitalizeWords(string) {
            if (!string) return "";
            return string
                .split(" ")
                .map(
                    (word) =>
                        word.charAt(0).toUpperCase() +
                        word.slice(1).toLowerCase()
                )
                .join(" ");
        },

        formatPhoneNumber(participant) {
            if (!participant.phone) return "N/A";
            const phone = participant.phone.replace(/\D/g, ""); // Remove non-digits
            const code = participant.code_phone || "+56";

            if (phone.length === 8) {
                return `${code} 9 ${phone.slice(0, 4)} ${phone.slice(4)}`;
            } else if (phone.length === 9) {
                return `${code} 9 ${phone.slice(0, 5)} ${phone.slice(5)}`;
            } else {
                return `${code} ${phone}`;
            }
        },

        formatDocument(documentNumber, documentType) {
            if (!documentNumber) return "N/A";
            
            // Detectar si es RUT basándose en el formato del número
            // Los RUTs chilenos tienen formato: 12345678-9 o 12.345.678-9
            const cleanNumber = documentNumber.toString().replace(/\./g, "").replace(/-/g, "");
            const isRut = /^\d{7,8}[\dK]$/.test(cleanNumber);
            
            if (isRut) {
                return this.formatRut(documentNumber);
            } else {
                // Para pasaporte u otros documentos, mostrar en uppercase
                return documentNumber.toString().toUpperCase();
            }
        },

        getFullName(participant) {
            if (!participant) return "N/A";
            
            let firstName = participant.first_name || '';
            let secondName = participant.second_name || '';
            
            // Concatenar solo nombres
            let fullName = firstName;
            if (secondName) {
                fullName += ' ' + secondName;
            }
            
            // Aplicar CapitalCase
            return this.capitalizeWords(fullName.trim());
        },

        getFullLastName(participant) {
            if (!participant) return "N/A";
            
            let firstLastName = participant.first_last_name || '';
            let secondLastName = participant.second_last_name || '';
            
            // Concatenar apellidos
            let fullLastName = firstLastName;
            if (secondLastName) {
                fullLastName += ' ' + secondLastName;
            }
            
            // Aplicar CapitalCase
            return this.capitalizeWords(fullLastName.trim());
        },
    },
};
</script>

<style scoped>
/* Custom font classes - add these to your Tailwind config or use existing ones */
.font-nexa-bold {
    font-family: "Nexa-Bold", sans-serif;
    font-weight: 700;
}

.font-nexa-xbold {
    font-family: "Nexa-XBold", sans-serif;
    font-weight: 400;
}

.text-verde-oscuro {
    color: #1c4f4a;
}

.bg-turquesa {
    background-color: #007e93;
}
</style>
