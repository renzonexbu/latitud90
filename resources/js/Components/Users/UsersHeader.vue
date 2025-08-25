<template>
    <div class="py-5 flex flex-row items-center justify-between relative">
        <!-- Left side - Breadcrumb and title -->
        <div class="flex flex-row gap-4 items-center justify-start flex-shrink-0 relative">
            <div class="text-[30px] leading-9 font-normal text-turquesa font-nexa-xbold">
                Usuarios
            </div>
            <div class="w-0 h-[35px] relative overflow-visible">
                <!-- Vector separator -->
                <div class="w-px h-full bg-gray-300"></div>
            </div>
            <div class="text-[20px] leading-7 font-normal text-[#5b5b5b] font-nexa-regular">
                {{ subtitle }}
            </div>
        </div>

        <!-- Right side - Action buttons -->
        <div v-if="showCreateButton" class="flex flex-row items-center justify-end flex-shrink-0 relative gap-4">
            <!-- Create User Button Container -->
            <div class="relative">
                <!-- Plus icon positioned above the button -->
                <div class="absolute top-[0.8rem] -right-[230px] z-10 flex-shrink-0 w-[21.289px] h-[21.289px] relative overflow-visible">
                    <svg 
                        xmlns="http://www.w3.org/2000/svg" 
                        width="21.289" 
                        height="21.289" 
                        viewBox="0 0 22 22" 
                        fill="none"
                        class="flex-shrink-0"
                    >
                        <circle cx="11.3553" cy="10.6447" r="10.6447" fill="#FBBD51"/>
                        <path d="M4.58154 9.49167H9.74326V4.36182H12.2923V9.49167H17.5177V11.9451H12.2923V17.2024H9.74326V11.9451H4.58154V9.49167Z" fill="#F9F9F9"/>
                    </svg>
                </div>
                
                <!-- Create User Button -->
                <button
                    @click="handleCreateUser"
                    class="bg-turquesa rounded-[112.89px] px-[18px] py-[14px] flex flex-row gap-[11.29px] items-center justify-center hover:bg-turquesa-dark transition-colors"
                >
                    <div 
                        class="flex-shrink-0 relative overflow-hidden"
                        style="width: 22px; height: 22px; aspect-ratio: 1/1;"
                    >
                        <svg 
                            width="22" 
                            height="22" 
                            viewBox="0 0 24 24" 
                            fill="none" 
                            stroke="white" 
                            stroke-width="2"
                        >
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <div class="text-white text-left font-nexa-bold text-base leading-[22px] font-bold relative flex items-end justify-start">
                        Crear Usuario
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "UsersHeader",
    props: {
        subtitle: {
            type: String,
            default: "Administración de usuarios del sistema"
        },

        showCreateButton: {
            type: Boolean,
            default: true
        }
    },
    methods: {
        handleCreateUser() {
            this.$emit('create-user');
        }
    }
};
</script>
