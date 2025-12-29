<template>
  <div class="bg-white rounded-[20px] border border-[#d3d3d3] p-[30px_20px] flex flex-col gap-[14px] items-center justify-center relative w-[90vw] max-w-[1200px] min-h-[450px] max-h-[80vh] mx-auto overflow-y-auto">
    <!-- Botón de cerrar -->
    <div class="flex flex-row gap-[10px] items-center justify-end self-stretch flex-shrink-0 h-auto relative overflow-visible w-full">
      <button @click="$emit('close')" class="bg-none border-none cursor-pointer p-0 flex items-center justify-center">
        <svg xmlns="http://www.w3.org/2000/svg" width="38" height="37" viewBox="0 0 38 37" fill="none">
          <path d="M24.75 25.875L13.5 10.875M24.75 10.875L13.5 25.875M2.25 18.375C2.25 16.1589 2.68648 13.9646 3.53453 11.9172C4.38258 9.86985 5.62558 8.00957 7.19257 6.44258C8.75956 4.87559 10.6198 3.63258 12.6672 2.78453C14.7146 1.93649 16.9089 1.5 19.125 1.5C21.3411 1.5 23.5354 1.93648 25.5828 2.78453C27.6301 3.63258 29.4904 4.87559 31.0574 6.44257C32.6244 8.00956 33.8674 9.86985 34.7155 11.9172C35.5635 13.9646 36 16.1589 36 18.375C36 22.8505 34.2221 27.1428 31.0574 30.3074C27.8928 33.4721 23.6005 35.25 19.125 35.25C14.6495 35.25 10.3573 33.4721 7.19257 30.3074C4.0279 27.1428 2.25 22.8505 2.25 18.375Z" stroke="#C7C7C7" stroke-width="2.8125" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    </div>

    <!-- Contenedor desktop -->
    <div class="hidden md:flex flex-col gap-[59px] items-center justify-start flex-shrink-0 w-full max-w-[1400px] relative">
      <!-- Título -->
      <div class="text-[#007e93] text-center font-nexa-bold text-[24px] leading-[28px] font-bold relative w-full max-w-[1200px]">
        Bienvenido a la plataforma de pago de Latitud 90
      </div>

      <!-- Pasos del proceso -->
      <div class="flex-shrink-0 w-full h-[110px] relative max-w-[1400px]">
        <!-- Contenedor de pasos -->
        <div class="flex flex-row items-start justify-between relative z-10 w-full">
          <!-- Paso 1 -->
          <div class="flex flex-col gap-[16px] items-center justify-center flex-1 relative">
            <div class="bg-[#007e93] rounded-[19px] px-[14.25px] py-0 flex flex-col gap-[6.79px] items-center justify-center w-[38px] h-[38px] relative z-10">
              <div class="text-white text-left font-nexa-xbold text-[20.36px] leading-[44.11px] tracking-[0.01em] font-normal w-[14px] h-[38px] flex items-center justify-center">
                1
              </div>
            </div>
            <div class="text-[#007e93] text-center font-nexa-bold text-[20px] leading-[28px] font-bold self-stretch word-wrap break-word">
              Busca al participante mediante su documento
            </div>
            <!-- Línea desde el primer círculo -->
            <div class="absolute top-[19px] left-1/2 w-full h-0 border-t-2 border-[#007e93] z-0"></div>
          </div>

          <!-- Paso 2 -->
          <div class="flex flex-col gap-[16px] items-center justify-center flex-1 relative">
            <div class="bg-[#007e93] rounded-[19px] px-[14.25px] py-0 flex flex-col gap-[6.79px] items-center justify-center w-[38px] h-[38px] relative z-10">
              <div class="text-white text-left font-nexa-xbold text-[20.36px] leading-[44.11px] tracking-[0.01em] font-normal w-[14px] h-[38px] flex items-center justify-center">
                2
              </div>
            </div>
            <div class="text-[#007e93] text-center font-nexa-bold text-[20px] leading-[28px] font-bold self-stretch word-wrap break-word">
              Seleccione el programa a pagar
            </div>
            <!-- Línea desde el paso 2 hacia el paso 3 -->
            <div class="absolute top-[19px] left-1/2 w-full h-0 border-t-2 border-[#007e93] z-0"></div>
          </div>

          <!-- Paso 3 -->
          <div class="flex flex-col gap-[16px] items-center justify-center flex-1 relative">
            <div class="bg-[#007e93] rounded-[19px] px-[14.25px] py-0 flex flex-col gap-[6.79px] items-center justify-center w-[38px] h-[38px] relative z-10">
              <div class="text-white text-left font-nexa-xbold text-[20.36px] leading-[44.11px] tracking-[0.01em] font-normal w-[14px] h-[38px] flex items-center justify-center">
                3
              </div>
            </div>
            <div class="text-[#007e93] text-center font-nexa-bold text-[20px] leading-[28px] font-bold self-stretch word-wrap break-word">
              Elige el modo de pago
            </div>
            <!-- Línea desde el paso 3 hacia el paso 4 -->
            <div class="absolute top-[19px] left-1/2 w-full h-0 border-t-2 border-[#007e93] z-0"></div>
          </div>

          <!-- Paso 4 -->
          <div class="flex flex-col gap-[16px] items-center justify-center flex-1 relative">
            <div class="bg-[#007e93] rounded-[19px] px-[14.25px] py-0 flex flex-col gap-[6.79px] items-center justify-center w-[38px] h-[38px] relative z-10">
              <div class="text-white text-left font-nexa-xbold text-[20.36px] leading-[44.11px] tracking-[0.01em] font-normal w-[14px] h-[38px] flex items-center justify-center">
                4
              </div>
            </div>
            <div class="text-[#007e93] text-center font-nexa-bold text-[20px] leading-[28px] font-bold self-stretch word-wrap break-word">
              Paga y Listo
            </div>
            <!-- Línea hasta el último círculo -->
            <div class="absolute top-[19px] right-1/2 w-full h-0 border-t-2 border-[#007e93] z-0"></div>
          </div>
        </div>
      </div>

      <!-- Botón de acción -->
      <div class="bg-[#ffb232] rounded-[47px] px-[28px] py-[14px] flex flex-row gap-[10px] items-center justify-center flex-shrink-0 h-[50px] relative overflow-hidden cursor-pointer transition-colors duration-200 hover:bg-[#e6a029]" @click="$emit('close')">
        <div class="text-white text-left font-nexa-bold text-[16px] leading-[22px] font-bold relative flex items-end justify-center whitespace-nowrap">
          Empezar ahora
        </div>
      </div>
    </div>

    <!-- Contenedor mobile -->
    <div class="flex md:hidden flex-col w-full h-full p-[46px_36px_32.5px_35px] box-border">
      <div class="flex flex-col gap-[20px] w-full h-full">
        <!-- Título mobile -->
        <div class="text-[#007E93] text-center font-nexa text-[16px] font-normal font-bold leading-[18px] self-stretch">
          Bienvenido a la plataforma de pago de Latitud 90
        </div>

        <!-- Pasos mobile -->
        <div class="flex flex-col gap-[15px] relative flex-1 before:content-[''] before:absolute before:left-[19px] before:top-[38px] before:bottom-[60px] before:w-[2px] before:bg-[#007e93] before:z-[1]">
          <div class="flex items-center gap-[12px] relative z-[2]">
            <div class="bg-[#007e93] rounded-full w-[38px] h-[38px] flex items-center justify-center text-white font-nexa text-[14px] font-normal font-bold leading-[1] tracking-[0.117px] flex-shrink-0">
              1
            </div>
            <div class="text-[#007E93] text-left font-nexa text-[14px] font-normal font-normal leading-[18px] flex-1">
              Busca al participante mediante su documento
            </div>
          </div>
          <div class="flex items-center gap-[12px] relative z-[2]">
            <div class="bg-[#007e93] rounded-full w-[38px] h-[38px] flex items-center justify-center text-white font-nexa text-[14px] font-normal font-bold leading-[1] tracking-[0.117px] flex-shrink-0">
              2
            </div>
            <div class="text-[#007E93] text-left font-nexa text-[14px] font-normal font-normal leading-[18px] flex-1">
              Seleccione el programa a pagar
            </div>
          </div>
          <div class="flex items-center gap-[12px] relative z-[2]">
            <div class="bg-[#007e93] rounded-full w-[38px] h-[38px] flex items-center justify-center text-white font-nexa text-[14px] font-normal font-bold leading-[1] tracking-[0.117px] flex-shrink-0">
              3
            </div>
            <div class="text-[#007E93] text-left font-nexa text-[14px] font-normal font-normal leading-[18px] flex-1">
              Elige el modo de pago
            </div>
          </div>
          <div class="flex items-center gap-[12px] relative z-[2]">
            <div class="bg-[#007e93] rounded-full w-[38px] h-[38px] flex items-center justify-center text-white font-nexa text-[14px] font-normal font-bold leading-[1] tracking-[0.117px] flex-shrink-0">
              4
            </div>
            <div class="text-[#007E93] text-left font-nexa text-[14px] font-normal font-normal leading-[18px] flex-1">
              Paga y Listo
            </div>
          </div>
        </div>

        <!-- Botón mobile -->
        <div class="bg-[#ffb232] rounded-[47px] px-[28px] py-[14px] flex items-center justify-center text-white font-nexa-bold text-[16px] font-bold cursor-pointer transition-colors duration-200 hover:bg-[#e6a029] w-full" @click="$emit('close')">
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
.font-nexa-bold {
  font-family: "Nexa-Bold", sans-serif;
}

.font-nexa-xbold {
  font-family: "Nexa-XBold", sans-serif;
}

.font-nexa {
  font-family: "Nexa", sans-serif;
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
    top: 15px;
    right: 15px;
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
  
  .pop-up {
    width: 85vw;
    max-width: 400px;
  }
}

@media (min-width: 600px) and (max-width: 768px) {
  .mobile-steps::before {
    bottom: 85px;
  }
  
  .pop-up {
    width: 80vw;
    max-width: 450px;
  }
}
</style> 