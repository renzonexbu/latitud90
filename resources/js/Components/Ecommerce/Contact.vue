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
                            <div class="heading-h-1">Contactanos</div>
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
                                    {{
                                        loading
                                            ? "Enviando..."
                                            : "Enviar mensaje"
                                    }}
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

const submitForm = async () => {
    loading.value = true;
    errorMessage.value = "";
    successMessage.value = "";

    try {
        router.post("/contact/send", formData.value, {
            preserveState: true,
            onSuccess: (page) => {
                // Verificar si hay mensaje de éxito en la sesión
                if (page.props.flash?.contact_success) {
                    successMessage.value = page.props.flash.contact_success;
                    // Limpiar formulario
                    formData.value = {
                        name: "",
                        email: "",
                        phone: "",
                        message: "",
                    };
                } else {
                    successMessage.value =
                        "Mensaje enviado correctamente. Te responderemos pronto.";
                    // Limpiar formulario
                    formData.value = {
                        name: "",
                        email: "",
                        phone: "",
                        message: "",
                    };
                }
                loading.value = false;
            },
            onError: (errors) => {
                // Verificar si hay errores específicos de contacto
                if (errors.contact_error) {
                    errorMessage.value = errors.contact_error;
                } else if (Object.keys(errors).length > 0) {
                    // Si hay errores de validación
                    const firstError = Object.values(errors)[0];
                    errorMessage.value = Array.isArray(firstError) ? firstError[0] : firstError;
                } else {
                    errorMessage.value =
                        "Error al enviar el mensaje. Por favor, verifica los datos e intenta nuevamente.";
                }
                loading.value = false;
            },
            onFinish: () => {
                loading.value = false;
            },
        });
    } catch (error) {
        errorMessage.value =
            "Error al enviar el mensaje. Por favor, intenta nuevamente.";
        loading.value = false;
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
</style>
