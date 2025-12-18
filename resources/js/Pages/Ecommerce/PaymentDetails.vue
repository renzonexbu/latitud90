<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <Header />

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Process Steps -->
            <ProcessSteps :current-step="3" />

            <!-- Content Section -->
			<div class="mt-6 text-center">
				<h2 class="text-[#1C4F4A] font-nexa-bold text-[18px] leading-[22px]">
					Ingrese los datos del comprador
				</h2>
			</div>

			<div class="mt-8 flex justify-center">
                <!-- Form -->
                <div class="flex flex-col gap-8 w-full">
                    <div class="flex flex-col md:flex-row items-center md:items-start md:justify-center gap-6 md:gap-[54px] w-full md:w-auto mx-auto">
                        <!-- Left Column - Document and Personal Information -->
                        <div class="flex flex-col gap-[18px] w-full md:w-[364px]">
                            <!-- Tipo de Documento -->
                            <div class="flex flex-col gap-[12px]">
                                <label
                                    class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal"
                                >
                                    Tipo de documento *
                                </label>
                                <div class="flex gap-6">
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <div class="relative">
                                            <input
                                                type="radio"
                                                name="documentType"
                                                :value="getDocumentTypeId('RUT')"
                                                v-model="formData.documentType"
                                                class="sr-only peer"
                                            />
                                            <div class="w-5 h-5 border-2 border-[#5B5B5B] rounded-full peer-checked:border-[#FBBD51] peer-checked:bg-[#FBBD51] transition-all duration-200 flex items-center justify-center">
                                                <div class="w-2 h-2 bg-white rounded-full opacity-0 peer-checked:opacity-100 transition-opacity duration-200"></div>
                                            </div>
                                        </div>
                                        <span class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal group-hover:text-[#FBBD51] transition-colors duration-200">
                                            RUT
                                        </span>
                                    </label>
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <div class="relative">
                                            <input
                                                type="radio"
                                                name="documentType"
                                                :value="getDocumentTypeId('Pasaporte')"
                                                v-model="formData.documentType"
                                                class="sr-only peer"
                                            />
                                            <div class="w-5 h-5 border-2 border-[#5B5B5B] rounded-full peer-checked:border-[#FBBD51] peer-checked:bg-[#FBBD51] transition-all duration-200 flex items-center justify-center">
                                                <div class="w-2 h-2 bg-white rounded-full opacity-0 peer-checked:opacity-100 transition-opacity duration-200"></div>
                                            </div>
                                        </div>
                                        <span class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal group-hover:text-[#FBBD51] transition-colors duration-200">
                                            Pasaporte
                                        </span>
                                    </label>
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <div class="relative">
                                            <input
                                                type="radio"
                                                name="documentType"
                                                :value="getDocumentTypeId('DNI')"
                                                v-model="formData.documentType"
                                                class="sr-only peer"
                                            />
                                            <div class="w-5 h-5 border-2 border-[#5B5B5B] rounded-full peer-checked:border-[#FBBD51] peer-checked:bg-[#FBBD51] transition-all duration-200 flex items-center justify-center">
                                                <div class="w-2 h-2 bg-white rounded-full opacity-0 peer-checked:opacity-100 transition-opacity duration-200"></div>
                                            </div>
                                        </div>
                                        <span class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal group-hover:text-[#FBBD51] transition-colors duration-200">
                                            DNI
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <!-- Número de Documento -->
                            <div class="flex flex-col gap-[12px]">
                                <label
                                    class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal"
                                >
                                    {{ getDocumentLabel() }} *
                                </label>
                                <input
                                    type="text"
                                    :placeholder="getDocumentPlaceholder()"
                                    :class="[
                                        'w-full h-[46px] bg-white rounded-lg border px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]',
                                        (isRutDocument || isDniDocument) &&
                                        documentValidation.isValid === false
                                            ? 'border-red-500'
                                            : '',
                                        (isRutDocument || isDniDocument) &&
                                        documentValidation.isValid === true
                                            ? 'border-green-500'
                                            : 'border-[#5B5B5B]',
                                    ]"
                                    v-model="formData.documentNumber"
                                    @input="handleDocumentInput"
                                    @blur="handleDocumentBlur"
                                />
                                <div
                                    v-if="
                                        (isRutDocument || isDniDocument) && documentValidation.message
                                    "
                                    class="text-xs mt-1 validation-message"
                                    :class="[
                                        documentValidation.isValid === true
                                            ? 'text-green-500'
                                            : 'text-red-500',
                                    ]"
                                >
                                    {{ documentValidation.message }}
                                </div>
                            </div>

                            <!-- Nombre completo -->
                            <div class="flex flex-col gap-[12px]">
                                <label
                                    class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal"
                                >
                                    Nombre completo *
                                </label>
                                <input
                                    type="text"
                                    placeholder="Nombre y apellido"
                                    class="w-full h-[46px] bg-white rounded-lg border border-[#5B5B5B] px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
                                    v-model="formData.fullName"
                                />
                            </div>

                            <!-- Correo electrónico -->
                            <div class="flex flex-col gap-[12px]">
                                <label
                                    class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal"
                                >
                                    Correo electrónico *
                                </label>
                                <input
                                    type="email"
                                    placeholder="Escriba su correo electronico"
                                    class="w-full h-[46px] bg-white rounded-lg border border-[#5B5B5B] px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
                                    v-model="formData.email"
                                />
                            </div>

                            <!-- Número de celular -->
                            <div class="flex flex-col gap-[12px]">
                                <label
                                    class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal"
                                >
                                    Número de celular *
                                </label>
                                <div class="flex">
                                    <select
                                        v-model="formData.code_phone"
                                        class="w-[70px] h-[46px] bg-white border border-[#5B5B5B] rounded-l border-r-0 flex items-center justify-center gap-2 px-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none appearance-none"
                                    >
                                        <option value="+56">🇨🇱</option>
                                        <option value="+54">🇦🇷</option>
                                        <option value="+51">🇵🇪</option>
                                        <option value="+598">🇺🇾</option>
                                    </select>
                                    <input
                                        type="tel"
                                        placeholder="9-- --- ---"
                                        class="flex-1 h-[46px] bg-white border rounded-r px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
                                        v-model="formData.phone"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Location and Agreements -->
                        <div class="flex flex-col gap-[21px] w-full md:w-[366px]">
                            <!-- Location Information -->
                            <div class="flex flex-col gap-[21px]">
                                <!-- País -->
                                <div class="flex flex-col gap-[12px] mt-0 md:mt-[66px]">
                                    <label
                                        class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal"
                                    >
                                        País *
                                    </label>
                                    <SearchableSelect
                                        ref="countrySelect"
                                        :options="countries"
                                        :value="formData.country"
                                        placeholder="Busca y selecciona tu país"
                                        @input="handleCountryChange"
                                        search-key="name"
                                    />
                                </div>

                                <div class="flex flex-col gap-[12px]">
                                    <label
                                        class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal"
                                    >
                                        Región *
                                    </label>
                                    <SearchableSelect
                                        :options="regions"
                                        :value="formData.region"
                                        placeholder="Busca y selecciona tu región"
                                        @input="handleRegionChange"
                                        search-key="name"
                                    />
                                </div>

                                <div class="flex flex-col gap-[12px]">
                                    <label
                                        class="text-[#434343] font-nexa text-[14px] leading-[18px] font-normal"
                                    >
                                        Comuna *
                                    </label>
                                    <SearchableSelect
                                        :options="filteredComunes"
                                        :value="formData.city"
                                        placeholder="Busca y selecciona tu comuna"
                                        :disabled="!formData.region"
                                        @input="handleCityChange"
                                        search-key="name"
                                    />
                                </div>
                            </div>

                            <!-- Agreements - Moved to align with phone input -->
                            <div class="flex flex-col gap-[7px] mt-[18px]">
                                <!-- Marketing Agreement -->
                                <div class="flex flex-col gap-[8px]">
                                    <label class="flex items-center gap-[8px] cursor-pointer">
                                        <input
                                            id="marketing-checkbox"
                                            type="checkbox"
                                            class="custom-checkbox w-[12px] h-[12px] rounded-[1.5px] cursor-pointer"
                                            v-model="formData.marketingAccepted"
                                        />
                                        <span
                                            class="text-[#434343] font-nexa text-[10px] leading-[16px] font-normal"
                                        >
                                            Acepto recibir información sobre
                                            programas educativos, viajes y
                                            ofertas
                                        </span>
                                    </label>
                                </div>

                                <!-- Terms and Conditions -->
                                <div class="flex flex-col gap-[8px]">
                                    <label class="flex items-center gap-[8px] cursor-pointer">
                                        <input
                                            id="terms-checkbox"
                                            type="checkbox"
                                            class="custom-checkbox w-[12px] h-[12px] rounded-[1.5px] cursor-pointer"
                                            v-model="formData.termsAccepted"
                                        />
                                        <span
                                            class="text-[#434343] font-nexa text-[10px] leading-[16px] font-normal"
                                        >
                                            <span class="font-nexa"
                                                >Acepto todos los</span
                                            >
                                            <a
                                                href="/terminos-y-condiciones"
                                                target="_blank"
                                                class="font-nexa-bold font-bold underline hover:text-[#007E93] transition-colors"
                                                @click.stop
                                            >
                                                términos y condiciones*
                                            </a>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col md:flex-row md:justify-between items-stretch md:items-center w-full gap-4 md:gap-8 mt-12">
                <!-- Back Button -->
                <div class="w-full md:w-auto">
                    <BackToHomeButton
                        :token="token"
                        variant="programs"
                        :route="`/programs/${programId}?token=${token}`"
                    />
                </div>

                <!-- Continue Button -->
                <button
                    class="rounded-[59px] bg-[#C7C7C7] w-full md:w-auto flex px-[20px] py-[11px] justify-center items-center gap-[12px] transition-colors duration-300"
                    :class="{
                        'bg-[#FBBD51] cursor-pointer': isFormValid,
                        'bg-[#C7C7C7] cursor-not-allowed': !isFormValid,
                    }"
                    :disabled="!isFormValid"
                    @click="continueToPayment"
                    @mouseenter="checkButtonState"
                >
                    <span class="text-white font-medium">Continuar</span>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path
                            d="M2.25 12C2.25 17.385 6.615 21.75 12 21.75C17.385 21.75 21.75 17.385 21.75 12C21.75 6.615 17.385 2.25 12 2.25C6.615 2.25 2.25 6.615 2.25 12ZM12.47 7.97C12.6106 7.82955 12.8012 7.75066 13 7.75066C13.1988 7.75066 13.3894 7.82955 13.53 7.97L17.03 11.47C17.1705 11.6106 17.2493 11.8012 17.2493 12C17.2493 12.1988 17.1705 12.3894 17.03 12.53L13.53 16.03C13.4613 16.1037 13.3785 16.1628 13.2865 16.2038C13.1945 16.2448 13.0952 16.2668 12.9945 16.2686C12.8938 16.2704 12.7938 16.2518 12.7004 16.2141C12.607 16.1764 12.5222 16.1203 12.451 16.049C12.3797 15.9778 12.3236 15.893 12.2859 15.7996C12.2482 15.7062 12.2296 15.6062 12.2314 15.5055C12.2332 15.4048 12.2552 15.3055 12.2962 15.2135C12.3372 15.1215 12.3963 15.0387 12.47 14.97L14.69 12.75H7.5C7.30109 12.75 7.11032 12.671 6.96967 12.5303C6.82902 12.3897 6.75 12.1989 6.75 12C6.75 11.8011 6.82902 11.6103 6.96967 11.4697C7.11032 11.329 7.30109 11.25 7.5 11.25H14.69L12.47 9.03C12.3295 8.88937 12.2507 8.69875 12.2507 8.5C12.2507 8.30125 12.3295 8.11063 12.47 7.97Z"
                            fill="white"
                        />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Footer -->
        <Footer class="mt-16" />

        <!-- Sistema de Alertas -->
        <Alerts
            :show="showAlert"
            :type="alertType"
            :title="alertTitle"
            :message="alertMessage"
            :auto-close="true"
            :duration="5000"
            @close="closeAlert"
        />
    </div>
</template>

<script>
import { Head } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";
import { decodeParticipantToken } from "@/utils/tokenUtils";
import Header from "@/Components/Ecommerce/Header.vue";
import Footer from "@/Components/Ecommerce/Footer.vue";
import ProcessSteps from "@/Components/Ecommerce/ProcessSteps.vue";
import BackToHomeButton from "@/Components/Ecommerce/BackToHomeButton.vue";
import SearchableSelect from "@/Components/Ecommerce/SearchableSelect.vue";
import Alerts from "@/Components/Alerts.vue";

export default {
    name: "PaymentDetails",
    components: {
        Header,
        Footer,
        ProcessSteps,
        BackToHomeButton,
        SearchableSelect,
        Alerts,
    },
    props: {
        paymentData: {
            type: Object,
            required: true,
        },
        programId: {
            type: [String, Number],
            required: true,
        },
        token: {
            type: String,
            required: true,
        },
        countries: {
            type: Array,
            default: () => [],
        },
        regions: {
            type: Array,
            default: () => [],
        },
        documentTypes: {
            type: Array,
            default: () => [],
        },
        guardian: {
            type: Object,
            default: null,
        },
    },
    data() {
        return {
            document: null,
            document_type: null,
            selectedPaymentType: "total",
            selectedPaymentMethod: "debit",
            formData: {
                fullName: "",
                documentType: "",
                documentNumber: "",
                email: "",
                phone: "",
                code_phone: "+56",
                country: "",
                region: "",
                city: "",
                termsAccepted: false,
                marketingAccepted: false,
                isFrequentClient: false, // Nuevo campo para indicar si es cliente frecuente
            },
            documentValidation: {
                isValid: null,
                message: "",
            },
            // Estado de alertas
            showAlert: false,
            alertType: "error",
            alertTitle: "",
            alertMessage: "",
        };
    },
    created() {
        // Decodificar token para obtener document y document_type
        const data = decodeParticipantToken(this.token);
        if (data) {
            this.document = data.document;
            this.document_type = data.document_type;
        }
    },
    computed: {
        filteredComunes() {
            if (!this.formData.region) {
                return [];
            }

            const selectedRegion = this.regions.find(
                (r) => r.id == this.formData.region
            );

            if (!selectedRegion || !selectedRegion.comunes) {
                return [];
            }

            return selectedRegion.comunes;
        },

        isRutDocument() {
            if (!this.formData.documentType) return false;
            const selectedDocType = this.documentTypes.find(
                (doc) => doc.id == this.formData.documentType
            );
            return (
                selectedDocType && selectedDocType.name.toLowerCase() === "rut"
            );
        },
        isDniDocument() {
            if (!this.formData.documentType) return false;
            const selectedDocType = this.documentTypes.find(
                (doc) => doc.id == this.formData.documentType
            );
            return (
                selectedDocType && selectedDocType.name.toLowerCase() === "dni"
            );
        },
        isFormValid() {
            const validations = {
                fullName: this.formData.fullName.trim() !== "",
                documentType: this.formData.documentType !== "",
                documentNumber: this.formData.documentNumber.trim() !== "",
                email: this.formData.email.trim() !== "",
                phone: this.formData.phone.trim() !== "",
                country: this.formData.country !== "",
                region: this.formData.region !== "",
                city: this.formData.city !== "",
                termsAccepted: this.formData.termsAccepted,
            };

            const basicValidation = Object.values(validations).every(
                (v) => v === true
            );
            const documentOk = (this.isRutDocument || this.isDniDocument)
                ? this.documentValidation.isValid === true
                : true;
            return basicValidation && documentOk;
        },
    },
    mounted() {
        // Verificar datos iniciales

        // Registrar vista de detalles de pago en analytics
        this.recordPaymentDetailsView();

        // Establecer RUT como tipo de documento por defecto
        this.formData.documentType = this.getDocumentTypeId('RUT');

        // ✅ AUTOCOMPLETAR con datos del guardian si está logeado
        if (this.guardian) {
            console.log('✅ Guardian logeado detectado, autocompletando formulario:', this.guardian);

            this.formData.fullName = this.guardian.name || '';
            this.formData.documentType = this.guardian.document_id || this.getDocumentTypeId('RUT');
            this.formData.documentNumber = this.guardian.document || '';
            this.formData.email = this.guardian.email || '';
            this.formData.phone = this.guardian.phone || '';
            this.formData.code_phone = this.guardian.phone_code || '+56';
            this.formData.country = this.guardian.country_id || '';
            this.formData.region = this.guardian.region_id || '';

            // Para la comuna, esperar a que se carguen las comunas después de establecer la región
            // Mismo patrón que autocompleteForm() para cliente frecuente
            this.$nextTick(() => {
                // Esperar un poco más para que las comunas se carguen completamente
                setTimeout(() => {
                    if (this.filteredComunes.length > 0 && this.guardian.comune_id) {
                        this.formData.city = this.guardian.comune_id;
                        console.log('✅ Comuna autocompletada:', this.guardian.comune_id);
                    } else {
                        // Reintentar si las comunas no están disponibles
                        setTimeout(() => {
                            if (this.filteredComunes.length > 0 && this.guardian.comune_id) {
                                this.formData.city = this.guardian.comune_id;
                                console.log('✅ Comuna autocompletada (reintento):', this.guardian.comune_id);
                            }
                        }, 200);
                    }
                }, 300);
            });
        }

        // Leer los datos de pago del localStorage
        const savedPaymentData = localStorage.getItem("selectedPaymentData");
        if (savedPaymentData) {
            try {
                const paymentData = JSON.parse(savedPaymentData);
                this.selectedPaymentType = paymentData.paymentType;
                this.selectedPaymentMethod = paymentData.paymentMethod;
            } catch (error) {
            }
        } else {
        }

        // Leer los datos del comprador del localStorage solo si viene del paso 4 (confirmation)
        const urlParams = new URLSearchParams(window.location.search);
        const isComingFromConfirmation =
            urlParams.get("from") === "confirmation";

        if (isComingFromConfirmation) {
            const savedBuyerData = localStorage.getItem("buyerData");
            if (savedBuyerData) {
                try {
                    const buyerData = JSON.parse(savedBuyerData);

                    // Convertir IDs a números para los SearchableSelect
                    const countryId = buyerData.countryId
                        ? parseInt(buyerData.countryId)
                        : "";
                    const regionId = buyerData.regionId
                        ? parseInt(buyerData.regionId)
                        : "";
                    const cityId = buyerData.cityId
                        ? parseInt(buyerData.cityId)
                        : "";

                    // Buscar el ID del tipo de documento por nombre
                    let documentTypeId = "";
                    if (buyerData.documentType) {
                        const docType = this.documentTypes.find(
                            (doc) =>
                                doc.name.toLowerCase() ===
                                buyerData.documentType.toLowerCase()
                        );
                        documentTypeId = docType ? docType.id : "";
                    }

                    // Cargar los datos del formulario desde localStorage
                    this.formData = {
                        fullName: buyerData.name || "",
                        documentType: documentTypeId || this.getDocumentTypeId('RUT'),
                        documentNumber: buyerData.documentNumber || "",
                        email: buyerData.email || "",
                        phone: buyerData.phone || "",
                        code_phone: buyerData.code_phone || "+56",
                        country: countryId,
                        region: regionId,
                        city: cityId,
                        termsAccepted: buyerData.termsAccepted || false,
                        marketingAccepted: buyerData.marketingAccepted || false,
                        isFrequentClient: buyerData.isFrequentClient || false, // Cargar el nuevo campo
                    };

                    // Forzar actualización de los SearchableSelect después de cargar los datos
                    this.$nextTick(() => {
                        // Trigger validation para actualizar el estado del formulario
                        this.validateForm();

                        // Si es un RUT o DNI, validar el formato
                        if (
                            (this.isRutDocument || this.isDniDocument) &&
                            this.formData.documentNumber
                        ) {
                            this.validateDocument();
                        }

                        // Forzar actualización de los SearchableSelect con delay para asegurar que las opciones estén disponibles
                        setTimeout(() => {
                            this.forceUpdateSearchableSelects();
                        }, 100);
                    });
                } catch (error) {
                }
            } else {
            }
        } else {
            // Limpiar localStorage si no viene del paso 4
            localStorage.removeItem("buyerData");
        }

        // No forzar validación; el computed isFormValid reacciona de inmediato
    },
    watch: {
        // Eliminado watcher profundo que recalculaba manualmente

        "formData.documentType"() {
            // Limpiar validación de documento cuando cambie el tipo de documento
            this.documentValidation.isValid = null;
            this.documentValidation.message = "";
            this.formData.documentNumber = "";
        },

        "formData.region": {
            handler(newRegionId, oldRegionId) {

                if (newRegionId && newRegionId !== oldRegionId) {
                    // Limpiar comuna cuando cambie la región
                    this.formData.city = "";

                    // Si hay una ciudad guardada en localStorage, intentar cargarla después de que las comunas estén disponibles
                    const savedBuyerData = localStorage.getItem("buyerData");
                    if (savedBuyerData) {
                        try {
                            const buyerData = JSON.parse(savedBuyerData);
                            if (buyerData.cityId) {
                                // Esperar a que las comunas estén disponibles
                                this.$nextTick(() => {
                                    setTimeout(() => {
                                        const cityId = parseInt(
                                            buyerData.cityId
                                        );
                                        if (
                                            cityId &&
                                            this.filteredComunes.length > 0
                                        ) {
                                            const city =
                                                this.filteredComunes.find(
                                                    (c) => c.id == cityId
                                                );
                                            if (city) {
                                                this.handleCityChange(cityId);
                                            }
                                        }
                                    }, 200);
                                });
                            }
                        } catch (error) {
                        }
                    }
                }
            },
            immediate: false,
        },

        // Eliminado watcher que escribía en localStorage en cada cambio

        // Watcher para manejar cambios en el tipo de documento
        "formData.documentType"(newValue) {
            // Si se selecciona un tipo de documento, validar el número si ya existe
            if (newValue && this.formData.documentNumber) {
                this.$nextTick(() => {
                    this.validateDocument();
                });
            }
        },

        // Watcher para cuando las opciones estén disponibles
        countries: {
            handler() {
                if (this.formData.country && this.countries.length > 0) {
                    this.$nextTick(() => {
                        this.forceUpdateSearchableSelects();
                    });
                }
            },
            immediate: true,
        },

        regions: {
            handler() {
                if (this.formData.region && this.regions.length > 0) {
                    this.$nextTick(() => {
                        this.forceUpdateSearchableSelects();
                    });
                }
            },
            immediate: true,
        },

        documentTypes: {
            handler() {
                if (
                    this.formData.documentType &&
                    this.documentTypes.length > 0
                ) {
                    this.$nextTick(() => {
                        this.forceUpdateSearchableSelects();
                    });
                }
            },
            immediate: true,
        },

        // Watcher para las comunas filtradas
        filteredComunes: {
            handler(newComunes, oldComunes) {

                // Si hay una ciudad seleccionada y las comunas están disponibles, actualizar
                if (this.formData.city && newComunes.length > 0) {
                    const city = newComunes.find(
                        (c) => c.id == this.formData.city
                    );
                    if (city) {
                        this.$nextTick(() => {
                            this.handleCityChange(this.formData.city);
                        });
                    }
                }
            },
            immediate: true,
        },
    },
    methods: {
        validateForm() {
            // Ya no es necesario: isFormValid es computed
        },

        getDocumentLabel() {
            if (!this.formData.documentType) return "Número de documento";

            const selectedDocType = this.documentTypes.find(
                (doc) => doc.id == this.formData.documentType
            );

            return selectedDocType
                ? selectedDocType.name
                : "Número de documento";
        },

        getDocumentPlaceholder() {
            if (!this.formData.documentType)
                return "Ingresa tu número de documento";

            const selectedDocType = this.documentTypes.find(
                (doc) => doc.id == this.formData.documentType
            );

            if (!selectedDocType) return "Ingresa tu número de documento";

            switch (selectedDocType.name.toLowerCase()) {
                case "rut":
                    return "Ej: 12.345.678-9";
                case "pasaporte":
                    return "Ej: A12345678";
                case "dni":
                    return "Ej: 12.345.678";
                default:
                    return "Ingresa tu número de documento";
            }
        },

        getDocumentTypeId(name) {
            const docType = this.documentTypes.find(
                (doc) => doc.name.toLowerCase() === name.toLowerCase()
            );
            return docType ? docType.id : "";
        },

        handleRegionChange(regionId) {
            this.formData.region = regionId;
            this.formData.city = ""; // Limpiar comuna

            // Verificar las comunas disponibles
            if (regionId) {
                const selectedRegion = this.regions.find(
                    (r) => r.id == regionId
                );

                if (!selectedRegion || !selectedRegion.comunes) {
                    return;
                }

                // Forzar la validación del formulario
                this.$nextTick(() => {
                    this.validateForm();
                });
            }
        },

        handleCountryChange(countryId) {
            this.formData.country = countryId;

            // Forzar la validación del formulario
            this.$nextTick(() => {
                this.validateForm();
            });
        },

        handleCityChange(cityId) {
            this.formData.city = cityId;

            // Forzar la validación del formulario
            this.$nextTick(() => {
                this.validateForm();
            });
        },

        handleDocumentInput() {
            if (this.isRutDocument) {
                this.formatRut();
            } else if (this.isDniDocument) {
                this.formatDni();
            }
        },

        async handleDocumentBlur() {
            // Buscar cliente frecuente primero si hay tipo de documento y número
            if (this.formData.documentType && this.formData.documentNumber.trim()) {
                const foundClient = await this.searchFrequentClient();
                
                // Solo validar el documento si NO se encontró un cliente frecuente
                if (!foundClient && (this.isRutDocument || this.isDniDocument)) {
                    this.validateDocument();
                }
            } else if (this.isRutDocument || this.isDniDocument) {
                // Si no hay documento para buscar cliente frecuente, validar documento
                this.validateDocument();
            }
        },

        async searchFrequentClient() {
            try {
                const response = await fetch('/frequent-clients/find-by-document', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        document_id: this.formData.documentType,
                        document: this.formData.documentNumber.trim()
                    })
                });

                const result = await response.json();

                if (result.success && result.data) {
                    // Autocompletar el formulario con los datos del cliente frecuente
                    this.autocompleteForm(result.data);
                    return true; // Cliente encontrado
                }
                
                return false; // Cliente no encontrado
            } catch (error) {
                // Mostrar alerta de error genérico
                this.showClientSearchErrorAlert();
                return false; // Error en la búsqueda
            }
        },

        autocompleteForm(clientData) {
            // Autocompletar todos los campos del formulario
            this.formData.fullName = clientData.full_name;
            this.formData.email = clientData.email;
            this.formData.phone = clientData.phone;
            this.formData.code_phone = clientData.phone_code;
            this.formData.country = clientData.country_id;
            this.formData.region = clientData.region_id;
            // NO autocompletar los checkboxes - el usuario debe seleccionarlos manualmente
            // this.formData.termsAccepted = clientData.terms_accepted;
            // this.formData.marketingAccepted = clientData.marketing_accepted;
            
            // Marcar que es un cliente frecuente
            this.formData.isFrequentClient = true;
            
            // Limpiar cualquier validación de documento previa ya que es un cliente frecuente
            this.documentValidation.isValid = true;
            this.documentValidation.message = "";

            // Para la comuna, esperar a que se carguen las comunas después de establecer la región
            this.$nextTick(() => {
                // Esperar un poco más para que las comunas se carguen completamente
                setTimeout(() => {
                    if (this.filteredComunes.length > 0) {
                        this.formData.city = clientData.comune_id;
                        // Comuna establecida
                    } else {
                        // Reintentar si las comunas no están disponibles
                        setTimeout(() => {
                            if (this.filteredComunes.length > 0) {
                                this.formData.city = clientData.comune_id;
                            }
                        }, 200);
                    }
                }, 300);
            });

        },

        validateDocument() {
            if (this.isRutDocument) {
                this.validateRut();
            } else if (this.isDniDocument) {
                this.validateDni();
            }
        },

        formatDni() {
            // Para DNI, solo permitir números y formatear con puntos
            let documentNumber = this.formData.documentNumber.replace(/[^0-9]/g, "");
            
            if (documentNumber.length > 0) {
                // Formatear DNI argentino con puntos (ej: 12.345.678)
                if (documentNumber.length > 6) {
                    const formatted = documentNumber.replace(/(\d{2})(\d{3})(\d{3})/, '$1.$2.$3');
                    this.formData.documentNumber = formatted;
                } else if (documentNumber.length > 3) {
                    const formatted = documentNumber.replace(/(\d{2})(\d{3})/, '$1.$2');
                    this.formData.documentNumber = formatted;
                } else {
                    this.formData.documentNumber = documentNumber;
                }
            }

            // Validar el DNI después de formatearlo
            this.validateDni();
        },

        validateDni() {
            const documentNumber = this.formData.documentNumber.replace(/\./g, "");

            if (documentNumber.length === 0) {
                this.documentValidation.isValid = null;
                this.documentValidation.message = "";
                return;
            }

            // Validar DNI argentino
            if (!/^[0-9]+$/.test(documentNumber)) {
                this.documentValidation.isValid = false;
                this.documentValidation.message = "DNI debe contener solo números";
                return;
            }

            // Validar longitud del DNI (7-8 dígitos)
            if (documentNumber.length < 7 || documentNumber.length > 8) {
                this.documentValidation.isValid = false;
                this.documentValidation.message = "DNI debe tener entre 7 y 8 dígitos";
                return;
            }

            this.documentValidation.isValid = true;
            this.documentValidation.message = "DNI válido";
        },

        formatRut() {
            // Remover todos los caracteres no numéricos excepto K
            let rut = this.formData.documentNumber.replace(/[^0-9kK]/g, "");

            if (rut.length > 0) {
                rut = rut.toUpperCase();

                // Si tiene más de 1 carácter, separar cuerpo y dígito verificador
                if (rut.length > 1) {
                    const body = rut.slice(0, -1);
                    const dv = rut.slice(-1);

                    // Formatear el cuerpo con puntos
                    let formattedBody = "";
                    for (let i = body.length - 1, j = 0; i >= 0; i--, j++) {
                        if (j > 0 && j % 3 === 0) {
                            formattedBody = "." + formattedBody;
                        }
                        formattedBody = body[i] + formattedBody;
                    }

                    // Combinar cuerpo formateado con dígito verificador
                    this.formData.documentNumber = `${formattedBody}-${dv}`;
                } else {
                    this.formData.documentNumber = rut;
                }
            }

            // Validar el RUT después de formatearlo
            this.validateRut();
        },

        validateRut() {
            const rut = this.formData.documentNumber
                .replace(/\./g, "")
                .replace(/-/g, "");

            if (rut.length === 0) {
                this.documentValidation.isValid = null;
                this.documentValidation.message = "";
                return;
            }

            // Validar formato básico
            if (!/^[0-9]+[0-9kK]$/.test(rut)) {
                this.documentValidation.isValid = false;
                this.documentValidation.message = "Formato de RUT inválido";
                return;
            }

            // Separar cuerpo y dígito verificador
            const body = rut.slice(0, -1);
            const dv = rut.slice(-1).toUpperCase();

            // Validar que el cuerpo tenga al menos 7 dígitos
            if (body.length < 7) {
                this.documentValidation.isValid = false;
                this.documentValidation.message =
                    "RUT debe tener al menos 7 dígitos";
                return;
            }

            // Calcular dígito verificador
            const dvCalculado = this.calculateDv(body);

            // Comparar dígitos verificadores
            this.documentValidation.isValid = dv === dvCalculado;
            this.documentValidation.message = this.documentValidation.isValid
                ? "RUT válido"
                : "RUT inválido";

        },

        calculateDv(body) {
            let sum = 0;
            let factor = 2;
            for (let i = body.length - 1; i >= 0; i--) {
                sum += body[i] * factor;
                factor = factor === 7 ? 2 : factor + 1;
            }
            const dv = 11 - (sum % 11);
            return dv === 10 ? "K" : dv === 11 ? "0" : dv.toString();
        },

        goBackToProgram() {
            // Usar el router directamente para ir a program detail con token
            router.visit(`/programs/${this.programId}?token=${this.token}`);
        },
        continueToPayment() {
            if (!this.isFormValid) {
                this.showFormValidationAlert();
                return;
            }
            // Obtener el tipo de documento seleccionado
            const selectedDocType = this.documentTypes.find(
                (doc) => doc.id == this.formData.documentType
            );

            // Obtener nombres de país, región y comuna
            const selectedCountry = this.countries.find(
                (c) => c.id == this.formData.country
            );
            const selectedRegion = this.regions.find(
                (r) => r.id == this.formData.region
            );
            const selectedComune = this.filteredComunes.find(
                (c) => c.id == this.formData.city
            );

            // Preparar datos del comprador
            const buyerData = {
                // Datos personales
                name: this.formData.fullName,
                documentType: selectedDocType ? selectedDocType.name : "",
                documentNumber: this.formData.documentNumber,
                email: this.formData.email,
                code_phone: this.formData.code_phone,
                phone: this.formData.phone,

                // Datos de ubicación
                countryId: this.formData.country,
                countryName: selectedCountry ? selectedCountry.name : "",
                regionId: this.formData.region,
                regionName: selectedRegion ? selectedRegion.name : "",
                cityId: this.formData.city,
                cityName: selectedComune ? selectedComune.name : "",

                // Acuerdos
                termsAccepted: this.formData.termsAccepted,
                marketingAccepted: this.formData.marketingAccepted,
                isFrequentClient: this.formData.isFrequentClient, // Incluir el nuevo campo

                // Timestamp
                submittedAt: new Date().toISOString(),
            };


            // Guardar datos del comprador en localStorage
            localStorage.setItem("buyerData", JSON.stringify(buyerData));

            // Continuar a la página de confirmación con token
            router.visit(
                `/programs/${this.programId}/confirmation?token=${this.token}`
            );
        },

        checkButtonState() {
            this.validateForm(); // Forzar validación
        },

        updateLocalStorage() {
            // Obtener el tipo de documento seleccionado
            const selectedDocType = this.documentTypes.find(
                (doc) => doc.id == this.formData.documentType
            );

            // Obtener nombres de país, región y comuna
            const selectedCountry = this.countries.find(
                (c) => c.id == this.formData.country
            );
            const selectedRegion = this.regions.find(
                (r) => r.id == this.formData.region
            );
            const selectedComune = this.filteredComunes.find(
                (c) => c.id == this.formData.city
            );

            // Preparar datos del comprador
            const buyerData = {
                // Datos personales
                name: this.formData.fullName,
                documentType: selectedDocType ? selectedDocType.name : "",
                documentNumber: this.formData.documentNumber,
                email: this.formData.email,
                code_phone: this.formData.code_phone,
                phone: this.formData.phone,

                // Datos de ubicación (guardar tanto IDs como nombres)
                countryId: this.formData.country,
                countryName: selectedCountry ? selectedCountry.name : "",
                regionId: this.formData.region,
                regionName: selectedRegion ? selectedRegion.name : "",
                cityId: this.formData.city,
                cityName: selectedComune ? selectedComune.name : "",

                // Acuerdos
                termsAccepted: this.formData.termsAccepted,
                marketingAccepted: this.formData.marketingAccepted,
                isFrequentClient: this.formData.isFrequentClient, // Incluir el nuevo campo

                // Timestamp
                submittedAt: new Date().toISOString(),
            };

            // Guardar datos del comprador en localStorage
            localStorage.setItem("buyerData", JSON.stringify(buyerData));
        },

        forceUpdateSearchableSelects() {

            // Verificar que las opciones estén disponibles antes de forzar la actualización
            if (this.formData.country && this.countries.length > 0) {
                const country = this.countries.find(
                    (c) => c.id == this.formData.country
                );
                if (country) {
                    this.handleCountryChange(this.formData.country);
                }
            }

            if (this.formData.region && this.regions.length > 0) {
                const region = this.regions.find(
                    (r) => r.id == this.formData.region
                );
                if (region) {
                    this.handleRegionChange(this.formData.region);
                }
            }

            // Para la ciudad, esperar a que las comunas estén disponibles
            if (this.formData.city) {
                setTimeout(() => {
                    if (this.filteredComunes.length > 0) {
                        const city = this.filteredComunes.find(
                            (c) => c.id == this.formData.city
                        );
                        if (city) {
                            this.handleCityChange(this.formData.city);
                        }
                    }
                }, 300);
            }

            // Para el tipo de documento, verificar que esté disponible
            if (this.formData.documentType && this.documentTypes.length > 0) {
                const docType = this.documentTypes.find(
                    (d) => d.id == this.formData.documentType
                );
            }
        },

        recordPaymentDetailsView() {
            // Obtener session_id desde localStorage
            const sessionId = localStorage.getItem('analytics_session_id');
            
            if (!sessionId) {
                console.warn('No se encontró session_id en localStorage');
                return;
            }
            
            // Enviar datos de vista de detalles de pago al backend
            fetch('/api/analytics/payment-details-view', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    session_id: sessionId,
                    program_id: this.programId,
                    participant_rut: this.rut,
                })
            }).catch(error => {
                console.error('Error recording payment details view:', error);
                // No mostrar alerta al usuario por errores de analytics
                // Solo log en consola para debugging
            });
        },

        showAlertMessage(type, title, message) {
            this.alertType = type;
            this.alertTitle = title;
            this.alertMessage = message;
            this.showAlert = true;
        },

        closeAlert() {
            this.showAlert = false;
        },

        // Método para mostrar alerta de validación de formulario
        showFormValidationAlert() {
            this.showAlertMessage(
                'warning',
                'Formulario incompleto',
                'Por favor, completa todos los campos obligatorios correctamente antes de continuar.'
            );
        },

        // Método para mostrar alerta de error genérico
        showGenericErrorAlert() {
            this.showAlertMessage(
                'error',
                'Error inesperado',
                'Ha ocurrido un error inesperado. Por favor, intenta nuevamente.'
            );
        },

        // Método para mostrar alerta de error en búsqueda de cliente
        showClientSearchErrorAlert() {
            this.showAlertMessage(
                'warning',
                'Búsqueda no disponible',
                'No se pudo verificar si eres cliente frecuente. Puedes continuar con el formulario.'
            );
        },

        // Método para mostrar alerta de validación de formulario
        showFormValidationAlert() {
            this.showAlertMessage(
                'warning',
                'Formulario incompleto',
                'Por favor, completa todos los campos obligatorios correctamente antes de continuar.'
            );
        },

        // Método para mostrar alerta de error genérico
        showGenericErrorAlert() {
            this.showAlertMessage(
                'error',
                'Error inesperado',
                'Ha ocurrido un error inesperado. Por favor, intenta nuevamente.'
            );
        },

        // Método para mostrar alerta de error en búsqueda de cliente
        showClientSearchErrorAlert() {
            this.showAlertMessage(
                'warning',
                'Búsqueda no disponible',
                'No se pudo verificar si eres cliente frecuente. Puedes continuar con el formulario.'
            );
        },


    },
};
</script>

<style scoped>
.custom-checkbox {
    accent-color: #fbbd51;
}

.custom-checkbox:checked {
    background-color: #fbbd51;
    border-color: #fbbd51;
}

/* Estilos adicionales para mayor compatibilidad */
.custom-checkbox:checked::before {
    background-color: #fbbd51;
}

/* Para navegadores que no soportan accent-color */
.custom-checkbox:checked {
    background-color: #fbbd51 !important;
    border-color: #fbbd51 !important;
}
</style>
