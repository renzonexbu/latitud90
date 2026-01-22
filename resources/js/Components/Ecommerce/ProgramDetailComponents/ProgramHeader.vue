<template>
  <div class="flex flex-col md:flex-row md:items-center md:justify-between self-stretch flex-shrink-0 relative gap-3">
    <!-- Frame izquierdo con ubicación y nombre -->
    <div class="flex flex-col gap-[18px] items-start justify-start flex-shrink-0 w-full md:w-[434px] relative">
      <div class="flex flex-col gap-[2px] items-start justify-start self-stretch flex-shrink-0 relative">
        <div class="text-[#434343] text-left font-nexa text-base leading-[22px] font-normal self-stretch">
          {{ destination }}
        </div>
        <div class="text-[#434343] text-left font-nexa text-[30px] leading-[36px] font-extrabold self-stretch">
          {{ programName }}
        </div>
      </div>
    </div>

    <!-- Frame derecho con fecha de salida -->
    <div class="bg-[#007E93] rounded-[52px] p-[11px_14px] flex justify-center items-center gap-[10px] w-fit md:w-auto mt-2 md:mt-0">
      <div class="text-[#D3D3D3] text-center font-nexa text-sm leading-[18px] font-bold">
        <span class="font-nexa-regular">
          Inicio
        </span>
        <span class="font-nexa-bold font-bold">
          {{ formatDate(departureDate) }}
        </span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProgramHeader',
  props: {
    programName: {
      type: String,
      required: true
    },
    destination: {
      type: String,
      required: true
    },
    departureDate: {
      type: String,
      required: true
    }
  },
  methods: {
    formatDate(date) {
      if (!date) return 'No especificada';
      const dateObj = new Date(date);
      const month = dateObj.toLocaleDateString('es-CL', { month: 'long' });
      const day = dateObj.getDate();
      const year = dateObj.getFullYear();
      return `${month.charAt(0).toUpperCase() + month.slice(1)} ${day} del ${year}`;
    }
  }
};
</script> 