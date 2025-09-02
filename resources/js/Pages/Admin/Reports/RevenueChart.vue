<template>
  <AdminLayout>
    <Head title="Gráfico de Ingresos" />

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <RevenueChart 
          :initial-data="revenueData"
          :payment-methods-data="paymentMethodsData"
        />
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import RevenueChart from '@/Components/Reports/RevenueChart.vue'

defineProps({
  revenueData: {
    type: Array,
    default: () => []
  },
  paymentMethodsData: {
    type: Array,
    default: () => []
  }
})
</script>
