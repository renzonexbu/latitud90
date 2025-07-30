<template>
  <div class="pop-up">
    <!-- Botón de cerrar -->
    <div class="mynaui-letter-x-circle">
      <button @click="$emit('close')" class="close-button">
        <svg xmlns="http://www.w3.org/2000/svg" width="38" height="37" viewBox="0 0 38 37" fill="none">
          <path d="M24.75 25.875L13.5 10.875M24.75 10.875L13.5 25.875M2.25 18.375C2.25 16.1589 2.68648 13.9646 3.53453 11.9172C4.38258 9.86985 5.62558 8.00957 7.19257 6.44258C8.75956 4.87559 10.6198 3.63258 12.6672 2.78453C14.7146 1.93649 16.9089 1.5 19.125 1.5C21.3411 1.5 23.5354 1.93648 25.5828 2.78453C27.6301 3.63258 29.4904 4.87559 31.0574 6.44257C32.6244 8.00956 33.8674 9.86985 34.7155 11.9172C35.5635 13.9646 36 16.1589 36 18.375C36 22.8505 34.2221 27.1428 31.0574 30.3074C27.8928 33.4721 23.6005 35.25 19.125 35.25C14.6495 35.25 10.3573 33.4721 7.19257 30.3074C4.0279 27.1428 2.25 22.8505 2.25 18.375Z" stroke="#C7C7C7" stroke-width="2.8125" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    </div>

    <!-- Contenedor desktop -->
    <div class="frame-1000006103 desktop-content">
      <!-- Título -->
      <div class="bienvenido-a-la-plataforma-de-pago-de-lat-90">
        Bienvenido a la plataforma de pago de Lat90
      </div>

      <!-- Pasos del proceso -->
      <div class="group-1000005851">
        <!-- Línea conectora -->
        <div class="vector-10"></div>
        
        <div class="frame-1000006102">
          <!-- Paso 1 -->
          <div class="frame-1000006085">
            <div class="frame-1000006084">
              <div class="_1">1</div>
            </div>
            <div class="busca-al-alumno-mediante-su-rut">
              Busca al viajero mediante su RUT
            </div>
          </div>

          <!-- Paso 2 -->
          <div class="frame-1000006086">
            <div class="frame-1000006084">
              <div class="_2">2</div>
            </div>
            <div class="seleccione-el-programa-a-pagar">
              Seleccione el programa a pagar
            </div>
          </div>

          <!-- Paso 3 -->
          <div class="frame-1000006087">
            <div class="frame-1000006084">
              <div class="_3">3</div>
            </div>
            <div class="elige-el-medo-de-pago">
              Elige el modo de pago
            </div>
          </div>

          <!-- Paso 4 -->
          <div class="frame-1000006088">
            <div class="frame-1000006084">
              <div class="_4">4</div>
            </div>
            <div class="paga-y-listo">
              Paga y Listo
            </div>
          </div>
        </div>
      </div>

      <!-- Botón de acción -->
      <div class="boton-s" @click="$emit('close')">
        <div class="placeholder">Empezar ahora</div>
      </div>
    </div>

    <!-- Contenedor mobile -->
    <div class="mobile-content">
      <div class="mobile-inner-container">
        <!-- Título mobile -->
        <div class="mobile-title">
          Bienvenido a la plataforma de pago de Lat90
        </div>

        <!-- Pasos mobile -->
        <div class="mobile-steps">
          <div class="mobile-step">
            <div class="mobile-step-number">1</div>
            <div class="mobile-step-text">Busca al viajero mediante su RUT</div>
          </div>
          <div class="mobile-step">
            <div class="mobile-step-number">2</div>
            <div class="mobile-step-text">Seleccione el programa a pagar</div>
          </div>
          <div class="mobile-step">
            <div class="mobile-step-number">3</div>
            <div class="mobile-step-text">Elige el modo de pago</div>
          </div>
          <div class="mobile-step">
            <div class="mobile-step-number">4</div>
            <div class="mobile-step-text">Paga y Listo</div>
          </div>
        </div>

        <!-- Botón mobile -->
        <div class="mobile-button" @click="$emit('close')">
          Empezar ahora
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// No necesitamos props adicionales, todo está hardcodeado según Figma
</script>

<style scoped>
.pop-up,
.pop-up * {
  box-sizing: border-box;
}

.pop-up {
  background: var(--colores-neutro-blanco, #ffffff);
  border-radius: 20px;
  border-style: solid;
  border-color: var(--colores-neutro-gris-2, #d3d3d3);
  border-width: 1px;
  padding: 30px 20px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  align-items: center;
  justify-content: center;
  position: relative;
  width: 90vw;
  max-width: 1200px;
  min-height: 450px;
  max-height: 80vh;
  margin: 0 auto;
  overflow-y: auto;
}

.mynaui-letter-x-circle {
  display: flex;
  flex-direction: row;
  gap: 10px;
  align-items: center;
  justify-content: flex-end;
  align-self: stretch;
  flex-shrink: 0;
  height: auto;
  position: relative;
  overflow: visible;
  width: 100%;
}

.close-button {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.frame-1000006103 {
  display: flex;
  flex-direction: column;
  gap: 59px;
  align-items: center;
  justify-content: flex-start;
  flex-shrink: 0;
  width: 100%;
  max-width: 1400px;
  position: relative;
}

.bienvenido-a-la-plataforma-de-pago-de-lat-90 {
  color: var(--colores-op2-turquesa, #007e93);
  text-align: center;
  font-family: var(--subtitle-font-family, "Nexa-Bold", sans-serif);
  font-size: var(--subtitle-font-size, 24px);
  line-height: var(--subtitle-line-height, 28px);
  font-weight: var(--subtitle-font-weight, 700);
  position: relative;
  width: 100%;
  max-width: 1200px;
}

.group-1000005851 {
  flex-shrink: 0;
  width: 100%;
  height: 110px;
  position: static;
  max-width: 1400px;
}

.vector-10 {
  width: 80%;
  height: 0px;
  position: absolute;
  left: 10%;
  top: 106px;
  overflow: visible;
  border-top: 2px solid var(--colores-op2-turquesa, #007e93);
}

.frame-1000006102 {
  display: flex;
  flex-direction: row;
  gap: 80px;
  align-items: flex-start;
  justify-content: center;
  position: absolute;
  left: 0px;
  top: 87px;
  width: 100%;
  max-width: 100%;
}

.frame-1000006085,
.frame-1000006086,
.frame-1000006087,
.frame-1000006088 {
  display: flex;
  flex-direction: column;
  gap: 16px;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 250px;
  position: relative;
  flex: 1;
  min-width: 0;
}

.frame-1000006084 {
  background: var(--colores-op2-turquesa, #007e93);
  border-radius: 19px;
  padding: 0px 14.25px 0px 14.25px;
  display: flex;
  flex-direction: column;
  gap: 6.79px;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  width: 38px;
  height: 38px;
  position: relative;
}

._1, ._2, ._3, ._4 {
  color: var(--colores-neutro-blanco, #ffffff);
  text-align: left;
  font-family: "Nexa-XBold", sans-serif;
  font-size: 20.357141494750977px;
  line-height: 44.11px;
  letter-spacing: 0.01em;
  font-weight: 400;
  position: relative;
  width: 14px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.busca-al-alumno-mediante-su-rut,
.seleccione-el-programa-a-pagar,
.elige-el-medo-de-pago,
.paga-y-listo {
  color: var(--colores-op2-turquesa, #007e93);
  text-align: center;
  font-family: var(--subtitulo-s-font-family, "Nexa-Bold", sans-serif);
  font-size: var(--subtitulo-s-font-size, 20px);
  line-height: var(--subtitulo-s-line-height, 28px);
  font-weight: var(--subtitulo-s-font-weight, 700);
  position: relative;
  align-self: stretch;
  word-wrap: break-word;
}

.boton-s {
  background: var(--colores-op2-amarillo, #ffb232);
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
}

.boton-s:hover {
  background: #e6a029;
}

.placeholder {
  color: #ffffff;
  text-align: left;
  font-family: var(--cuerpo-de-texto-l-font-family, "Nexa-Bold", sans-serif);
  font-size: var(--cuerpo-de-texto-l-font-size, 16px);
  line-height: var(--cuerpo-de-texto-l-line-height, 22px);
  font-weight: var(--cuerpo-de-texto-l-font-weight, 700);
  position: relative;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  white-space: nowrap;
}

/* Mobile content styles */
.mobile-content {
  display: none;
  flex-direction: column;
  width: 100%;
  height: 100%;
  padding: 46px 36px 32.5px 35px;
  box-sizing: border-box;
}

.mobile-inner-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
  width: 100%;
  height: 100%;
}

.mobile-title {
  color: var(--Colores-Primario-Turquesa, #007E93);
  text-align: center;
  font-family: Nexa;
  font-size: 16px;
  font-style: normal;
  font-weight: 800;
  line-height: 18px;
  align-self: stretch;
}

.mobile-steps {
  display: flex;
  flex-direction: column;
  gap: 15px;
  position: relative;
  flex: 1;
}

.mobile-steps::before {
  content: '';
  position: absolute;
  left: 19px;
  top: 38px;
  bottom: 60px;
  width: 2px;
  background-color: var(--colores-op2-turquesa, #007e93);
  z-index: 1;
}

.mobile-step {
  display: flex;
  align-items: center;
  gap: 12px;
  position: relative;
  z-index: 2;
}

.mobile-step-number {
  background: var(--colores-op2-turquesa, #007e93);
  border-radius: 50%;
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--Colores-Neutro-Blanco, #FFF);
  font-family: Nexa;
  font-size: 14px;
  font-style: normal;
  font-weight: 800;
  line-height: 1;
  letter-spacing: 0.117px;
  flex-shrink: 0;
}

.mobile-step-text {
  color: var(--Colores-OP2-Turquesa, #007E93);
  text-align: left;
  font-family: Nexa;
  font-size: 14px;
  font-style: normal;
  font-weight: 400;
  line-height: 18px;
  flex: 1;
}

.mobile-button {
  background: var(--colores-op2-amarillo, #ffb232);
  border-radius: 47px;
  padding: 14px 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ffffff;
  font-family: "Nexa-Bold", sans-serif;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: background-color 0.2s ease;
  width: 100%;
}

.mobile-button:hover {
  background: #e6a029;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .desktop-content {
    display: none;
  }
  
  .mobile-content {
    display: flex;
  }
  
  .pop-up {
    padding: 0;
    gap: 0;
    width: 95vw;
    height: 440px;
    max-height: 440px;
    overflow: hidden;
  }

  .mynaui-letter-x-circle {
    position: absolute;
    top: 10px;
    right: 10px;
    width: auto;
    z-index: 10;
  }

  .mynaui-letter-x-circle svg {
    width: 24px;
    height: 24px;
  }

  .close-button svg {
    width: 24px;
    height: 24px;
  }

  .mobile-button {
    width: 95%;
    margin: 0 auto;
  }
}

/* Ajustes específicos para diferentes tamaños de pantalla mobile */
@media (min-width: 449px) and (max-width: 768px) {
  .mobile-steps::before {
    bottom: 75px;
  }
}

@media (min-width: 600px) and (max-width: 768px) {
  .mobile-steps::before {
    bottom: 85px;
  }
}
</style> 