<template>
  <AdminLayout>
    <Head title="Cronograma de Cuotas" />

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <InstallmentScheduleReport 
          :programs="programs"
          :initial-data="installmentSchedule"
        />
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import InstallmentScheduleReport from '@/Components/Reports/InstallmentScheduleReport.vue'

defineProps({
  programs: {
    type: Array,
    default: () => []
  },
  installmentSchedule: {
    type: Array,
    default: () => []
  }
})
</script>
