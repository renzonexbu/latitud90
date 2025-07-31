<template>
  <div class="contact-section">
    <div class="contact-container">
      <!-- Imagen a la izquierda -->
      <div class="contact-image">
        <img
          :src="contactImage"
          alt="Naturaleza"
          class="contact-img" />
      </div>
      
      <!-- Formulario a la derecha -->
      <div class="contact-form">
        <div class="container">
        <div class="form">
          <div class="frame-1000006075">
            <div class="heading-h-1">Contactanos</div>
            <div class="heading-h-12">
              Escríbenos para cotizar un programa, evento o pedir más información:
            </div>
          </div>
          <div class="frame-1000006158">
            <div class="frame-1000006074">
              <div class="frame-1000006072">
                <div class="frame-1154">
                  <div class="frame-1149">
                    <div class="nombre-completo">Nombre completo *</div>
                    <input
                      type="text"
                      placeholder="Nombre y apellido"
                      class="form-input"
                    />
                  </div>
                </div>
                <div class="frame-1155">
                  <div class="tel-fono">Teléfono*</div>
                  <input
                    type="tel"
                    placeholder="+56 9-- --- ---"
                    class="form-input"
                  />
                </div>
              </div>
              <div class="frame-1000006073">
                <div class="frame-1154">
                  <div class="frame-1149">
                    <div class="email">Email*</div>
                    <input
                      type="email"
                      placeholder="Escriba aqui su email"
                      class="form-input"
                    />
                  </div>
                </div>
              </div>
              <div class="frame-1149">
                <div class="mensaje">Mensaje</div>
                <textarea
                  placeholder="Escriba aqui su mensaje"
                  class="form-textarea"
                  rows="3"
                ></textarea>
              </div>
            </div>
            <button type="submit" class="boton-s">
              <div class="placeholder">Enviar mensaje</div>
            </button>
          </div>
        </div>
      </div>
    </div>
    </div>
  </div>
</template>

<script setup>
  import contactImage from "@images/image.png";
</script>

<style scoped>
.contact-section {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: #F9F9F9;
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
  opacity: 0.37;
  background: lightgray -14.977px -45px / 127.297% 114.95% no-repeat;
  background-blend-mode: overlay;
}

.contact-form {
  flex-shrink: 0;
}

.container,
.container * {
  box-sizing: border-box;
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

.frame-1000006075 {
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

.frame-1000006158 {
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

.frame-1000006074 {
  display: flex;
  flex-direction: column;
  gap: 24px;
  align-items: flex-start;
  justify-content: flex-start;
  align-self: stretch;
  flex-shrink: 0;
  position: relative;
}

.frame-1000006072 {
  display: flex;
  flex-direction: row;
  gap: 24px;
  align-items: flex-start;
  justify-content: flex-start;
  align-self: stretch;
  flex-shrink: 0;
  position: relative;
}

.frame-1154 {
  display: flex;
  flex-direction: column;
  gap: 15px;
  align-items: flex-start;
  justify-content: center;
  flex: 1;
  position: relative;
}

.frame-1149 {
  display: flex;
  flex-direction: column;
  gap: 12px;
  align-items: flex-start;
  justify-content: flex-start;
  align-self: stretch;
  flex-shrink: 0;
  position: relative;
}

.nombre-completo {
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
  border: 1px solid #C7C7C7;
  background: #FEFEFF;
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
  border: 1px solid #C7C7C7;
  background: #FEFEFF;
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

.button-text {
  color: #c7c7c7;
  text-align: center;
  font-family: "Nexa-Regular", sans-serif;
  font-size: 14px;
  line-height: 22px;
  font-weight: 400;
  position: relative;
}

.line-rounded-chevron-down,
.line-rounded-chevron-down2,
.line-rounded-chevron-down3,
.line-rounded-chevron-down4 {
  opacity: 0;
  flex-shrink: 0;
  width: 15px;
  height: 15px;
  position: relative;
  overflow: visible;
}

.frame-1155 {
  display: flex;
  flex-direction: column;
  gap: 12px;
  align-items: flex-start;
  justify-content: flex-start;
  flex: 1;
  position: relative;
}

.tel-fono {
  color: #434343;
  text-align: left;
  font-family: "Nexa-Regular", sans-serif;
  font-size: 14px;
  line-height: 18px;
  font-weight: 400;
  position: relative;
  align-self: stretch;
}

.frame-1000006073 {
  display: flex;
  flex-direction: row;
  gap: 24px;
  align-items: flex-start;
  justify-content: flex-start;
  align-self: stretch;
  flex-shrink: 0;
  position: relative;
}

.email {
  color: #434343;
  text-align: left;
  font-family: "Nexa-Regular", sans-serif;
  font-size: 14px;
  line-height: 18px;
  font-weight: 400;
  position: relative;
  align-self: stretch;
}

.mensaje {
  color: #434343;
  text-align: left;
  font-family: "Nexa-Regular", sans-serif;
  font-size: 14px;
  line-height: 18px;
  font-weight: 400;
  position: relative;
  align-self: stretch;
}

.primary-button2 {
  background: #fefeff;
  border-radius: 4px;
  border-style: solid;
  border-color: #c7c7c7;
  border-width: 1px;
  padding: 18px 14px 18px 14px;
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  justify-content: space-between;
  align-self: stretch;
  flex-shrink: 0;
  height: 92px;
  position: relative;
}

.boton-s {
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

.boton-s:hover {
  background: #e6a826;
}

.placeholder {
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
    height: 200px;
    border-radius: 20px 20px 0 0;
  }
  
  .container {
    width: 100%;
    height: auto;
    border-radius: 0 0 20px 20px;
    padding: 24px 20px;
  }
  
  .frame-1000006072 {
    flex-direction: column;
    gap: 20px;
  }
  
  .frame-1000006073 {
    flex-direction: column;
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
  
  .frame-1000006158 {
    gap: 20px;
  }
  
  .frame-1000006074 {
    gap: 20px;
  }
  
  .frame-1149 {
    gap: 10px;
  }
  
  .frame-1154 {
    gap: 12px;
  }
  
  .frame-1155 {
    gap: 10px;
  }
  
  .form-input,
  .form-textarea {
    padding: 12px 16px !important;
    font-size: 16px !important; /* Mejor para mobile */
    width: 100% !important;
    box-sizing: border-box !important;
  }
  
  .frame-1154,
  .frame-1149,
  .frame-1155 {
    width: 100% !important;
    flex: none !important;
  }
  
  .boton-s {
    width: 100%;
    height: 56px;
    font-size: 16px;
  }
  
  .placeholder {
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
  
  .frame-1000006158 {
    gap: 18px;
  }
  
  .frame-1000006074 {
    gap: 18px;
  }
  
  .frame-1000006072 {
    gap: 18px;
  }
  
  .frame-1149 {
    gap: 8px;
  }
  
  .frame-1154 {
    gap: 10px;
  }
  
  .frame-1155 {
    gap: 8px;
  }
  
  .form-input,
  .form-textarea {
    padding: 14px 16px !important;
    border-radius: 8px !important;
    width: 100% !important;
    box-sizing: border-box !important;
  }
  
  .frame-1154,
  .frame-1149,
  .frame-1155 {
    width: 100% !important;
    flex: none !important;
  }
  
  .boton-s {
    height: 52px;
    border-radius: 26px;
  }
}
</style>
