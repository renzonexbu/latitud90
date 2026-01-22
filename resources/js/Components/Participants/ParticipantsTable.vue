<template>
    <div class="bg-white rounded-lg border border-gray-200">
        <!-- Table Container with horizontal scroll -->
        <div class="overflow-x-auto">
            <table class="w-full text-xs" style="min-width: 1400px;">
                <!-- Table Header -->
                <thead class="bg-[#007e93] sticky top-0 z-10">
                    <tr>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[120px]">
                            RUT / Pasaporte
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[140px]">
                            Nombre
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[140px]">
                            Apellido
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[200px]">
                            Institución
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[120px]">
                            Nivel
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[220px]">
                            Programa
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Estado Pago
                        </th>
                        <th class="px-2 py-2 text-right text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[150px]">
                            Total Pagado
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap w-[100px]">

                        </th>
                    </tr>
                </thead>

                <!-- Table Body -->
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr
                        v-for="(participant, index) in participants"
                        :key="participant.id + '-' + (getFirstCourseInfo(participant, 'program', 'code') || 'none')"
                        :class="[
                            'hover:bg-gray-50 transition-colors',
                            index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                        ]"
                    >
                        <!-- RUT/PASAPORTE -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs text-gray-900">
                                {{ formatDocument(participant.document_number, participant.document_type) }}
                            </div>
                        </td>

                        <!-- Nombre -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs font-medium text-gray-900">
                                {{ getFullName(participant) }}
                            </div>
                        </td>

                        <!-- Apellido -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs font-medium text-gray-900">
                                {{ getFullLastName(participant) }}
                            </div>
                        </td>

                        <!-- Institución -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs text-gray-900">
                                {{ capitalizeWords(getFirstCourseInfo(participant, "institution", "name") || "N/A") }}
                            </div>
                        </td>

                        <!-- Nivel -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs font-medium text-[#1c4f4a]">
                                {{ formatCourseInfo(participant) }}
                            </div>
                        </td>

                        <!-- Programa -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs font-medium text-[#1c4f4a]">
                                {{ capitalizeWords(getFirstCourseInfo(participant, "program", "name") || "N/A") }}
                            </div>
                        </td>

                        <!-- Estado de pago -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <span
                                :class="[
                                    'inline-flex px-1.5 py-0.5 text-[10px] font-semibold rounded-full text-white',
                                    getPaymentStatusClass(
                                        getFirstCoursePivotStatus(participant),
                                        getFirstCoursePivotPercentage(participant)
                                    ),
                                ]"
                            >
                                {{ getPaymentStatusText(getFirstCoursePivotStatus(participant), getFirstCoursePivotPercentage(participant)) }}
                            </span>
                        </td>

                        <!-- Total pagado -->
                        <td class="px-2 py-2 whitespace-nowrap text-right">
                            <div class="text-xs font-bold text-gray-900">
                                {{ formatPaymentPair(getPaidAmount(participant), getTotalDue(participant)) }}
                            </div>
                        </td>

                        <!-- Acciones -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="flex gap-2 items-center justify-center">
                                <!-- Estado Activo/Baja del programa -->
                                <span
                                    :class="[
                                        'inline-flex px-1.5 py-0.5 text-[10px] font-semibold rounded-full text-white',
                                        participant.is_active ? 'bg-[#4b8d7f]' : 'bg-[#d54b44]',
                                    ]"
                                >
                                    {{ participant.is_active ? 'Activo' : 'Baja' }}
                                </span>

                                <!-- Edit Button -->
                                <button
                                    @click="$emit('edit-participant', participant.id)"
                                    class="w-[18px] h-[19px] hover:opacity-75 transition-opacity"
                                >
                                    <EditPencilIcon fill-color="#C7C7C7" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
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
        // Component mounted - debug logging removed for production
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
