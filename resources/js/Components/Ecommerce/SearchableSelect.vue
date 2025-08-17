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
            class="w-full h-[46px] bg-white rounded-lg border border-[#5B5B5B] px-4 py-2 text-left font-nexa-bold text-[12px] leading-[18px] font-bold outline-none placeholder-[#c7c7c7]"
            :class="{ 'border-[#007E93]': showDropdown }"
        />
        
        <!-- Dropdown con opciones filtradas -->
        <div 
            v-if="showDropdown && filteredOptions.length > 0"
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
            v-if="showDropdown && searchTerm && filteredOptions.length === 0"
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
            },
            deep: true
        }
    },
    methods: {
        handleSearch(event) {
            this.searchTerm = event.target.value;
            this.showDropdown = true;
            
            // Si el usuario borra todo, emitir valor vacío
            if (!this.searchTerm) {
                this.$emit('input', '');
                this.selectedOption = null;
            }
        },
        
        selectOption(option) {
            this.selectedOption = option;
            this.searchTerm = option[this.searchKey];
            this.showDropdown = false;
            this.$emit('input', option.id);
        },
        
        handleBlur() {
            // Pequeño delay para permitir que el click en las opciones funcione
            setTimeout(() => {
                this.showDropdown = false;
                
                // Si hay un término de búsqueda pero no coincide con la opción seleccionada, limpiar
                if (this.searchTerm && this.selectedOption && 
                    this.searchTerm !== this.selectedOption[this.searchKey]) {
                    this.searchTerm = this.selectedOption[this.searchKey];
                }
            }, 150);
        },
        
        isSelected(option) {
            return this.selectedOption && this.selectedOption.id === option.id;
        }
    }
}
</script>
