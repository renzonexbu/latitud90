<template>
    <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div @click.stop class="bg-white rounded-[20px] border border-[#d3d3d3] p-6 max-w-[500px] w-full modal-content">
            <div class="flex flex-col gap-[20px] items-end justify-center mb-6">
                <div class="flex flex-row gap-[20px] items-start justify-end w-full">
                    <div class="text-[#434343] text-center font-nexa-bold text-[24px] leading-[28px] font-bold flex-1 text-center">
                        Crear Ejecutivo Comercial
                    </div>
                    <button @click="closeModal" class="rounded-full w-8 h-8 flex items-center justify-center bg-gray-500 hover:bg-gray-600 transition-colors text-white">
                        ✕
                    </button>
                </div>
            </div>

            <form @submit.prevent="saveExecutive" class="modal-form">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-[10px]">
                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">Código *</label>
                        <input v-model="form.code" type="text" placeholder="SE-001" :class="inputClass('code')" />
                        <span v-if="localErrors.code" class="text-red-500 text-xs mt-1">{{ getError('code') }}</span>
                    </div>
                    <div class="flex flex-col gap-[10px]">
                        <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">Nombre *</label>
                        <input v-model="form.name" type="text" placeholder="Nombre" :class="inputClass('name')" />
                        <span v-if="localErrors.name" class="text-red-500 text-xs mt-1">{{ getError('name') }}</span>
                    </div>
                    <div class="flex flex-row gap-[18px]">
                        <div class="flex flex-col gap-[10px] flex-1">
                            <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">Email</label>
                            <input v-model="form.email" type="email" placeholder="email@ejecutivo.com" :class="inputClass('email')" />
                            <span v-if="localErrors.email" class="text-red-500 text-xs mt-1">{{ getError('email') }}</span>
                        </div>
                        <div class="flex flex-col gap-[10px] flex-1">
                            <label class="text-[#5b5b5b] text-left font-nexa-bold text-[12px] leading-[13px] font-bold">Teléfono</label>
                            <input v-model="form.phone" type="tel" placeholder="000000000" :class="inputClass('phone')" />
                            <span v-if="localErrors.phone" class="text-red-500 text-xs mt-1">{{ getError('phone') }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <button type="submit" :disabled="isSubmitting" class="bg-[#007e93] rounded-[112.89px] px-[18px] py-[14px] w-full text-white font-nexa-bold hover:bg-[#006580] transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ isSubmitting ? 'Guardando...' : 'Crear ejecutivo' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    
</template>

<script>
import axios from 'axios';

export default {
    name: 'CreateExecutiveModal',
    props: {
        show: { type: Boolean, default: false }
    },
    data() {
        return {
            isSubmitting: false,
            form: { code: '', name: '', email: '', phone: '' },
            localErrors: {}
        }
    },
    methods: {
        inputClass(field) {
            return [
                'w-full h-[46px] bg-white rounded-lg border p-4 text-left font-nexa-bold text-[12px] leading-[18px] font-bold shadow-[0px_1px_4px_0px_rgba(25,33,61,0.08)] outline-none placeholder-[#c7c7c7]',
                this.localErrors[field] ? 'border-red-500' : 'border-[#5b5b5b]'
            ]
        },
        getError(field) {
            const error = this.localErrors[field];
            return Array.isArray(error) ? error[0] : error;
        },
        closeModal() {
            this.$emit('close');
            this.form = { code: '', name: '', email: '', phone: '' };
            this.localErrors = {};
            this.isSubmitting = false;
        },
        toProperCase(str) {
            if (!str) return '';
            return str.trim().split(/\s+/).map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()).join(' ');
        },
        async saveExecutive() {
            this.isSubmitting = true;
            this.localErrors = {};
            this.form.name = this.toProperCase(this.form.name);
            try {
                const { data } = await axios.post(route('admin.sales-executives.store'), this.form);
                if (data && data.success && data.executive) {
                    this.$emit('executive-created', data.executive);
                    this.$emit('success', 'Ejecutivo comercial creado exitosamente');
                    this.form = { code: '', name: '', email: '', phone: '' };
                    this.localErrors = {};
                    this.$emit('close');
                }
            } catch (error) {
                if (error.response && error.response.data && error.response.data.errors) {
                    this.localErrors = error.response.data.errors;
                } else if (error.response && error.response.data && error.response.data.message) {
                    this.$emit('error', error.response.data.message);
                } else {
                    this.$emit('error', 'Error al crear el ejecutivo comercial');
                }
            } finally {
                this.isSubmitting = false;
            }
        }
    }
}
</script>

<style scoped>
.modal-content { max-height: calc(100vh - 2rem); overflow: hidden; display: flex; flex-direction: column; }
.modal-form { flex: 1; overflow-y: auto; padding-right: 0.5rem; }
</style>


