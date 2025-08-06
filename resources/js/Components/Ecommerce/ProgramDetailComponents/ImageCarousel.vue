<template>
  <div class="w-full relative" v-if="images && images.length > 0">
    <div class="overflow-hidden rounded-xl">
      <img
        :src="images[currentImageIndex].url"
        class="w-full h-96 object-cover transition-all duration-500"
        :alt="programName"
        @click="openImageModal(images[currentImageIndex].url)"
      />
    </div>

    <!-- Indicadores de puntos -->
    <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex gap-2">
      <button
        v-for="(image, index) in images"
        :key="index"
        @click="handleDotClick(index)"
        class="w-3 h-3 rounded-full transition-all duration-200"
        :class="index === currentImageIndex ? 'bg-orange-500' : 'bg-gray-400'">
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ImageCarousel',
  props: {
    images: {
      type: Array,
      required: true
    },
    programName: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      currentImageIndex: 0,
      autoPlayInterval: null
    };
  },
  mounted() {
    this.startAutoPlay();
  },
  beforeUnmount() {
    this.stopAutoPlay();
  },
  methods: {
    startAutoPlay() {
      if (this.images && this.images.length > 1) {
        this.autoPlayInterval = setInterval(() => {
          this.nextImage();
        }, 3000); // Cambia cada 3 segundos
      }
    },

    stopAutoPlay() {
      if (this.autoPlayInterval) {
        clearInterval(this.autoPlayInterval);
        this.autoPlayInterval = null;
      }
    },

    nextImage() {
      if (this.images && this.images.length > 0) {
        this.currentImageIndex = (this.currentImageIndex + 1) % this.images.length;
      }
    },

    handleDotClick(index) {
      this.currentImageIndex = index;
      this.restartAutoPlay();
    },

    restartAutoPlay() {
      this.stopAutoPlay();
      this.startAutoPlay();
    },

    openImageModal(imageUrl) {
      // Abrir la imagen en una nueva pestaña
      window.open(imageUrl, '_blank');
    }
  }
};
</script> 