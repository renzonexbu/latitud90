<template>
  <div class="min-h-screen bg-gray-50 w-full p-3">
    <!-- Modal de Bienvenida -->
    <Modal :show="showWelcomeModal" maxWidth="7xl" @close="showWelcomeModal = false">
      <WelcomePaymentModal @close="showWelcomeModal = false" />
    </Modal>
    
    <!-- Header -->
    <Header class="bg-transparent text-blanco shadow-none"> </Header>
    <!-- Hero Section -->
    <!-- <div class="relative py-24">
      <div
        class="rounded-lg absolute inset-0 bg-[url('/resources/images/dashboard.png')] bg-cover bg-center">
        <div class="opacity-75"></div>
      </div>
      <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-left">
        <h1
          class="text-white text-3xl md:text-5xl lg:text-6xl font-extrabold leading-tight font-nexa">
          Bienvenido<span
            class="text-2xl md:text-4xl lg:text-5xl text-amber-400 font-quincy font-medium">
            al portal</span
          ><br />
          <span
            class="text-2xl md:text-4xl lg:text-5xl text-amber-400 font-quincy font-medium"
            >de pago</span
          >
          Latitud 90.
        </h1>

        <div class="max-w-lg py-3">
          <div
            class="flex flex-col sm:flex-row items-center justify-center md:justify-start relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="¿A dónde te gustaría ir?"
              class="flex-1 px-4 py-3 rounded-full border-0 focus:ring-2 focus:ring-indigo-500"
              @keypress.enter="performSearch" />
            <button
              @click="performSearch"
              class="absolute top-1/2 right-2 transform -translate-y-1/2 bg-turquesa hover:bg-turquesa text-white font-semibold py-2 px-6 rounded-full transition-colors duration-200">
              Buscar
            </button>
          </div>
        </div>

        <p class="text-xl text-indigo-100 mb-8 max-w-3xl mx-auto">
          Ingrese el número Rut del alumno
        </p>
      </div>
    </div> -->
    <!-- Hero Section -->
    <HeroSection id=""></HeroSection>

    <!-- Logo Carousel -->
    <LogoCarousel></LogoCarousel>

    <!-- Experiences Section -->
    <ExperienceSection id="nuestrosProgramas"></ExperienceSection>

    <!-- Schools Section -->
    <SchoolsSection></SchoolsSection>

    <!-- Courses Section -->
    <CoursesSection></CoursesSection>

    <!-- Testimonials Section -->
    <TestimonialsSection></TestimonialsSection>

    <!-- FAQ Section -->
    <FaqSection></FaqSection>

    <!-- Contact Section -->
    <Contact></Contact>

    <!-- Footer -->
    <Footer class="rounded-lg"></Footer>
  </div>
</template>

<script>
  import { Link, Head } from "@inertiajs/vue3";
  import { ref, reactive } from "vue";
  import { router } from "@inertiajs/vue3";
  import Header from "@/Components/Header.vue";
  import Footer from "@/Components/Footer.vue";
  import Contact from "@/Components/Contact.vue";
  import Slider from "@/Components/ImageSlider.vue";
  import HeroSection from "@/Components/HeroSection.vue";
  import AboutSection from "@/Components/AboutSection.vue";
  import ExperienceSection from "@/Components/ExperienceSection.vue";
  import SchoolsSection from "@/Components/SchoolsSection.vue";
  import CoursesSection from "@/Components/CoursesSection.vue";
  import TestimonialsSection from "@/Components/TestimonialsSection.vue";
  import FaqSection from "@/Components/FaqSection.vue";
  import Modal from "@/Components/Modal.vue";
  import WelcomePaymentModal from "@/Components/WelcomePaymentModal.vue";
  import LogoCarousel from "@/Components/LogoCarousel.vue";

  export default {
    components: {
      Header,
      Footer,
      Contact,
      Slider,
      HeroSection,
      AboutSection,
      ExperienceSection,
      SchoolsSection,
      CoursesSection,
      TestimonialsSection,
      FaqSection,
      Modal,
      WelcomePaymentModal,
      Link,
      Head,
      LogoCarousel
    },
    props: {
      programs: Object,
      filters: Object,
      serviceTypes: Array
    },
    setup(props) {
      const searchQuery = ref(props.filters.search || "");
      const filters = reactive({
        service_type: props.filters.service_type || "",
        price_min: props.filters.price_min || "",
        price_max: props.filters.price_max || "",
        departure_date_from: props.filters.departure_date_from || "",
        departure_date_to: props.filters.departure_date_to || ""
      });

      const formatServiceType = (type) => {
        const types = {
          tours: "Tours",
          excursiones: "Excursiones",
          intercambio: "Intercambios",
          cruceros: "Cruceros"
        };
        return types[type] || type;
      };

      const formatPrice = (price) => {
        return new Intl.NumberFormat("es-CL").format(price);
      };

      const formatDate = (date) => {
        return new Date(date).toLocaleDateString("es-CL", {
          day: "2-digit",
          month: "2-digit",
          year: "numeric"
        });
      };

      const performSearch = () => {
        router.get(
          "/",
          {
            ...filters,
            search: searchQuery.value
          },
          {
            preserveState: true,
            preserveScroll: true
          }
        );
      };

      const applyFilters = () => {
        router.get(
          "/",
          {
            ...filters,
            search: searchQuery.value
          },
          {
            preserveState: true,
            preserveScroll: true
          }
        );
      };

      const generatePagination = () => {
        const pages = [];
        const current = props.programs.current_page;
        const last = props.programs.last_page;

        // Mostrar siempre la primera página
        pages.push(1);

        // Calcular rango alrededor de la página actual
        const start = Math.max(2, current - 2);
        const end = Math.min(last - 1, current + 2);

        // Agregar puntos suspensivos si es necesario
        if (start > 2) pages.push("...");

        // Agregar páginas del rango
        for (let i = start; i <= end; i++) {
          pages.push(i);
        }

        // Agregar puntos suspensivos si es necesario
        if (end < last - 1) pages.push("...");

        // Mostrar siempre la última página (si no es la primera)
        if (last > 1) pages.push(last);

        return pages;
      };

      const showWelcomeModal = ref(true);

      return {
        searchQuery,
        filters,
        showWelcomeModal,
        formatServiceType,
        formatPrice,
        formatDate,
        performSearch,
        applyFilters,
        generatePagination
      };
    }
  };
</script>

<style>
  .line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
</style>
