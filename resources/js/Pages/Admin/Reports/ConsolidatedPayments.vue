<template>
  <AdminLayout>
    <Head title="Consolidado de Pagos" />

    <div class="py-12">
      <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">
        <ConsolidatedPaymentsReport 
          :consolidated-payments="consolidatedPayments"
          :payment-methods="paymentMethods"
          :programs="programs"
          :filters="filters"
          :summary="summary"
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
    type: Object,
    default: () => ({
      data: [],
      links: []
    })
  },
  paymentMethods: {
    type: Array,
    default: () => []
  },
  programs: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  summary: {
    type: Object,
    default: () => ({})
  }
})
</script>
