<template>
    <div class="relative h-[80vh] rounded-3xl overflow-hidden mx-4 my-4">
        <div
            class="absolute inset-0 bg-cover bg-center rounded-3xl"
            :style="{ backgroundImage: `url('${backgroundImage}')` }"
        >
            <!-- Overlay para la imagen de fondo -->
            <div class="absolute inset-0 bg-black opacity-30 rounded-3xl"></div>
        </div>

        <!-- Esferas decorativas -->
        <div
            class="absolute right-0 top-1/2 transform -translate-y-1/2 hidden md:flex md:flex-col"
        >
            <div class="w-[69px] h-[99px] relative">
                <svg
                    width="103"
                    height="103"
                    viewBox="0 0 52 104"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <circle
                        cx="51.5"
                        cy="51.8203"
                        r="51.5"
                        transform="rotate(-90 51.5 51.8203)"
                        fill="#FBBD51"
                    />
                </svg>
            </div>
            <div class="w-[69px] h-[99px] relative">
                <svg
                    width="103"
                    height="103"
                    viewBox="0 0 52 104"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <circle
                        cx="51.5"
                        cy="51.8203"
                        r="51.5"
                        transform="rotate(-90 51.5 51.8203)"
                        fill="#FBBD51"
                    />
                </svg>
            </div>
            <div class="w-[69px] h-[99px] relative">
                <svg
                    width="103"
                    height="103"
                    viewBox="0 0 52 104"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <circle
                        cx="51.5"
                        cy="51.8203"
                        r="51.5"
                        transform="rotate(-90 51.5 51.8203)"
                        fill="#FBBD51"
                    />
                </svg>
            </div>
            <div class="w-[69px] h-[99px] relative">
                <svg
                    width="103"
                    height="103"
                    viewBox="0 0 52 104"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <circle
                        cx="51.5"
                        cy="51.8203"
                        r="51.5"
                        transform="rotate(-90 51.5 51.8203)"
                        fill="#FBBD51"
                    />
                </svg>
            </div>
        </div>

        <!-- Esferas decorativas Mobile -->
        <div
            class="absolute left-0 right-0 bottom-0 flex flex-row justify-center md:hidden"
        >
            <div class="mobile-circle-item"></div>
            <div class="mobile-circle-item"></div>
            <div class="mobile-circle-item"></div>
            <div class="mobile-circle-item"></div>
        </div>

        <div
            class="relative h-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col justify-center text-left"
        >
            <img
                src="images\hero_lat90_logo.png"
                alt="lat90logo"
                width="482"
                height="auto"
            />

            <div class="max-w-lg my-8">
                <!-- Tipo de Documento -->
                <div class="flex flex-col gap-3 mb-4">
                    <label class="text-white font-medium text-sm">
                        Tipo de documento
                    </label>
                    <div class="flex gap-6 justify-center md:justify-start">
                        <label
                            class="flex items-center gap-3 cursor-pointer group"
                        >
                            <div class="relative">
                                <input
                                    type="radio"
                                    name="documentType"
                                    value="RUT"
                                    v-model="selectedDocumentType"
                                    class="sr-only peer"
                                />
                                <div
                                    class="w-5 h-5 border-2 border-white rounded-full peer-checked:border-[#FBBD51] peer-checked:bg-[#FBBD51] transition-all duration-200 flex items-center justify-center"
                                >
                                    <div
                                        class="w-2 h-2 bg-white rounded-full opacity-0 peer-checked:opacity-100 transition-opacity duration-200"
                                    ></div>
                                </div>
                            </div>
                            <span
                                class="text-white font-medium text-sm group-hover:text-[#FBBD51] transition-colors duration-200"
                            >
                                RUT
                            </span>
                        </label>
                        <label
                            class="flex items-center gap-3 cursor-pointer group"
                        >
                            <div class="relative">
                                <input
                                    type="radio"
                                    name="documentType"
                                    value="PASAPORTE"
                                    v-model="selectedDocumentType"
                                    class="sr-only peer"
                                />
                                <div
                                    class="w-5 h-5 border-2 border-white rounded-full peer-checked:border-[#FBBD51] peer-checked:bg-[#FBBD51] transition-all duration-200 flex items-center justify-center"
                                >
                                    <div
                                        class="w-2 h-2 bg-white rounded-full opacity-0 peer-checked:opacity-100 transition-opacity duration-200"
                                    ></div>
                                </div>
                            </div>
                            <span
                                class="text-white font-medium text-sm group-hover:text-[#FBBD51] transition-colors duration-200"
                            >
                                PASAPORTE
                            </span>
                        </label>
                        <label
                            class="flex items-center gap-3 cursor-pointer group"
                        >
                            <div class="relative">
                                <input
                                    type="radio"
                                    name="documentType"
                                    value="DNI"
                                    v-model="selectedDocumentType"
                                    class="sr-only peer"
                                />
                                <div
                                    class="w-5 h-5 border-2 border-white rounded-full peer-checked:border-[#FBBD51] peer-checked:bg-[#FBBD51] transition-all duration-200 flex items-center justify-center"
                                >
                                    <div
                                        class="w-2 h-2 bg-white rounded-full opacity-0 peer-checked:opacity-100 transition-opacity duration-200"
                                    ></div>
                                </div>
                            </div>
                            <span
                                class="text-white font-medium text-sm group-hover:text-[#FBBD51] transition-colors duration-200"
                            >
                                DNI
                            </span>
                        </label>
                    </div>
                </div>

                <div
                    class="relative input-container"
                >
                    <input
                        v-model="searchQuery"
                        type="text"
                        :placeholder="documentPlaceholder"
                        :class="[
                            'w-full pl-4 pr-[120px] sm:pl-6 sm:pr-36 py-3 rounded-full border-0 shadow-lg focus:ring-2 focus:ring-teal-500 text-sm sm:text-base placeholder:text-xs sm:placeholder:text-sm placeholder:text-ellipsis placeholder:overflow-hidden',
                            rutValidation.isValid === false
                                ? 'border-red-500 ring-red-500'
                                : '',
                            rutValidation.isValid === true
                                ? 'border-green-500 ring-green-500'
                                : '',
                        ]"
                        @input="formatDocument"
                        @blur="validateDocument"
                        @keypress.enter="performSearch"
                    />
                    <div
                        v-if="rutValidation.message"
                        class="absolute -bottom-6 left-0 text-xs mt-1 validation-message"
                        :class="[
                            rutValidation.isValid === true
                                ? 'text-green-500'
                                : 'text-red-500',
                        ]"
                    >
                        {{ rutValidation.message }}
                    </div>
                    <button
                        @click="performSearch"
                        :disabled="!rutValidation.isValid"
                        :class="[
                            'absolute top-1/2 right-2 transform -translate-y-1/2 text-white font-semibold py-2 px-3 sm:px-6 rounded-full transition-colors duration-200 text-xs sm:text-base whitespace-nowrap',
                            rutValidation.isValid
                                ? 'bg-teal-500 hover:bg-teal-600'
                                : 'bg-gray-400 cursor-not-allowed',
                        ]"
                    >
                        {{ botonBuscarText }}
                    </button>
                </div>
            </div>

            <!-- Indicadores del proceso con iconos -->
            <div
                class="flex flex-col md:flex-row items-start space-y-2 md:space-y-0 md:space-x-6 mt-8 text-white text-sm"
            >
                <div class="flex items-center">
                    <div class="bg-teal-500 rounded-full p-1 mr-2">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                    <span>{{ paso1Text }}</span>
                </div>

                <div class="flex items-center">
                    <div class="bg-teal-500 rounded-full p-1 mr-2">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                    <span>{{ paso2Text }}</span>
                </div>

                <div class="flex items-center">
                    <div class="bg-teal-500 rounded-full p-1 mr-2">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                    <span>{{ paso3Text }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, watch, computed, onMounted, onUnmounted } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import { encodeParticipantToken } from "@/utils/tokenUtils";

const props = defineProps({
    content: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(["participant-not-found"]);

// Detectar si es mobile
const isMobile = ref(false);
const checkMobile = () => {
    isMobile.value = window.innerWidth < 640;
};

onMounted(() => {
    checkMobile();
    window.addEventListener('resize', checkMobile);
});

onUnmounted(() => {
    window.removeEventListener('resize', checkMobile);
});

// Helper para obtener contenido dinámico
const getContent = (key, defaultValue) => {
    return props.content?.[key]?.value || defaultValue;
};

// Computed para textos dinámicos
const paso1Text = computed(() => getContent('paso_1', 'Ingrese el RUT/DNI PASAPORTE del participante'));
const paso2Text = computed(() => getContent('paso_2', 'Seleccione el programa a pagar'));
const paso3Text = computed(() => getContent('paso_3', 'Seleccione el método de pago'));
const botonBuscarText = computed(() => getContent('boton_buscar', 'Pagar aquí'));

// Computed para imagen de fondo
const backgroundImage = computed(() => {
    const bgValue = props.content?.background?.value;
    if (!bgValue) return '/home_images/dashboard.png';
    if (bgValue.startsWith('http')) return bgValue;
    if (bgValue.startsWith('/')) return bgValue;
    if (bgValue.startsWith('site-content/')) return `/storage/${bgValue}`;
    return `/${bgValue}`;
});

const searchQuery = ref("");
const rutValidation = reactive({
    isValid: null,
    message: "",
});

const selectedDocumentType = ref("RUT");

// Watcher para cuando cambie el tipo de documento
watch(selectedDocumentType, (newType) => {
    // Limpiar validación y búsqueda cuando cambie el tipo
    rutValidation.isValid = null;
    rutValidation.message = "";
    searchQuery.value = "";
});

const documentPlaceholder = computed(() => {
    if (isMobile.value) {
        // Placeholder corto para mobile
        if (selectedDocumentType.value === "RUT") {
            return "Ingrese RUT";
        } else if (selectedDocumentType.value === "DNI") {
            return "Ingrese DNI";
        } else {
            return "Ingrese Pasaporte";
        }
    } else {
        // Placeholder largo para desktop
        if (selectedDocumentType.value === "RUT") {
            return "Ingresar Rut del participante";
        } else if (selectedDocumentType.value === "DNI") {
            return "Ingresar Número de DNI";
        } else {
            return "Ingresar Número de Pasaporte";
        }
    }
});

const formatDocument = () => {
    if (selectedDocumentType.value === "RUT") {
        // Solo formatear RUTs
        let documentNumber = searchQuery.value.replace(/[^0-9kK]/g, "");

        if (documentNumber.length > 0) {
            documentNumber = documentNumber.toUpperCase();

            // Si tiene más de 1 carácter, separar cuerpo y dígito verificador
            if (documentNumber.length > 1) {
                const body = documentNumber.slice(0, -1);
                const dv = documentNumber.slice(-1);

                // Formatear el cuerpo con puntos
                let formattedBody = "";
                for (let i = body.length - 1, j = 0; i >= 0; i--, j++) {
                    if (j > 0 && j % 3 === 0) {
                        formattedBody = "." + formattedBody;
                    }
                    formattedBody = body[i] + formattedBody;
                }

                // Combinar cuerpo formateado con dígito verificador
                searchQuery.value = `${formattedBody}-${dv}`;
            } else {
                searchQuery.value = documentNumber;
            }
        }
    } else if (selectedDocumentType.value === "DNI") {
        // Para DNI, solo permitir números y formatear con puntos
        let documentNumber = searchQuery.value.replace(/[^0-9]/g, "");

        if (documentNumber.length > 0) {
            // Formatear DNI argentino con puntos (ej: 12.345.678)
            if (documentNumber.length > 6) {
                const formatted = documentNumber.replace(
                    /(\d{2})(\d{3})(\d{3})/,
                    "$1.$2.$3"
                );
                searchQuery.value = formatted;
            } else if (documentNumber.length > 3) {
                const formatted = documentNumber.replace(
                    /(\d{2})(\d{3})/,
                    "$1.$2"
                );
                searchQuery.value = formatted;
            } else {
                searchQuery.value = documentNumber;
            }
        }
    } else {
        // Para pasaporte, solo convertir a mayúsculas y limpiar espacios
        searchQuery.value = searchQuery.value.toUpperCase().trim();
    }

    // Validar el documento después de formatearlo
    validateDocument();
};

const validateDocument = () => {
    const documentNumber = searchQuery.value
        .replace(/\./g, "")
        .replace(/-/g, "");

    if (documentNumber.length === 0) {
        rutValidation.isValid = null;
        rutValidation.message = "";
        return;
    }

    if (selectedDocumentType.value === "RUT") {
        // Validar RUT
        if (!/^[0-9]+[0-9kK]$/.test(documentNumber)) {
            rutValidation.isValid = false;
            rutValidation.message = "Formato de RUT inválido";
            return;
        }

        // Separar cuerpo y dígito verificador
        const body = documentNumber.slice(0, -1);
        const dv = documentNumber.slice(-1).toUpperCase();

        // Validar que el cuerpo tenga al menos 7 dígitos
        if (body.length < 7) {
            rutValidation.isValid = false;
            rutValidation.message = "RUT debe tener al menos 7 dígitos";
            return;
        }

        // Calcular dígito verificador
        const dvCalculado = calculateDv(body);

        // Comparar dígitos verificadores
        rutValidation.isValid = dv === dvCalculado;
        rutValidation.message = rutValidation.isValid
            ? "RUT válido"
            : "RUT inválido";
    } else if (selectedDocumentType.value === "DNI") {
        // Validar DNI argentino
        if (!/^[0-9]+$/.test(documentNumber)) {
            rutValidation.isValid = false;
            rutValidation.message = "DNI debe contener solo números";
            return;
        }

        // Validar longitud del DNI (7-8 dígitos)
        if (documentNumber.length < 7 || documentNumber.length > 8) {
            rutValidation.isValid = false;
            rutValidation.message = "DNI debe tener entre 7 y 8 dígitos";
            return;
        }

        rutValidation.isValid = true;
        rutValidation.message = "DNI válido";
    } else {
        // Validar PASAPORTE (más flexible)
        if (documentNumber.length < 3) {
            rutValidation.isValid = false;
            rutValidation.message =
                "Pasaporte debe tener al menos 3 caracteres";
            return;
        }

        // Para pasaporte, solo validar que tenga caracteres válidos
        if (!/^[A-Z0-9]+$/i.test(documentNumber)) {
            rutValidation.isValid = false;
            rutValidation.message = "Pasaporte contiene caracteres inválidos";
            return;
        }

        rutValidation.isValid = true;
        rutValidation.message = ""; // No mostrar mensaje de "válido" para pasaporte
    }
};

const calculateDv = (body) => {
    let sum = 0;
    let factor = 2;
    for (let i = body.length - 1; i >= 0; i--) {
        sum += body[i] * factor;
        factor = factor === 7 ? 2 : factor + 1;
    }
    const dv = 11 - (sum % 11);
    return dv === 10 ? "K" : dv === 11 ? "0" : dv.toString();
};

const performSearch = async () => {
    if (searchQuery.value.trim() && rutValidation.isValid) {
        // Limpiar el documento antes de enviarlo
        const cleanDocument = searchQuery.value
            .replace(/\./g, "")
            .replace(/-/g, "");

        try {
            const response = await axios.post(
                route("ecommerce.search-participant"),
                {
                    document: cleanDocument,
                    document_type: selectedDocumentType.value,
                }
            );
            if (response.data.found) {
                // Guardar session_id en localStorage para mantener la sesión en todo el flujo
                if (response.data.session_id) {
                    localStorage.setItem(
                        "analytics_session_id",
                        response.data.session_id
                    );
                }

                // Codificar datos en token
                const token = encodeParticipantToken({
                    document: cleanDocument,
                    document_type: selectedDocumentType.value,
                });

                // Redirigir a la vista de programas con token
                router.get(route("ecommerce.programs"), {
                    token: token,
                });
            } else {
                emit("participant-not-found");
            }
        } catch (error) {
            console.error("Error en la búsqueda:", error);
        }
    }
};
</script>

<style scoped>
/* Estilos adicionales si son necesarios */

/* Color de fondo para toda la sección */
.relative.h-\[80vh\].rounded-3xl.overflow-hidden.mx-4.my-4 {
    background-color: #f9f9f9;
}

/* Estilos para el mensaje de validación */
.validation-message {
    font-size: 0.75rem;
    line-height: 1rem;
    margin-top: 0.25rem;
    z-index: 10;
}

/* Ajustar el contenedor del input para el mensaje de validación */
.input-container {
    position: relative;
    margin-bottom: 1.5rem;
}

/* Estilos específicos para mobile */
@media (max-width: 768px) {
    /* Componente mobile circles */
    .mobile-circle-item {
        width: 77.927px !important;
        height: 36.4635px !important;
        background: #fbbd51 !important;
        border-radius: 77.927px 77.927px 0 0 !important;
        flex-shrink: 0 !important;
    }

    /* Ajustar distribución del navbar mobile */
    .absolute.left-0.right-0.bottom-0.flex.flex-row.justify-center.md\\:hidden {
        padding: 0 20px !important;
        gap: 8px !important;
    }

    /* Centrar logo SVG verticalmente */
    .capa-1 {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .capa-1 svg {
        align-self: center !important;
    }
}
</style>
