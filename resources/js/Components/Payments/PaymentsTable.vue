<template>
    <div class="bg-white rounded-lg border border-gray-200">
        <!-- Table Container -->
        <div class="overflow-x-auto">
            <table class="w-full text-xs" style="min-width: 1100px;">
                <!-- Table Header -->
                <thead class="bg-[#007e93] sticky top-0 z-10">
                    <tr>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[130px]">
                            Fecha / Hora
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[140px]">
                            Pagador
                        </th>
                        <th class="px-2 py-2 text-right text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Monto
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Tipo de Pago
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Origen
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            N° Cuota
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Estado
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[100px]">
                            Código
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[100px]">
                            RUT
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[120px]">
                            Apellido
                        </th>
                        <th class="px-2 py-2 text-left text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap min-w-[120px]">
                            Nombre
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap">
                            Boleta
                        </th>
                        <th class="px-2 py-2 text-center text-[10px] font-medium text-white uppercase tracking-wider whitespace-nowrap w-[50px]">

                        </th>
                    </tr>
                </thead>

                <!-- Table Body -->
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr
                        v-for="(payment, index) in payments"
                        :key="payment.id"
                        :class="[
                            'hover:bg-gray-50 transition-colors cursor-pointer',
                            index % 2 === 0 ? 'bg-white' : 'bg-gray-50',
                        ]"
                        @click="$emit('show-payment-details', payment)"
                    >
                        <!-- Fecha / Hora -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs text-gray-900">
                                {{ formatDateTime(payment) }}
                            </div>
                        </td>

                        <!-- Pagador -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs text-gray-900">
                                {{ getBuyerName(payment) }}
                            </div>
                        </td>

                        <!-- Monto -->
                        <td class="px-2 py-2 whitespace-nowrap text-right">
                            <div class="text-xs font-bold text-gray-900">
                                ${{ formatPrice(payment.amount) }}
                            </div>
                        </td>

                        <!-- Tipo de Pago -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs text-gray-900">
                                {{ getPaymentMethodDisplay(payment) }}
                            </div>
                        </td>

                        <!-- Origen -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <span
                                :class="[
                                    'inline-flex px-1.5 py-0.5 text-[10px] font-semibold rounded-full',
                                    getPaymentSourceClass(payment),
                                ]"
                            >
                                {{ getPaymentSourceLabel(payment) }}
                            </span>
                        </td>

                        <!-- N° Cuota -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs text-gray-900">
                                {{ getInstallmentNumber(payment) }}
                            </div>
                        </td>

                        <!-- Estado -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <span
                                :class="[
                                    'inline-flex px-1.5 py-0.5 text-[10px] font-semibold rounded-full text-white',
                                    getStatusClass(payment.status),
                                ]"
                            >
                                {{ getStatusLabel(payment.status, payment) }}
                            </span>
                        </td>

                        <!-- Código (de Programa) -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs font-medium text-[#1c4f4a]">
                                {{ getProgramCode(payment) }}
                            </div>
                        </td>

                        <!-- RUT (participante) -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="text-xs text-gray-900">
                                {{ getParticipantRut(payment) }}
                            </div>
                        </td>

                        <!-- Apellido (participante) -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs font-medium text-gray-900">
                                {{ getParticipantLastName(payment) }}
                            </div>
                        </td>

                        <!-- Nombre (participante) -->
                        <td class="px-2 py-2 whitespace-nowrap">
                            <div class="text-xs font-medium text-gray-900">
                                {{ getParticipantFirstName(payment) }}
                            </div>
                        </td>

                        <!-- Boleta BSale -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <span
                                v-if="payment.bsale_number"
                                class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-green-100 text-green-800"
                                :title="'Boleta #' + payment.bsale_number"
                            >
                                <svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                {{ payment.bsale_number }}
                            </span>
                            <span
                                v-else-if="payment.bsale_error_code"
                                class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-red-100 text-red-800"
                                :title="payment.bsale_error || 'Error al generar boleta'"
                            >
                                <svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                Error
                            </span>
                            <span
                                v-else-if="getBsaleStatus(payment) === 'na'"
                                class="inline-flex px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-gray-100 text-gray-500"
                                title="No aplica (programa de año posterior)"
                            >
                                N/A
                            </span>
                            <span
                                v-else-if="getBsaleStatus(payment) === 'failed'"
                                class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-red-100 text-red-800"
                                :title="'Fallido después de ' + (payment.latest_bsale_request?.attempts || 3) + ' intentos'"
                            >
                                <svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                Fallido
                            </span>
                            <span
                                v-else-if="getBsaleStatus(payment) === 'pending'"
                                class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-yellow-100 text-yellow-800"
                                title="Pendiente de generar"
                            >
                                <svg class="w-3 h-3 mr-0.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Pend.
                            </span>
                            <span
                                v-else
                                class="inline-flex px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-gray-100 text-gray-400"
                            >
                                —
                            </span>
                        </td>

                        <!-- Acciones -->
                        <td class="px-2 py-2 whitespace-nowrap text-center">
                            <div class="flex gap-1 items-center justify-center">
                                <!-- Reconfirm Button (pagos pendientes con token o external_payment_id) -->
                                <button
                                    v-if="payment.status === 'pending' && (payment.token || payment.external_payment_id)"
                                    @click.stop="reconfirmPayment(payment)"
                                    :disabled="reconfirmingId === payment.id"
                                    class="w-[18px] h-[18px] hover:opacity-75 transition-opacity disabled:opacity-50"
                                    :title="reconfirmingId === payment.id ? 'Reconfirmando...' : 'Reconfirmar pago'"
                                >
                                    <!-- Loading spinner -->
                                    <svg
                                        v-if="reconfirmingId === payment.id"
                                        class="animate-spin"
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                    >
                                        <circle cx="12" cy="12" r="10" stroke="#007e93" stroke-width="2" stroke-linecap="round" stroke-dasharray="31.4 31.4" />
                                    </svg>
                                    <!-- Refresh icon -->
                                    <svg
                                        v-else
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="#007e93"
                                        stroke-width="2"
                                    >
                                        <path d="M23 4v6h-6" />
                                        <path d="M1 20v-6h6" />
                                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15" />
                                    </svg>
                                </button>

                                <!-- View Details Button -->
                                <button
                                    @click.stop="$emit('show-payment-details', payment)"
                                    class="w-[18px] h-[18px] hover:opacity-75 transition-opacity"
                                >
                                    <svg
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="#C7C7C7"
                                        stroke-width="2"
                                    >
                                        <path
                                            d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"
                                        />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Toast de notificación -->
        <div
            v-if="toastMessage"
            :class="[
                'fixed bottom-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 transition-all duration-300 max-w-md',
                toastType === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            ]"
        >
            {{ toastMessage }}
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: "PaymentsTable",
    props: {
        payments: {
            type: Array,
            default: () => [],
        },
    },

    data() {
        return {
            reconfirmingId: null,
            toastMessage: '',
            toastType: 'success',
        };
    },

    methods: {
        async reconfirmPayment(payment) {
            if (this.reconfirmingId) return;

            this.reconfirmingId = payment.id;
            this.toastMessage = '';

            try {
                const response = await axios.post(route('admin.payments.reconfirm', payment.id));

                if (response.data.success) {
                    this.showToast(response.data.message, 'success');

                    // Actualizar el estado del pago localmente
                    if (response.data.payment) {
                        Object.assign(payment, response.data.payment);
                    } else if (response.data.status) {
                        payment.status = response.data.status;
                    }

                    // Emitir evento para que el padre pueda refrescar si es necesario
                    this.$emit('payment-reconfirmed', payment, response.data);
                } else {
                    this.showToast(response.data.message || 'Error al reconfirmar el pago', 'error');
                }
            } catch (error) {
                console.error('Error reconfirmando pago:', error);
                const message = error.response?.data?.message || 'Error al reconfirmar el pago';
                this.showToast(message, 'error');
            } finally {
                this.reconfirmingId = null;
            }
        },

        showToast(message, type = 'success') {
            this.toastMessage = message;
            this.toastType = type;

            // Auto-hide after 5 seconds
            setTimeout(() => {
                this.toastMessage = '';
            }, 5000);
        },

        toCapitalCase(text) {
            if (!text) return '';
            return text
                .toLowerCase()
                .split(' ')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        },

        getParticipantName(payment) {
            // Si es un installment
            if (payment.is_installment && payment.order?.participant) {
                const p = payment.order.participant;
                const firstName = this.toCapitalCase(p.first_name || '');
                const lastName = this.toCapitalCase(p.first_last_name || '');
                return `${firstName} ${lastName}`.trim() || "N/A";
            }
            // Usar el atributo del modelo Payment que ya está construido correctamente
            return this.toCapitalCase(payment.participant_name || "N/A");
        },

        getBuyerName(payment) {
            // Si es un installment
            if (payment.is_installment && payment.order) {
                const firstName = this.toCapitalCase(payment.order.buyer_first_name || '');
                const lastName = this.toCapitalCase(payment.order.buyer_first_last_name || '');
                const fullName = `${firstName} ${lastName}`.trim();
                return fullName || "N/A";
            }
            // Los datos del comprador están en order_detail
            if (payment.order_detail?.name) {
                return this.toCapitalCase(payment.order_detail.name);
            }
            // Fallback al participante si no hay datos del comprador
            if (payment.order?.participant) {
                return this.getParticipantName(payment);
            }
            return "N/A";
        },

        getInstitutionName(payment) {
            return payment.order?.program_course?.course?.institution?.name || "N/A";
        },

        getPaymentMethodDisplay(payment) {
            // Si es un installment (cuota de suscripción)
            if (payment.is_installment) {
                return 'PAT';
            }

            // Si es una orden de suscripción (empieza con SUB-)
            if (payment.order?.order_number?.startsWith('SUB-')) {
                return 'PAT';
            }

            // Si tiene payment_option_id, mostrar el código de reporte
            if (payment.payment_option_id && payment.payment_option) {
                // Prioritize report_code over gateway_code
                if (payment.payment_option.report_code) {
                    return payment.payment_option.report_code;
                }

                // Fallback a gateway_code
                return payment.payment_option.gateway_code || "N/A";
            }

            // Si no tiene payment_option_id, es un pago offline sin código específico
            if (!payment.payment_option_id) {
                return "Offline";
            }

            // Fallback
            return payment.payment_gateway?.name || "N/A";
        },

        /**
         * Obtiene la etiqueta para mostrar el origen del pago
         * Usa el campo payment_source_calculated que viene del backend
         */
        getPaymentSourceLabel(payment) {
            const source = payment.payment_source_calculated || 'online';
            const labels = {
                'online': 'Pago Total',
                'subscription': 'Suscripción',
                'offline': 'Offline',
                'devolucion': 'Devolución'
            };
            return labels[source] || source;
        },

        /**
         * Obtiene la clase CSS para el badge del origen del pago
         */
        getPaymentSourceClass(payment) {
            const source = payment.payment_source_calculated || 'online';
            const classes = {
                'online': 'bg-blue-100 text-blue-800',
                'subscription': 'bg-purple-100 text-purple-800',
                'offline': 'bg-green-100 text-green-800',
                'devolucion': 'bg-red-100 text-red-800'
            };
            return classes[source] || 'bg-gray-100 text-gray-800';
        },

        getStatusClass(status) {
            const classes = {
                pending: "bg-[#ffb232]", // Amarillo
                completed: "bg-[#4b8d7f]", // Verde
                failed: "bg-[#d54b44]", // Rojo
                authorized: "bg-[#1c4f4a]", // Verde oscuro
                approved: "bg-[#4b8d7f]", // Verde (aprobado = completado)
                cancelled: "bg-[#6b7280]", // Gris
                refunded: "bg-[#f59e0b]", // Naranja
                processing: "bg-[#3b82f6]", // Azul
            };
            return classes[status] || "bg-[#ffb232]";
        },

        getStatusLabel(status, payment = null) {
            // Estandarizar etiquetas de estado para todos los pagos
            const labels = {
                pending: "Pendiente",
                completed: "Pagado",      // Estandarizado: todo completado = "Pagado"
                failed: "Fallido",
                authorized: "Autorizado",
                approved: "Pagado",       // Aprobado también es "Pagado"
                cancelled: "Cancelado",
                refunded: "Reembolsado",
                processing: "Procesando",
            };
            return labels[status] || status;
        },

        formatPrice(amount) {
            return new Intl.NumberFormat("es-CL").format(amount || 0);
        },

        // Helper para normalizar fechas sin hora (YYYY-MM-DD) evitando problemas de timezone
        normalizeDate(date) {
            if (!date) return null;
            // Si es solo fecha (YYYY-MM-DD), agregar T12:00:00 para evitar
            // que el cambio de timezone afecte el día mostrado
            if (typeof date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(date)) {
                return date + 'T12:00:00';
            }
            return date;
        },

        formatDate(date) {
            if (!date) return "N/A";

            try {
                const dateObj = new Date(this.normalizeDate(date));
                if (isNaN(dateObj.getTime())) {
                    return "Invalid Date";
                }

                return dateObj.toLocaleDateString("es-CL", {
                    day: "2-digit",
                    month: "2-digit",
                    year: "numeric",
                });
            } catch (error) {
                return "Error Date";
            }
        },

        formatDateTime(payment) {
            const date = payment.transaction_date_formatted || payment.transaction_date || payment.created_at;
            if (!date) {
                // Fallback al ID si no hay fecha
                return `ID: ${payment.id}`;
            }

            try {
                const dateObj = new Date(this.normalizeDate(date));
                if (isNaN(dateObj.getTime())) {
                    return `ID: ${payment.id}`;
                }

                // Formato: dd/mm/yyyy HH:mm
                const dateStr = dateObj.toLocaleDateString("es-CL", {
                    day: "2-digit",
                    month: "2-digit",
                    year: "numeric",
                });
                const timeStr = dateObj.toLocaleTimeString("es-CL", {
                    hour: "2-digit",
                    minute: "2-digit",
                    hour12: false,
                });
                return `${dateStr} ${timeStr}`;
            } catch (error) {
                return `ID: ${payment.id}`;
            }
        },

        getInstallmentNumber(payment) {
            // Para pagos normales, buscar primero en payments.installments_number
            if (payment.installments_number) {
                return payment.installments_number;
            }
            // Para cuotas de suscripción
            if (payment.is_installment && payment.installment_number) {
                return payment.installment_number;
            }
            // Para pagos normales (fallback), buscar en order_detail
            if (payment.order_detail?.installment_number) {
                return payment.order_detail.installment_number;
            }
            // Si es pago total (no cuota), mostrar "—"
            return "—";
        },

        getProgramCode(payment) {
            // Para installments, el código puede venir en order.program_course
            if (payment.order?.program_course?.code) {
                return payment.order.program_course.code;
            }
            // Buscar también con underscore (respuesta del backend puede variar)
            if (payment.order?.programCourse?.code) {
                return payment.order.programCourse.code;
            }
            return "N/A";
        },

        getParticipantRut(payment) {
            const participant = payment.order?.participant;
            if (!participant?.document_number) return "—";

            const docType = participant.document_type;
            // Si el tipo de documento es RUT (id=1 o name="RUT"), formatear
            const isRut = docType === 1 || docType?.id === 1 || docType?.name === 'RUT';
            if (isRut) {
                return this.formatRut(participant.document_number);
            }
            return participant.document_number;
        },

        formatRut(value) {
            // Limpiar: quitar puntos, guiones y espacios
            let clean = String(value).replace(/[.\-\s]/g, '').toUpperCase();
            if (clean.length < 2) return value;

            // Separar cuerpo y dígito verificador (último carácter, puede ser K)
            const dv = clean.slice(-1);
            const body = clean.slice(0, -1);

            // Formatear cuerpo con puntos cada 3 dígitos desde la derecha
            const formatted = body.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

            return `${formatted}-${dv}`;
        },

        getParticipantLastName(payment) {
            // Para installments
            if (payment.is_installment && payment.order?.participant) {
                const p = payment.order.participant;
                const lastName = p.first_last_name || '';
                const secondLastName = p.second_last_name || '';
                return this.toCapitalCase(`${lastName} ${secondLastName}`.trim()) || "N/A";
            }
            // Para pagos normales
            if (payment.order?.participant) {
                const p = payment.order.participant;
                const lastName = p.first_last_name || '';
                const secondLastName = p.second_last_name || '';
                return this.toCapitalCase(`${lastName} ${secondLastName}`.trim()) || "N/A";
            }
            return "N/A";
        },

        getParticipantFirstName(payment) {
            // Para installments
            if (payment.is_installment && payment.order?.participant) {
                const p = payment.order.participant;
                const firstName = p.first_name || '';
                const secondName = p.second_name || '';
                return this.toCapitalCase(`${firstName} ${secondName}`.trim()) || "N/A";
            }
            // Para pagos normales
            if (payment.order?.participant) {
                const p = payment.order.participant;
                const firstName = p.first_name || '';
                const secondName = p.second_name || '';
                return this.toCapitalCase(`${firstName} ${secondName}`.trim()) || "N/A";
            }
            return "N/A";
        },

        /**
         * Determina el estado de la boleta BSale para un pago
         * @returns 'generated' | 'error' | 'failed' | 'pending' | 'na' | 'none'
         */
        getBsaleStatus(payment) {
            // Si ya tiene boleta generada
            if (payment.bsale_number || payment.bsale_document_id) {
                return 'generated';
            }

            // Si tiene error permanente en el payment
            if (payment.bsale_error_code) {
                return 'error';
            }

            // Si el pago no está completado, no aplica boleta
            const completedStatuses = ['completed', 'approved'];
            if (!completedStatuses.includes(payment.status)) {
                return 'none';
            }

            // Crédito Temporal (CT) NUNCA genera boleta
            if (payment.document_type === 'CT' ||
                payment.payment_option?.code === 'presential_credit_temp' ||
                payment.paymentOption?.code === 'presential_credit_temp') {
                return 'none';
            }

            // Reverso Administrativo (RA) NUNCA genera boleta
            if (payment.document_type === 'RA' ||
                payment.payment_option?.code === 'refund_admin_reversal' ||
                payment.paymentOption?.code === 'refund_admin_reversal') {
                return 'none';
            }

            // Si es Nota de Crédito (NC) o devolución, no aplica boleta
            // NC se identifica por: document_type='VC', monto negativo, o payment_source_calculated='devolucion'
            const isRefundOrNC = payment.document_type === 'VC'
                || (payment.amount && Number(payment.amount) < 0)
                || payment.payment_source_calculated === 'devolucion';

            if (isRefundOrNC) {
                return 'none';
            }

            // Verificar el estado del BsaleRequest si existe
            const bsaleRequest = payment.latest_bsale_request;
            if (bsaleRequest) {
                // Si el request falló después de agotar los intentos
                if (bsaleRequest.status === 'failed' ||
                    (bsaleRequest.attempts >= bsaleRequest.max_attempts && bsaleRequest.status !== 'completed')) {
                    return 'failed';
                }
                // Si está pendiente o procesando, mostrar como pendiente
                if (['pending', 'processing'].includes(bsaleRequest.status)) {
                    return 'pending';
                }
            }

            // Verificar si es de año posterior (no aplica boleta)
            // El programa debe salir en el año actual para generar boleta
            const programDepartureDate = payment.order?.program_course?.departure_date
                || payment.order?.programCourse?.departure_date;

            if (programDepartureDate) {
                const departureYear = new Date(programDepartureDate).getFullYear();
                const currentYear = new Date().getFullYear();
                if (departureYear > currentYear) {
                    return 'na';
                }
            }

            // Verificar tiempo transcurrido desde la creación del pago
            // Si ha pasado más de 1 hora sin generar boleta, mostrar "-" en lugar de "Pend."
            if (payment.created_at) {
                const createdAt = new Date(payment.created_at);
                const now = new Date();
                const hoursPassed = (now - createdAt) / (1000 * 60 * 60);

                // Si ha pasado más de 1 hora (3600 segundos) sin boleta, no aplica
                if (hoursPassed > 1) {
                    return 'none';
                }
            }

            // Si está completado, año actual, pero no hay BsaleRequest = pendiente de crear
            return 'pending';
        },
    },
};
</script>

<style scoped>
/* Custom font classes */
.font-nexa-bold {
    font-family: "Nexa-Bold", sans-serif;
    font-weight: 700;
}

.font-nexa-xbold {
    font-family: "Nexa-XBold", sans-serif;
    font-weight: 400;
}

.bg-turquesa {
    background-color: #007e93;
}

/* Animation for loading spinner */
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
