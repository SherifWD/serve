<template>
  <OwnerLayout>
    <v-container class="py-6">
      <v-row>
        <v-col cols="12" md="6">
          <v-card class="rounded-xl mb-6" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Suppliers</span>
              <v-spacer />
              <v-btn icon color="primary" @click="fetchSuppliers" :loading="loading.suppliers">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.suppliers" type="error" variant="tonal" class="mb-4">
                {{ errors.suppliers }}
              </v-alert>
              <v-form @submit.prevent="createSupplier" :disabled="loading.suppliers" class="mb-4">
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-text-field v-model="forms.supplier.code" label="Code" dense required />
                  </v-col>
                  <v-col cols="12" md="8">
                    <v-text-field v-model="forms.supplier.name" label="Name" dense required />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.supplier.contact_name" label="Contact" dense />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model="forms.supplier.email" label="Email" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.suppliers">
                      <v-icon start>mdi-plus</v-icon>
                      Add Supplier
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="supplierHeaders"
                :items="suppliers"
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
              <span class="text-h6 font-weight-bold">Purchase Orders</span>
              <v-spacer />
              <v-btn icon color="primary" @click="fetchPurchaseOrders" :loading="loading.purchaseOrders">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.purchaseOrders" type="error" variant="tonal" class="mb-4">
                {{ errors.purchaseOrders }}
              </v-alert>
              <v-form @submit.prevent="createPurchaseOrder" :disabled="loading.purchaseOrders" class="mb-4">
                <v-row dense>
                  <v-col cols="12" md="5">
                    <v-text-field v-model="forms.purchaseOrder.po_number" label="PO Number" dense required />
                  </v-col>
                  <v-col cols="12" md="7">
                    <v-select
                      v-model="forms.purchaseOrder.supplier_id"
                      :items="suppliers"
                      item-title="name"
                      item-value="id"
                      label="Supplier"
                      dense
                      required
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model.number="forms.purchaseOrder.quantity" label="Qty" type="number" dense min="1" />
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field v-model.number="forms.purchaseOrder.unit_price" label="Unit Price" type="number" dense min="0" step="0.01" />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.purchaseOrders">
                      <v-icon start>mdi-plus</v-icon>
                      Create Purchase Order
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="purchaseOrderHeaders"
                :items="purchaseOrders"
                :items-per-page="5"
                density="comfortable"
                class="rounded-lg"
              >
                <template #item.supplier="{ item }">
                  {{ item.supplier?.name || '—' }}
                </template>
              </v-data-table>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-row>
        <v-col cols="12">
          <v-card class="rounded-xl" elevation="3">
            <v-card-title class="d-flex align-center">
              <span class="text-h6 font-weight-bold">Demand Plans</span>
              <v-spacer />
              <v-btn icon color="primary" @click="fetchDemandPlans" :loading="loading.demandPlans">
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-alert v-if="errors.demandPlans" type="error" variant="tonal" class="mb-4">
                {{ errors.demandPlans }}
              </v-alert>
              <v-form @submit.prevent="createDemandPlan" :disabled="loading.demandPlans" class="mb-4">
                <v-row dense>
                  <v-col cols="12" md="3">
                    <v-text-field v-model="forms.demandPlan.period" label="Period (e.g. 2025-10)" dense required />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model.number="forms.demandPlan.forecast_quantity" label="Forecast Qty" dense type="number" min="0" />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-select
                      v-model="forms.demandPlan.item_id"
                      :items="items"
                      item-title="name"
                      item-value="id"
                      label="Item"
                      dense
                    />
                  </v-col>
                  <v-col cols="12" md="3">
                    <v-text-field v-model="forms.demandPlan.planning_strategy" label="Strategy" dense />
                  </v-col>
                  <v-col cols="12">
                    <v-btn type="submit" color="primary" class="rounded-pill" :loading="loading.demandPlans">
                      <v-icon start>mdi-plus</v-icon>
                      Add Plan
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>

              <v-data-table
                :headers="demandPlanHeaders"
                :items="demandPlans"
                :items-per-page="8"
                density="comfortable"
                class="rounded-lg"
              >
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

const suppliers = ref([])
const purchaseOrders = ref([])
const demandPlans = ref([])
const items = ref([])

const loading = reactive({
  suppliers: false,
  purchaseOrders: false,
  demandPlans: false
})

const errors = reactive({
  suppliers: null,
  purchaseOrders: null,
  demandPlans: null
})

const forms = reactive({
  supplier: {
    code: '',
    name: '',
    contact_name: '',
    email: ''
  },
  purchaseOrder: {
    po_number: '',
    supplier_id: null,
    quantity: 10,
    unit_price: 80
  },
  demandPlan: {
    period: new Date().toISOString().slice(0, 7),
    forecast_quantity: 100,
    item_id: null,
    planning_strategy: 'make-to-stock'
  }
})

const supplierHeaders = [
  { title: 'Code', key: 'code' },
  { title: 'Name', key: 'name' },
  { title: 'Contact', key: 'contact_name' },
  { title: 'Email', key: 'email' }
]

const purchaseOrderHeaders = [
  { title: 'PO #', key: 'po_number' },
  { title: 'Supplier', key: 'supplier' },
  { title: 'Status', key: 'status' },
  { title: 'Expected', key: 'expected_date' },
  { title: 'Total', key: 'grand_total' }
]

const demandPlanHeaders = [
  { title: 'Period', key: 'period' },
  { title: 'Item', key: 'item' },
  { title: 'Forecast', key: 'forecast_quantity' },
  { title: 'Strategy', key: 'planning_strategy' }
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

async function fetchSuppliers() {
  loading.suppliers = true
  errors.suppliers = null
  try {
    const { data } = await axios.get(`${API_BASE}/scm/suppliers`, { headers: authHeaders() })
    suppliers.value = data.data || data
  } catch (error) {
    errors.suppliers = parseError(error)
  } finally {
    loading.suppliers = false
  }
}

async function createSupplier() {
  loading.suppliers = true
  errors.suppliers = null
  try {
    await axios.post(`${API_BASE}/scm/suppliers`, forms.supplier, { headers: authHeaders() })
    Object.assign(forms.supplier, { code: '', name: '', contact_name: '', email: '' })
    await fetchSuppliers()
  } catch (error) {
    errors.suppliers = parseError(error)
  } finally {
    loading.suppliers = false
  }
}

async function fetchPurchaseOrders() {
  loading.purchaseOrders = true
  errors.purchaseOrders = null
  try {
    const { data } = await axios.get(`${API_BASE}/scm/purchase-orders`, { headers: authHeaders() })
    purchaseOrders.value = data.data || data
  } catch (error) {
    errors.purchaseOrders = parseError(error)
  } finally {
    loading.purchaseOrders = false
  }
}

async function createPurchaseOrder() {
  loading.purchaseOrders = true
  errors.purchaseOrders = null
  try {
    await axios.post(`${API_BASE}/scm/purchase-orders`, {
      supplier_id: forms.purchaseOrder.supplier_id,
      po_number: forms.purchaseOrder.po_number,
      lines: [
        {
          item_id: items.value[0]?.id ?? null,
          quantity: forms.purchaseOrder.quantity,
          unit_price: forms.purchaseOrder.unit_price
        }
      ]
    }, { headers: authHeaders() })
    Object.assign(forms.purchaseOrder, { po_number: '', supplier_id: null, quantity: 10, unit_price: 80 })
    await fetchPurchaseOrders()
  } catch (error) {
    errors.purchaseOrders = parseError(error)
  } finally {
    loading.purchaseOrders = false
  }
}

async function fetchDemandPlans() {
  loading.demandPlans = true
  errors.demandPlans = null
  try {
    const { data } = await axios.get(`${API_BASE}/scm/demand-plans`, { headers: authHeaders() })
    demandPlans.value = data.data || data
  } catch (error) {
    errors.demandPlans = parseError(error)
  } finally {
    loading.demandPlans = false
  }
}

async function createDemandPlan() {
  loading.demandPlans = true
  errors.demandPlans = null
  try {
    await axios.post(`${API_BASE}/scm/demand-plans`, forms.demandPlan, { headers: authHeaders() })
    await fetchDemandPlans()
  } catch (error) {
    errors.demandPlans = parseError(error)
  } finally {
    loading.demandPlans = false
  }
}

onMounted(async () => {
  await fetchItems()
  await Promise.all([fetchSuppliers(), fetchPurchaseOrders(), fetchDemandPlans()])
})
</script>

<style scoped>
.rounded-xl {
  border-radius: 1.5rem !important;
}
</style>

