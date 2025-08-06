<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <Header />

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Process Steps -->
            <ProcessSteps :current-step="4" />

            <!-- Success Message -->
            <div class="text-center mb-8 mt-8">
                <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    ¡Pago Confirmado!
                </h1>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Tu pago ha sido procesado exitosamente. Recibirás un email con todos los detalles de tu reserva.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Program Information -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">
                            Información del Programa
                        </h2>

                        <!-- Confirmation Number -->
                        <div class="bg-gray-50 rounded-md p-4 mb-6">
                            <div class="text-center">
                                <p class="text-sm text-gray-600">Número de Confirmación</p>
                                <p class="text-2xl font-bold text-indigo-600">
                                    {{ confirmationData.confirmation_number }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Guarda este número para futuras consultas
                                </p>
                            </div>
                        </div>

                        <!-- Program Details -->
                        <div class="space-y-4">
                            <div>
                                <h3 class="font-medium text-gray-900">Programa</h3>
                                <p class="text-gray-600">{{ confirmationData.program.name }}</p>
                            </div>

                            <div>
                                <h3 class="font-medium text-gray-900">Destino</h3>
                                <p class="text-gray-600">{{ confirmationData.program.destination }}</p>
                            </div>

                            <div>
                                <h3 class="font-medium text-gray-900">Fecha de Salida</h3>
                                <p class="text-gray-600">
                                    {{ formatDate(confirmationData.program.departure_date) }}
                                </p>
                            </div>

                            <div>
                                <h3 class="font-medium text-gray-900">Precio Total</h3>
                                <p class="text-2xl font-bold text-indigo-600">
                                    ${{ formatPrice(confirmationData.program.trip_price) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">
                            Información Personal
                        </h2>

                        <div class="space-y-4">
                            <div>
                                <h3 class="font-medium text-gray-900">Nombre Completo</h3>
                                <p class="text-gray-600">{{ confirmationData.form_data.fullName }}</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <h3 class="font-medium text-gray-900">Email</h3>
                                    <p class="text-gray-600">{{ confirmationData.form_data.email }}</p>
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-900">Teléfono</h3>
                                    <p class="text-gray-600">{{ confirmationData.form_data.code_phone }} {{ confirmationData.form_data.phone }}</p>
                                </div>
                            </div>

                            <div>
                                <h3 class="font-medium text-gray-900">Documento</h3>
                                <p class="text-gray-600">RUT: {{ confirmationData.form_data.rut }}</p>
                            </div>

                            <div>
                                <h3 class="font-medium text-gray-900">Ubicación</h3>
                                <p class="text-gray-600">{{ confirmationData.form_data.country }}, {{ confirmationData.form_data.region }}, {{ confirmationData.form_data.city }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">
                            Información de Pago
                        </h2>

                        <div class="space-y-4">
                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-gray-600">Método de Pago</span>
                                <span class="font-medium">{{ getPaymentMethodLabel(confirmationData.payment_data.paymentType, confirmationData.payment_data.paymentMethod) }}</span>
                            </div>

                            <div class="flex justify-between items-center py-2 border-b">
                                <span class="text-gray-600">Tipo de Pago</span>
                                <span class="font-medium">{{ confirmationData.payment_data.paymentType === 'total' ? 'Pago Total' : 'Pago en Cuotas' }}</span>
                            </div>

                            <div v-if="confirmationData.payment_data.paymentType === 'monthly'" class="flex justify-between items-center py-2 border-b">
                                <span class="text-gray-600">Número de Cuotas</span>
                                <span class="font-medium">{{ confirmationData.payment_data.installments }}</span>
                            </div>

                            <div class="flex justify-between items-center py-2 text-lg font-bold">
                                <span>Total Pagado</span>
                                <span class="text-green-600">${{ formatPrice(confirmationData.program.trip_price) }}</span>
                            </div>

                            <div class="mt-4">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 rounded-full bg-green-400"></div>
                                    <span class="text-sm font-medium text-green-800">Pago Confirmado</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Next Steps -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Próximos Pasos</h2>

                        <div class="space-y-4">
                            <div class="flex items-start space-x-3">
                                <div class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <span class="text-indigo-600 text-sm font-bold">1</span>
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-900">Confirmación por Email</h3>
                                    <p class="text-sm text-gray-600">
                                        Recibirás un email de confirmación con todos los detalles en los próximos minutos.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-3">
                                <div class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <span class="text-indigo-600 text-sm font-bold">2</span>
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-900">Documentación</h3>
                                    <p class="text-sm text-gray-600">
                                        Te enviaremos la documentación necesaria y lista de equipaje recomendado.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-3">
                                <div class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <span class="text-indigo-600 text-sm font-bold">3</span>
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-900">Contacto Pre-Viaje</h3>
                                    <p class="text-sm text-gray-600">
                                        Nos pondremos en contacto contigo una semana antes del viaje con detalles finales.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                <button
                    @click="goToHome"
                    class="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-md hover:bg-indigo-700 transition duration-200 text-center">
                    Explorar Más Programas
                </button>
                <button
                    @click="goToPrograms"
                    class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-md hover:bg-gray-50 transition duration-200 text-center">
                    Ver Todos los Programas
                </button>
                <button
                    @click="printConfirmation"
                    class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-md hover:bg-gray-50 transition duration-200">
                    Imprimir Confirmación
                </button>
            </div>

            <!-- Contact Information -->
            <div class="mt-12 bg-indigo-50 rounded-lg p-6 text-center">
                <h3 class="text-lg font-bold text-indigo-900 mb-2">
                    ¿Necesitas Ayuda?
                </h3>
                <p class="text-indigo-700 mb-4">
                    Nuestro equipo está disponible para ayudarte con cualquier consulta.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="mailto:reservas@lat90.cl" class="text-indigo-600 hover:text-indigo-500 font-medium">
                        📧 reservas@lat90.cl
                    </a>
                    <a href="tel:+56222334455" class="text-indigo-600 hover:text-indigo-500 font-medium">
                        📞 +56 2 2233 4455
                    </a>
                    <a href="https://wa.me/56912345678" class="text-indigo-600 hover:text-indigo-500 font-medium">
                        💬 WhatsApp
                    </a>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <Footer class="mt-16" />
    </div>
</template>

<script>
import { router } from "@inertiajs/vue3";
import Header from "@/Components/Ecommerce/Header.vue";
import Footer from "@/Components/Ecommerce/Footer.vue";
import ProcessSteps from "@/Components/Ecommerce/ProcessSteps.vue";

export default {
    name: "Confirmation",
    components: {
        Header,
        Footer,
        ProcessSteps
    },
    props: {
        confirmationData: {
            type: Object,
            required: true
        },
        programId: {
            type: [String, Number],
            required: true
        },
        rut: {
            type: String,
            default: ''
        }
    },
    methods: {
        formatDate(date) {
            return new Date(date).toLocaleDateString("es-ES", {
                year: "numeric",
                month: "long",
                day: "numeric"
            });
        },
        formatPrice(price) {
            return new Intl.NumberFormat("es-CL", {
                style: "currency",
                currency: "CLP"
            })
                .format(price)
                .replace("CLP", "")
                .trim();
        },
        getPaymentMethodLabel(type, method) {
            const methods = {
                total: {
                    debit: 'Pago con Tarjeta de Débito',
                    credit: 'Pago con Tarjeta de Crédito',
                    khipu: 'Pago con Transferencia Khipu'
                },
                monthly: {
                    debit: 'Pago con Tarjeta de Débito',
                    credit: 'Pago con Tarjeta de Crédito',
                    khipu: 'Pago con Transferencia Khipu'
                }
            };
            return methods[type]?.[method] || 'Método no especificado';
        },
        goToHome() {
            router.visit('/');
        },
        goToPrograms() {
            router.visit('/programs');
        },
        printConfirmation() {
            window.print();
        }
    },
    mounted() {
        // Auto-scroll to top
        window.scrollTo(0, 0);
    }
};
</script>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }

    body {
        background: white !important;
    }
}
</style>
