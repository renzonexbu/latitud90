<template>
  <AdminLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Emails de Marketing
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Estadísticas -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
          <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">📊 Estadísticas</h3>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
              <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-blue-600">{{ stats.total_emails }}</div>
                <div class="text-sm text-blue-600">Total Emails</div>
              </div>
              <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-green-600">{{ stats.active_emails }}</div>
                <div class="text-sm text-green-600">Emails Activos</div>
              </div>
              <div class="bg-yellow-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-yellow-600">{{ stats.inactive_emails }}</div>
                <div class="text-sm text-yellow-600">Emails Inactivos</div>
              </div>
              <div class="bg-purple-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-purple-600">{{ stats.total_orders_with_marketing }}</div>
                <div class="text-sm text-purple-600">Orders con Marketing</div>
              </div>
              <div class="bg-indigo-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-indigo-600">{{ stats.total_frequent_clients_with_marketing }}</div>
                <div class="text-sm text-indigo-600">Clientes Frecuentes</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Acciones -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
          <div class="p-6">
            <div class="flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
              <div class="flex flex-col sm:flex-row gap-4">
                <!-- Búsqueda -->
                <div class="relative">
                  <input
                    v-model="filters.search"
                    type="text"
                    placeholder="Buscar email..."
                    class="w-full sm:w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    @input="debounceSearch"
                  />
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                  </div>
                </div>

                <!-- Filtro de estado -->
                <select
                  v-model="filters.status"
                  class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  @change="applyFilters"
                >
                  <option value="">Todos los estados</option>
                  <option value="active">Solo activos</option>
                  <option value="inactive">Solo inactivos</option>
                </select>
              </div>

              <!-- Botón procesar emails -->
              <button
                @click="processEmails"
                :disabled="processing"
                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
              >
                <svg v-if="processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                {{ processing ? 'Procesando...' : 'Procesar Emails' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Tabla de emails -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Email
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Estado
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Fecha Creación
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Acciones
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="email in marketingMails.data" :key="email.id" class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {{ email.email }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span
                        :class="[
                          'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                          email.is_active
                            ? 'bg-green-100 text-green-800'
                            : 'bg-red-100 text-red-800'
                        ]"
                      >
                        {{ email.is_active ? 'Activo' : 'Inactivo' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ formatDate(email.created_at) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <div class="flex space-x-2">
                        <button
                          @click="toggleStatus(email)"
                          :disabled="updating === email.id"
                          class="text-indigo-600 hover:text-indigo-900 disabled:opacity-50"
                        >
                          {{ email.is_active ? 'Desactivar' : 'Activar' }}
                        </button>
                        <button
                          @click="deleteEmail(email)"
                          :disabled="deleting === email.id"
                          class="text-red-600 hover:text-red-900 disabled:opacity-50"
                        >
                          Eliminar
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Paginación -->
            <div v-if="marketingMails.links" class="mt-6">
              <Pagination :links="marketingMails.links" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { toast } from 'vue3-toastify'

const props = defineProps({
  marketingMails: Object,
  stats: Object,
  filters: Object
})

const processing = ref(false)
const updating = ref(null)
const deleting = ref(null)

const filters = reactive({
  search: props.filters?.search || '',
  status: props.filters?.status || '',
  sort_by: props.filters?.sort_by || 'created_at',
  sort_direction: props.filters?.sort_direction || 'desc'
})

// Debounce para búsqueda
let searchTimeout
const debounceSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 300)
}

const applyFilters = () => {
  router.get(route('admin.marketing.emails.index'), filters, {
    preserveState: true,
    preserveScroll: true
  })
}

const processEmails = async () => {
  processing.value = true
  
  try {
    const response = await fetch(route('admin.marketing.emails.process'), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Content-Type': 'application/json'
      }
    })
    
    const data = await response.json()
    
    if (data.success) {
      toast.success(data.message)
      // Recargar la página para mostrar los resultados actualizados
      router.reload()
    } else {
      toast.error(data.message)
    }
  } catch (error) {
    toast.error('Error al procesar emails')
  } finally {
    processing.value = false
  }
}

const toggleStatus = async (email) => {
  updating.value = email.id
  
  try {
    const response = await fetch(route('admin.marketing.emails.toggle-status', email.id), {
      method: 'PATCH',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Content-Type': 'application/json'
      }
    })
    
    const data = await response.json()
    
    if (data.success) {
      toast.success(data.message)
      // Actualizar el estado localmente
      email.is_active = data.is_active
    } else {
      toast.error(data.message)
    }
  } catch (error) {
    toast.error('Error al cambiar el estado')
  } finally {
    updating.value = null
  }
}

const deleteEmail = async (email) => {
  if (!confirm('¿Estás seguro de que quieres eliminar este email?')) {
    return
  }
  
  deleting.value = email.id
  
  try {
    const response = await fetch(route('admin.marketing.emails.destroy', email.id), {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Content-Type': 'application/json'
      }
    })
    
    const data = await response.json()
    
    if (data.success) {
      toast.success(data.message)
      // Recargar la página para actualizar la lista
      router.reload()
    } else {
      toast.error(data.message)
    }
  } catch (error) {
    toast.error('Error al eliminar el email')
  } finally {
    deleting.value = null
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('es-CL', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>
