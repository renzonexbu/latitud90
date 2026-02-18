<template>
    <div class="h-screen flex overflow-hidden" :style="`background-color: #F9F9F9; background-image: url('${backgroundImage}'); background-size: cover; background-position: center; background-repeat: no-repeat;`">
        <!-- Sidebar -->
        <aside
            @mouseenter="sidebarExpanded = true"
            @mouseleave="sidebarExpanded = false"
            class="mt-3 mb-3 ml-6 rounded-[20px] bg-white shadow-md flex flex-col h-[calc(100vh-24px)] fixed z-50 transition-all duration-300 ease-in-out"
            :class="sidebarExpanded ? 'w-[250px]' : 'w-[102px]'"
        >
            <!-- Logo Section -->
            <div class="flex items-center justify-center py-4">
                <a href="/" class="text-lg font-bold">
                    <img
                        :src="images['logo-color']"
                        alt="Logo Latitud 90"
                        class="h-12"
                    />
                </a>
            </div>

            <!-- Navigation Icons -->
            <nav class="flex flex-col flex-1">
                <NavLink
                    v-if="!isOnlyMarketing"
                    :href="route('admin.dashboard')"
                    :active="route().current('admin.dashboard')"
                    class="flex items-center w-full px-6 py-3 group transition-colors mt-4"
                >
                    <HouseIcon
                        class="w-6 h-6 transition-colors flex-shrink-0"
                        :class="
                            route().current('admin.dashboard')
                                ? 'text-turquesa'
                                : 'text-gray-400 group-hover:text-turquesa'
                        "
                    />
                    <span
                        class="ml-4 text-sm font-medium whitespace-nowrap transition-all duration-300"
                        :class="[
                            route().current('admin.dashboard') ? 'text-turquesa' : 'text-gray-600 group-hover:text-turquesa',
                            sidebarExpanded ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'
                        ]"
                    >
                        Dashboard
                    </span>
                </NavLink>
                <NavLink
                    v-if="isSuperAdmin || isContabilidad"
                    :href="route('admin.programs.index')"
                    :active="route().current('admin.programs.*')"
                    class="flex items-center w-full px-6 py-3 group transition-colors mt-4"
                >
                    <BackpackIcon
                        class="w-6 h-6 transition-colors flex-shrink-0"
                        :class="
                            route().current('admin.programs.*')
                                ? 'text-turquesa'
                                : 'text-gray-400 group-hover:text-turquesa'
                        "
                        stroke-color="currentColor"
                    />
                    <span
                        class="ml-4 text-sm font-medium whitespace-nowrap transition-all duration-300"
                        :class="[
                            route().current('admin.programs.*') ? 'text-turquesa' : 'text-gray-600 group-hover:text-turquesa',
                            sidebarExpanded ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'
                        ]"
                    >
                        Plantillas
                    </span>
                </NavLink>
                <!-- Programas - Cursos con Submenú -->
                <div v-if="!isEjecutivoComercial && !isMarketing" class="relative courses-dropdown-container">
                    <button
                        @click="toggleCoursesMenu"
                        class="flex items-center w-full px-6 py-3 group transition-colors mt-4"
                        :class="route().current('admin.courses.*') || route().current('admin.executives.*') || route().current('admin.institutions.*') ? 'bg-gray-50' : ''"
                    >
                        <LuggageIcon
                            class="w-6 h-6 transition-colors flex-shrink-0"
                            :class="
                                route().current('admin.courses.*') || route().current('admin.executives.*') || route().current('admin.institutions.*')
                                    ? 'text-turquesa'
                                    : 'text-gray-400 group-hover:text-turquesa'
                            "
                            stroke-color="currentColor"
                        />
                        <span
                            class="ml-4 text-sm font-medium whitespace-nowrap transition-all duration-300"
                            :class="[
                                route().current('admin.courses.*') || route().current('admin.executives.*') || route().current('admin.institutions.*') ? 'text-turquesa' : 'text-gray-600 group-hover:text-turquesa',
                                sidebarExpanded ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'
                            ]"
                        >
                            Programas - Cursos
                        </span>
                        <svg
                            v-if="sidebarExpanded"
                            class="w-4 h-4 ml-auto transition-transform duration-200"
                            :class="[
                                showingCoursesMenu ? 'rotate-180' : '',
                                route().current('admin.courses.*') || route().current('admin.executives.*') || route().current('admin.institutions.*') ? 'text-turquesa' : 'text-gray-400'
                            ]"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Submenú de Programas - Cursos -->
                    <div
                        v-show="showingCoursesMenu && sidebarExpanded"
                        class="flex flex-col pl-10 py-1 bg-gray-50 rounded-b-lg"
                    >
                        <NavLink
                            :href="route('admin.courses.index')"
                            :active="route().current('admin.courses.index')"
                            class="block px-4 py-2 text-xs text-gray-600 hover:text-turquesa hover:bg-gray-100 transition-colors"
                            @click="showingCoursesMenu = false"
                        >
                            Ver todos
                        </NavLink>
                        <NavLink
                            :href="route('admin.executives.index')"
                            :active="route().current('admin.executives.*')"
                            class="block px-4 py-2 text-xs text-gray-600 hover:text-turquesa hover:bg-gray-100 transition-colors"
                            @click="showingCoursesMenu = false"
                        >
                            Ejecutivos
                        </NavLink>
                        <NavLink
                            :href="route('admin.institutions.index')"
                            :active="route().current('admin.institutions.*')"
                            class="block px-4 py-2 text-xs text-gray-600 hover:text-turquesa hover:bg-gray-100 transition-colors"
                            @click="showingCoursesMenu = false"
                        >
                            Instituciones
                        </NavLink>
                        <NavLink
                            :href="route('admin.courses.create')"
                            :active="route().current('admin.courses.create')"
                            class="block px-4 py-2 text-xs text-gray-600 hover:text-turquesa hover:bg-gray-100 transition-colors"
                            @click="showingCoursesMenu = false"
                        >
                            Agregar nuevo programa - curso
                        </NavLink>
                        <NavLink
                            v-if="isSuperAdmin || isContabilidad"
                            :href="route('admin.courses.payment-options-programs')"
                            :active="route().current('admin.courses.payment-options-programs')"
                            class="block px-4 py-2 text-xs text-gray-600 hover:text-turquesa hover:bg-gray-100 transition-colors"
                            @click="showingCoursesMenu = false"
                        >
                            Medios de Pago por Programa
                        </NavLink>
                    </div>
                </div>
                <NavLink
                    v-if="!isEjecutivoComercial && !isMarketing"
                    :href="route('admin.participants.index')"
                    :active="route().current('admin.participants.*')"
                    class="flex items-center w-full px-6 py-3 group transition-colors mt-4"
                >
                    <PersonsIcon
                        class="w-6 h-6 transition-colors flex-shrink-0"
                        :class="
                            route().current('admin.participants.*')
                                ? 'text-turquesa'
                                : 'text-gray-400 group-hover:text-turquesa'
                        "
                        stroke-color="currentColor"
                    />
                    <span
                        class="ml-4 text-sm font-medium whitespace-nowrap transition-all duration-300"
                        :class="[
                            route().current('admin.participants.*') ? 'text-turquesa' : 'text-gray-600 group-hover:text-turquesa',
                            sidebarExpanded ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'
                        ]"
                    >
                        Participantes
                    </span>
                </NavLink>
                <!-- Pagos con Submenú -->
                <div v-if="!isEjecutivoComercial && !isMarketing" class="relative payments-dropdown-container">
                    <button
                        @click="togglePaymentsMenu"
                        class="flex items-center w-full px-6 py-3 group transition-colors mt-4"
                        :class="route().current('admin.payments.*') ? 'bg-gray-50' : ''"
                    >
                        <PaymentsIcon
                            class="w-6 h-6 transition-colors flex-shrink-0"
                            :class="
                                route().current('admin.payments.*')
                                    ? 'text-turquesa'
                                    : 'text-gray-400 group-hover:text-turquesa'
                            "
                            stroke-color="currentColor"
                        />
                        <span
                            class="ml-4 text-sm font-medium whitespace-nowrap transition-all duration-300"
                            :class="[
                                route().current('admin.payments.*') ? 'text-turquesa' : 'text-gray-600 group-hover:text-turquesa',
                                sidebarExpanded ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'
                            ]"
                        >
                            Pagos
                        </span>
                        <svg
                            v-if="sidebarExpanded"
                            class="w-4 h-4 ml-auto transition-transform duration-200"
                            :class="[
                                showingPaymentsMenu ? 'rotate-180' : '',
                                route().current('admin.payments.*') ? 'text-turquesa' : 'text-gray-400'
                            ]"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Submenú de Pagos -->
                    <div
                        v-show="showingPaymentsMenu && sidebarExpanded"
                        class="flex flex-col pl-10 py-1 bg-gray-50 rounded-b-lg"
                    >
                        <NavLink
                            :href="route('admin.payments.index')"
                            :active="route().current('admin.payments.index')"
                            class="block px-4 py-2 text-xs text-gray-600 hover:text-turquesa hover:bg-gray-100 transition-colors"
                            @click="showingPaymentsMenu = false"
                        >
                            Ver todos los pagos
                        </NavLink>
                        <NavLink
                            :href="route('admin.payments.confirmations.index')"
                            :active="route().current('admin.payments.confirmations.*')"
                            class="block px-4 py-2 text-xs text-gray-600 hover:text-turquesa hover:bg-gray-100 transition-colors"
                            @click="showingPaymentsMenu = false"
                        >
                            Historial de Confirmaciones
                        </NavLink>
                        <NavLink
                            :href="route('admin.payments.refunds.menu')"
                            :active="route().current('admin.payments.refunds.*')"
                            class="block px-4 py-2 text-xs text-gray-600 hover:text-turquesa hover:bg-gray-100 transition-colors"
                            @click="showingPaymentsMenu = false"
                        >
                            Procesar Reembolso
                        </NavLink>
                        <NavLink
                            :href="route('admin.payments.presential.menu')"
                            :active="route().current('admin.payments.presential.*')"
                            class="block px-4 py-2 text-xs text-gray-600 hover:text-turquesa hover:bg-gray-100 transition-colors"
                            @click="showingPaymentsMenu = false"
                        >
                            Registrar Pago Offline
                        </NavLink>
                    </div>
                </div>
                <!-- Suscripciones con Submenú -->
                <div v-if="!isEjecutivoComercial && !isMarketing" class="relative subscriptions-dropdown-container">
                    <button
                        @click="toggleSubscriptionsMenu"
                        class="flex items-center w-full px-6 py-3 group transition-colors mt-4"
                        :class="route().current('admin.subscriptions.*') || route().current('admin.guardian-users.*') ? 'bg-gray-50' : ''"
                    >
                        <SubscriptionIcon
                            class="w-6 h-6 transition-colors flex-shrink-0"
                            :class="
                                route().current('admin.subscriptions.*') || route().current('admin.guardian-users.*')
                                    ? 'text-turquesa'
                                    : 'text-gray-400 group-hover:text-turquesa'
                            "
                            stroke-color="currentColor"
                        />
                        <span
                            class="ml-4 text-sm font-medium whitespace-nowrap transition-all duration-300"
                            :class="[
                                route().current('admin.subscriptions.*') || route().current('admin.guardian-users.*') ? 'text-turquesa' : 'text-gray-600 group-hover:text-turquesa',
                                sidebarExpanded ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'
                            ]"
                        >
                            Suscripciones
                        </span>
                        <svg
                            v-if="sidebarExpanded"
                            class="w-4 h-4 ml-auto transition-transform duration-200"
                            :class="[
                                showingSubscriptionsMenu ? 'rotate-180' : '',
                                route().current('admin.subscriptions.*') || route().current('admin.guardian-users.*') ? 'text-turquesa' : 'text-gray-400'
                            ]"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Submenú de Suscripciones -->
                    <div
                        v-show="showingSubscriptionsMenu && sidebarExpanded"
                        class="flex flex-col pl-10 py-1 bg-gray-50 rounded-b-lg"
                    >
                        <NavLink
                            :href="route('admin.subscriptions.index')"
                            :active="route().current('admin.subscriptions.*')"
                            class="block px-4 py-2 text-xs text-gray-600 hover:text-turquesa hover:bg-gray-100 transition-colors"
                            @click="showingSubscriptionsMenu = false"
                        >
                            Ver Suscripciones
                        </NavLink>
                        <NavLink
                            :href="route('admin.guardian-users.index')"
                            :active="route().current('admin.guardian-users.*')"
                            class="block px-4 py-2 text-xs text-gray-600 hover:text-turquesa hover:bg-gray-100 transition-colors"
                            @click="showingSubscriptionsMenu = false"
                        >
                            Usuarios Registrados
                        </NavLink>
                    </div>
                </div>
                <!-- Reportes con Submenú -->
                <div v-if="(isEjecutivoComercial || isContabilidad || isSuperAdmin) && !isOnlyMarketing" class="relative reports-dropdown-container">
                    <button
                        @click="toggleReportsMenu"
                        class="flex items-center w-full px-6 py-3 group transition-colors mt-4"
                        :class="route().current('admin.reports.*') ? 'bg-gray-50' : ''"
                    >
                        <ReportIcon
                            class="w-6 h-6 transition-colors flex-shrink-0"
                            :class="
                                route().current('admin.reports.*')
                                    ? 'text-turquesa'
                                    : 'text-gray-400 group-hover:text-turquesa'
                            "
                            fill-color="currentColor"
                        />
                        <span
                            class="ml-4 text-sm font-medium whitespace-nowrap transition-all duration-300"
                            :class="[
                                route().current('admin.reports.*') ? 'text-turquesa' : 'text-gray-600 group-hover:text-turquesa',
                                sidebarExpanded ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'
                            ]"
                        >
                            Reportes
                        </span>
                        <svg
                            v-if="sidebarExpanded"
                            class="w-4 h-4 ml-auto transition-transform duration-200"
                            :class="[
                                showingReportsMenu ? 'rotate-180' : '',
                                route().current('admin.reports.*') ? 'text-turquesa' : 'text-gray-400'
                            ]"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Submenú de Reportes -->
                    <div
                        v-show="showingReportsMenu && sidebarExpanded"
                        class="flex flex-col pl-10 py-1 bg-gray-50 rounded-b-lg"
                    >
                        <NavLink
                            v-if="isContabilidad || isSuperAdmin"
                            :href="route('admin.reports.index')"
                            :active="route().current('admin.reports.index') || route().current('admin.reports.daily-payments') || route().current('admin.reports.consolidated-payments') || route().current('admin.reports.softland') || route().current('admin.reports.bsale-documents') || route().current('admin.reports.terms-acceptance') || route().current('admin.reports.paid-installments') || route().current('admin.reports.it-simple')"
                            class="block px-4 py-2 text-xs text-gray-600 hover:text-turquesa hover:bg-gray-100 transition-colors"
                            @click="showingReportsMenu = false"
                        >
                            Contables
                        </NavLink>
                        <NavLink
                            :href="route('admin.reports.executives.index')"
                            :active="route().current('admin.reports.executives.*')"
                            class="block px-4 py-2 text-xs text-gray-600 hover:text-turquesa hover:bg-gray-100 transition-colors"
                            @click="showingReportsMenu = false"
                        >
                            Comerciales
                        </NavLink>
                    </div>
                </div>

                <!-- Contenido del Sitio -->
                <NavLink
                    v-if="isMarketing || isSuperAdmin"
                    :href="route('admin.site-content.index')"
                    :active="route().current('admin.site-content.*')"
                    class="flex items-center w-full px-6 py-3 group transition-colors mt-4"
                    title="Contenido del Sitio"
                >
                    <svg
                        class="w-6 h-6 transition-colors flex-shrink-0"
                        :class="
                            route().current('admin.site-content.*')
                                ? 'text-turquesa'
                                : 'text-gray-400 group-hover:text-turquesa'
                        "
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span
                        class="ml-4 text-sm font-medium whitespace-nowrap transition-all duration-300"
                        :class="[
                            route().current('admin.site-content.*') ? 'text-turquesa' : 'text-gray-600 group-hover:text-turquesa',
                            sidebarExpanded ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'
                        ]"
                    >
                        Contenido
                    </span>
                </NavLink>

                <!-- Mantenedor - Para Super Admin y Marketing -->
                <NavLink
                    v-if="($page.props.auth.user && $page.props.auth.user.roles && $page.props.auth.user.roles.includes('super_admin')) || isMarketing"
                    :href="route('admin.maintainer.index')"
                    :active="route().current('admin.maintainer.*')"
                    class="flex items-center w-full px-6 py-3 group transition-colors mt-4"
                >
                    <SettingsIcon
                        class="w-6 h-6 transition-colors flex-shrink-0"
                        :class="
                            route().current('admin.maintainer.*')
                                ? 'text-turquesa'
                                : 'text-gray-400 group-hover:text-turquesa'
                        "
                        stroke-color="currentColor"
                    />
                    <span
                        class="ml-4 text-sm font-medium whitespace-nowrap transition-all duration-300"
                        :class="[
                            route().current('admin.maintainer.*') ? 'text-turquesa' : 'text-gray-600 group-hover:text-turquesa',
                            sidebarExpanded ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'
                        ]"
                    >
                        Mantenedor
                    </span>
                </NavLink>
            </nav>

            <!-- User Info Section -->
            <div class="border-t border-gray-200">
                <div class="flex flex-col py-4">
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
                    <div class="relative user-dropdown-container w-full">
                        <button
                            @click="showingUserDropdown = !showingUserDropdown"
                            class="flex items-center w-full px-6 py-3 group transition-colors"
                        >
                            <UserIcon
                                class="w-6 h-6 transition-colors flex-shrink-0"
                                :class="
                                    route().current('admin.profile.edit') || route().current('admin.profile.*') || $page.url.includes('/admin/profile')
                                        ? 'text-turquesa'
                                        : 'text-gray-400 group-hover:text-turquesa'
                                "
                            />
                            <span
                                class="ml-4 text-sm font-medium whitespace-nowrap transition-all duration-300"
                                :class="[
                                    (route().current('admin.profile.edit') || route().current('admin.profile.*') || $page.url.includes('/admin/profile')) ? 'text-turquesa' : 'text-gray-600 group-hover:text-turquesa',
                                    sidebarExpanded ? 'opacity-100' : 'opacity-0 w-0 overflow-hidden'
                                ]"
                            >
                                Usuario
                            </span>
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
                                    v-if="!isEjecutivoComercial && !isMarketing && !isContabilidad"
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
        <div class="flex-1 flex flex-col h-full ml-[108px] lg:ml-[126px] min-w-0">
            <main class="flex-1 overflow-y-auto overflow-x-auto">
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
import SubscriptionIcon from "@/Components/Icons/SubscriptionIcon.vue";

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
        SubscriptionIcon,
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
            showingPaymentsMenu: false,
            showingCoursesMenu: false,
            showingSubscriptionsMenu: false,
            showingReportsMenu: false,
            sidebarExpanded: false,
            images,
            backgroundImage,
            showAlert: false,
            alertType: 'success',
            alertTitle: '',
            alertMessage: '',
        };
    },
    computed: {
        isEjecutivoComercial() {
            return this.$page.props.auth.user &&
                   this.$page.props.auth.user.roles &&
                   this.$page.props.auth.user.roles.includes('ejecutivo_comercial');
        },
        isMarketing() {
            return this.$page.props.auth.user &&
                   this.$page.props.auth.user.roles &&
                   this.$page.props.auth.user.roles.includes('marketing');
        },
        isContabilidad() {
            return this.$page.props.auth.user &&
                   this.$page.props.auth.user.roles &&
                   this.$page.props.auth.user.roles.includes('contabilidad');
        },
        isSuperAdmin() {
            return this.$page.props.auth.user &&
                   this.$page.props.auth.user.roles &&
                   this.$page.props.auth.user.roles.includes('super_admin');
        },
        isOnlyMarketing() {
            return this.isMarketing &&
                   !this.isSuperAdmin &&
                   !this.isContabilidad &&
                   !this.isEjecutivoComercial;
        },
    },
    watch: {
        sidebarExpanded(newVal) {
            // Cerrar los submenús cuando el sidebar se colapsa
            if (!newVal) {
                this.showingPaymentsMenu = false;
                this.showingCoursesMenu = false;
                this.showingSubscriptionsMenu = false;
                this.showingReportsMenu = false;
            }
        }
    },
    mounted() {
        // Cerrar dropdown cuando se hace clic fuera
        document.addEventListener('click', this.closeUserDropdown);

        // Si estamos en una página de pagos, abrir el submenú automáticamente
        if (this.route().current('admin.payments.*')) {
            this.showingPaymentsMenu = true;
        }

        // Si estamos en una página de cursos/ejecutivos/instituciones, abrir el submenú automáticamente
        if (this.route().current('admin.courses.*') || this.route().current('admin.executives.*') || this.route().current('admin.institutions.*')) {
            this.showingCoursesMenu = true;
        }

        // Si estamos en una página de suscripciones o guardian users, abrir el submenú automáticamente
        if (this.route().current('admin.subscriptions.*') || this.route().current('admin.guardian-users.*')) {
            this.showingSubscriptionsMenu = true;
        }

        // Si estamos en una página de reportes, abrir el submenú automáticamente
        if (this.route().current('admin.reports.*')) {
            this.showingReportsMenu = true;
        }
        
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
            // También cerrar el menú de pagos si se hace clic fuera
            const paymentsDropdown = this.$el.querySelector('.payments-dropdown-container');
            if (paymentsDropdown && !paymentsDropdown.contains(event.target)) {
                this.showingPaymentsMenu = false;
            }
            // También cerrar el menú de cursos si se hace clic fuera
            const coursesDropdown = this.$el.querySelector('.courses-dropdown-container');
            if (coursesDropdown && !coursesDropdown.contains(event.target)) {
                this.showingCoursesMenu = false;
            }
            // También cerrar el menú de suscripciones si se hace clic fuera
            const subscriptionsDropdown = this.$el.querySelector('.subscriptions-dropdown-container');
            if (subscriptionsDropdown && !subscriptionsDropdown.contains(event.target)) {
                this.showingSubscriptionsMenu = false;
            }
            // También cerrar el menú de reportes si se hace clic fuera
            const reportsDropdown = this.$el.querySelector('.reports-dropdown-container');
            if (reportsDropdown && !reportsDropdown.contains(event.target)) {
                this.showingReportsMenu = false;
            }
        },
        togglePaymentsMenu() {
            this.showingPaymentsMenu = !this.showingPaymentsMenu;
        },
        toggleCoursesMenu() {
            this.showingCoursesMenu = !this.showingCoursesMenu;
        },
        toggleSubscriptionsMenu() {
            this.showingSubscriptionsMenu = !this.showingSubscriptionsMenu;
        },
        toggleReportsMenu() {
            this.showingReportsMenu = !this.showingReportsMenu;
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
