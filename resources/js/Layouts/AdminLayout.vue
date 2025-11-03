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
                    :href="route('admin.dashboard')"
                    :active="route().current('admin.dashboard')"
                    class="flex justify-center w-full p-1 group transition-colors mt-8"
                >
                    <HouseIcon
                        class="w-8 h-8 transition-colors"
                        :class="
                            route().current('admin.dashboard')
                                ? 'text-turquesa'
                                : 'text-gray-400 group-hover:text-turquesa'
                        "
                    />
                </NavLink>
                <NavLink
                    :href="route('admin.programs.index')"
                    :active="route().current('admin.programs.*')"
                    class="flex justify-center w-full p-1 group transition-colors mt-8"
                >
                    <BackpackIcon
                        class="w-8 h-8 transition-colors"
                        :class="
                            route().current('admin.programs.*')
                                ? 'text-turquesa'
                                : 'text-gray-400 group-hover:text-turquesa'
                        "
                        stroke-color="currentColor"
                    />
                </NavLink>
                <NavLink
                    :href="route('admin.courses.index')"
                    :active="route().current('admin.courses.*')"
                    class="flex justify-center w-full p-1 group transition-colors mt-8"
                >
                    <LuggageIcon
                        class="w-8 h-8 transition-colors"
                        :class="
                            route().current('admin.courses.*')
                                ? 'text-turquesa'
                                : 'text-gray-400 group-hover:text-turquesa'
                        "
                        stroke-color="currentColor"
                    />
                </NavLink>
                <NavLink
                    :href="route('admin.participants.index')"
                    :active="route().current('admin.participants.*')"
                    class="flex justify-center w-full p-1 group transition-colors mt-8"
                >
                    <PersonsIcon
                        class="w-8 h-8 transition-colors"
                        :class="
                            route().current('admin.participants.*')
                                ? 'text-turquesa'
                                : 'text-gray-400 group-hover:text-turquesa'
                        "
                        stroke-color="currentColor"
                    />
                </NavLink>
                 <NavLink
                    :href="route('admin.payments.index')"
                    :active="route().current('admin.payments.*')"
                    class="flex justify-center w-full p-1 group transition-colors mt-8"
                >
                    <PaymentsIcon
                        class="w-8 h-8 transition-colors"
                        :class="
                            route().current('admin.payments.*')
                                ? 'text-turquesa'
                                : 'text-gray-400 group-hover:text-turquesa'
                        "
                        stroke-color="currentColor"
                    />
                </NavLink>
                <NavLink
                    :href="route('admin.reports.index')"
                    :active="route().current('admin.reports.*')"
                    class="flex justify-center w-full p-1 group transition-colors mt-8"
                >
                    <ReportIcon
                        class="w-8 h-8 transition-colors"
                        :class="
                            route().current('admin.reports.*')
                                ? 'text-turquesa'
                                : 'text-gray-400 group-hover:text-turquesa'
                        "
                        fill-color="currentColor"
                    />
                </NavLink>
                
                <!-- Mantenedor - Solo para Super Admin -->
                <NavLink
                    v-if="$page.props.auth.user && $page.props.auth.user.roles && $page.props.auth.user.roles.includes('super_admin')"
                    :href="route('admin.maintainer.index')"
                    :active="route().current('admin.maintainer.*')"
                    class="flex justify-center w-full p-1 group transition-colors mt-8"
                >
                    <SettingsIcon
                        class="w-8 h-8 transition-colors"
                        :class="
                            route().current('admin.maintainer.*')
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
                    <!-- <NavLink
                        :href="route('admin.profile.edit')"
                        class="flex justify-center w-full p-1 group transition-colors"
                    >
                        <AyudaIcon 
                            class="w-8 h-8 transition-colors"
                            :class="
                                route().current('admin.profile.edit') || route().current('admin.profile.*') || $page.url.includes('/admin/profile')
                                    ? 'text-turquesa'
                                    : 'text-gray-400 group-hover:text-turquesa'
                            "
                        />
                    </NavLink> -->
                    <div class="relative user-dropdown-container">
                        <button
                            @click="showingUserDropdown = !showingUserDropdown"
                            class="flex justify-center w-full p-1 group transition-colors"
                        >
                            <UserIcon
                                class="w-8 h-8 transition-colors"
                                :class="
                                    route().current('admin.profile.edit') || route().current('admin.profile.*') || $page.url.includes('/admin/profile')
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
                                <NavLink
                                    :href="route('admin.profile.edit')"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                                    @click="showingUserDropdown = false"
                                >
                                    Ver perfil
                                </NavLink>
                                <NavLink
                                    :href="route('admin.users.index')"
                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                                    @click="showingUserDropdown = false"
                                >
                                    Administrar usuarios
                                </NavLink>
                                <NavLink
                                    :href="route('admin.logout')"
                                    method="post"
                                    as="button"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                                    @click="showingUserDropdown = false"
                                    :data="{ redirect: 'http://latitud90.test/login' }"
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
import { Link } from "@inertiajs/vue3";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import NavLink from "@/Components/NavLink.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
import Alerts from "@/Components/Alerts.vue";
import { watch } from "vue";

// Importar iconos SVG
import {
    HouseIcon,
    BackpackIcon,
    LuggageIcon,
    SchoolIcon,
    PersonsIcon,
    PaymentsIcon,
    EditIcon,
    AyudaIcon,
    UserIcon,
    ReportIcon,
    SettingsIcon,
} from "@/Components/Icons";

import images from "../../images/index.js";
import backgroundImage from "../../images/admin/background.png";

export default {
    name: "AdminLayout",
    components: {
        Link,
        Dropdown,
        DropdownLink,
        NavLink,
        ResponsiveNavLink,
        Alerts,
        HouseIcon,
        BackpackIcon,
        LuggageIcon,
        SchoolIcon,
        PersonsIcon,
        PaymentsIcon,
        EditIcon,
        AyudaIcon,
        UserIcon,
        ReportIcon,
        SettingsIcon,
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
