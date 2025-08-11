<template>
  <div class="w-full">
    <div class="flex flex-col md:flex-row items-stretch md:items-start gap-[12px] md:gap-[22px] self-stretch w-full">
      <!-- Itinerario -->
      <a 
        v-if="itineraryFile"
        :href="itineraryFile" 
        target="_blank"
        class="rounded-[59px] bg-[#007E93] flex p-[11px_23px] justify-center items-center gap-[12px] hover:scale-105 transition-transform duration-200 ease-in-out w-full md:w-auto"
      >
        <span class="text-white text-left font-nexa text-sm leading-[18px] font-normal">
          Itinerario
        </span>
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none" class="w-[20px] h-[20px] aspect-square">
          <path d="M3.2041 8.52025L7.9866 3.91191C12.5108 -0.452253 19.8583 6.28108 15.1499 10.8236L9.22327 16.5419C6.15327 19.5036 1.16743 14.9344 4.36243 11.8519L10.2041 6.21608C11.8199 4.65775 14.4433 7.06191 12.7624 8.68441L7.90993 13.3336" stroke="white" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </a>
      
      <!-- Cobertura de asistencia en viaje -->
      <a 
        v-if="travelAssistanceCoverage"
        :href="travelAssistanceCoverage" 
        target="_blank"
        class="rounded-[59px] bg-[#007E93] flex p-[11px_23px] justify-center items-center gap-[12px] hover:scale-105 transition-transform duration-200 ease-in-out w-full md:w-auto"
      >
        <span class="text-white text-left font-nexa text-sm leading-[18px] font-normal">
          Cobertura de asistencia en viaje
        </span>
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none" class="w-[20px] h-[20px] aspect-square">
          <path d="M3.2041 8.52025L7.9866 3.91191C12.5108 -0.452253 19.8583 6.28108 15.1499 10.8236L9.22327 16.5419C6.15327 19.5036 1.16743 14.9344 4.36243 11.8519L10.2041 6.21608C11.8199 4.65775 14.4433 7.06191 12.7624 8.68441L7.90993 13.3336" stroke="white" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </a>
      
      <!-- Lista de equipo -->
      <a 
        v-if="equipmentList"
        :href="equipmentList" 
        target="_blank"
        class="rounded-[59px] bg-[#007E93] flex p-[11px_23px] justify-center items-center gap-[12px] hover:scale-105 transition-transform duration-200 ease-in-out w-full md:w-auto"
      >
        <span class="text-white text-left font-nexa text-sm leading-[18px] font-normal">
          Lista de equipo
        </span>
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none" class="w-[20px] h-[20px] aspect-square">
          <path d="M3.2041 8.52025L7.9866 3.91191C12.5108 -0.452253 19.8583 6.28108 15.1499 10.8236L9.22327 16.5419C6.15327 19.5036 1.16743 14.9344 4.36243 11.8519L10.2041 6.21608C11.8199 4.65775 14.4433 7.06191 12.7624 8.68441L7.90993 13.3336" stroke="white" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </a>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProgramDocuments',
  props: {
    itineraryFile: {
      type: String,
      default: null
    },
    travelAssistanceCoverage: {
      type: String,
      default: null
    },
    equipmentList: {
      type: String,
      default: null
    }
  }
};
</script> 