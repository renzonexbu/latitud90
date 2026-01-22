<template>
    <div class="relative">
        <!-- Input de búsqueda -->
        <input
            type="text"
            :placeholder="placeholder"
            :value="searchTerm"
            @input="handleSearch"
            @focus="showDropdown = true"
            @blur="handleBlur"
            :disabled="disabled"
            class="w-full h-[46px] bg-white rounded-lg border border-[#5B5B5B] px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
            :class="{
                'border-[#007E93]': showDropdown && !disabled,
                'opacity-50 cursor-not-allowed bg-gray-100': disabled
            }"
        />
        
        <!-- Dropdown con opciones filtradas -->
        <div
            v-if="showDropdown && filteredOptions.length > 0 && !disabled"
            class="absolute z-50 w-full mt-1 bg-white border border-[#D3D3D3] rounded-lg shadow-lg max-h-48 overflow-y-auto"
        >
            <div
                v-for="option in filteredOptions"
                :key="option.id"
                @mousedown="selectOption(option)"
                class="px-4 py-2 cursor-pointer hover:bg-gray-100 text-left font-nexa text-[12px] leading-[18px]"
                :class="{ 'bg-[#007E93] text-white': isSelected(option) }"
            >
                {{ option.name }}
            </div>
        </div>
        
        <!-- Mensaje cuando no hay resultados -->
        <div
            v-if="showDropdown && searchTerm && filteredOptions.length === 0 && !disabled"
            class="absolute z-50 w-full mt-1 bg-white border border-[#D3D3D3] rounded-lg shadow-lg px-4 py-2 text-left font-nexa text-[12px] leading-[18px] text-gray-500"
        >
            No se encontraron resultados
        </div>
    </div>
</template>

<script>
export default {
    name: 'SearchableSelect',
    props: {
        options: {
            type: Array,
            required: true
        },
        placeholder: {
            type: String,
            default: 'Selecciona una opción'
        },
        value: {
            type: [String, Number],
            default: ''
        },
        searchKey: {
            type: String,
            default: 'name'
        },
        disabled: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            searchTerm: '',
            showDropdown: false,
            selectedOption: null
        }
    },
    computed: {
        filteredOptions() {
            if (!this.searchTerm) {
                return this.options;
            }
            
            return this.options.filter(option => 
                option[this.searchKey].toLowerCase().includes(this.searchTerm.toLowerCase())
            );
        }
    },
    watch: {
        value: {
            immediate: true,
            handler(newValue) {
                if (newValue) {
                    const option = this.options.find(opt => opt.id == newValue);
                    if (option) {
                        this.selectedOption = option;
                        this.searchTerm = option[this.searchKey];
                    }
                } else {
                    this.selectedOption = null;
                    this.searchTerm = '';
                }
            }
        },
        options: {
            immediate: true,
            handler(newOptions) {
                // Si las opciones cambian (como cuando se selecciona una región diferente),
                // verificar si la opción seleccionada aún existe en las nuevas opciones
                if (this.selectedOption) {
                    const stillExists = newOptions.find(opt => opt.id == this.selectedOption.id);
                    if (!stillExists) {
                        // La opción seleccionada ya no existe, limpiar la selección
                        this.selectedOption = null;
                        this.searchTerm = '';
                        this.$emit('input', '');
                    }
                }

                // Auto-seleccionar si solo hay una opción y no hay valor seleccionado
                if (newOptions && newOptions.length === 1 && !this.value && !this.selectedOption) {
                    this.selectedOption = newOptions[0];
                    this.searchTerm = newOptions[0][this.searchKey];
                    this.$emit('input', newOptions[0].id);
                }
            },
            deep: true
        }
    },
    methods: {
        handleSearch(event) {
            if (this.disabled) return;

            this.searchTerm = event.target.value;
            this.showDropdown = true;

            // Si el usuario borra todo, emitir valor vacío
            if (!this.searchTerm) {
                this.$emit('input', '');
                this.selectedOption = null;
            }
        },
        
        selectOption(option) {
            if (this.disabled) return;

            this.selectedOption = option;
            this.searchTerm = option[this.searchKey];
            this.showDropdown = false;
            this.$emit('input', option.id);
        },
        
        handleBlur() {
            // Pequeño delay para permitir que el click en las opciones funcione
            setTimeout(() => {
                this.showDropdown = false;

                // Si hay un término de búsqueda, intentar auto-seleccionar si coincide exactamente
                if (this.searchTerm) {
                    const exactMatch = this.options.find(opt =>
                        opt[this.searchKey].toLowerCase() === this.searchTerm.toLowerCase()
                    );

                    if (exactMatch && (!this.selectedOption || this.selectedOption.id !== exactMatch.id)) {
                        // Auto-seleccionar la opción que coincide exactamente
                        this.selectedOption = exactMatch;
                        this.searchTerm = exactMatch[this.searchKey];
                        this.$emit('input', exactMatch.id);
                    } else if (this.selectedOption && this.searchTerm !== this.selectedOption[this.searchKey]) {
                        // Si no coincide exactamente, restaurar al valor seleccionado
                        this.searchTerm = this.selectedOption[this.searchKey];
                    } else if (!this.selectedOption && !exactMatch) {
                        // Si no hay selección y no coincide con nada, limpiar
                        this.searchTerm = '';
                        this.$emit('input', '');
                    }
                }
            }, 150);
        },
        
        isSelected(option) {
            return this.selectedOption && this.selectedOption.id === option.id;
        }
    }
}
</script>
