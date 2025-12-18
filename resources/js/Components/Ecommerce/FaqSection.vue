<template>
  <section class="faq-section" style="background: #007E93;" id="faq">
    <div class="faq-container">
      <!-- Headings Section -->
      <div class="headings">
        <h2 class="title">{{ getContent('titulo', 'Preguntas frecuentes') }}</h2>
        <div class="spacer">
          <div class="_4-px"></div>
          <div class="text">{{ faqItems.length }}</div>
        </div>
      </div>

      <!-- Accordion Section -->
      <div class="accordion">
        <div
          v-for="(faq, index) in faqItems"
          :key="index"
          class="accordion-card"
          :class="{ 'open': openFaq === index }"
        >
          <div class="accordion-content">
            <div class="accordion-top">
              <div class="card-title">
                {{ faq.question }}
              </div>
              <div class="line-rounded-plus" @click="toggleFaq(index)">
                <svg
                  v-if="openFaq !== index"
                  xmlns="http://www.w3.org/2000/svg"
                  width="12"
                  height="12"
                  viewBox="0 0 14 14"
                  fill="none"
                  class="plus-icon">
                  <path d="M7 0.769531V12.7695" stroke="#007E93" stroke-width="1.32353" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M13 6.76953H1" stroke="#007E93" stroke-width="1.32353" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <svg
                  v-else
                  xmlns="http://www.w3.org/2000/svg"
                  width="9.1"
                  height="9.1"
                  viewBox="0 0 12 11"
                  fill="none"
                  class="close-icon">
                  <path d="M10.5502 1.21875L1.4502 10.3188" stroke="#007E93" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M1.4502 1.21875L10.5502 10.3188" stroke="#007E93" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
            </div>
            <transition name="accordion">
              <div v-if="openFaq === index" class="paragraph">
                {{ faq.answer }}
              </div>
            </transition>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: "FaqSection",
  props: {
    content: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      openFaq: null,
      defaultFaqs: [
        {
          question: "¿Cómo solicito el descuento de hermanos mellizos o gemelos?",
          answer: "A través de su ejecutivo comercial."
        },
        {
          question: "¿Cómo activar las cuotas para el pago mensual con tarjeta de débito o transferencia electrónica a través de la tienda online?",
          answer: "En caso de elegir esta opción de pago debes escribir a pagos@latitud90.com."
        },
        {
          question: "¿Cómo puedo confirmar que he pagado?",
          answer: "La confirmación es automática, a través de la recepción de la boleta a la dirección electrónica informada al momento del pago."
        },
        {
          question: "¿En caso de no ser apoderado o representante legal puedo pagar?",
          answer: "Si puede hacerlo, siempre que conozca los datos del participante al cual pagará o abonará."
        },
        {
          question: "¿Hasta cuándo tengo plazo para pagar el viaje de estudios?",
          answer: "La fecha límite para comenzar el pago está indicada en el documento \"FORMAS DE PAGO\"."
        },
        {
          question: "¿Qué pasa si la operación del pago con mi tarjeta de débito o transferencia Khipu es rechazada?",
          answer: "Verifique si el límite diario de su banco le permite pagar el monto que intenta abonar. Tenga presente que por tema de seguridad bancaria, el monto de la primera transferencia o pago estará limitada."
        }
      ]
    };
  },
  computed: {
    faqItems() {
      // Si hay contenido dinámico, construir las FAQs desde el contenido
      if (this.content && Object.keys(this.content).length > 0) {
        const faqs = [];
        let i = 1;
        while (this.content[`pregunta_${i}`]) {
          faqs.push({
            question: this.content[`pregunta_${i}`]?.value || '',
            answer: this.content[`respuesta_${i}`]?.value || ''
          });
          i++;
        }
        return faqs.length > 0 ? faqs : this.defaultFaqs;
      }
      return this.defaultFaqs;
    }
  },
  methods: {
    toggleFaq(index) {
      this.openFaq = this.openFaq === index ? null : index;
    },
    getContent(key, defaultValue) {
      return this.content?.[key]?.value || defaultValue;
    }
  }
};
</script>

<style scoped>
.faq-section {
  background: #007E93;
  border-radius: 20px;
  padding: 80px 97px;
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  justify-content: space-between;
  align-self: stretch;
  flex-shrink: 0;
  position: relative;
  overflow: hidden;
  margin: 0;
}

.faq-container {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  justify-content: space-between;
  align-self: stretch;
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
}

.headings {
  display: flex;
  flex-direction: column;
  gap: 0px;
  align-items: flex-start;
  justify-content: flex-start;
  flex-shrink: 0;
  width: 407px;
  position: relative;
}

.title {
  color: #ffffff;
  text-align: left;
  font-family: "Nexa-XBold", sans-serif;
  font-size: 30px;
  line-height: 36px;
  font-weight: 400;
  position: relative;
  align-self: stretch;
  display: flex;
  align-items: flex-end;
  justify-content: flex-start;
  margin: 0;
}

.spacer {
  opacity: 0;
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  position: relative;
}

._4-px {
  background: #f1f3f7;
  border-style: solid;
  border-color: #6d758f;
  border-width: 1px;
  width: 100%;
  height: 100%;
  position: absolute;
  right: 0%;
  left: 0%;
  bottom: 0%;
  top: 0%;
}

.text {
  color: #6d758f;
  text-align: center;
  font-family: "Inter-SemiBold", sans-serif;
  font-size: 12px;
  line-height: 18px;
  letter-spacing: 0.08em;
  font-weight: 600;
  text-transform: uppercase;
  position: absolute;
  right: 12.5%;
  left: 16.67%;
  width: 70.83%;
  bottom: 33.33%;
  top: 29.17%;
  height: 37.5%;
}

.accordion {
  display: flex;
  flex-direction: column;
  gap: 24px;
  align-items: flex-start;
  justify-content: flex-start;
  flex-shrink: 0;
  width: 584px;
  position: relative;
}

.accordion-card {
  background: #ffffff;
  border-radius: 79px;
  border-style: solid;
  border-color: #ffffff;
  border-width: 1px;
  padding: 20px 20px 20px 24px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  align-items: flex-start;
  justify-content: flex-start;
  align-self: stretch;
  flex-shrink: 0;
  position: relative;
  box-shadow: 0px 0.5px 2px 0px rgba(25, 33, 61, 0.1);
  overflow: hidden;
  transition: border-radius 0.3s ease;
}

.accordion-card.open {
  border-radius: 20px;
}

.accordion-content {
  display: flex;
  flex-direction: column;
  gap: 11px;
  align-items: flex-start;
  justify-content: flex-start;
  align-self: stretch;
  flex-shrink: 0;
  position: relative;
}

.accordion-top {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  align-self: stretch;
  flex-shrink: 0;
  position: relative;
}

.card-title {
  color: #007e93;
  text-align: left;
  font-family: "Nexa-Regular", sans-serif;
  font-size: 14px;
  line-height: 18px;
  font-weight: 400;
  position: relative;
}

.line-rounded-plus {
  flex-shrink: 0;
  width: 16px;
  height: 16px;
  position: relative;
  overflow: hidden;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.plus-icon,
.close-icon {
  flex-shrink: 0;
}

.paragraph {
  color: #007e93;
  text-align: left;
  font-family: "Nexa-Regular", sans-serif;
  font-size: 14px;
  line-height: 18px;
  font-weight: 400;
  position: relative;
  align-self: stretch;
  padding-top: 8px;
}

/* Transiciones de acordeón */
.accordion-enter-active,
.accordion-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}

.accordion-enter-from,
.accordion-leave-to {
  opacity: 0;
  max-height: 0;
  transform: translateY(-10px);
}

.accordion-enter-to,
.accordion-leave-from {
  opacity: 1;
  max-height: 200px;
  transform: translateY(0);
}

/* Responsive */
@media (max-width: 1024px) {
  .faq-section {
    padding: 40px 20px;
    flex-direction: column;
    gap: 2rem;
  }

  .faq-container {
    flex-direction: column;
    gap: 2rem;
  }

  .headings {
    width: 100%;
    margin-bottom: 2rem;
  }

  .accordion {
    width: 100%;
  }

  .accordion-card {
    border-radius: 20px;
  }
}

@media (max-width: 768px) {
  .faq-section {
    padding: 40px 20px;
    flex-direction: column;
    gap: 1.5rem;
    margin: 0;
  }

  .faq-container {
    flex-direction: column;
    gap: 1.5rem;
  }

  .headings {
    width: 100%;
    margin-bottom: 1rem;
  }

  .title {
    color: #FFF;
    font-family: Nexa, sans-serif;
    font-size: 18px;
    font-style: normal;
    font-weight: 700;
    line-height: 28px;
    margin-bottom: 1rem;
  }

  .accordion {
    width: 100%;
    gap: 16px;
  }

  .accordion-card {
    border-radius: 20px;
    padding: 16px 20px;
  }

  .card-title {
    color: #007E93;
    font-family: Nexa, sans-serif;
    font-size: 10px;
    font-style: normal;
    font-weight: 400;
    line-height: 13px;
  }

  .paragraph {
    color: #007E93;
    font-family: Nexa, sans-serif;
    font-size: 10px;
    font-style: normal;
    font-weight: 400;
    line-height: 13px;
    padding-top: 12px;
  }

  .line-rounded-plus {
    width: 14px;
    height: 14px;
  }

  .plus-icon,
  .close-icon {
    width: 10px;
    height: 10px;
  }
}
</style>
