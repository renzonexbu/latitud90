<script setup>
  import { ref, onMounted, watch } from "vue";
  import ApplicationLogo from "@/Components/Icons/ApplicationLogo.vue";
  import Dropdown from "@/Components/Dropdown.vue";
  import DropdownLink from "@/Components/DropdownLink.vue";
  import NavLink from "@/Components/NavLink.vue";
  import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
  import { Link, usePage } from "@inertiajs/vue3";
  import {
    HouseIcon,
    BackpackIcon,
    SchoolIcon,
    PersonsIcon,
    EditIcon
  } from "@/Components/Icons";

  const showingNavigationDropdown = ref(false);
  const showSuccessNotification = ref(false);
  const showErrorNotification = ref(false);
  const page = usePage();

  // Watch for flash messages and show notifications
  watch(() => page.props.flash, (flash) => {
    if (flash.message) {
      showSuccessNotification.value = true;
      // Auto-close after 5 seconds
      setTimeout(() => {
        showSuccessNotification.value = false;
      }, 5000);
    }
    if (flash.error) {
      showErrorNotification.value = true;
      // Auto-close after 5 seconds
      setTimeout(() => {
        showErrorNotification.value = false;
      }, 5000);
    }
  }, { immediate: true });

  const closeSuccessNotification = () => {
    showSuccessNotification.value = false;
  };

  const closeErrorNotification = () => {
    showErrorNotification.value = false;
  };
</script>

<template>
  <div class="min-h-screen flex bg-gray-100">
    <!-- Sidebar (estilo de AdminLayout) -->
    <aside
      class="mt-3 mb-3 ml-6 rounded-xl w-32 bg-white shadow-md flex flex-col">
      <div class="flex items-center justify-center py-6">
        <Link
          :href="route('dashboard')"
          class="text-lg font-bold">
          <ApplicationLogo
            class="block h-12 w-auto fill-current text-gray-800" />
        </Link>
      </div>

      <nav class="flex flex-col items-center">
        <!-- Enlaces de navegación principales -->
        <NavLink
          :href="route('dashboard')"
          :active="route().current('dashboard')"
          class="flex justify-center w-full p-2 hover:bg-gray-50 transition-colors mt-11">
          <HouseIcon
            class="w-10 h-10 transition-colors"
            :class="
              route().current('dashboard') ? 'text-turquesa' : 'text-gray-400'
            " />
        </NavLink>

        <!-- Aquí puedes agregar más enlaces de navegación específicos si los necesitas -->
      </nav>

      <!-- User info y logout (parte inferior) -->
      <div class="mt-auto border-t border-gray-200">
        <div class="px-4 py-2">
          <div class="font-medium text-base text-gray-800">
            {{ $page.props.auth.user.name }}
          </div>
          <div class="font-medium text-sm text-gray-500">
            {{ $page.props.auth.user.email }}
          </div>
        </div>
        <nav class="mt-2">
          <NavLink
            :href="route('admin.profile.edit')"
            class="block px-4 py-2 hover:bg-gray-50">
            Perfil
          </NavLink>
          <NavLink
            :href="route('logout')"
            method="post"
            as="button"
            class="block px-4 py-2 w-full text-left hover:bg-gray-50">
            Cerrar Sesión
          </NavLink>
        </nav>
      </div>
    </aside>

    <!-- Contenido principal -->
    <div class="flex-1">
      <!-- Navegación responsive (solo visible en móviles) -->
      <nav class="bg-white border-b border-gray-100 md:hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex justify-between h-16">
            <div class="flex items-center">
              <!-- Logo para móvil -->
              <Link :href="route('dashboard')">
                <ApplicationLogo
                  class="block h-9 w-auto fill-current text-gray-800" />
              </Link>
            </div>

            <!-- Hamburger -->
            <div class="flex items-center">
              <button
                @click="showingNavigationDropdown = !showingNavigationDropdown"
                class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                <svg
                  class="h-6 w-6"
                  stroke="currentColor"
                  fill="none"
                  viewBox="0 0 24 24">
                  <path
                    :class="{
                      hidden: showingNavigationDropdown,
                      'inline-flex': !showingNavigationDropdown
                    }"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16" />
                  <path
                    :class="{
                      hidden: !showingNavigationDropdown,
                      'inline-flex': showingNavigationDropdown
                    }"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Menú responsive desplegable -->
        <div
          :class="{
            block: showingNavigationDropdown,
            hidden: !showingNavigationDropdown
          }"
          class="sm:hidden">
          <div class="pt-2 pb-3 space-y-1">
            <ResponsiveNavLink
              :href="route('dashboard')"
              :active="route().current('dashboard')">
              Dashboard
            </ResponsiveNavLink>
          </div>

          <!-- Responsive Settings Options -->
          <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
              <div class="font-medium text-base text-gray-800">
                {{ $page.props.auth.user.name }}
              </div>
              <div class="font-medium text-sm text-gray-500">
                {{ $page.props.auth.user.email }}
              </div>
            </div>

            <div class="mt-3 space-y-1">
              <ResponsiveNavLink :href="route('admin.profile.edit')">
                Perfil
              </ResponsiveNavLink>
              <ResponsiveNavLink
                :href="route('logout')"
                method="post"
                as="button">
                Cerrar Sesión
              </ResponsiveNavLink>
            </div>
          </div>
        </div>
      </nav>

      <!-- Page Heading -->
      <header
        class=""
        v-if="$slots.header">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
          <slot name="header" />
        </div>
      </header>

      <!-- Page Content -->
      <main>
        <slot />
      </main>

      <!-- Toast Notifications -->
      <div
        v-if="showSuccessNotification && $page.props.flash.message"
        class="fixed top-4 right-4 z-50 transition-all duration-300 ease-in-out">
        <div class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center justify-between min-w-80">
          <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
            </svg>
            <span>{{ $page.props.flash.message }}</span>
          </div>
          <button
            @click="closeSuccessNotification"
            class="ml-4 text-white hover:text-gray-200 focus:outline-none">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
            </svg>
          </button>
        </div>
      </div>

      <div
        v-if="showErrorNotification && $page.props.flash.error"
        class="fixed top-4 right-4 z-50 transition-all duration-300 ease-in-out">
        <div class="bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center justify-between min-w-80">
          <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            <span>{{ $page.props.flash.error }}</span>
          </div>
          <button
            @click="closeErrorNotification"
            class="ml-4 text-white hover:text-gray-200 focus:outline-none">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
