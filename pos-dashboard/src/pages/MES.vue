<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col cols="12" md="5">
          <v-card elevation="3" class="pa-4 mb-6 rounded-xl">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Production Lines</span>
              <v-spacer />
              <v-btn icon color="primary" class="rounded-pill" @click="loadProductionLines" :loading="loading.lines">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.lines" type="error" variant="tonal" class="mb-4">
                {{ errors.lines }}
              </v-alert>
              <v-form @submit.prevent="createLine" :disabled="loading.lines" class="mb-4">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.line.code" label="Code" dense required />
                  </v-col>
                  <v-col cols="12" md="8">
                    <v-text-field v-model="forms.line.name" label="Name" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.lines">
                      <v-icon start>mdi-plus</v-icon>
                      Add Production Line
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="lineHeaders"
                :items="productionLines"
                :items-per-page="5"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.status="{ item }">
                  <v-chip size="small" :color="item.status === 'active' ? 'success' : 'grey'">
                    {{ item.status }}
                  </v-chip>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="7">
          <v-card elevation="3" class="pa-4 mb-6 rounded-xl">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Work Orders</span>
              <v-spacer />
              <v-btn icon color="primary" class="rounded-pill" @click="loadWorkOrders" :loading="loading.workOrders">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.workOrders" type="error" variant="tonal" class="mb-4">
                {{ errors.workOrders }}
              </v-alert>
              <v-form @submit.prevent="createWorkOrder" class="mb-4" :disabled="loading.workOrders">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.workOrder.code" label="Code" dense required />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.workOrder.item_id"
                      :items="items"
                      item-title="name"
                      item-value="id"
                      label="Item"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.workOrder.production_line_id"
                      :items="productionLines"
                      item-title="name"
                      item-value="id"
                      label="Production Line"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model.number="forms.workOrder.quantity"
                      label="Quantity"
                      type="number"
                      min="0.0001"
                      step="0.0001"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.workOrder.status"
                      :items="workOrderStatuses"
                      label="Status"
                      dense
                    />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.workOrders">
                      <v-icon start>mdi-plus</v-icon>
                      Create Work Order
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="workOrderHeaders"
                :items="workOrders"
                :items-per-page="5"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.status="{ item }">
                  <v-chip size="small" :color="statusColor(item.status)">
                    {{ item.status }}
                  </v-chip>
                </template>
                <template #item.progress="{ item }">
                  {{ item.quantity_completed }} / {{ item.quantity }}
                </template>
                <template #item.actions="{ item }">
                  <v-btn size="small" variant="text" color="primary" @click="openEventDialog(item)">
                    Log Event
                  </v-btn>
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-dialog v-model="eventDialog.open" max-width="480px">
        <v-card>
          <v-card-title class="font-weight-bold">Log Production Event</v-card-title>
          <v-card-text>
            <v-alert v-if="errors.events" type="error" variant="tonal" class="mb-4">
              {{ errors.events }}
            </v-alert>
            <v-form @submit.prevent="logEvent" :disabled="loading.events">
              <v-text-field v-model="eventDialog.form.event_type" label="Event Type" dense required />
              <v-text-field
                v-model="eventDialog.form.event_timestamp"
                label="Event Timestamp"
                type="datetime-local"
                dense
                required
              />
              <v-textarea
                v-model="eventDialog.form.notes"
                label="Notes"
                dense
                rows="3"
              />
            </v-form>
          </v-card-text>
          <v-card-actions>
            <v-spacer />
            <v-btn text @click="eventDialog.open = false">Cancel</v-btn>
            <v-btn color="primary" @click="logEvent" :loading="loading.events">
              Save
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </OwnerLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import axios from 'axios'
import OwnerLayout from '../layouts/OwnerLayout.vue'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

const productionLines = ref([])
const workOrders = ref([])
const items = ref([])

const loading = reactive({
  lines: false,
  workOrders: false,
  items: false,
  events: false
})

const errors = reactive({
  lines: null,
  workOrders: null,
  events: null
})

const forms = reactive({
  line: {
    code: '',
    name: '',
    status: 'active'
  },
  workOrder: {
    code: '',
    item_id: null,
    production_line_id: null,
    quantity: 1,
    status: 'planned'
  }
})

const eventDialog = reactive({
  open: false,
  workOrderId: null,
  form: {
    event_type: 'status_changed',
    event_timestamp: new Date().toISOString().slice(0, 16),
    notes: ''
  }
})

const lineHeaders = [
  { title: 'Code', key: 'code' },
  { title: 'Name', key: 'name' },
  { title: 'Status', key: 'status' }
]

const workOrderHeaders = [
  { title: 'Code', key: 'code' },
  { title: 'Item', key: 'item.name' },
  { title: 'Line', key: 'production_line.name' },
  { title: 'Status', key: 'status' },
  { title: 'Progress', key: 'progress' },
  { title: 'Actions', key: 'actions', sortable: false }
]

const workOrderStatuses = [
  { title: 'Planned', value: 'planned' },
  { title: 'Released', value: 'released' },
  { title: 'In Progress', value: 'in_progress' },
  { title: 'Completed', value: 'completed' },
  { title: 'Closed', value: 'closed' }
]

function authHeaders() {
  const token = localStorage.getItem('token')
  return {
    Authorization: token ? `Bearer ${token}` : undefined
  }
}

function statusColor(status) {
  switch (status) {
    case 'planned': return 'grey'
    case 'released': return 'primary'
    case 'in_progress': return 'orange'
    case 'completed': return 'success'
    case 'closed': return 'blue-grey'
    default: return 'grey'
  }
}

function parseError(error) {
  if (error.response?.data?.message) return error.response.data.message
  if (error.response?.data?.errors) {
    return Object.values(error.response.data.errors).flat().join(', ')
  }
  return error.message || 'An unexpected error occurred.'
}

async function loadProductionLines() {
  loading.lines = true
  errors.lines = null
  try {
    const { data } = await axios.get(`${API_BASE}/mes/production-lines`, { headers: authHeaders() })
    productionLines.value = data.data || data
  } catch (error) {
    errors.lines = parseError(error)
  } finally {
    loading.lines = false
  }
}

async function loadWorkOrders() {
  loading.workOrders = true
  errors.workOrders = null
  try {
    const { data } = await axios.get(`${API_BASE}/mes/work-orders`, { headers: authHeaders() })
    workOrders.value = data.data || data
  } catch (error) {
    errors.workOrders = parseError(error)
  } finally {
    loading.workOrders = false
  }
}

async function loadItems() {
  loading.items = true
  try {
    const { data } = await axios.get(`${API_BASE}/erp/items`, { headers: authHeaders() })
    items.value = data.data || data
  } catch (error) {
    console.error(error)
  } finally {
    loading.items = false
  }
}

async function createLine() {
  loading.lines = true
  errors.lines = null
  try {
    await axios.post(`${API_BASE}/mes/production-lines`, forms.line, { headers: authHeaders() })
    forms.line.code = ''
    forms.line.name = ''
    await loadProductionLines()
  } catch (error) {
    errors.lines = parseError(error)
  } finally {
    loading.lines = false
  }
}

async function createWorkOrder() {
  loading.workOrders = true
  errors.workOrders = null
  try {
    await axios.post(`${API_BASE}/mes/work-orders`, forms.workOrder, { headers: authHeaders() })
    Object.assign(forms.workOrder, {
      code: '',
      item_id: null,
      production_line_id: null,
      quantity: 1,
      status: 'planned'
    })
    await loadWorkOrders()
  } catch (error) {
    errors.workOrders = parseError(error)
  } finally {
    loading.workOrders = false
  }
}

function openEventDialog(workOrder) {
  eventDialog.workOrderId = workOrder.id
  eventDialog.form.event_type = 'status_changed'
  eventDialog.form.event_timestamp = new Date().toISOString().slice(0, 16)
  eventDialog.form.notes = ''
  errors.events = null
  eventDialog.open = true
}

async function logEvent() {
  if (!eventDialog.workOrderId) return
  loading.events = true
  errors.events = null
  try {
    await axios.post(`${API_BASE}/mes/events`, {
      work_order_id: eventDialog.workOrderId,
      event_type: eventDialog.form.event_type,
      event_timestamp: new Date(eventDialog.form.event_timestamp).toISOString(),
      payload: eventDialog.form.notes ? { notes: eventDialog.form.notes } : undefined
    }, { headers: authHeaders() })
    eventDialog.open = false
    await loadWorkOrders()
  } catch (error) {
    errors.events = parseError(error)
  } finally {
    loading.events = false
  }
}

onMounted(async () => {
  await Promise.all([loadProductionLines(), loadWorkOrders(), loadItems()])
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}
</style>

