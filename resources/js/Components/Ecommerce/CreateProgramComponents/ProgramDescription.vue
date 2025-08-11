<template>
    <div class="creacion-de-viaje">
        <div class="program-form-container">
            <div class="program-form-header">
                <div class="descripcion-del-programa">
                    Descripción del Programa
                </div>

                <!-- Acordeón 1: Detalle del programa -->
                <div class="accordion-section">
                    <div
                        class="accordion-header"
                        @click="detailsOpen = !detailsOpen"
                    >
                        <div class="accordion-title">Detalle del programa</div>
                        <svg
                            class="accordion-arrow"
                            :class="{ rotated: detailsOpen }"
                            xmlns="http://www.w3.org/2000/svg"
                            width="26"
                            height="14"
                            viewBox="0 0 26 14"
                            fill="none"
                        >
                            <g clip-path="url(#clip0_833_13975)">
                                <path
                                    d="M19.0873 3.27314L20.2008 4.38775L14.1319 10.4588C14.0347 10.5566 13.919 10.6343 13.7917 10.6873C13.6643 10.7403 13.5277 10.7676 13.3897 10.7676C13.2518 10.7676 13.1152 10.7403 12.9878 10.6873C12.8604 10.6343 12.7448 10.5566 12.6475 10.4588L6.57544 4.38775L7.689 3.27419L13.3881 8.97227L19.0873 3.27314Z"
                                    fill="#007E93"
                                />
                            </g>
                            <defs>
                                <clipPath id="clip0_833_13975">
                                    <rect
                                        width="12.6173"
                                        height="25.2128"
                                        fill="white"
                                        transform="translate(26 0.691406) rotate(90)"
                                    />
                                </clipPath>
                            </defs>
                        </svg>
                    </div>
                    <transition name="accordion-slide">
                        <div v-if="detailsOpen" class="accordion-content">
                            <div class="name-field-row">
                                <div class="field-container">
                                    <div class="field-wrapper">
                                        <div class="nombre-del-programa">
                                            Nombre del programa *
                                        </div>
                                        <input
                                            type="text"
                                            v-model="formData.name"
                                            placeholder="Nombre"
                                            class="input-text"
                                            :class="{
                                                'border-red-500': errors.name,
                                            }"
                                        />
                                        <span
                                            v-if="errors.name"
                                            class="text-red-500 text-sm mt-1"
                                        >
                                            {{ errors.name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="destination-date-row">
                                <div class="field-container">
                                    <div class="field-wrapper">
                                        <div class="destino">Destino *</div>
                                        <input
                                            type="text"
                                            v-model="formData.destination"
                                            placeholder="Libertad 22 - Santiago"
                                            class="input-text"
                                            :class="{
                                                'border-red-500':
                                                    errors.destination,
                                            }"
                                        />
                                        <span
                                            v-if="errors.destination"
                                            class="text-red-500 text-sm mt-1"
                                        >
                                            {{ errors.destination }}
                                        </span>
                                    </div>
                                </div>
                                <div class="field-container">
                                    <div class="field-wrapper">
                                        <div class="fecha-de-salida">
                                            Fecha de salida *
                                        </div>
                                        <input
                                            type="date"
                                            v-model="formData.departure_date"
                                            placeholder="00/00/0000"
                                            class="input-text"
                                            :class="{
                                                'border-red-500':
                                                    errors.departure_date,
                                            }"
                                        />
                                        <span
                                            v-if="errors.departure_date"
                                            class="text-red-500 text-sm mt-1"
                                        >
                                            {{ errors.departure_date }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="description-field-row">
                                <div class="field-wrapper">
                                    <div class="descripcion-del-viaje">
                                        Descripción del viaje
                                    </div>
                                    <div
                                        class="wysiwyg-wrapper"
                                        :class="{ 'has-error': errors.description }"
                                    >
                                        <QuillEditor
                                            v-model:content="formData.description"
                                            contentType="html"
                                            theme="snow"
                                            :toolbar="editorToolbar"
                                            placeholder="Escriba aquí la descripción del viaje."
                                        />
                                    </div>
                                    <span
                                        v-if="errors.description"
                                        class="text-red-500 text-sm mt-1"
                                    >
                                        {{ errors.description }}
                                    </span>
                                </div>
                            </div>
                            <div class="images-section-row">
                                <div class="images-section-wrapper">
                                    <div class="images-header">
                                        <div class="imagenes">Imagenes *</div>
                                        <div class="maximo-2-mb-por-foto">
                                            Maximo 2MB por foto
                                        </div>
                                    </div>

                                    <!-- Input de imágenes mejorado -->
                                    <label
                                        class="image-upload-area"
                                        :class="{
                                            'border-red-500': errors.images,
                                        }"
                                        for="program-images"
                                    >
                                        <div class="upload-placeholder">
                                            <svg
                                                class="upload-icon"
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="24"
                                                height="24"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                            >
                                                <path
                                                    d="M12 15V3M12 3L8 7M12 3L16 7"
                                                    stroke="#007E93"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                />
                                                <path
                                                    d="M21 15V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V15"
                                                    stroke="#007E93"
                                                    stroke-width="2"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                />
                                            </svg>
                                            <div class="upload-text">
                                                <div class="upload-main-text">
                                                    Adjunta las imágenes que
                                                    quieras mostrar en el
                                                    programa
                                                </div>
                                                <div class="upload-sub-text">
                                                    PNG, JPG hasta 2MB cada una
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                    <span
                                        v-if="errors.images"
                                        class="text-red-500 text-sm mt-1 block"
                                    >
                                        {{ errors.images }}
                                    </span>
                                    <input
                                        type="file"
                                        id="program-images"
                                        multiple
                                        accept="image/*"
                                        style="display: none"
                                        @change="handleImageUpload"
                                    />

                                    <!-- Preview de imágenes seleccionadas -->
                                    <div
                                        v-if="selectedImages.length > 0"
                                        class="images-preview"
                                    >
                                        <div
                                            v-for="(
                                                image, index
                                            ) in selectedImages"
                                            :key="index"
                                            class="image-preview-item"
                                        >
                                            <img
                                                :src="image.url"
                                                :alt="image.name"
                                                class="preview-image"
                                            />
                                            <div class="image-info">
                                                <div class="image-name">
                                                    {{ image.name }}
                                                </div>
                                                <div class="image-size">
                                                    {{
                                                        image.size 
                                                            ? formatFileSize(image.size)
                                                            : 'Imagen existente'
                                                    }}
                                                </div>
                                            </div>
                                            <button
                                                type="button"
                                                @click="removeImage(index)"
                                                class="remove-image-btn"
                                            >
                                                <svg
                                                    width="16"
                                                    height="16"
                                                    viewBox="0 0 16 16"
                                                    fill="none"
                                                >
                                                    <path
                                                        d="M12 4L4 12M4 4L12 12"
                                                        stroke="#666"
                                                        stroke-width="1.5"
                                                        stroke-linecap="round"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>

                <!-- Separador -->
                <AccordionSeparator />

                <!-- Acordeón 2: Pilares -->
                <div class="accordion-section">
                    <div
                        class="accordion-header"
                        @click="pillarsOpen = !pillarsOpen"
                    >
                        <div class="accordion-title">Pilares</div>
                        <svg
                            class="accordion-arrow"
                            :class="{ rotated: pillarsOpen }"
                            xmlns="http://www.w3.org/2000/svg"
                            width="26"
                            height="14"
                            viewBox="0 0 26 14"
                            fill="none"
                        >
                            <g clip-path="url(#clip0_833_13975_2)">
                                <path
                                    d="M19.0873 3.27314L20.2008 4.38775L14.1319 10.4588C14.0347 10.5566 13.919 10.6343 13.7917 10.6873C13.6643 10.7403 13.5277 10.7676 13.3897 10.7676C13.2518 10.7676 13.1152 10.7403 12.9878 10.6873C12.8604 10.6343 12.7448 10.5566 12.6475 10.4588L6.57544 4.38775L7.689 3.27419L13.3881 8.97227L19.0873 3.27314Z"
                                    fill="#007E93"
                                />
                            </g>
                            <defs>
                                <clipPath id="clip0_833_13975_2">
                                    <rect
                                        width="12.6173"
                                        height="25.2128"
                                        fill="white"
                                        transform="translate(26 0.691406) rotate(90)"
                                    />
                                </clipPath>
                            </defs>
                        </svg>
                    </div>
                    <transition name="accordion-slide">
                        <div v-if="pillarsOpen" class="accordion-content">
                            <div class="pillars-description">
                                En el desarrollo de nuestros programas
                                incorporamos de manera transversal 4 pilares
                                fundamentales:
                            </div>
                            <div class="pillars-grid">
                                <div class="pillars-left-column">
                                    <div class="pillar-item">
                                        <div class="pillar-number">1</div>
                                        <input
                                            type="text"
                                            v-model="formData.pilar_1"
                                            placeholder="Aventura"
                                            class="input-text4"
                                            disabled
                                        />
                                    </div>
                                    <div class="pillar-item">
                                        <div class="pillar-number">2</div>
                                        <input
                                            type="text"
                                            v-model="formData.pilar_2"
                                            placeholder="Entretenimiento"
                                            class="input-text4"
                                            disabled
                                        />
                                    </div>
                                </div>
                                <div class="pillars-right-column">
                                    <div class="pillar-item">
                                        <div class="pillar-number">3</div>
                                        <input
                                            type="text"
                                            v-model="formData.pilar_3"
                                            placeholder="Educación"
                                            class="input-text4"
                                            disabled
                                        />
                                    </div>
                                    <div class="pillar-item">
                                        <div class="pillar-number">4</div>
                                        <input
                                            type="text"
                                            v-model="formData.pilar_4"
                                            placeholder="Seguridad"
                                            class="input-text4"
                                            disabled
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>

                <!-- Separador -->
                <AccordionSeparator />

                <!-- Acordeón 3: Que vamos a hacer -->
                <div class="accordion-section">
                    <div
                        class="accordion-header"
                        @click="itineraryOpen = !itineraryOpen"
                    >
                        <div class="accordion-title">Que vamos a hacer</div>
                        <svg
                            class="accordion-arrow"
                            :class="{ rotated: itineraryOpen }"
                            xmlns="http://www.w3.org/2000/svg"
                            width="26"
                            height="14"
                            viewBox="0 0 26 14"
                            fill="none"
                        >
                            <g clip-path="url(#clip0_833_13975_3)">
                                <path
                                    d="M19.0873 3.27314L20.2008 4.38775L14.1319 10.4588C14.0347 10.5566 13.919 10.6343 13.7917 10.6873C13.6643 10.7403 13.5277 10.7676 13.3897 10.7676C13.2518 10.7676 13.1152 10.7403 12.9878 10.6873C12.8604 10.6343 12.7448 10.5566 12.6475 10.4588L6.57544 4.38775L7.689 3.27419L13.3881 8.97227L19.0873 3.27314Z"
                                    fill="#007E93"
                                />
                            </g>
                            <defs>
                                <clipPath id="clip0_833_13975_3">
                                    <rect
                                        width="12.6173"
                                        height="25.2128"
                                        fill="white"
                                        transform="translate(26 0.691406) rotate(90)"
                                    />
                                </clipPath>
                            </defs>
                        </svg>
                    </div>
                    <transition name="accordion-slide">
                        <div v-if="itineraryOpen" class="accordion-content">
                            <div class="itinerary-description-field">
                                <div class="field-wrapper">
                                    <div class="escribe-descripcion-itinerario">
                                        Escribe una breve descripción del
                                        itinerario.
                                    </div>
                                    <div class="wysiwyg-wrapper" :class="{ 'has-error': errors.itinerary }">
                                        <QuillEditor
                                            v-model:content="formData.itinerary"
                                            contentType="html"
                                            theme="snow"
                                            :toolbar="editorToolbar"
                                            placeholder="Escriba aquí la descripción del itinerario."
                                        />
                                    </div>
                                </div>
                            </div>
                            <div class="pdf-upload-grid">
                                <div class="pdf-upload-item">
                                    <div class="itinerario">Itinerario</div>

                                    <!-- Archivo existente (solo en modo edit) -->
                                    <div
                                        v-if="
                                            mode === 'edit' &&
                                            existingFiles.itinerary_file
                                        "
                                        class="existing-file"
                                    >
                                        <a
                                            :href="existingFiles.itinerary_file"
                                            target="_blank"
                                            class="existing-file-link"
                                        >
                                            📄 Ver archivo actual
                                        </a>
                                        <button
                                            type="button"
                                            @click="
                                                removeExistingFile('itinerary')
                                            "
                                            class="remove-existing-file"
                                        >
                                            ✕
                                        </button>
                                    </div>

                                    <label
                                        class="primary-button"
                                        for="itinerary-file"
                                    >
                                        <div class="button-text">
                                            {{
                                                mode === "edit" &&
                                                existingFiles.itinerary_file
                                                    ? "Cambiar PDF"
                                                    : "Adjunte aqui el PDF"
                                            }}
                                        </div>
                                        <svg
                                            class="paperclip-icon"
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="21"
                                            height="20"
                                            viewBox="0 0 21 20"
                                            fill="none"
                                        >
                                            <path
                                                d="M3.87061 8.52025L8.6531 3.91191C13.1773 -0.452253 20.5248 6.28108 15.8164 10.8236L9.88977 16.5419C6.81977 19.5036 1.83394 14.9344 5.02894 11.8519L10.8706 6.21608C12.4864 4.65775 15.1098 7.06191 13.4289 8.68441L8.57644 13.3336"
                                                stroke="#C7C7C7"
                                                stroke-width="1.25"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    </label>
                                    <input
                                        type="file"
                                        id="itinerary-file"
                                        accept=".pdf"
                                        style="display: none"
                                        @change="
                                            handleFileUpload(
                                                'itinerary',
                                                $event
                                            )
                                        "
                                    />

                                    <!-- Preview del archivo PDF seleccionado -->
                                    <div
                                        v-if="formData.itinerary_file"
                                        class="pdf-preview"
                                    >
                                        <div class="pdf-preview-item">
                                            <div class="pdf-icon">📄</div>
                                            <div class="pdf-info">
                                                <div class="pdf-name">
                                                    {{
                                                        formData.itinerary_file
                                                            .name
                                                    }}
                                                </div>
                                                <div class="pdf-size">
                                                    {{
                                                        formatFileSize(
                                                            formData
                                                                .itinerary_file
                                                                .size
                                                        )
                                                    }}
                                                </div>
                                            </div>
                                            <button
                                                type="button"
                                                @click="
                                                    removePdfFile('itinerary')
                                                "
                                                class="remove-pdf-btn"
                                            >
                                                <svg
                                                    width="16"
                                                    height="16"
                                                    viewBox="0 0 16 16"
                                                    fill="none"
                                                >
                                                    <path
                                                        d="M12 4L4 12M4 4L12 12"
                                                        stroke="#666"
                                                        stroke-width="1.5"
                                                        stroke-linecap="round"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="pdf-upload-item">
                                    <div
                                        class="cobertura-de-asistencia-en-viaje"
                                    >
                                        Cobertura de asistencia en viaje
                                    </div>

                                    <!-- Archivo existente (solo en modo edit) -->
                                    <div
                                        v-if="
                                            mode === 'edit' &&
                                            existingFiles.coverage_file
                                        "
                                        class="existing-file"
                                    >
                                        <a
                                            :href="existingFiles.coverage_file"
                                            target="_blank"
                                            class="existing-file-link"
                                        >
                                            📄 Ver archivo actual
                                        </a>
                                        <button
                                            type="button"
                                            @click="
                                                removeExistingFile('coverage')
                                            "
                                            class="remove-existing-file"
                                        >
                                            ✕
                                        </button>
                                    </div>

                                    <label
                                        class="primary-button"
                                        for="coverage-file"
                                    >
                                        <div class="button-text">
                                            {{
                                                mode === "edit" &&
                                                existingFiles.coverage_file
                                                    ? "Cambiar PDF"
                                                    : "Adjunte aqui el PDF"
                                            }}
                                        </div>
                                        <svg
                                            class="paperclip-icon"
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="21"
                                            height="20"
                                            viewBox="0 0 21 20"
                                            fill="none"
                                        >
                                            <path
                                                d="M3.87061 8.52025L8.6531 3.91191C13.1773 -0.452253 20.5248 6.28108 15.8164 10.8236L9.88977 16.5419C6.81977 19.5036 1.83394 14.9344 5.02894 11.8519L10.8706 6.21608C12.4864 4.65775 15.1098 7.06191 13.4289 8.68441L8.57644 13.3336"
                                                stroke="#C7C7C7"
                                                stroke-width="1.25"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    </label>
                                    <input
                                        type="file"
                                        id="coverage-file"
                                        accept=".pdf"
                                        style="display: none"
                                        @change="
                                            handleFileUpload('coverage', $event)
                                        "
                                    />

                                    <!-- Preview del archivo PDF seleccionado -->
                                    <div
                                        v-if="formData.coverage_file"
                                        class="pdf-preview"
                                    >
                                        <div class="pdf-preview-item">
                                            <div class="pdf-icon">📄</div>
                                            <div class="pdf-info">
                                                <div class="pdf-name">
                                                    {{
                                                        formData.coverage_file
                                                            .name
                                                    }}
                                                </div>
                                                <div class="pdf-size">
                                                    {{
                                                        formatFileSize(
                                                            formData
                                                                .coverage_file
                                                                .size
                                                        )
                                                    }}
                                                </div>
                                            </div>
                                            <button
                                                type="button"
                                                @click="
                                                    removePdfFile('coverage')
                                                "
                                                class="remove-pdf-btn"
                                            >
                                                <svg
                                                    width="16"
                                                    height="16"
                                                    viewBox="0 0 16 16"
                                                    fill="none"
                                                >
                                                    <path
                                                        d="M12 4L4 12M4 4L12 12"
                                                        stroke="#666"
                                                        stroke-width="1.5"
                                                        stroke-linecap="round"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="pdf-upload-item">
                                    <div class="lista-de-equipo">
                                        Lista de equipo
                                    </div>

                                    <!-- Archivo existente (solo en modo edit) -->
                                    <div
                                        v-if="
                                            mode === 'edit' &&
                                            existingFiles.equipment_file
                                        "
                                        class="existing-file"
                                    >
                                        <a
                                            :href="existingFiles.equipment_file"
                                            target="_blank"
                                            class="existing-file-link"
                                        >
                                            📄 Ver archivo actual
                                        </a>
                                        <button
                                            type="button"
                                            @click="
                                                removeExistingFile('equipment')
                                            "
                                            class="remove-existing-file"
                                        >
                                            ✕
                                        </button>
                                    </div>

                                    <label
                                        class="primary-button"
                                        for="equipment-file"
                                    >
                                        <div class="button-text">
                                            {{
                                                mode === "edit" &&
                                                existingFiles.equipment_file
                                                    ? "Cambiar PDF"
                                                    : "Adjunte aqui el PDF"
                                            }}
                                        </div>
                                        <svg
                                            class="paperclip-icon"
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="21"
                                            height="20"
                                            viewBox="0 0 21 20"
                                            fill="none"
                                        >
                                            <path
                                                d="M3.87061 8.52025L8.6531 3.91191C13.1773 -0.452253 20.5248 6.28108 15.8164 10.8236L9.88977 16.5419C6.81977 19.5036 1.83394 14.9344 5.02894 11.8519L10.8706 6.21608C12.4864 4.65775 15.1098 7.06191 13.4289 8.68441L8.57644 13.3336"
                                                stroke="#C7C7C7"
                                                stroke-width="1.25"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            />
                                        </svg>
                                    </label>
                                    <input
                                        type="file"
                                        id="equipment-file"
                                        accept=".pdf"
                                        style="display: none"
                                        @change="
                                            handleFileUpload(
                                                'equipment',
                                                $event
                                            )
                                        "
                                    />

                                    <!-- Preview del archivo PDF seleccionado -->
                                    <div
                                        v-if="formData.equipment_file"
                                        class="pdf-preview"
                                    >
                                        <div class="pdf-preview-item">
                                            <div class="pdf-icon">📄</div>
                                            <div class="pdf-info">
                                                <div class="pdf-name">
                                                    {{
                                                        formData.equipment_file
                                                            .name
                                                    }}
                                                </div>
                                                <div class="pdf-size">
                                                    {{
                                                        formatFileSize(
                                                            formData
                                                                .equipment_file
                                                                .size
                                                        )
                                                    }}
                                                </div>
                                            </div>
                                            <button
                                                type="button"
                                                @click="
                                                    removePdfFile('equipment')
                                                "
                                                class="remove-pdf-btn"
                                            >
                                                <svg
                                                    width="16"
                                                    height="16"
                                                    viewBox="0 0 16 16"
                                                    fill="none"
                                                >
                                                    <path
                                                        d="M12 4L4 12M4 4L12 12"
                                                        stroke="#666"
                                                        stroke-width="1.5"
                                                        stroke-linecap="round"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, defineEmits, onMounted, nextTick } from "vue";
import { AccordionSeparator } from "@/Components/Icons";
import { QuillEditor } from "@vueup/vue-quill";
import "@vueup/vue-quill/dist/vue-quill.snow.css";

// Props
const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({
            name: "",
            destination: "",
            departure_date: "",
            description: "",
            pilar_1: "",
            pilar_2: "",
            pilar_3: "",
            pilar_4: "",
            itinerary: "",
            itinerary_file: null,
            coverage_file: null,
            equipment_file: null,
        }),
    },
    mode: {
        type: String,
        default: "create",
        validator: (value) => ["create", "edit"].includes(value),
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    existingImages: {
        type: Array,
        default: () => [],
    },
    existingFiles: {
        type: Object,
        default: () => ({
            itinerary_file: null,
            coverage_file: null,
            equipment_file: null,
        }),
    },
});

//

// Emits
const emit = defineEmits([
    "update:modelValue",
    "update:images",
    "remove:existingImage",
    "remove:existingFile",
]);

// Reactive data
const formData = ref({ ...props.modelValue });
const isSyncingFromProps = ref(false);
const editorToolbar = [
    [{ header: [1, 2, 3, false] }],
    ["bold", "italic", "underline", "strike"],
    [{ list: "ordered" }, { list: "bullet" }],
    [{ align: [] }],
    ["link"],
    ["clean"],
];

// Estados para los acordeones
const detailsOpen = ref(true); // Abierto por defecto
const pillarsOpen = ref(false);
const itineraryOpen = ref(false);

// Estado para las imágenes
const selectedImages = ref([]);

// Cargar imágenes existentes en modo edit
if (props.mode === "edit" && props.existingImages.length > 0) {
    selectedImages.value = props.existingImages.map((image, index) => ({
        id: `existing-${index}`,
        file: null,
        url: image.url,
        name: image.name || `Imagen ${index + 1}`,
        isExisting: true,
        originalId: image.id || null,
    }));
}

// Watch para sincronizar con el padre
watch(
    formData,
    (newValue) => {
        if (isSyncingFromProps.value) return;
        emit("update:modelValue", newValue);
    },
    { deep: true }
);

// Sincronizar cambios desde el padre (especialmente en modo edit)
watch(
    () => props.modelValue,
    (newValue) => {
        if (!newValue) return;
        isSyncingFromProps.value = true;
        // Mezclar manteniendo campos locales no relacionados
        formData.value = {
            ...formData.value,
            ...newValue,
        };
        nextTick(() => { isSyncingFromProps.value = false; });
    },
    { deep: true, immediate: true }
);

// Prefijar pilares por defecto en modo creación si están vacíos
onMounted(() => {
    if (props.mode === 'create') {
        const isEmpty = (v) => !v || v.toString().trim() === '';
        if (
            isEmpty(formData.value.pilar_1) &&
            isEmpty(formData.value.pilar_2) &&
            isEmpty(formData.value.pilar_3) &&
            isEmpty(formData.value.pilar_4)
        ) {
            formData.value.pilar_1 = 'Aventura';
            formData.value.pilar_2 = 'Entretenimiento';
            formData.value.pilar_3 = 'Educación';
            formData.value.pilar_4 = 'Seguridad';
        }
    }
});

watch(
    selectedImages,
    (newImages) => {
        emit("update:images", newImages);
    },
    { deep: true }
);

// Función para manejar la carga de archivos PDF
const handleFileUpload = (type, event) => {
    const file = event.target.files[0];
    if (file) {
        // Validar tipo de archivo
        if (file.type !== "application/pdf") {
            alert("Por favor seleccione un archivo PDF válido.");
            event.target.value = "";
            return;
        }

        // Validar tamaño (10MB = 10 * 1024 * 1024 bytes)
        if (file.size > 10 * 1024 * 1024) {
            alert(
                `${file.name} es demasiado grande. El tamaño máximo es 10MB.`
            );
            event.target.value = "";
            return;
        }

        formData.value[`${type}_file`] = file;
    } else {
        event.target.value = "";
    }
};

// Función para manejar la carga de imágenes
const handleImageUpload = (event) => {
    const files = Array.from(event.target.files);

    files.forEach((file) => {
        // Validar tipo de archivo
        if (!file.type.startsWith("image/")) {
            alert(`${file.name} no es una imagen válida.`);
            return;
        }

        // Validar tamaño (2MB = 2 * 1024 * 1024 bytes)
        if (file.size > 2 * 1024 * 1024) {
            alert(`${file.name} es demasiado grande. El tamaño máximo es 2MB.`);
            return;
        }

        // Crear URL para preview
        const reader = new FileReader();
        reader.onload = (e) => {
            selectedImages.value.push({
                file: file,
                url: e.target.result,
                name: file.name,
                size: file.size,
            });
        };
        reader.readAsDataURL(file);
    });

    // Limpiar el input para permitir seleccionar los mismos archivos de nuevo
    event.target.value = "";
};

// Función para remover una imagen
const removeImage = (index) => {
    const imageToRemove = selectedImages.value[index];

    if (imageToRemove.isExisting) {
        // En modo edit, emitir evento para marcar imagen como eliminada
        emit("remove:existingImage", imageToRemove.originalId);
    }

    selectedImages.value.splice(index, 1);
    // Emitir imágenes actualizadas al padre
    emit("update:images", selectedImages.value);
};

// Función para eliminar archivo existente
const removeExistingFile = (fileType) => {
    emit("remove:existingFile", fileType);
};

// Función para remover un archivo PDF seleccionado
const removePdfFile = (fileType) => {
    formData.value[`${fileType}_file`] = null;
};

// Función para formatear el tamaño del archivo
const formatFileSize = (bytes) => {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
};
</script>

<style scoped>
.creacion-de-viaje,
.creacion-de-viaje * {
    box-sizing: border-box;
}
.creacion-de-viaje {
    background: var(--colores-neutro-blanco, #ffffff);
    border-radius: 20px;
    padding: 30px;
    display: flex;
    flex-direction: row;
    gap: 10px;
    align-items: center;
    justify-content: flex-start;
    position: relative;
    box-shadow: var(
        --sombra-box-shadow,
        0px 4px 11.6px 0px rgba(163, 163, 163, 0.11)
    );
    overflow: hidden;
}

/* Program Form Container */
.program-form-container {
    display: flex;
    flex-direction: column;
    gap: 28px;
    align-items: flex-start;
    justify-content: flex-start;
    align-self: stretch;
    flex-shrink: 0;
    width: 100%;
    position: relative;
}

/* Program Form Header */
.program-form-header {
    display: flex;
    flex-direction: column;
    gap: 19px;
    align-items: flex-start;
    justify-content: flex-start;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
}

/* Accordion Styles */
.accordion-section {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: flex-start;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
    margin-bottom: 30px;
    z-index: 1;
}

.accordion-header {
    padding: 12px 0px 12px 0px;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
    cursor: pointer;
    transition: all 0.2s ease;
}

.accordion-header:hover {
    background-color: rgba(0, 126, 147, 0.05);
    border-radius: 8px;
    padding-left: 16px;
    padding-right: 16px;
}

.accordion-title {
    color: var(--Colores-OP2-Turquesa, #007e93);
    font-family: Nexa;
    font-size: var(--Numeros-Cuerpo-de-texto-XL, 18px);
    font-style: normal;
    font-weight: 700;
    line-height: 22px; /* 122.222% */
    position: relative;
    display: flex;
    align-items: center;
    justify-content: flex-start;
}

.accordion-content {
    display: flex;
    flex-direction: column;
    gap: 23px;
    align-items: center;
    justify-content: center;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
    padding: 20px 0;
}

/* Transiciones de acordeón mejoradas */
.accordion-slide-enter-active,
.accordion-slide-leave-active {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    transform-origin: top;
    overflow: visible;
}

.accordion-slide-enter-from {
    opacity: 0;
    max-height: 0;
    transform: translateY(-20px) scaleY(0.8);
    padding-top: 0;
    padding-bottom: 0;
}

.accordion-slide-enter-to {
    opacity: 1;
    max-height: 5000px;
    transform: translateY(0) scaleY(1);
    padding-top: 20px;
    padding-bottom: 30px;
}

.accordion-slide-leave-from {
    opacity: 1;
    max-height: 5000px;
    transform: translateY(0) scaleY(1);
    padding-top: 20px;
    padding-bottom: 30px;
}

.accordion-slide-leave-to {
    opacity: 0;
    max-height: 0;
    transform: translateY(-20px) scaleY(0.8);
    padding-top: 0;
    padding-bottom: 0;
}

.accordion-arrow {
    flex-shrink: 0;
    width: 18px;
    height: 32px;
    position: relative;
    overflow: visible;
    transition: transform 0.3s ease;
    transform: rotate(0deg);
}

.accordion-arrow.rotated {
    transform: rotate(180deg);
}

.descripcion-del-programa {
    color: var(--colores-op2-turquesa, #007e93);
    text-align: left;
    font-family: var(--subtitle-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--subtitle-font-size, 24px);
    line-height: var(--subtitle-line-height, 28px);
    font-weight: var(--subtitle-font-weight, 700);
    position: relative;
    display: flex;
    align-items: flex-end;
    justify-content: flex-start;
}

/* Name Field Row */
.name-field-row {
    display: flex;
    flex-direction: row;
    gap: 12px;
    align-items: flex-start;
    justify-content: flex-start;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
}

.field-container {
    display: flex;
    flex-direction: row;
    gap: 18px;
    align-items: flex-end;
    justify-content: center;
    flex: 1;
    position: relative;
}

.field-wrapper {
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: flex-start;
    justify-content: flex-start;
    flex: 1;
    position: relative;
}

.nombre-del-programa {
    color: var(--colores-neutro-gris-4, #5b5b5b);
    text-align: left;
    font-family: var(--cuerpo-de-texto-s-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--cuerpo-de-texto-s-font-size, 12px);
    line-height: var(--cuerpo-de-texto-s-line-height, 13px);
    font-weight: var(--cuerpo-de-texto-s-font-weight, 700);
    position: relative;
    align-self: stretch;
}

.input-text {
    background: #ffffff;
    border-radius: 8px;
    border-style: solid;
    border-color: var(--colores-neutro-gris-4, #5b5b5b);
    border-width: 1px;
    padding: 8px 16px 8px 16px;
    align-self: stretch;
    flex-shrink: 0;
    height: 46px;
    position: relative;
    color: var(--colores-op2-turquesa, #007e93);
    text-align: left;
    font-family: var(--cuerpo-de-texto-m-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--cuerpo-de-texto-m-font-size, 12px);
    line-height: var(--cuerpo-de-texto-m-line-height, 18px);
    font-weight: var(--cuerpo-de-texto-m-font-weight, 700);
    outline: none;
    width: 100%;
}

.input-text::placeholder {
    color: var(--colores-neutro-gris-3, #c7c7c7);
}

.input-text:focus {
    border-color: var(--colores-op2-turquesa, #007e93);
    color: var(--colores-op2-turquesa, #007e93);
}

/* Destination Date Row */
.destination-date-row {
    display: flex;
    flex-direction: row;
    gap: 12px;
    align-items: flex-start;
    justify-content: flex-start;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
}

.destino {
    color: var(--colores-neutro-gris-4, #5b5b5b);
    text-align: left;
    font-family: var(--cuerpo-de-texto-s-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--cuerpo-de-texto-s-font-size, 12px);
    line-height: var(--cuerpo-de-texto-s-line-height, 13px);
    font-weight: var(--cuerpo-de-texto-s-font-weight, 700);
    position: relative;
    align-self: stretch;
}

.fecha-de-salida {
    color: var(--colores-neutro-gris-4, #5b5b5b);
    text-align: left;
    font-family: var(--cuerpo-de-texto-s-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--cuerpo-de-texto-s-font-size, 12px);
    line-height: var(--cuerpo-de-texto-s-line-height, 13px);
    font-weight: var(--cuerpo-de-texto-s-font-weight, 700);
    position: relative;
    align-self: stretch;
}

/* Description Field Row */
.description-field-row {
    display: flex;
    flex-direction: row;
    gap: 18px;
    align-items: flex-end;
    justify-content: center;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
}

.descripcion-del-viaje {
    color: var(--colores-neutro-gris-4, #5b5b5b);
    text-align: left;
    font-family: var(--cuerpo-de-texto-s-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--cuerpo-de-texto-s-font-size, 12px);
    line-height: var(--cuerpo-de-texto-s-line-height, 13px);
    font-weight: var(--cuerpo-de-texto-s-font-weight, 700);
    position: relative;
    align-self: stretch;
}

.input-text2 {
    background: #ffffff;
    border-radius: 8px;
    border-style: solid;
    border-color: var(--colores-neutro-gris-4, #5b5b5b);
    border-width: 1px;
    padding: 8px 16px 8px 16px;
    flex-shrink: 0;
    width: 611px;
    height: 134px;
    position: relative;
    color: var(--colores-op2-turquesa, #007e93);
    text-align: left;
    font-family: var(--cuerpo-de-texto-m-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--cuerpo-de-texto-m-font-size, 12px);
    line-height: var(--cuerpo-de-texto-m-line-height, 18px);
    font-weight: var(--cuerpo-de-texto-m-font-weight, 700);
    resize: none;
    outline: none;
}

.input-text2::placeholder {
    color: var(--colores-neutro-gris-3, #c7c7c7);
}

.input-text2:focus {
    border-color: var(--colores-op2-turquesa, #007e93);
    color: var(--colores-op2-turquesa, #007e93);
}

/* Images Section Row */
.images-section-row {
    display: flex;
    flex-direction: row;
    gap: 15px;
    align-items: flex-end;
    justify-content: center;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
}

.images-section-wrapper {
    display: flex;
    flex-direction: column;
    gap: 15px;
    align-items: flex-start;
    justify-content: flex-start;
    flex: 1;
    min-height: 120px;
    position: relative;
    width: 100%;
}

.images-header {
    display: flex;
    flex-direction: row;
    align-items: flex-start;
    justify-content: space-between;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
}

.imagenes {
    color: var(--colores-neutro-gris-4, #5b5b5b);
    text-align: left;
    font-family: var(--cuerpo-de-texto-s-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--cuerpo-de-texto-s-font-size, 12px);
    line-height: var(--cuerpo-de-texto-s-line-height, 13px);
    font-weight: var(--cuerpo-de-texto-s-font-weight, 700);
    position: relative;
}

.maximo-2-mb-por-foto {
    color: var(--colores-neutro-gris-4, #5b5b5b);
    text-align: left;
    font-family: var(--cuerpo-de-texto-s-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--cuerpo-de-texto-s-font-size, 12px);
    line-height: var(--cuerpo-de-texto-s-line-height, 13px);
    font-weight: var(--cuerpo-de-texto-s-font-weight, 700);
    position: relative;
}

/* Image Upload Styles */
.image-upload-area {
    background: #ffffff;
    border-radius: 8px;
    border-style: dashed;
    border-color: var(--colores-neutro-gris-4, #5b5b5b);
    border-width: 1px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    align-self: stretch;
    flex-shrink: 0;
    min-height: 120px;
    width: 100%;
    position: relative;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-bottom: 10px;
}

.image-upload-area:hover {
    border-color: var(--colores-op2-turquesa, #007e93);
    background-color: rgba(0, 126, 147, 0.02);
}

.upload-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.upload-icon {
    flex-shrink: 0;
}

.upload-text {
    text-align: center;
}

.upload-main-text {
    color: var(--colores-neutro-gris-4, #5b5b5b);
    font-family: var(--cuerpo-de-texto-m-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--cuerpo-de-texto-m-font-size, 14px);
    line-height: var(--cuerpo-de-texto-m-line-height, 18px);
    font-weight: var(--cuerpo-de-texto-m-font-weight, 700);
    margin-bottom: 4px;
}

.upload-sub-text {
    color: var(--colores-neutro-gris-3, #c7c7c7);
    font-family: var(
        --cuerpo-de-texto-s-font-family,
        "Nexa-Regular",
        sans-serif
    );
    font-size: var(--cuerpo-de-texto-s-font-size, 12px);
    line-height: var(--cuerpo-de-texto-s-line-height, 16px);
    font-weight: var(--cuerpo-de-texto-s-font-weight, 400);
}

/* Image Preview Styles */
.images-preview {
    margin-top: 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    width: 100%;
    position: relative;
    z-index: 1;
}

.image-preview-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.preview-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
    flex-shrink: 0;
}

.image-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.image-name {
    color: var(--colores-neutro-gris-4, #5b5b5b);
    font-family: "Nexa-Bold", sans-serif;
    font-size: 14px;
    font-weight: 700;
    line-height: 1.2;
    word-break: break-word;
}

.image-size {
    color: var(--colores-neutro-gris-3, #c7c7c7);
    font-family: "Nexa-Regular", sans-serif;
    font-size: 12px;
    font-weight: 400;
    line-height: 1.2;
}

.remove-image-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    border-radius: 4px;
    transition: background-color 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.remove-image-btn:hover {
    background-color: rgba(255, 0, 0, 0.1);
}

/* Pillars Section Row */

.pillars-description {
    color: var(--colores-neutro-gris-4, #5b5b5b);
    text-align: left;
    font-family: var(
        --cuerpo-de-texto-m-font-family,
        "Nexa-Regular",
        sans-serif
    );
    font-size: var(--cuerpo-de-texto-m-font-size, 14px);
    line-height: var(--cuerpo-de-texto-m-line-height, 18px);
    font-weight: var(--cuerpo-de-texto-m-font-weight, 400);
    position: relative;
    align-self: stretch;
    margin-bottom: 25px;
}

.pillars-grid {
    display: flex;
    flex-direction: row;
    gap: 20px;
    align-items: flex-start;
    justify-content: flex-start;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
}

.pillars-left-column {
    display: flex;
    flex-direction: column;
    gap: 13px;
    align-items: flex-start;
    justify-content: flex-start;
    flex: 1;
    position: relative;
}

.pillar-item {
    display: flex;
    flex-direction: row;
    gap: 7px;
    align-items: center;
    justify-content: flex-start;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
}

.pillar-number {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    position: relative;
    background-color: var(--colores-primario-turquesa, #007e93);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-family: "Nexa-Bold", sans-serif;
    font-size: 16px;
    font-weight: 700;
    line-height: 1;
}

.input-text4 {
    background: #ffffff;
    border-radius: 8px;
    border-style: solid;
    border-color: var(--colores-neutro-gris-4, #5b5b5b);
    border-width: 1px;
    padding: 8px 16px 8px 16px;
    flex: 1;
    height: 46px;
    position: relative;
    color: var(--colores-op2-turquesa, #007e93);
    text-align: left;
    font-family: var(
        --cuerpo-de-texto-l-font-family,
        "Nexa-Regular",
        sans-serif
    );
    font-size: var(--cuerpo-de-texto-l-font-size, 14px);
    line-height: var(--cuerpo-de-texto-l-line-height, 22px);
    font-weight: var(--cuerpo-de-texto-l-font-weight, 400);
    outline: none;
}

.input-text4::placeholder {
    color: var(--colores-neutro-gris-3, #c7c7c7);
}

.input-text4:focus {
    border-color: var(--colores-op2-turquesa, #007e93);
    box-shadow: 0 0 0 2px rgba(0, 126, 147, 0.1);
}

.pillars-right-column {
    display: flex;
    flex-direction: column;
    gap: 13px;
    align-items: flex-start;
    justify-content: flex-start;
    flex: 1;
    position: relative;
}

/* Itinerary Section Row */

.itinerary-description-field {
    display: flex;
    flex-direction: row;
    gap: 18px;
    align-items: flex-end;
    justify-content: center;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
}

.escribe-descripcion-itinerario {
    color: var(--colores-neutro-gris-4, #5b5b5b);
    text-align: left;
    font-family: var(--cuerpo-de-texto-s-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--cuerpo-de-texto-s-font-size, 12px);
    line-height: var(--cuerpo-de-texto-s-line-height, 13px);
    font-weight: var(--cuerpo-de-texto-s-font-weight, 700);
    position: relative;
    align-self: stretch;
}

.input-text5 {
    background: #ffffff;
    border-radius: 8px;
    border-style: solid;
    border-color: var(--colores-neutro-gris-4, #5b5b5b);
    border-width: 1px;
    padding: 8px 16px 8px 16px;
    align-self: stretch;
    flex-shrink: 0;
    height: 134px;
    position: relative;
    color: var(--colores-op2-turquesa, #007e93);
    text-align: left;
    font-family: var(--cuerpo-de-texto-m-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--cuerpo-de-texto-m-font-size, 12px);
    line-height: var(--cuerpo-de-texto-m-line-height, 18px);
    font-weight: var(--cuerpo-de-texto-m-font-weight, 700);
    resize: none;
    outline: none;
}

.input-text5::placeholder {
    color: var(--colores-neutro-gris-3, #c7c7c7);
}

.input-text5:focus {
    border-color: var(--colores-op2-turquesa, #007e93);
    color: var(--colores-op2-turquesa, #007e93);
}

.pdf-upload-grid {
    display: flex;
    flex-direction: row;
    gap: 20px;
    align-items: flex-start;
    justify-content: flex-start;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
}

.pdf-upload-item {
    display: flex;
    flex-direction: column;
    gap: 7px;
    align-items: center;
    justify-content: center;
    flex: 1;
    position: relative;
}

.itinerario {
    color: var(--colores-neutro-gris-4, #5b5b5b);
    text-align: left;
    font-family: var(--cuerpo-de-texto-s-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--cuerpo-de-texto-s-font-size, 12px);
    line-height: var(--cuerpo-de-texto-s-line-height, 13px);
    font-weight: var(--cuerpo-de-texto-s-font-weight, 700);
    position: relative;
}

.primary-button {
    background: var(--blanco, #fefeff);
    border-radius: 4px;
    border-style: dashed;
    border-color: var(--colores-neutro-gris-4, #5b5b5b);
    border-width: 1px;
    padding: 18px 10px 18px 10px;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    align-self: stretch;
    flex-shrink: 0;
    position: relative;
    cursor: pointer;
    transition: all 0.2s ease;
}

.primary-button:hover {
    border-color: var(--colores-op2-turquesa, #007e93);
    background-color: rgba(0, 126, 147, 0.05);
}

.button-text {
    color: var(--colores-neutro-gris-3, #c7c7c7);
    text-align: center;
    font-family: "Nexa-Regular", sans-serif;
    font-size: 14px;
    line-height: 22px;
    font-weight: 400;
    position: relative;
}

.paperclip-icon {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    position: relative;
    overflow: visible;
    aspect-ratio: 1;
}

.cobertura-de-asistencia-en-viaje {
    color: var(--colores-neutro-gris-4, #5b5b5b);
    text-align: left;
    font-family: var(--cuerpo-de-texto-s-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--cuerpo-de-texto-s-font-size, 11px);
    line-height: var(--cuerpo-de-texto-s-line-height, 13px);
    font-weight: var(--cuerpo-de-texto-s-font-weight, 700);
    position: relative;
}

.lista-de-equipo {
    color: var(--colores-neutro-gris-4, #5b5b5b);
    text-align: left;
    font-family: var(--cuerpo-de-texto-s-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--cuerpo-de-texto-s-font-size, 12px);
    line-height: var(--cuerpo-de-texto-s-line-height, 13px);
    font-weight: var(--cuerpo-de-texto-s-font-weight, 700);
    position: relative;
}

/* Estilos para archivos existentes en modo edit */
.existing-file {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    padding: 8px 12px;
    background-color: #f0f9ff;
    border: 1px solid #0284c7;
    border-radius: 6px;
}

.existing-file-link {
    color: #0284c7;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    flex: 1;
}

.existing-file-link:hover {
    color: #0369a1;
    text-decoration: underline;
}

.remove-existing-file {
    background: none;
    border: none;
    color: #dc2626;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.remove-existing-file:hover {
    background-color: #fee2e2;
}

/* PDF Preview Styles */
.pdf-preview {
    margin-top: 10px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    width: 100%;
    position: relative;
    z-index: 1;
}

.pdf-preview-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.pdf-icon {
    flex-shrink: 0;
    font-size: 20px;
    color: var(--colores-op2-turquesa, #007e93);
}

.pdf-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.pdf-name {
    color: var(--colores-neutro-gris-4, #5b5b5b);
    font-family: "Nexa-Bold", sans-serif;
    font-size: 13px;
    font-weight: 700;
    line-height: 1.2;
    word-break: break-word;
}

.pdf-size {
    color: var(--colores-neutro-gris-3, #c7c7c7);
    font-family: "Nexa-Regular", sans-serif;
    font-size: 11px;
    font-weight: 400;
    line-height: 1.2;
}

.remove-pdf-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: background-color 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.remove-pdf-btn:hover {
    background-color: rgba(255, 0, 0, 0.1);
}

/* WYSIWYG (Quill) Styles */
.wysiwyg-wrapper {
    width: 100%;
}

.wysiwyg-wrapper :deep(.ql-toolbar.ql-snow) {
    border-color: var(--colores-neutro-gris-4, #5b5b5b);
    border-radius: 8px 8px 0 0;
}

.wysiwyg-wrapper :deep(.ql-container.ql-snow) {
    border-color: var(--colores-neutro-gris-4, #5b5b5b);
    border-radius: 0 0 8px 8px;
    min-height: 134px;
}

.wysiwyg-wrapper :deep(.ql-editor) {
    min-height: 100px;
    color: var(--colores-op2-turquesa, #007e93);
    font-family: var(--cuerpo-de-texto-m-font-family, "Nexa-Bold", sans-serif);
    font-size: var(--cuerpo-de-texto-m-font-size, 12px);
    line-height: var(--cuerpo-de-texto-m-line-height, 18px);
}

.wysiwyg-wrapper.has-error :deep(.ql-toolbar.ql-snow),
.wysiwyg-wrapper.has-error :deep(.ql-container.ql-snow) {
    border-color: #ef4444;
}
</style>
