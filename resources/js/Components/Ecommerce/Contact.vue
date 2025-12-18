<template>
    <div class="contact-section" id="contact">
        <div class="contact-container">
            <!-- Imagen a la izquierda -->
            <div class="contact-image">
                <img :src="contactImage" alt="Naturaleza" class="contact-img" />
            </div>

            <!-- Formulario a la derecha -->
            <div class="contact-form">
                <div class="container">
                    <div class="form">
                        <div class="form-header">
                            <div class="heading-h-1">Contáctanos</div>
                            <div class="heading-h-12">
                                Escríbenos para cotizar un programa, evento o
                                pedir más información:
                            </div>
                        </div>
                        <form @submit.prevent="submitForm" class="form-content">
                            <div class="form-fields">
                                <div class="form-row">
                                    <div class="form-field">
                                        <div class="field-wrapper">
                                            <div class="field-label">
                                                Nombre completo *
                                            </div>
                                            <input
                                                v-model="formData.name"
                                                type="text"
                                                placeholder="Nombre y apellido"
                                                class="form-input"
                                                required
                                            />
                                        </div>
                                    </div>
                                    <div class="form-field">
                                        <div class="field-label">Teléfono*</div>
                                        <input
                                            v-model="formData.phone"
                                            type="tel"
                                            placeholder="+56 9-- --- ---"
                                            class="form-input"
                                            required
                                        />
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-field">
                                        <div class="field-wrapper">
                                            <div class="field-label">
                                                Email*
                                            </div>
                                            <input
                                                v-model="formData.email"
                                                type="email"
                                                placeholder="Escriba aqui su email"
                                                class="form-input"
                                                required
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="form-field full-width">
                                    <div class="field-label">Mensaje</div>
                                    <textarea
                                        v-model="formData.message"
                                        placeholder="Escriba aqui su mensaje"
                                        class="form-textarea"
                                        rows="3"
                                        required
                                    ></textarea>
                                </div>
                            </div>

                            <!-- Mensajes de éxito y error -->
                            <div v-if="successMessage" class="success-message">
                                {{ successMessage }}
                            </div>
                            <div v-if="errorMessage" class="error-message">
                                {{ errorMessage }}
                            </div>

                            <button
                                type="submit"
                                class="submit-button"
                                :disabled="loading"
                            >
                                <div class="button-text">
                                    <span v-if="loading" class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Enviando...
                                    </span>
                                    <span v-else>Enviar mensaje</span>
                                </div>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import contactImage from "@images/image.png";

const formData = ref({
    name: "",
    email: "",
    phone: "",
    message: "",
});

const loading = ref(false);
const successMessage = ref("");
const errorMessage = ref("");

const emit = defineEmits(['contact-sent', 'contact-error']);

const submitForm = async () => {
    loading.value = true;
    errorMessage.value = "";
    successMessage.value = "";

    try {
        // Usar fetch directamente para evitar recarga de página
        const response = await fetch('/contact/send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify(formData.value)
        });

        const result = await response.json();

        if (response.ok && result.success) {
            // Éxito
            successMessage.value = result.message || "Mensaje enviado correctamente. Te responderemos pronto.";
            
            // Limpiar formulario
            formData.value = {
                name: "",
                email: "",
                phone: "",
                message: "",
            };
            
            // Emitir evento de éxito
            emit('contact-sent');
            
            // Limpiar mensaje de éxito después de 5 segundos
            setTimeout(() => {
                successMessage.value = "";
            }, 5000);
        } else {
            // Error
            let errorMsg = "Error al enviar el mensaje. Por favor, verifica los datos e intenta nuevamente.";
            
            // Si hay errores de validación del backend
            if (result.errors) {
                const firstError = Object.values(result.errors)[0];
                errorMsg = Array.isArray(firstError) ? firstError[0] : firstError;
            } else if (result.message) {
                errorMsg = result.message;
            }
            
            errorMessage.value = errorMsg;
            
            // Emitir evento de error
            emit('contact-error');
            
            // Limpiar mensaje de error después de 8 segundos
            setTimeout(() => {
                errorMessage.value = "";
            }, 8000);
        }
    } catch (error) {
        console.error('Error en el formulario:', error);
        
        let errorMsg = "Error de conexión. Por favor, verifica tu conexión a internet e intenta nuevamente.";
        
        // Si es un error de validación del navegador
        if (error.name === 'ValidationError') {
            errorMsg = "Por favor, completa todos los campos requeridos correctamente.";
        }
        
        errorMessage.value = errorMsg;
        
        // Emitir evento de error
        emit('contact-error');
        
        // Limpiar mensaje de error después de 8 segundos
        setTimeout(() => {
            errorMessage.value = "";
        }, 8000);
    }
};
</script>

<style scoped>
.contact-section {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    background: #f9f9f9;
    padding: 2rem;
    margin: 0;
}

.contact-container {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    border-radius: 20px;
    overflow: hidden;
}

.contact-image {
    width: 592px;
    height: 650px;
    flex-shrink: 0;
    position: relative;
    overflow: hidden;
    border-radius: 20px 0 0 20px;
}

.contact-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0.7;
    background: lightgray -14.977px -45px / 127.297% 114.95% no-repeat;
    background-blend-mode: overlay;
}

.contact-form {
    flex-shrink: 0;
}

.container {
    background: #ffffff;
    border-radius: 0 20px 20px 0;
    padding: 34px 40px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: flex-start;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
    overflow: hidden;
    width: 565px;
    height: 650px;
    box-sizing: border-box;
}

.container * {
    box-sizing: border-box;
}

.form {
    display: flex;
    flex-direction: column;
    gap: 37px;
    align-items: flex-start;
    justify-content: flex-start;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
    height: 100%;
}

.form-header {
    border-style: solid;
    border-color: #d3d3d3;
    border-width: 0px 0px 1px 0px;
    display: flex;
    flex-direction: column;
    gap: 13px;
    align-items: flex-start;
    justify-content: flex-start;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
}

.heading-h-1 {
    color: #007e93;
    text-align: left;
    font-family: "Nexa", sans-serif;
    font-size: 30px;
    font-style: normal;
    font-weight: 800;
    line-height: 36px;
    position: relative;
    align-self: stretch;
}

.heading-h-12 {
    color: #007e93;
    text-align: left;
    font-family: "Nexa", sans-serif;
    font-size: 14px;
    font-style: normal;
    font-weight: 400;
    line-height: 18px;
    position: relative;
    align-self: stretch;
    height: 31px;
}

.form-content {
    display: flex;
    flex-direction: column;
    gap: 24px;
    align-items: flex-start;
    justify-content: flex-start;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
    flex: 1;
}

.form-fields {
    display: flex;
    flex-direction: column;
    gap: 24px;
    align-items: flex-start;
    justify-content: flex-start;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
}

.form-row {
    display: flex;
    flex-direction: row;
    gap: 24px;
    align-items: flex-start;
    justify-content: flex-start;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
    justify-content: flex-start;
    flex: 1;
    position: relative;
}

.form-field.full-width {
    flex: none;
    width: 100%;
}

.field-wrapper {
    display: flex;
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
    justify-content: flex-start;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
}

.field-label {
    color: #434343;
    text-align: left;
    font-family: "Nexa-Regular", sans-serif;
    font-size: 14px;
    line-height: 18px;
    font-weight: 400;
    position: relative;
    align-self: stretch;
}

.form-input {
    border-radius: 4px;
    border: 1px solid #c7c7c7;
    background: #fefeff;
    padding: 8px 14px;
    color: #c7c7c7;
    font-family: "Nexa-Regular", sans-serif;
    font-size: 14px;
    line-height: 22px;
    font-weight: 400;
    outline: none;
    width: 100%;
    box-sizing: border-box;
}

.form-input::placeholder {
    color: #c7c7c7;
}

.form-input:focus {
    color: #434343;
    border-color: #007e93;
}

.form-textarea {
    border-radius: 4px;
    border: 1px solid #c7c7c7;
    background: #fefeff;
    padding: 8px 14px;
    color: #c7c7c7;
    font-family: "Nexa-Regular", sans-serif;
    font-size: 14px;
    line-height: 22px;
    font-weight: 400;
    outline: none;
    width: 100%;
    height: 92px;
    resize: none;
    box-sizing: border-box;
}

.form-textarea::placeholder {
    color: #c7c7c7;
}

.form-textarea:focus {
    color: #434343;
    border-color: #007e93;
}

.submit-button {
    background: #ffb232;
    border-radius: 47px;
    padding: 14px 28px 14px 28px;
    display: flex;
    flex-direction: row;
    gap: 10px;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    height: 50px;
    position: relative;
    overflow: hidden;
    cursor: pointer;
    transition: background-color 0.2s ease;
    border: none;
    width: auto;
    margin-top: auto;
}

.submit-button:hover {
    background: #e6a826;
}

.submit-button:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 12px;
    border-radius: 4px;
    border: 1px solid #c3e6cb;
    margin-bottom: 16px;
    font-size: 14px;
}

.error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 12px;
    border-radius: 4px;
    border: 1px solid #f5c6cb;
    margin-bottom: 16px;
    font-size: 14px;
}

.button-text {
    color: #ffffff;
    text-align: center;
    font-family: "Nexa-Bold", sans-serif;
    font-size: 16px;
    line-height: 22px;
    font-weight: 700;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Responsive */
@media (max-width: 1024px) {
    .contact-section {
        flex-direction: column;
        padding: 1rem;
        min-height: auto;
    }

    .contact-container {
        flex-direction: column;
        width: 100%;
        max-width: 500px;
    }

    .contact-image {
        width: 100%;
        height: 250px;
        border-radius: 20px 20px 0 0;
    }

    .contact-img {
        object-fit: cover;
        object-position: center;
    }

    .container {
        width: 100%;
        height: auto;
        border-radius: 0 0 20px 20px;
        padding: 24px 20px;
    }

    .form-row {
        flex-direction: column;
        gap: 20px;
    }

    .heading-h-1 {
        font-size: 24px;
        line-height: 28px;
    }

    .heading-h-12 {
        font-size: 13px;
        line-height: 16px;
        height: auto;
    }

    .form {
        gap: 30px;
    }

    .form-content {
        gap: 20px;
    }

    .form-fields {
        gap: 20px;
    }

    .field-wrapper {
        gap: 10px;
    }

    .form-field {
        gap: 10px;
    }

    .form-input,
    .form-textarea {
        padding: 12px 16px !important;
        font-size: 16px !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }

    .form-field {
        width: 100% !important;
        flex: none !important;
    }

    .submit-button {
        width: 100%;
        height: 56px;
        font-size: 16px;
    }

    .button-text {
        font-size: 16px;
    }
}

/* Mobile específico */
@media (max-width: 768px) {
    .contact-section {
        padding: 0.5rem;
    }

    .contact-container {
        max-width: 100%;
    }

    .container {
        padding: 20px 16px;
    }

    .heading-h-1 {
        font-size: 22px;
        line-height: 26px;
    }

    .heading-h-12 {
        font-size: 12px;
        line-height: 15px;
    }

    .form {
        gap: 25px;
    }

    .form-content {
        gap: 18px;
    }

    .form-fields {
        gap: 18px;
    }

    .form-row {
        gap: 18px;
    }

    .field-wrapper {
        gap: 8px;
    }

    .form-field {
        gap: 8px;
    }

    .form-input,
    .form-textarea {
        padding: 14px 16px !important;
        border-radius: 8px !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }

    .form-field {
        width: 100% !important;
        flex: none !important;
    }

    .submit-button {
        height: 52px;
        border-radius: 26px;
    }
}

/* Animación del spinner */
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

/* Mejorar la transición del botón */
.submit-button:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: scale(0.98);
}

.submit-button:not(:disabled):hover {
    transform: scale(1.02);
    transition: transform 0.2s ease;
}
</style>
