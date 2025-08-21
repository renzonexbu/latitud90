<template>
    <AdminLayout>
        <Head title="Editar Participante" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Flash Messages -->
                <div v-if="$page.props.flash.success" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    <span class="block sm:inline">{{ $page.props.flash.success }}</span>
                    <button @click="$page.props.flash.success = null" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                        <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <title>Close</title>
                            <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                        </svg>
                    </button>
                </div>

                <div v-if="$page.props.flash.error" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <span class="block sm:inline">{{ $page.props.flash.error }}</span>
                    <button @click="$page.props.flash.error = null" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                        <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <title>Close</title>
                            <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                        </svg>
                    </button>
                </div>

                <!-- Header -->
                <ParticipantsHeader
                    subtitle="Visualización de participantes"
                    :participant-name="getFullName(participant)"
                />

                <!-- Participant Info Card -->
                <div
                    class="bg-white rounded-[20px] min-h-[166px] relative overflow-hidden mb-6"
                >
                    <div
                        class="px-8 py-3 flex flex-col gap-3 items-start justify-start w-full"
                    >
                        <div
                            class="flex flex-col gap-[10px] items-start justify-start self-stretch flex-shrink-0 relative"
                        >
                            <div
                                class="flex flex-col gap-[22px] items-start justify-start self-stretch flex-shrink-0 relative"
                            >
                                <div
                                    class="flex flex-col gap-[12px] items-start justify-start self-stretch flex-shrink-0 relative"
                                >
                                    <div
                                        class="flex flex-col items-start justify-between self-stretch flex-shrink-0 h-[46px] relative"
                                    >
                                        <div
                                            class="flex flex-col gap-[20px] items-start justify-start self-stretch flex-shrink-0 relative"
                                        >
                                            <div
                                                class="flex flex-row items-start justify-between self-stretch flex-shrink-0 relative"
                                            >
                                                <div
                                                    class="flex flex-col gap-[6px] items-start justify-center flex-shrink-0 flex-1 relative"
                                                >
                                                    <div
                                                        class="text-turquesa text-left font-nexa-bold text-[24px] leading-[28px] font-bold relative self-stretch"
                                                    >
                                                        {{ getFullName(participant) }}
                                                    </div>
                                                </div>
                                                <div
                                                    class="flex flex-row gap-[10px] items-center justify-start flex-shrink-0 relative"
                                                >
                                                    <button
                                                        @click="
                                                            openEmergencyContactsModal
                                                        "
                                                        class="bg-azul-oscuro rounded-[50px] px-4 py-2 flex flex-row gap-0 items-center justify-start flex-shrink-0 h-[40px] relative hover:bg-azul-oscuro-dark transition-colors"
                                                    >
                                                        <div
                                                            class="text-white text-left font-nexa-bold text-[12px] leading-[18px] font-bold relative flex items-end justify-start"
                                                        >
                                                            Ver apoderado
                                                        </div>
                                                    </button>
                                                    <button
                                                        @click="
                                                            openMedicalModal
                                                        "
                                                        class="bg-rojo rounded-[112.89px] border border-rojo border-solid p-[8px_7px] flex flex-row gap-[14px] items-center justify-start flex-shrink-0 relative overflow-hidden hover:bg-red-600 transition-colors"
                                                    >
                                                        <div
                                                            class="flex-shrink-0 w-6 h-6 relative overflow-hidden aspect-square"
                                                        >
                                                            <!-- Health Icon -->
                                                            <svg
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                width="25"
                                                                height="24"
                                                                viewBox="0 0 25 24"
                                                                fill="none"
                                                            >
                                                                <path
                                                                    d="M12.5789 5.57355L12.0309 6.08555C12.101 6.16065 12.1859 6.22052 12.2801 6.26145C12.3744 6.30238 12.4761 6.3235 12.5789 6.3235C12.6816 6.3235 12.7833 6.30238 12.8776 6.26145C12.9718 6.22052 13.0567 6.16065 13.1269 6.08555L12.5789 5.57355ZM10.0129 18.7956C8.56286 17.6196 6.87086 16.0766 5.54686 14.3876C4.20786 12.6806 3.32886 10.9276 3.32886 9.31755H1.82886C1.82886 11.4346 2.95886 13.5196 4.36586 15.3136C5.78786 17.1266 7.57586 18.7496 9.06786 19.9606L10.0129 18.7956ZM3.32886 9.31755C3.32886 6.41255 4.59686 4.61755 6.16486 4.00255C7.72986 3.38955 9.91886 3.82755 12.0309 6.08555L13.1269 5.06155C10.7389 2.50755 7.92786 1.70155 5.61786 2.60555C3.31086 3.50955 1.82886 5.99155 1.82886 9.31755H3.32886ZM16.0889 19.9596C17.5819 18.7486 19.3699 17.1256 20.7919 15.3126C22.1989 13.5186 23.3289 11.4336 23.3289 9.31555H21.8289C21.8289 10.9276 20.9489 12.6796 19.6109 14.3866C18.2869 16.0756 16.5949 17.6186 15.1449 18.7946L16.0889 19.9596ZM23.3289 9.31555C23.3289 5.99055 21.8469 3.50855 19.5389 2.60555C17.2289 1.70055 14.4189 2.50555 12.0309 5.06055L13.1269 6.08555C15.2389 3.82755 17.4279 3.38855 18.9929 4.00155C20.5609 4.61555 21.8289 6.41155 21.8289 9.31555H23.3289ZM9.06786 19.9606C10.3379 20.9926 11.2209 21.7496 12.5789 21.7496V20.2496C11.8559 20.2496 11.4059 19.9256 10.0129 18.7956L9.06786 19.9606ZM15.1449 18.7946C13.7519 19.9246 13.3019 20.2496 12.5789 20.2496V21.7496C13.9369 21.7496 14.8199 20.9926 16.0899 19.9606L15.1449 18.7946Z"
                                                                    fill="white"
                                                                />
                                                                <path
                                                                    d="M19.0789 9H17.0789M17.0789 9H15.0789M17.0789 9V7M17.0789 9V11"
                                                                    stroke="white"
                                                                    stroke-width="1.5"
                                                                    stroke-linecap="round"
                                                                />
                                                            </svg>
                                                        </div>
                                                    </button>
                                                    <button
                                                        @click="openEditModal"
                                                        class="rounded-[112.894px] border border-[#D3D3D3] bg-[#F9F9F9] flex h-[40px] px-3 py-[11.289px] items-center gap-[14px] relative overflow-visible hover:bg-gray-100 transition-colors"
                                                    >
                                                        <!-- Edit Icon -->
                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            width="17"
                                                            height="18"
                                                            viewBox="0 0 17 18"
                                                            fill="none"
                                                        >
                                                            <path
                                                                d="M16.1402 1.39297C15.5891 0.842436 14.8419 0.533203 14.0629 0.533203C13.2839 0.533203 12.5367 0.842436 11.9856 1.39297L2.17204 11.2065C1.83831 11.5399 1.60378 11.9595 1.49465 12.4184L0.595571 16.197C0.571227 16.2994 0.57351 16.4062 0.602201 16.5075C0.630891 16.6087 0.685034 16.7009 0.759465 16.7752C0.833896 16.8496 0.926133 16.9036 1.02738 16.9322C1.12863 16.9608 1.23551 16.9629 1.33783 16.9385L5.11561 16.0386C5.57484 15.9296 5.99472 15.695 6.32835 15.3612L7.27341 14.4161C7.15439 13.8787 7.11922 13.3262 7.16913 12.7781L5.45718 14.4908C5.28475 14.6632 5.06799 14.7848 4.8307 14.8414L2.02589 15.5098L2.69343 12.705C2.75008 12.4669 2.8716 12.2501 3.04403 12.0777L11.2532 3.86606L13.6672 6.28003L12.0036 7.94354C12.5515 7.89501 13.1036 7.93017 13.6409 8.04781L16.1402 5.54845C16.6908 4.99729 17 4.25013 17 3.47112C17 2.69211 16.6908 1.94413 16.1402 1.39297ZM12.8559 2.26413C13.0144 2.10563 13.2026 1.9799 13.4097 1.89412C13.6168 1.80833 13.8388 1.76418 14.0629 1.76418C14.2871 1.76418 14.509 1.80833 14.7161 1.89412C14.9232 1.9799 15.1114 2.10563 15.2699 2.26413C15.4284 2.42264 15.5541 2.61081 15.6399 2.8179C15.7257 3.025 15.7698 3.24696 15.7698 3.47112C15.7698 3.69528 15.7257 3.91724 15.6399 4.12434C15.5541 4.33143 15.4284 4.5196 15.2699 4.67811L14.5375 5.40887L12.1235 2.99571L12.8559 2.26413ZM9.83846 10.3665C9.89973 10.5788 9.91753 10.8013 9.89079 11.0206C9.86406 11.2399 9.79334 11.4516 9.68287 11.643C9.5724 11.8343 9.42445 12.0014 9.24788 12.1342C9.07131 12.267 8.86976 12.3629 8.65528 12.416L8.17577 12.535C8.09851 13.0267 8.10018 13.5275 8.1807 14.0187L8.62408 14.1254C8.8405 14.1776 9.04406 14.2732 9.2224 14.4064C9.40073 14.5396 9.55011 14.7077 9.66147 14.9005C9.77282 15.0933 9.84382 15.3066 9.87016 15.5277C9.89649 15.7487 9.8776 15.9728 9.81465 16.1864L9.66111 16.7045C10.0224 17.0214 10.4329 17.2792 10.8804 17.4615L11.2852 17.0353C11.4386 16.874 11.6232 16.7455 11.8278 16.6577C12.0324 16.5699 12.2527 16.5247 12.4754 16.5247C12.698 16.5247 12.9183 16.5699 13.1229 16.6577C13.3275 16.7455 13.5121 16.874 13.6655 17.0353L14.0752 17.4672C14.5194 17.2861 14.9305 17.0328 15.2921 16.7176L15.1295 16.1543C15.0682 15.942 15.0505 15.7195 15.0773 15.5001C15.104 15.2807 15.1748 15.069 15.2854 14.8777C15.3959 14.6863 15.544 14.5192 15.7206 14.3865C15.8973 14.2537 16.0989 14.1579 16.3135 14.1049L16.7922 13.9859C16.8694 13.4942 16.8678 12.9933 16.7873 12.5022L16.3439 12.3954C16.1275 12.3432 15.924 12.2476 15.7458 12.1143C15.5676 11.981 15.4183 11.8129 15.307 11.6201C15.1957 11.4274 15.1248 11.214 15.0985 10.993C15.0722 10.772 15.0912 10.548 15.1541 10.3345L15.3068 9.81642C14.9454 9.49841 14.5336 9.24259 14.0884 9.05938L13.6836 9.4847C13.5302 9.64619 13.3455 9.77478 13.1408 9.86265C12.9362 9.95051 12.7157 9.99583 12.493 9.99583C12.2703 9.99583 12.0499 9.95051 11.8452 9.86265C11.6405 9.77478 11.4559 9.64619 11.3024 9.4847L10.8935 9.05364C10.4469 9.23427 10.0363 9.48881 9.67589 9.80328L9.83846 10.3665ZM12.4848 14.4908C11.8279 14.4908 11.2942 13.9399 11.2942 13.2592C11.2942 12.5794 11.8279 12.0276 12.4848 12.0276C13.1417 12.0276 13.6754 12.5794 13.6754 13.2592C13.6754 13.9399 13.1417 14.4908 12.4848 14.4908Z"
                                                                fill="#C7C7C7"
                                                            />
                                                        </svg>
                                                    </button>
                                                    <button
                                                        @click="confirmDeleteParticipant"
                                                        class="rounded-[112.894px] border border-red-500 bg-red-50 flex h-[40px] w-[40px] items-center justify-center relative overflow-visible hover:bg-red-100 transition-colors"
                                                    >
                                                        <!-- Delete Icon -->
                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            width="16"
                                                            height="16"
                                                            viewBox="0 0 24 24"
                                                            fill="none"
                                                            class="flex-shrink-0"
                                                        >
                                                            <path
                                                                d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"
                                                                fill="#DC2626"
                                                            />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="flex flex-col gap-[25px] items-start justify-start self-stretch flex-shrink-0 relative"
                            >
                                <div
                                    class="flex flex-row gap-[9.56px] items-center justify-start flex-shrink-0 relative"
                                >
                                    <!-- <div
                                        class="flex flex-row gap-[5.98px] items-center justify-start flex-shrink-0 relative"
                                    >
                                        <div
                                            class="flex flex-row gap-[2.39px] items-center justify-start flex-shrink-0 relative"
                                        >
                                            <div
                                                class="text-turquesa text-left font-nexa-bold text-[14px] leading-[18px] font-bold relative"
                                            >
                                                {{
                                                    participant.course
                                                        ?.institution?.name ||
                                                    "Institución no asignada"
                                                }}
                                            </div>
                                            <div
                                                class="text-turquesa text-left font-nexa-bold text-[14px] leading-[15.54px] font-bold relative"
                                            >
                                                |
                                            </div>
                                            <div
                                                class="text-turquesa text-left font-nexa-bold text-[14px] leading-[18px] font-bold relative"
                                            >
                                                {{
                                                    participant.course?.grade ||
                                                    "N/A"
                                                }}
                                            </div>
                                            <div
                                                class="text-turquesa text-left font-nexa-bold text-[14px] leading-[18px] font-bold relative"
                                            >
                                                |
                                            </div>
                                            <div
                                                class="text-turquesa text-left font-nexa-bold text-[14px] leading-[18px] font-bold relative"
                                            >
                                                {{
                                                    participant.course
                                                        ?.education_level ||
                                                    "N/A"
                                                }}
                                            </div>
                                            <div
                                                class="text-turquesa text-left font-nexa-bold text-[14px] leading-[18px] font-bold relative"
                                            >
                                                |
                                            </div>
                                            <div
                                                class="text-turquesa text-left font-nexa-bold text-[14px] leading-[18px] font-bold relative"
                                            >
                                                {{
                                                    participant.course?.shift ||
                                                    "N/A"
                                                }}
                                            </div>
                                        </div>
                                    </div> -->
                                </div>
                                <div
                                    class="flex flex-col gap-[11px] items-start justify-start self-stretch flex-shrink-0 relative"
                                >
                                    <!-- Documento (RUT/Pasaporte) - Full width, below -->
                                    <div class="flex flex-row gap-[5px] items-center justify-start flex-shrink-0 relative w-full">
                                        <div class="flex-shrink-0 w-[18px] h-[18px] relative overflow-visible">
                                            <!-- ID Icon -->
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="18"
                                                height="19"
                                                viewBox="0 0 18 19"
                                                fill="none"
                                            >
                                                <rect
                                                    width="18"
                                                    height="18"
                                                    transform="translate(0 0.888184)"
                                                    fill="white"
                                                />
                                                <path
                                                    d="M2.4 15.7763V16.3763H3.6V15.7763H2.4ZM8.4 15.7763V16.3763H9.6V15.7763H8.4ZM3.6 15.7763V15.1763H2.4V15.7763H3.6ZM8.4 15.1763V15.7763H9.6V15.1763H8.4ZM6 12.7763C6.63652 12.7763 7.24697 13.0291 7.69706 13.4792C8.14714 13.9293 8.4 14.5398 8.4 15.1763H9.6C9.6 14.2215 9.22072 13.3058 8.54558 12.6307C7.87045 11.9556 6.95478 11.5763 6 11.5763V12.7763ZM3.6 15.1763C3.6 14.5398 3.85286 13.9293 4.30294 13.4792C4.75303 13.0291 5.36348 12.7763 6 12.7763V11.5763C5.04522 11.5763 4.12955 11.9556 3.45442 12.6307C2.77928 13.3058 2.4 14.2215 2.4 15.1763H3.6ZM6 5.57627C5.36348 5.57627 4.75303 5.82913 4.30294 6.27921C3.85286 6.7293 3.6 7.33975 3.6 7.97627H4.8C4.8 7.65801 4.92643 7.35279 5.15147 7.12774C5.37652 6.9027 5.68174 6.77627 6 6.77627V5.57627ZM8.4 7.97627C8.4 7.33975 8.14714 6.7293 7.69706 6.27921C7.24697 5.82913 6.63652 5.57627 6 5.57627V6.77627C6.31826 6.77627 6.62348 6.9027 6.84853 7.12774C7.07357 7.35279 7.2 7.65801 7.2 7.97627H8.4ZM6 10.3763C6.63652 10.3763 7.24697 10.1234 7.69706 9.67333C8.14714 9.22324 8.4 8.61279 8.4 7.97627H7.2C7.2 8.29453 7.07357 8.59975 6.84853 8.8248C6.62348 9.04984 6.31826 9.17627 6 9.17627V10.3763ZM6 9.17627C5.68174 9.17627 5.37652 9.04984 5.15147 8.8248C4.92643 8.59975 4.8 8.29453 4.8 7.97627H3.6C3.6 8.61279 3.85286 9.22324 4.30294 9.67333C4.75303 10.1234 5.36348 10.3763 6 10.3763V9.17627ZM1.8 4.37627H16.2V3.17627H1.8V4.37627ZM16.8 4.97627V14.5763H18V4.97627H16.8ZM16.2 15.1763H1.8V16.3763H16.2V15.1763ZM1.2 14.5763V4.97627H0V14.5763H1.2ZM1.8 15.1763C1.64087 15.1763 1.48826 15.1131 1.37574 15.0005C1.26321 14.888 1.2 14.7354 1.2 14.5763H0C0 15.0537 0.189642 15.5115 0.527208 15.8491C0.864773 16.1866 1.32261 16.3763 1.8 16.3763V15.1763ZM16.8 14.5763C16.8 14.7354 16.7368 14.888 16.6243 15.0005C16.5117 15.1131 16.3591 15.1763 16.2 15.1763V16.3763C16.6774 16.3763 17.1352 16.1866 17.4728 15.8491C17.8104 15.5115 18 15.0537 18 14.5763H16.8ZM16.2 4.37627C16.3591 4.37627 16.5117 4.43948 16.6243 4.55201C16.7368 4.66453 16.8 4.81714 16.8 4.97627H18C18 4.49888 17.8104 4.04104 17.4728 3.70348C17.1352 3.36591 16.6774 3.17627 16.2 3.17627V4.37627ZM1.8 3.17627C1.32261 3.17627 0.864773 3.36591 0.527208 3.70348C0.189642 4.04104 0 4.49888 0 4.97627H1.2C1.2 4.81714 1.26321 4.66453 1.37574 4.55201C1.48826 4.43948 1.64087 4.37627 1.8 4.37627V3.17627ZM10.8 7.97627H14.4V6.77627H10.8V7.97627ZM10.8 11.5763H14.4V10.3763H10.8V11.5763Z"
                                                fill="#007E93"
                                            />
                                        </svg>
                                    </div>
                                    <div class="text-turquesa text-left font-nexa-bold text-[14px] leading-[18px] font-bold relative">
                                        RUT/PASAPORTE: {{ formatRutDisplay(participant.document_number) || "00.000.000-0" }}
                                    </div>
                                </div>

                                <div class="flex flex-row gap-[25px] items-center justify-start flex-shrink-0 relative">
                                    <!-- Fecha de Nacimiento -->
                                    <div class="flex flex-row gap-[5px] items-center justify-start flex-shrink-0 relative">
                                        <div class="bg-white flex-shrink-0 w-[18px] h-[18px] relative overflow-hidden">
                                            <!-- Birthday Icon -->
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="18"
                                                height="19"
                                                viewBox="0 0 18 19"
                                                fill="none"
                                            >
                                                <rect
                                                    width="18"
                                                    height="18"
                                                    transform="translate(0 0.888184)"
                                                    fill="white"
                                                />
                                                <path
                                                    fill-rule="evenodd"
                                                    clip-rule="evenodd"
                                                    d="M13.5 7.52588C14.0739 7.52585 14.6261 7.74512 15.0437 8.13884C15.4613 8.53256 15.7126 9.07096 15.7463 9.64388L15.75 9.77588V12.0259C15.75 12.5059 15.4657 12.8801 15.0885 13.0511L15 13.0864V15.7759C15.0001 16.1543 14.8572 16.5188 14.5999 16.7963C14.3426 17.0738 13.9899 17.2437 13.6125 17.2721L13.5 17.2759H4.5C4.12157 17.276 3.75708 17.1331 3.47959 16.8758C3.2021 16.6184 3.03213 16.2657 3.00375 15.8884L3 15.7759V13.0871C2.79758 13.0165 2.61973 12.8892 2.48768 12.7203C2.35563 12.5514 2.27494 12.3481 2.25525 12.1346L2.25 12.0259V9.77588C2.24997 9.20197 2.46924 8.64974 2.86296 8.23218C3.25668 7.81462 3.79508 7.5633 4.368 7.52963L4.5 7.52588H13.5ZM13.4498 12.5509C13.3334 12.4636 13.1942 12.412 13.049 12.4024C12.9039 12.3928 12.7591 12.4256 12.6322 12.4969L12.5497 12.5509L12.3502 12.7009C11.9822 12.977 11.5384 13.1337 11.0785 13.1498C10.6187 13.1659 10.1649 13.0406 9.7785 12.7909L9.65025 12.7009L9.45 12.5509C9.33365 12.4636 9.19442 12.412 9.04927 12.4024C8.90413 12.3928 8.75932 12.4256 8.6325 12.4969L8.55 12.5509L8.34975 12.7009C7.98179 12.9769 7.53807 13.1335 7.07838 13.1496C6.61869 13.1657 6.16511 13.0405 5.77875 12.7909L5.64975 12.7009L5.45025 12.5509C5.3339 12.4636 5.19467 12.412 5.04952 12.4024C4.90438 12.3928 4.75957 12.4256 4.63275 12.4969L4.55025 12.5509L4.5 12.5884V15.7759H13.5V12.5884L13.4498 12.5509ZM13.5 9.02588H4.5C4.30109 9.02588 4.11032 9.1049 3.96967 9.24555C3.82902 9.3862 3.75 9.57697 3.75 9.77588V11.2796C4.13745 11.0208 4.59569 10.8886 5.06146 10.9014C5.52724 10.9142 5.97756 11.0712 6.35025 11.3509L6.54975 11.5009C6.67957 11.5982 6.83747 11.6509 6.99975 11.6509C7.16203 11.6509 7.31993 11.5982 7.44975 11.5009L7.65 11.3509C8.03947 11.0588 8.51317 10.9009 9 10.9009C9.48683 10.9009 9.96053 11.0588 10.35 11.3509L10.5503 11.5009C10.6801 11.5982 10.838 11.6509 11.0002 11.6509C11.1625 11.6509 11.3204 11.5982 11.4503 11.5009L11.6497 11.3509C12.0224 11.0712 12.4728 10.9142 12.9385 10.9014C13.4043 10.8886 13.8626 11.0208 14.25 11.2796V9.77588C14.25 9.57697 14.171 9.3862 14.0303 9.24555C13.8897 9.1049 13.6989 9.02588 13.5 9.02588ZM9.45 2.42588C9.75965 2.66855 10.0475 2.93782 10.3102 3.23063C10.7032 3.67238 11.25 4.41713 11.25 5.27588C11.25 5.87262 11.0129 6.44491 10.591 6.86687C10.169 7.28883 9.59674 7.52588 9 7.52588C8.40326 7.52588 7.83097 7.28883 7.40901 6.86687C6.98705 6.44491 6.75 5.87262 6.75 5.27588C6.75 4.41713 7.2975 3.67238 7.68975 3.23063C7.9525 2.93782 8.24035 2.66855 8.55 2.42588C8.67982 2.32851 8.83772 2.27588 9 2.27588C9.16228 2.27588 9.32018 2.32851 9.45 2.42588ZM9 4.02638C8.93471 4.09142 8.87143 4.15845 8.81025 4.22738C8.45325 4.62938 8.25 5.00963 8.25 5.27588C8.25 5.47479 8.32902 5.66556 8.46967 5.80621C8.61032 5.94686 8.80109 6.02588 9 6.02588C9.19891 6.02588 9.38968 5.94686 9.53033 5.80621C9.67098 5.66556 9.75 5.47479 9.75 5.27588C9.75 5.00963 9.5475 4.62938 9.18975 4.22738C9.12857 4.15845 9.06529 4.09142 9 4.02638Z"
                                                fill="#007E93"
                                            />
                                        </svg>
                                    </div>
                                    <div class="text-turquesa text-left font-nexa-bold text-[14px] leading-[18px] font-bold relative">
                                                {{ formatBirthDate(participant.birth_date) || "Fecha no disponible" }}
                                            </div>
                                        </div>

                                        <!-- Viajes Totales -->
                                        <div class="flex flex-row gap-[5px] items-center justify-start flex-shrink-0 relative">
                                            <div class="flex-shrink-0 w-[17.931px] h-[17.931px] relative overflow-hidden aspect-square">
                                                <!-- Travel Bag Icon -->
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    width="18"
                                                    height="19"
                                                    viewBox="0 0 18 19"
                                                    fill="none"
                                                >
                                                    <path
                                                        d="M11.207 5.63198H6.72437M11.207 5.63198C13.3199 5.63198 14.377 5.63198 15.033 6.28869C15.6897 6.94465 15.6897 8.00181 15.6897 10.1146V11.9824C15.6897 14.0953 15.6897 15.1524 15.033 15.8084C14.377 16.4651 13.3199 16.4651 11.207 16.4651H6.72437C4.61154 16.4651 3.55437 16.4651 2.89841 15.8084C2.2417 15.1524 2.2417 14.0953 2.2417 11.9824V10.1146C2.2417 8.00181 2.2417 6.94465 2.89841 6.28869C3.55437 5.63198 4.61154 5.63198 6.72437 5.63198M11.207 5.63198V5.25842C11.207 4.20126 11.207 3.67305 10.8783 3.34582C10.5503 3.01709 10.0229 3.01709 8.9657 3.01709C7.90854 3.01709 7.38033 3.01709 7.05309 3.34582C6.72437 3.6738 6.72437 4.20201 6.72437 5.25842V5.63198"
                                                        stroke="#007E93"
                                                        stroke-width="1.12067"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    />
                                                    <path
                                                        d="M15.6897 8.62077C14.8971 8.62077 14.137 8.30592 13.5765 7.74547C13.0161 7.18503 12.7013 6.42491 12.7013 5.63232M2.2417 13.477C3.03428 13.477 3.79441 13.7918 4.35485 14.3523C4.91529 14.9127 5.23014 15.6728 5.23014 16.4654M2.2417 8.62077C3.03428 8.62077 3.79441 8.30592 4.35485 7.74547C4.91529 7.18503 5.23014 6.42491 5.23014 5.63232M15.6897 13.477C14.8971 13.477 14.137 13.7918 13.5765 14.3523C13.0161 14.9127 12.7013 15.6728 12.7013 16.4654M10.8335 8.99432V9.00179M6.91114 9.74143L6.35081 10.3018L6.91114 10.8621L7.47148 10.3018L6.91114 9.74143ZM10.4599 13.3276L9.33925 13.1034L10.2358 12.2069L10.4599 13.3276Z"
                                                        stroke="#007E93"
                                                        stroke-width="1.12067"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    />
                                                </svg>
                                            </div>
                                            <div class="text-turquesa text-left font-nexa-bold text-[14px] leading-[18px] font-bold relative">
                                                Viajes totales: {{ participantPrograms.length }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Programs Grid -->
                <div class="bg-white rounded-[20px] p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-6">
                        Programas del Participante
                    </h3>

                    <ProgramsGrid
                        :programs="formattedPrograms"
                        :prefer-participant-metrics="true"
                    />
                </div>

                <!-- Back Button -->
                <div class="mt-6">
                    <Link
                        :href="route('admin.participants.index')"
                        class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
                    >
                        Volver al listado
                    </Link>
                </div>
            </div>
        </div>

        <!-- Edit Participant Modal -->
        <EditParticipantModal
            :show="showEditModal"
            :participant="participant"
            :participant-programs-with-discounts="
                participantProgramsWithDiscounts
            "
            :errors="errors"
            @close="closeEditModal"
        />

        <!-- Medical Conditions Modal -->
        <MedicalConditionsModal
            :show="showMedicalModal"
            :participant="participant"
            :errors="errors"
            @close="closeMedicalModal"
        />

        <!-- Emergency Contacts Modal -->
        <EmergencyContactsModal
            :show="showEmergencyContactsModal"
            :participant="participant"
            :errors="errors"
            @close="closeEmergencyContactsModal"
        />
    </AdminLayout>
</template>

<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import ParticipantsHeader from "@/Components/Participants/ParticipantsHeader.vue";
import EditParticipantModal from "@/Components/Participants/EditParticipantModal.vue";
import MedicalConditionsModal from "@/Components/Participants/MedicalConditionsModal.vue";
import EmergencyContactsModal from "@/Components/Participants/EmergencyContactsModal.vue";
import ProgramsGrid from "@/Components/Programs/ProgramsGrid.vue";
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    participant: Object,
    participantPrograms: {
        type: Array,
        default: () => [],
    },
    participantProgramsWithDiscounts: {
        type: Array,
        default: () => [],
    },
});

const formatDate = (dateString) => {
    if (!dateString) return null;
    const date = new Date(dateString);
    return date.toLocaleDateString("es-ES");
};

const formatBirthDate = (dateString) => {
    if (!dateString) return null;
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('es-ES', options);
};

const formatCurrency = (amount) => {
    if (!amount) return "0";
    return new Intl.NumberFormat("es-CL").format(amount);
};

// Formatear RUT (12.345.678-9)
const formatRutDisplay = (rut) => {
    if (!rut) return null;
    
    // Detectar si es RUT o PASAPORTE basándose en el formato
    const isRut = /^\d+[\dK]$/.test(rut.replace(/\./g, "").replace(/-/g, ""));
    
    if (isRut) {
        // Formatear como RUT
        const clean = String(rut)
            .replace(/\./g, "")
            .replace(/-/g, "")
            .toUpperCase();
        const body = clean.slice(0, -1);
        const dv = clean.slice(-1);
        const withDots = body.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        return `${withDots}-${dv}`;
    } else {
        // Para pasaporte u otros documentos, mostrar en uppercase
        return String(rut).toUpperCase();
    }
};

// Computed property para formatear los programas al formato que espera ProgramsGrid
const formattedPrograms = computed(() => {
    if (!props.participantPrograms || props.participantPrograms.length === 0) {
        return {
            data: [],
            current_page: 1,
            total: 0,
            per_page: 6,
        };
    }

    return {
        data: props.participantPrograms.map((program) => {
            const totalForParticipant =
                program.participant_total_due ??
                program.totalAmount ??
                program.trip_price ??
                0;
            const paid = program.paidAmount ?? program.paid_amount ?? 0;
            const percentage =
                totalForParticipant > 0
                    ? Math.round((paid / totalForParticipant) * 10000) / 100
                    : 0;

            return {
                ...program,
                // Asegurar que la relación course con institution esté disponible
                course: program.course || null,
                // Agregar campos calculados para compatibilidad con el card
                price: totalForParticipant,
                totalAmount: totalForParticipant,
                paidAmount: paid,
                paymentPercentage: percentage,
                duration: calculateDuration(program.departure_date),
                participants: program.participants ?? 0,
            };
        }),
        current_page: 1,
        total: props.participantPrograms.length,
        per_page: 6,
    };
});

const calculateDuration = (departureDate) => {
    if (!departureDate) return 0;

    const today = new Date();
    const departure = new Date(departureDate);
    const diffTime = departure.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    // Por ahora retornar un valor fijo, se puede calcular basado en la lógica del negocio
    return 7; // 7 días por defecto
};

const getFullName = (participant) => {
    const firstName = participant.first_name ? participant.first_name.charAt(0).toUpperCase() + participant.first_name.slice(1) : '';
    const secondName = participant.second_name ? participant.second_name.charAt(0).toUpperCase() + participant.second_name.slice(1) : '';
    const firstLastName = participant.first_last_name ? participant.first_last_name.charAt(0).toUpperCase() + participant.first_last_name.slice(1) : '';
    const secondLastName = participant.second_last_name ? participant.second_last_name.charAt(0).toUpperCase() + participant.second_last_name.slice(1) : '';

    return `${firstLastName} ${secondLastName} ${firstName} ${secondName}`;
};

// Modal state
const showEditModal = ref(false);
const showMedicalModal = ref(false);
const showEmergencyContactsModal = ref(false);

// Modal functions
const openEditModal = () => {
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
};

const openMedicalModal = () => {
    showMedicalModal.value = true;
};

const closeMedicalModal = () => {
    showMedicalModal.value = false;
};

const openEmergencyContactsModal = () => {
    showEmergencyContactsModal.value = true;
};

const closeEmergencyContactsModal = () => {
    showEmergencyContactsModal.value = false;
};

const confirmDeleteParticipant = () => {
    if (confirm(`¿Estás seguro de que quieres desactivar al participante "${getFullName(participant)}"?\n\nEsta acción desactivará al participante pero mantendrá todos sus registros asociados.`)) {
        router.delete(route('admin.participants.destroy', participant.id), {
            onSuccess: () => {
                // Redirigir al listado de participantes
                router.visit(route('admin.participants.index'));
            },
            onError: (errors) => {
                console.error('Error al eliminar participante:', errors);
                alert('Error al eliminar el participante. Por favor, inténtalo de nuevo.');
            }
        });
    }
};

// Auto-cerrar mensajes flash después de 5 segundos
watch(() => props.flash, (newFlash) => {
    if (newFlash && (newFlash.success || newFlash.error)) {
        setTimeout(() => {
            if (newFlash.success) {
                newFlash.success = null;
            }
            if (newFlash.error) {
                newFlash.error = null;
            }
        }, 5000);
    }
}, { deep: true, immediate: true });
</script>

<style scoped>
.text-turquesa {
    color: #007e93;
}

.bg-turquesa {
    background-color: #007e93;
}

.bg-turquesa-dark {
    background-color: #006b7d;
}

.bg-azul-oscuro {
    background-color: #1a4b75;
}

.bg-rojo {
    background-color: #d54a42;
}

.border-rojo {
    border-color: #d54b44;
}

.font-nexa-bold {
    font-family: "Nexa-Bold", sans-serif;
}
</style>
