<template>
  <div class="w-full bg-[#f7efee] rounded-xl p-[18px_24px] flex flex-row gap-6 items-center justify-start self-stretch flex-shrink-0 relative">
    <!-- Ícono de Triángulo de Peligro -->
    <div class="flex-shrink-0 w-[33px] h-[33px] relative overflow-visible">
      <svg xmlns="http://www.w3.org/2000/svg" width="33" height="33" viewBox="0 0 33 33" fill="none">
        <path d="M16.5003 11.6875V19.25M16.5003 23.7146V23.0271M8.22279 14.7978C11.8363 7.68212 13.6403 4.125 16.5003 4.125C19.3603 4.125 21.1657 7.68212 24.7778 14.7978L25.2274 15.6833C28.2277 21.5958 29.7292 24.552 28.372 26.7135C27.0163 28.875 23.6613 28.875 16.9499 28.875H16.0507C9.34067 28.875 5.98429 28.875 4.62854 26.7135C3.27279 24.552 4.77292 21.5958 7.77317 15.6833L8.22279 14.7978Z" stroke="#D54B44" stroke-width="2.0625" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </div>

    <!-- Contenido de la Advertencia -->
    <div class="flex flex-col gap-1 items-start justify-start flex-1 relative">
      <div class="text-[#5b5b5b] text-left font-nexa-bold text-sm leading-[18px] font-bold relative w-full">
        {{ warningTitle }}
      </div>
      <div class="text-left font-nexa-regular text-xs leading-[17px] font-normal relative self-stretch">
        <span class="text-[#5b5b5b] font-nexa-regular text-xs">
          {{ warningDescription }}
        </span>
        <a
          v-if="itineraryFile"
          :href="itineraryFile"
          target="_blank"
          class="text-[#1a4b75] font-nexa-bold text-xs font-bold underline hover:text-[#0d3352] transition-colors"
        >
          aquí
        </a>
        <span v-else class="text-[#1a4b75] font-nexa-bold text-xs font-bold underline">
          aquí
        </span>
        <span class="text-[#5b5b5b] font-nexa-regular text-xs">
          {{ warningItineraryText }}
        </span>
        <a
          :href="`mailto:${warningEmail}`"
          class="text-[#1a4b75] font-nexa-bold text-xs font-bold underline hover:text-[#0d3352] transition-colors"
        >
          {{ warningEmail }}
        </a>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'WarningSection',
  props: {
    itineraryFile: {
      type: String,
      default: null
    },
    content: {
      type: Object,
      default: () => ({})
    }
  },
  computed: {
    warningTitle() {
      return this.getContent('titulo', 'El itinerario o programa puede sufrir modificaciones por razones de fuerza mayor');
    },
    warningDescription() {
      return this.getContent('descripcion', 'Como condiciones meteorológicas, pandemia, normas sanitarias, cortes de puentes, pasos fronterizos, catástrofe o estado de excepción. Para más información, descarga ');
    },
    warningItineraryText() {
      return this.getContent('texto_itinerario', ' el itinerario completo o consúltanos a nuestro correo eléctrico ');
    },
    warningEmail() {
      return this.getContent('email', 'educacion@latitud90.com');
    }
  },
  methods: {
    getContent(key, defaultValue) {
      return this.content?.[key]?.value || defaultValue;
    }
  }
};
</script> 