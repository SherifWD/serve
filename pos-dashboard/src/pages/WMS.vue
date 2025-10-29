<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col cols="12" md="6">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Warehouses</span>
              <v-spacer />
              <v-btn icon color="primary" @click="fetchWarehouses" :loading="loading.warehouses">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.warehouses" type="error" variant="tonal" class="mb-4">
                {{ errors.warehouses }}
              </v-alert>
              <v-form @submit.prevent="createWarehouse" :disabled="loading.warehouses" class="mb-4">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.warehouse.code" label="Code" dense required />
                  </v-col>
                  <v-col cols="12" md="8">
                    <v-text-field v-model="forms.warehouse.name" label="Name" dense required />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.warehouses">
                      <v-icon start>mdi-plus</v-icon>
                      Add Warehouse
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="warehouseHeaders"
                :items="warehouses"
                :items-per-page="5"
                density="comfortable"
                class="rounded-lg"
              />
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Storage Bins</span>
              <v-spacer />
              <v-btn icon color="primary" @click="fetchBins" :loading="loading.bins">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.bins" type="error" variant="tonal" class="mb-4">
                {{ errors.bins }}
              </v-alert>
              <v-form @submit.prevent="createBin" :disabled="loading.bins" class="mb-4">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.bin.warehouse_id"
                      :items="warehouses"
                      item-title="name"
                      item-value="id"
                      label="Warehouse"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.bin.code" label="Bin Code" dense required />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.bin.zone" label="Zone" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.bins">
                      <v-icon start>mdi-plus</v-icon>
                      Add Bin
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="binHeaders"
                :items="bins"
                :items-per-page="5"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.warehouse="{ item }">
                  {{ item.warehouse?.name || '—' }}
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
              <span class="text-h6 font-weight-bold">Inventory Lots</span>
              <v-spacer />
              <v-btn icon color="primary" @click="fetchLots" :loading="loading.lots">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.lots" type="error" variant="tonal" class="mb-4">
                {{ errors.lots }}
              </v-alert>
              <v-form @submit.prevent="createLot" :disabled="loading.lots" class="mb-4">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.lot.warehouse_id"
                      :items="warehouses"
                      item-title="name"
                      item-value="id"
                      label="Warehouse"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.lot.storage_bin_id"
                      :items="bins"
                      item-title="code"
                      item-value="id"
                      label="Bin"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.lot.item_id"
                      :items="items"
                      item-title="name"
                      item-value="id"
                      label="Item"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.lot.lot_number" label="Lot Number" dense />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model.number="forms.lot.quantity" label="Quantity" type="number" min="0" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.lots">
                      <v-icon start>mdi-plus</v-icon>
                      Add Lot
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="lotHeaders"
                :items="lots"
                :items-per-page="5"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.item="{ item }">
                  {{ item.item?.name || '—' }}
                </template>
                <template #item.warehouse="{ item }">
                  {{ item.warehouse?.name || '—' }}
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="6">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Transfer Orders</span>
              <v-spacer />
              <v-btn icon color="primary" @click="fetchTransfers" :loading="loading.transfers">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.transfers" type="error" variant="tonal" class="mb-4">
                {{ errors.transfers }}
              </v-alert>
              <v-form @submit.prevent="createTransfer" :disabled="loading.transfers" class="mb-4">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.transfer.reference" label="Reference" dense required />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.transfer.source_bin_id"
                      :items="bins"
                      item-title="code"
                      item-value="id"
                      label="From Bin"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="forms.transfer.destination_bin_id"
                      :items="bins"
                      item-title="code"
                      item-value="id"
                      label="To Bin"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="forms.transfer.item_id"
                      :items="items"
                      item-title="name"
                      item-value="id"
                      label="Item"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model.number="forms.transfer.quantity" label="Quantity" dense type="number" min="0" step="0.001" />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.transfers">
                      <v-icon start>mdi-plus</v-icon>
                      Create Transfer
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="transferHeaders"
                :items="transfers"
                :items-per-page="5"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.source_bin="{ item }">
                  {{ item.source_bin?.code || '—' }}
                </template>
                <template #item.destination_bin="{ item }">
                  {{ item.destination_bin?.code || '—' }}
                </template>
                <template #item.item="{ item }">
                  {{ item.item?.name || '—' }}
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
import { onMounted, reactive, ref } from 'vue'
import axios from 'axios'
import OwnerLayout from '../layouts/OwnerLayout.vue'

const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

const warehouses = ref([])
const bins = ref([])
const lots = ref([])
const transfers = ref([])
const items = ref([])

const loading = reactive({
  warehouses: false,
  bins: false,
  lots: false,
  transfers: false
})

const errors = reactive({
  warehouses: null,
  bins: null,
  lots: null,
  transfers: null
})

const forms = reactive({
  warehouse: {
    code: '',
    name: ''
  },
  bin: {
    warehouse_id: null,
    code: '',
    zone: ''
  },
  lot: {
    warehouse_id: null,
    storage_bin_id: null,
    item_id: null,
    lot_number: '',
    quantity: 10
  },
  transfer: {
    reference: '',
    source_bin_id: null,
    destination_bin_id: null,
    item_id: null,
    quantity: 5
  }
})

const warehouseHeaders = [
  { title: 'Code', key: 'code' },
  { title: 'Name', key: 'name' },
  { title: 'Status', key: 'status' }
]

const binHeaders = [
  { title: 'Code', key: 'code' },
  { title: 'Zone', key: 'zone' },
  { title: 'Warehouse', key: 'warehouse' }
]

const lotHeaders = [
  { title: 'Lot', key: 'lot_number' },
  { title: 'Item', key: 'item' },
  { title: 'Warehouse', key: 'warehouse' },
  { title: 'Qty', key: 'quantity' }
]

const transferHeaders = [
  { title: 'Reference', key: 'reference' },
  { title: 'Item', key: 'item' },
  { title: 'Source', key: 'source_bin' },
  { title: 'Destination', key: 'destination_bin' },
  { title: 'Qty', key: 'quantity' },
  { title: 'Status', key: 'status' }
]

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

async function fetchItems() {
  try {
    const { data } = await axios.get(`${API_BASE}/erp/items`, { headers: authHeaders() })
    items.value = data.data || data
  } catch (error) {
    console.error(error)
  }
}

async function fetchWarehouses() {
  loading.warehouses = true
  errors.warehouses = null
  try {
    const { data } = await axios.get(`${API_BASE}/wms/warehouses`, { headers: authHeaders() })
    warehouses.value = data.data || data
  } catch (error) {
    errors.warehouses = parseError(error)
  } finally {
    loading.warehouses = false
  }
}

async function createWarehouse() {
  loading.warehouses = true
  errors.warehouses = null
  try {
    await axios.post(`${API_BASE}/wms/warehouses`, forms.warehouse, { headers: authHeaders() })
    Object.assign(forms.warehouse, { code: '', name: '' })
    await fetchWarehouses()
  } catch (error) {
    errors.warehouses = parseError(error)
  } finally {
    loading.warehouses = false
  }
}

async function fetchBins() {
  loading.bins = true
  errors.bins = null
  try {
    const { data } = await axios.get(`${API_BASE}/wms/storage-bins`, { headers: authHeaders() })
    bins.value = data.data || data
  } catch (error) {
    errors.bins = parseError(error)
  } finally {
    loading.bins = false
  }
}

async function createBin() {
  loading.bins = true
  errors.bins = null
  try {
    await axios.post(`${API_BASE}/wms/storage-bins`, forms.bin, { headers: authHeaders() })
    Object.assign(forms.bin, { warehouse_id: null, code: '', zone: '' })
    await fetchBins()
  } catch (error) {
    errors.bins = parseError(error)
  } finally {
    loading.bins = false
  }
}

async function fetchLots() {
  loading.lots = true
  errors.lots = null
  try {
    const { data } = await axios.get(`${API_BASE}/wms/inventory-lots`, { headers: authHeaders() })
    lots.value = data.data || data
  } catch (error) {
    errors.lots = parseError(error)
  } finally {
    loading.lots = false
  }
}

async function createLot() {
  loading.lots = true
  errors.lots = null
  try {
    await axios.post(`${API_BASE}/wms/inventory-lots`, forms.lot, { headers: authHeaders() })
    Object.assign(forms.lot, { warehouse_id: null, storage_bin_id: null, item_id: null, lot_number: '', quantity: 10 })
    await fetchLots()
  } catch (error) {
    errors.lots = parseError(error)
  } finally {
    loading.lots = false
  }
}

async function fetchTransfers() {
  loading.transfers = true
  errors.transfers = null
  try {
    const { data } = await axios.get(`${API_BASE}/wms/transfer-orders`, { headers: authHeaders() })
    transfers.value = data.data || data
  } catch (error) {
    errors.transfers = parseError(error)
  } finally {
    loading.transfers = false
  }
}

async function createTransfer() {
  loading.transfers = true
  errors.transfers = null
  try {
    await axios.post(`${API_BASE}/wms/transfer-orders`, forms.transfer, { headers: authHeaders() })
    Object.assign(forms.transfer, { reference: '', source_bin_id: null, destination_bin_id: null, item_id: null, quantity: 5 })
    await fetchTransfers()
  } catch (error) {
    errors.transfers = parseError(error)
  } finally {
    loading.transfers = false
  }
}

onMounted(async () => {
  await fetchItems()
  await Promise.all([fetchWarehouses(), fetchBins(), fetchLots(), fetchTransfers()])
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}
</style>

