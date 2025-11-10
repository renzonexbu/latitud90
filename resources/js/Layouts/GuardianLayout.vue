<template>
    <div class="h-screen flex overflow-hidden" :style="`background-color: #F9F9F9; background-image: url('${backgroundImage}'); background-size: cover; background-position: center; background-repeat: no-repeat;`">
        <!-- Sidebar -->
        <aside
            class="mt-3 mb-3 ml-6 rounded-[20px] w-[102px] bg-white shadow-md flex flex-col h-[calc(100vh-24px)]"
        >
            <!-- Logo Section -->
            <div class="flex items-center justify-center py-6">
                <a href="/" class="text-lg font-bold">
                    <img
                        :src="images['logo-color']"
                        alt="Logo Latitud 90"
                        class="h-12"
                    />
                </a>
            </div>

            <!-- Navigation Icons -->
            <nav class="flex flex-col items-center flex-1">
                <NavLink
                    :href="route('guardian.dashboard')"
                    :active="route().current('guardian.dashboard')"
                    class="flex justify-center w-full p-1 group transition-colors mt-8"
                >
                    <HouseIcon
                        class="w-8 h-8 transition-colors"
                        :class="
                            route().current('guardian.dashboard')
                                ? 'text-turquesa'
                                : 'text-gray-400 group-hover:text-turquesa'
                        "
                    />
                </NavLink>

                <NavLink
                    :href="route('guardian.participants')"
                    :active="route().current('guardian.participants')"
                    class="flex justify-center w-full p-1 group transition-colors mt-8"
                >
                    <PersonsIcon
                        class="w-8 h-8 transition-colors"
                        :class="
                            route().current('guardian.participants')
                                ? 'text-turquesa'
                                : 'text-gray-400 group-hover:text-turquesa'
                        "
                        stroke-color="currentColor"
                    />
                </NavLink>
            </nav>

            <!-- User Info Section -->
            <div class="border-t border-gray-200">
                <div class="flex flex-col items-center space-y-4 py-4">
                    <div class="relative user-dropdown-container">
                        <button
                            @click="showingUserDropdown = !showingUserDropdown"
                            class="flex justify-center w-full p-1 group transition-colors"
                        >
                            <UserIcon
                                class="w-8 h-8 transition-colors"
                                :class="
                                    route().current('guardian.change-password')
                                        ? 'text-turquesa'
                                        : 'text-gray-400 group-hover:text-turquesa'
                                "
                            />
                        </button>

                        <!-- User Dropdown -->
                        <div
                            v-show="showingUserDropdown"
                            class="absolute bottom-full left-full ml-2 mb-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-50"
                        >
                            <div class="py-1">
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <p class="text-sm font-semibold text-gray-900">{{ user?.name }}</p>
                                    <p class="text-xs text-gray-500">{{ user?.email }}</p>
                                </div>
                                <NavLink
                                    :href="route('guardian.change-password')"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                                    @click="showingUserDropdown = false"
                                >
                                    Cambiar Contraseña
                                </NavLink>
                                <NavLink
                                    :href="route('guardian.logout')"
                                    method="post"
                                    as="button"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                                    @click="showingUserDropdown = false"
                                >
                                    Cerrar sesión
                                </NavLink>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Page Content -->
        <div class="flex-1 flex flex-col h-full">
            <main class="flex-1 overflow-y-auto">
                <slot />
            </main>

            <!-- Sistema de Alertas Unificado -->
            <Alerts
                v-if="showAlert"
                :show="showAlert"
                :type="alertType"
                :title="alertTitle"
                :message="alertMessage"
                :auto-close="true"
                :duration="5000"
                @close="closeAlert"
            />
        </div>
    </div>
</template>

<script>
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import NavLink from "@/Components/NavLink.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
import Alerts from "@/Components/Alerts.vue";

// Importar iconos SVG
import {
    HouseIcon,
    UserIcon,
    PersonsIcon,
} from "@/Components/Icons";

import images from "../../images/index.js";
import backgroundImage from "../../images/admin/background.png";

export default {
    name: "GuardianLayout",
    components: {
        Link,
        Dropdown,
        DropdownLink,
        NavLink,
        ResponsiveNavLink,
        Alerts,
        HouseIcon,
        UserIcon,
        PersonsIcon,
    },
    data() {
        return {
            showingNavigationDropdown: false,
            showingUserDropdown: false,
            images,
            backgroundImage,
            showAlert: false,
            alertType: 'success',
            alertTitle: '',
            alertMessage: '',
        };
    },
    computed: {
        user() {
            return usePage().props.auth?.user || null;
        }
    },
    mounted() {
        // Cerrar dropdown cuando se hace clic fuera
        document.addEventListener('click', this.closeUserDropdown);

        // Detectar y mostrar flash messages como alertas
        this.detectFlashMessages();

        // Watcher para detectar cambios en flash messages
        this.$watch('$page.props.flash', (newFlash) => {
            if (newFlash.success) {
                this.showAlertMessage('success', 'Éxito', newFlash.success);
                this.$page.props.flash.success = null;
            } else if (newFlash.error) {
                this.showAlertMessage('error', 'Error', newFlash.error);
                this.$page.props.flash.error = null;
            } else if (newFlash.message) {
                this.showAlertMessage('info', 'Información', newFlash.message);
                this.$page.props.flash.message = null;
            }
        }, { deep: true });
    },
    beforeUnmount() {
        document.removeEventListener('click', this.closeUserDropdown);
    },
    methods: {
        closeUserDropdown(event) {
            const dropdown = this.$el.querySelector('.user-dropdown-container');
            if (dropdown && !dropdown.contains(event.target)) {
                this.showingUserDropdown = false;
            }
        },
        clearFlashMessage(type) {
            this.$page.props.flash[type] = null;
        },
        detectFlashMessages() {
            // Detectar mensajes flash y convertirlos a alertas
            if (this.$page.props.flash.success) {
                this.showAlertMessage('success', 'Éxito', this.$page.props.flash.success);
                this.$page.props.flash.success = null;
            } else if (this.$page.props.flash.error) {
                this.showAlertMessage('error', 'Error', this.$page.props.flash.error);
                this.$page.props.flash.error = null;
            } else if (this.$page.props.flash.message) {
                this.showAlertMessage('info', 'Información', this.$page.props.flash.message);
                this.$page.props.flash.message = null;
            }
        },
        showAlertMessage(type, title, message) {
            this.alertType = type;
            this.alertTitle = title;
            this.alertMessage = message;
            this.showAlert = true;
        },
        closeAlert() {
            this.showAlert = false;
        },
        autoCloseFlashMessages() {
            // Auto-cerrar mensajes flash después de 5 segundos
            setTimeout(() => {
                if (this.$page.props.flash.message) {
                    this.$page.props.flash.message = null;
                }
                if (this.$page.props.flash.success) {
                    this.$page.props.flash.success = null;
                }
                if (this.$page.props.flash.error) {
                    this.$page.props.flash.error = null;
                }
            }, 5000);
        }
    },
};
</script>
