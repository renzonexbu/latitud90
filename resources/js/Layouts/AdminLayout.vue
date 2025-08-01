<template>
    <div class="h-screen flex bg-gray-100 overflow-hidden">
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
                    class="flex justify-center w-full p-2 hover:bg-gray-50 transition-colors mt-11"
                >
                    <HouseIcon
                        class="w-10 h-10 transition-colors"
                        :class="
                            route().current('admin.dashboard')
                                ? 'text-turquesa'
                                : 'text-gray-400'
                        "
                    />
                </NavLink>
                <NavLink
                    :href="route('admin.programs.index')"
                    :active="route().current('admin.programs.*')"
                    class="flex justify-center w-full p-2 hover:bg-gray-50 transition-colors mt-11"
                >
                    <BackpackIcon
                        class="w-10 h-10 transition-colors"
                        :class="
                            route().current('admin.programs.*')
                                ? 'text-turquesa'
                                : 'text-gray-400'
                        "
                        stroke-color="currentColor"
                    />
                </NavLink>
                <NavLink
                    :href="route('admin.courses.index')"
                    :active="route().current('admin.courses.*')"
                    class="flex justify-center w-full p-2 hover:bg-gray-50 transition-colors mt-11"
                >
                    <LuggageIcon
                        class="w-10 h-10 transition-colors"
                        :class="
                            route().current('admin.courses.*')
                                ? 'text-turquesa'
                                : 'text-gray-400'
                        "
                        stroke-color="currentColor"
                    />
                </NavLink>
                <NavLink
                    :href="route('admin.participants.index')"
                    :active="route().current('admin.participants.*')"
                    class="flex justify-center w-full p-2 hover:bg-gray-50 transition-colors mt-11"
                >
                    <PersonsIcon
                        class="w-10 h-10 transition-colors"
                        :class="
                            route().current('admin.participants.*')
                                ? 'text-turquesa'
                                : 'text-gray-400'
                        "
                        stroke-color="currentColor"
                    />
                </NavLink>
                <NavLink
                    :href="route('admin.payments.index')"
                    :active="route().current('admin.payments.*')"
                    class="flex justify-center w-full p-2 hover:bg-gray-50 transition-colors mt-11"
                >
                    <PaymentsIcon
                        class="w-10 h-10 transition-colors"
                        :class="
                            route().current('admin.payments.*')
                                ? 'text-turquesa'
                                : 'text-gray-400'
                        "
                        stroke-color="currentColor"
                    />
                </NavLink>
                <NavLink
                    :href="route('admin.reports.index')"
                    :active="route().current('admin.reports.*')"
                    class="flex justify-center w-full p-2 hover:bg-gray-50 transition-colors mt-11"
                >
                    <EditIcon
                        class="w-10 h-10 transition-colors"
                        :class="
                            route().current('admin.reports.*')
                                ? 'text-turquesa'
                                : 'text-gray-400'
                        "
                        fill-color="currentColor"
                    />
                </NavLink>
            </nav>

            <!-- User Info Section -->
            <div class="border-t border-gray-200">
                <div class="flex flex-col items-center space-y-4 py-4">
                    <NavLink
                        :href="route('profile.edit')"
                        class="flex justify-center w-full p-2 hover:bg-gray-50 transition-colors"
                    >
                        <AyudaIcon />
                    </NavLink>
                    <NavLink
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="flex justify-center w-full p-2 hover:bg-gray-50 transition-colors"
                    >
                        <UserIcon />
                    </NavLink>
                </div>
            </div>
        </aside>

        <!-- Page Content -->
        <div class="flex-1 flex flex-col h-full">
            <main class="flex-1 overflow-y-auto">
                <slot />
            </main>

            <!-- Toast Notifications -->
            <div
                v-if="$page.props.flash.message"
                class="fixed top-4 right-4 z-50"
            >
                <div
                    class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg"
                >
                    {{ $page.props.flash.message }}
                </div>
            </div>

            <div
                v-if="$page.props.flash.error"
                class="fixed top-4 right-4 z-50"
            >
                <div
                    class="bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg"
                >
                    {{ $page.props.flash.error }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { Link } from "@inertiajs/vue3";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import NavLink from "@/Components/NavLink.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";

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
} from "@/Components/Icons";

import images from "../../images/index.js";

export default {
    name: "AdminLayout",
    components: {
        Link,
        Dropdown,
        DropdownLink,
        NavLink,
        ResponsiveNavLink,
        HouseIcon,
        BackpackIcon,
        LuggageIcon,
        SchoolIcon,
        PersonsIcon,
        PaymentsIcon,
        EditIcon,
        AyudaIcon,
        UserIcon,
    },
    data() {
        return {
            showingNavigationDropdown: false,
            images,
        };
    },
};
</script>
