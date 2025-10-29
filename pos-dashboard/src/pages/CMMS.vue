<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col cols="12" md="6">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Assets</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.assets" @click="fetchAssets">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.assets" type="error" variant="tonal" class="mb-4">
                {{ errors.assets }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.assets" @submit.prevent="createAsset">
                <v-row dense>
                  <v-col cols="12" md="5">
                    <v-text-field v-model="forms.asset.code" label="Asset Code" dense required />
                  </v-col>
                  <v-col cols="12" md="7">
                    <v-text-field v-model="forms.asset.name" label="Asset Name" dense required />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.asset.asset_type" label="Type" dense />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.asset.status"
                      :items="assetStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="forms.asset.commissioned_at"
                      label="Commissioned"
                      type="date"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-text-field v-model="forms.asset.location.area" label="Area / Cell" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.assets">
                      <v-icon start>mdi-plus</v-icon>
                      Register Asset
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="assetHeaders"
                :items="assets"
                :loading="loading.assets"
                class="elevation-0"
              >
                <template #item.location="{ item }">
                  {{ item.location?.area ?? '—' }}
                </template>
                <template #item.commissioned_at="{ item }">
                  {{ item.commissioned_at ?? '—' }}
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="6">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Maintenance Work Orders</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.workOrders" @click="fetchWorkOrders">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.workOrders" type="error" variant="tonal" class="mb-4">
                {{ errors.workOrders }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.workOrders" @submit.prevent="createWorkOrder">
                <v-row dense>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.workOrder.reference" label="Reference" dense required />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.workOrder.asset_id"
                      :items="assets"
                      item-title="name"
                      item-value="id"
                      label="Asset"
                      dense
                      clearable
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.workOrder.maintenance_plan_id"
                      :items="maintenancePlans"
                      item-title="name"
                      item-value="id"
                      label="Maintenance Plan"
                      dense
                      clearable
                    />
                  </v-col>
                  <v-col cols="6" md="3">
                    <v-select
                      v-model="forms.workOrder.priority"
                      :items="workOrderPriorities"
                      label="Priority"
                      dense
                    />
                  </v-col>
                  <v-col cols="6" md="3">
                    <v-select
                      v-model="forms.workOrder.status"
                      :items="workOrderStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="forms.workOrder.scheduled_date"
                      type="date"
                      label="Scheduled Date"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="forms.workOrder.description"
                      label="Description"
                      rows="2"
                      density="compact"
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.workOrders">
                      <v-icon start>mdi-clipboard-text</v-icon>
                      Schedule Work Order
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="workOrderHeaders"
                :items="workOrders"
                :loading="loading.workOrders"
                class="elevation-0"
              >
                <template #item.asset="{ item }">
                  {{ item.asset?.name ?? '—' }}
                </template>
                <template #item.plan="{ item }">
                  {{ item.plan?.name ?? 'Unplanned' }}
                </template>
                <template #item.logs_count="{ item }">
                  {{ item.logs?.length ?? 0 }}
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
      <v-row>
        <v-col cols="12" md="6">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Maintenance Plans</span>
              <v-spacer />
              <v-btn icon color="primary" :loading="loading.plans" @click="fetchMaintenancePlans">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-data-table
                :headers="planHeaders"
                :items="maintenancePlans"
                :loading="loading.plans"
                class="elevation-0"
              >
                <template #item.asset="{ item }">
                  {{ assets.find(asset => asset.id === item.asset_id)?.name ?? '—' }}
                </template>
                <template #item.tasks="{ item }">
                  <ul class="ma-0 pa-0">
                    <li v-for="task in item.tasks ?? []" :key="task.step">{{ task.step }}</li>
                  </ul>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="6">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Performed Maintenance Logs</span>
              <v-spacer />
              <v-select
                v-model="selectedWorkOrderId"
                :items="workOrders"
                item-title="reference"
                item-value="id"
                label="Filter by Work Order"
                density="compact"
                hide-details
                style="max-width: 260px"
              />
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.logs" type="error" variant="tonal" class="mb-4">
                {{ errors.logs }}
              </v-alert>
              <v-form class="mb-4" :disabled="loading.logs" @submit.prevent="createMaintenanceLog">
                <v-row dense>
                  <v-col cols="12">
                    <v-textarea
                      v-model="forms.log.notes"
                      label="Log Notes"
                      rows="2"
                      density="compact"
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="forms.log.logged_at"
                      type="datetime-local"
                      label="Logged At"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn color="primary" class="rounded-pill" type="submit" :loading="loading.logs">
                      <v-icon start>mdi-wrench</v-icon>
                      Record Log Entry
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <v-data-table
                :headers="logHeaders"
                :items="maintenanceLogs"
                :loading="loading.logs"
                class="elevation-0"
              >
                <template #item.logged_at="{ item }">
                  {{ item.logged_at ?? '—' }}
                </template>
                <template #item.user="{ item }">
                  {{ item.user?.name ?? '—' }}
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </OwnerLayout>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import axios from 'axios'
import OwnerLayout from '../layouts/OwnerLayout.vue'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

const assets = ref([])
const maintenancePlans = ref([])
const workOrders = ref([])
const maintenanceLogs = ref([])

const loading = reactive({
  assets: false,
  plans: false,
  workOrders: false,
  logs: false
})

const errors = reactive({
  assets: null,
  workOrders: null,
  logs: null
})

const forms = reactive({
  asset: {
    code: '',
    name: '',
    asset_type: '',
    status: 'active',
    commissioned_at: '',
    location: {
      area: ''
    }
  },
  workOrder: {
    reference: '',
    asset_id: null,
    maintenance_plan_id: null,
    priority: 'medium',
    status: 'scheduled',
    description: '',
    scheduled_date: ''
  },
  log: {
    work_order_id: null,
    notes: '',
    logged_at: ''
  }
})

const assetHeaders = [
  { title: 'Code', key: 'code' },
  { title: 'Name', key: 'name' },
  { title: 'Type', key: 'asset_type' },
  { title: 'Status', key: 'status' },
  { title: 'Area', key: 'location' },
  { title: 'Commissioned', key: 'commissioned_at' }
]

const planHeaders = [
  { title: 'Plan', key: 'name' },
  { title: 'Asset', key: 'asset' },
  { title: 'Frequency', key: 'frequency' },
  { title: 'Tasks', key: 'tasks' },
  { title: 'Status', key: 'status' }
]

const workOrderHeaders = [
  { title: 'Reference', key: 'reference' },
  { title: 'Asset', key: 'asset' },
  { title: 'Plan', key: 'plan' },
  { title: 'Status', key: 'status' },
  { title: 'Priority', key: 'priority' },
  { title: 'Scheduled', key: 'scheduled_date' },
  { title: 'Logs', key: 'logs_count' }
]

const logHeaders = [
  { title: 'Timestamp', key: 'logged_at' },
  { title: 'Technician', key: 'user' },
  { title: 'Notes', key: 'notes' }
]

const assetStatuses = ['active', 'inactive', 'retired']
const workOrderStatuses = ['scheduled', 'open', 'in_progress', 'completed', 'cancelled']
const workOrderPriorities = ['low', 'medium', 'high']

const selectedWorkOrderId = ref(null)

function authHeaders() {
  const token = localStorage.getItem('token')
  return {
    Authorization: token ? `Bearer ${token}` : undefined
  }
}

function parseError(error) {
  if (error.response?.data?.message) return error.response.data.message
  if (error.response?.data?.errors) {
    return Object.values(error.response.data.errors).flat().join(', ')
  }
  return error.message || 'An unexpected error occurred.'
}

async function fetchAssets() {
  loading.assets = true
  errors.assets = null
  try {
    const { data } = await axios.get(`${API_BASE}/cmms/assets`, { headers: authHeaders() })
    assets.value = data.data || data
  } catch (error) {
    errors.assets = parseError(error)
  } finally {
    loading.assets = false
  }
}

async function fetchMaintenancePlans() {
  loading.plans = true
  try {
    const { data } = await axios.get(`${API_BASE}/cmms/maintenance-plans`, { headers: authHeaders() })
    maintenancePlans.value = data.data || data
  } catch (error) {
    console.error(error)
  } finally {
    loading.plans = false
  }
}

async function fetchWorkOrders() {
  loading.workOrders = true
  errors.workOrders = null
  try {
    const { data } = await axios.get(`${API_BASE}/cmms/work-orders`, { headers: authHeaders() })
    workOrders.value = (data.data || data).map(workOrder => ({
      ...workOrder,
      logs: workOrder.logs ?? []
    }))
    if (!selectedWorkOrderId.value && workOrders.value.length) {
      selectedWorkOrderId.value = workOrders.value[0].id
    }
  } catch (error) {
    errors.workOrders = parseError(error)
  } finally {
    loading.workOrders = false
  }
}

async function fetchMaintenanceLogs() {
  if (!selectedWorkOrderId.value) {
    maintenanceLogs.value = []
    return
  }

  loading.logs = true
  errors.logs = null
  try {
    const { data } = await axios.get(`${API_BASE}/cmms/maintenance-logs`, {
      headers: authHeaders(),
      params: { work_order_id: selectedWorkOrderId.value }
    })
    maintenanceLogs.value = data.data || data
  } catch (error) {
    errors.logs = parseError(error)
  } finally {
    loading.logs = false
  }
}

async function createAsset() {
  loading.assets = true
  errors.assets = null
  try {
    const payload = {
      code: forms.asset.code,
      name: forms.asset.name,
      asset_type: forms.asset.asset_type,
      status: forms.asset.status,
      commissioned_at: forms.asset.commissioned_at || null,
      location: forms.asset.location.area ? { area: forms.asset.location.area } : null
    }
    await axios.post(`${API_BASE}/cmms/assets`, payload, { headers: authHeaders() })
    Object.assign(forms.asset, {
      code: '',
      name: '',
      asset_type: '',
      status: 'active',
      commissioned_at: '',
      location: { area: '' }
    })
    await fetchAssets()
  } catch (error) {
    errors.assets = parseError(error)
  } finally {
    loading.assets = false
  }
}

async function createWorkOrder() {
  loading.workOrders = true
  errors.workOrders = null
  try {
    const payload = {
      reference: forms.workOrder.reference,
      asset_id: forms.workOrder.asset_id,
      maintenance_plan_id: forms.workOrder.maintenance_plan_id,
      priority: forms.workOrder.priority,
      status: forms.workOrder.status,
      scheduled_date: forms.workOrder.scheduled_date || null,
      description: forms.workOrder.description || null
    }
    await axios.post(`${API_BASE}/cmms/work-orders`, payload, { headers: authHeaders() })
    Object.assign(forms.workOrder, {
      reference: '',
      asset_id: null,
      maintenance_plan_id: null,
      priority: 'medium',
      status: 'scheduled',
      description: '',
      scheduled_date: ''
    })
    await fetchWorkOrders()
  } catch (error) {
    errors.workOrders = parseError(error)
  } finally {
    loading.workOrders = false
  }
}

async function createMaintenanceLog() {
  if (!selectedWorkOrderId.value) {
    errors.logs = 'Select a work order before recording a log entry.'
    return
  }

  loading.logs = true
  errors.logs = null
  try {
    const payload = {
      work_order_id: selectedWorkOrderId.value,
      notes: forms.log.notes,
      logged_at: forms.log.logged_at || null
    }
    await axios.post(`${API_BASE}/cmms/maintenance-logs`, payload, { headers: authHeaders() })
    Object.assign(forms.log, {
      work_order_id: selectedWorkOrderId.value,
      notes: '',
      logged_at: ''
    })
    await fetchMaintenanceLogs()
    await fetchWorkOrders()
  } catch (error) {
    errors.logs = parseError(error)
  } finally {
    loading.logs = false
  }
}

watch(selectedWorkOrderId, async () => {
  await fetchMaintenanceLogs()
})

onMounted(async () => {
  await Promise.all([fetchAssets(), fetchMaintenancePlans()])
  await fetchWorkOrders()
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}

ul {
  list-style-type: disc;
  padding-left: 18px;
}
</style>

