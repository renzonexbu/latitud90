<template>
  <AdminLayout>
    <Head title="Consolidado de Pagos" />

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <ConsolidatedPaymentsReport 
          :initial-data="consolidatedPayments"
        />
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ConsolidatedPaymentsReport from '@/Components/Reports/ConsolidatedPaymentsReport.vue'

defineProps({
  consolidatedPayments: {
    type: Array,
    default: () => []
  }
})
</script>
