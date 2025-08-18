<template>
  <AdminLayout>
    <Head title="Reporte de Pagos Diarios" />

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <DailyPaymentsReport 
          :programs="programs"
          :executives="executives"
          :initial-data="dailyPayments"
        />
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import DailyPaymentsReport from '@/Components/Reports/DailyPaymentsReport.vue'

defineProps({
  programs: {
    type: Array,
    default: () => []
  },
  executives: {
    type: Array,
    default: () => []
  },
  dailyPayments: {
    type: Array,
    default: () => []
  }
})
</script>
